<?php
/**
 * MANAGER PRO -> LOGI
 *
 * Lista logów audytowych z bb_ordermanager_logs.
 * Pokazuje: kiedy, kto, co zrobił + do jakiego zamówienia.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerLogger.php';
require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerLog.php';

class AdminManagerProLogsController extends ModuleAdminController
{
    public function __construct()
    {
        // Upewnij się, że tabela ma kolumny audytowe
        BbOrderManagerLogger::ensureSchema();

        $this->bootstrap = true;
        $this->table = 'bb_ordermanager_logs';
        $this->identifier = 'id_log';
        $this->className = 'BbOrderManagerLog';
        $this->lang = false;

        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';

        // JOIN do zamówień (dla reference)
        $this->_select = 'o.reference AS order_reference';
        $this->_join = ' LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_order = a.id_order)';

        // Ważne: parent::__construct() musi się wykonać zanim użyjemy translatora.
        // (W PS 8+ $this->l() korzysta z $this->translator.)
        parent::__construct();

        // Kolumny listy (tu nie używamy $this->l() na siłę, bo sklep jest PL; unikamy też ostrzeżeń deprecated)
        $this->fields_list = [
            'id_log' => [
                'title' => 'ID',
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'date_add' => [
                'title' => 'Data',
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
            ],
            'order_reference' => [
                'title' => 'Zamówienie',
                'havingFilter' => true,
                'callback' => 'renderOrderLink',
            ],
            'action' => [
                'title' => 'Akcja',
                'filter_key' => 'a!action',
                'class' => 'fixed-width-lg',
                'callback' => 'renderActionBadge',
            ],
            'employee_name' => [
                'title' => 'Pracownik',
                'filter_key' => 'a!employee_name',
                'class' => 'fixed-width-xl',
                'callback' => 'renderEmployeeDisplay',
            ],
            'message' => [
                'title' => 'Opis',
                'filter_key' => 'a!message',
                'callback' => 'truncateMessage',
                'orderby' => false,
            ],
        ];

        // Tylko podgląd
        $this->addRowAction('view');
        $this->actions = ['view'];
    }

    /**
     * Renderuj akcję jako czytelny "badge". Jeśli action jest puste (stare logi) – inferujemy z treści.
     */
    public function renderActionBadge($value, $row)
    {
        $code = strtoupper(trim((string) $value));
        if ($code === '') {
            $code = BbOrderManagerLogger::inferActionFromMessage(isset($row['message']) ? $row['message'] : '');
        }

        $label = BbOrderManagerLogger::getActionLabel($code);

        if ($code === '') {
            return '<span class="text-muted">-</span>';
        }

        return '<span class="label label-info" title="' . Tools::safeOutput($code) . '">' . Tools::safeOutput($label) . '</span>';
    }

    /**
     * Renderuj pracownika. Dla starych logów, gdy employee_name jest puste, próbujemy wyciągnąć z "(przez: ...)".
     * Jeśli nadal brak – pokazujemy System/Automat z oznaczeniem AUTO.
     */
    public function renderEmployeeDisplay($value, $row)
    {
        $name = trim((string) $value);
        $idEmp = isset($row['id_employee']) ? (int) $row['id_employee'] : 0;

        if ($name === '') {
            $fromMsg = BbOrderManagerLogger::extractEmployeeFromMessage(isset($row['message']) ? $row['message'] : '');
            if ($fromMsg !== '') {
                $name = $fromMsg;
            }
        }

        if ($name === '') {
            $name = 'System/Automat';
        }

        // AUTO tylko gdy faktycznie brak pracownika i nazwa to System/Automat
        $isAuto = ($idEmp <= 0 && $name === 'System/Automat');

        $out = Tools::safeOutput($name);

        if ($idEmp > 0) {
            $out .= ' <span class="text-muted">(ID: ' . (int) $idEmp . ')</span>';
        } elseif ($isAuto) {
            $out .= ' <span class="label label-default">AUTO</span>';
        }

        return $out;
    }

    /**
     * Przytnij opis i usuń dopisek "(przez: ...)" żeby nie dublować kolumny pracownika.
     */
    public function truncateMessage($value, $row)
    {
        $v = (string) $value;

        // usuń końcówkę "(przez: ... )" jeśli istnieje
        $v = preg_replace('/\s*\(przez:\s*[^\)]*\)\s*$/u', '', $v);

        $v = trim($v);

        // Ogranicz długość
        if (Tools::strlen($v) > 220) {
            $v = Tools::substr($v, 0, 220) . '…';
        }

        return Tools::safeOutput($v);
    }

    /**
     * Link do zamówienia w BO.
     */
    public function renderOrderLink($value, $row)
    {
        $ref = (string) $value;
        $idOrder = isset($row['id_order']) ? (int) $row['id_order'] : 0;

        if ($idOrder <= 0) {
            return $ref !== '' ? Tools::safeOutput($ref) : '-';
        }

        $url = $this->context->link->getAdminLink('AdminOrders');
        $url .= '&id_order=' . (int) $idOrder . '&vieworder=1';

        $label = $ref !== '' ? $ref : ('#' . $idOrder);

        return '<a href="' . Tools::safeOutput($url) . '" target="_blank" rel="noopener noreferrer">' . Tools::safeOutput($label) . '</a>';
    }

    /**
     * Widok szczegółów logu (pełna wiadomość + JSON).
     */
    public function renderView()
    {
        $id = (int) Tools::getValue($this->identifier);
        if ($id <= 0) {
            return parent::renderView();
        }

        $db = Db::getInstance();
        $row = $db->getRow(
            'SELECT a.*, o.reference AS order_reference
             FROM `' . _DB_PREFIX_ . 'bb_ordermanager_logs` a
             LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_order = a.id_order)
             WHERE a.id_log = ' . (int) $id
        );

        if (!$row) {
            $this->errors[] = 'Nie znaleziono wpisu logu.';
            return parent::renderList();
        }

        // --- Normalizacja / fallback dla starych logów ---
        $messageRaw = isset($row['message']) ? (string) $row['message'] : '';
        $messageDisplay = BbOrderManagerLogger::stripEmployeeSuffix($messageRaw);

        // pracownik
        $employeeDisplay = isset($row['employee_name']) ? trim((string) $row['employee_name']) : '';
        if ($employeeDisplay === '') {
            $fromMsg = BbOrderManagerLogger::extractEmployeeFromMessage($messageRaw);
            if ($fromMsg !== '') {
                $employeeDisplay = $fromMsg;
            }
        }
        if ($employeeDisplay === '') {
            $employeeDisplay = 'System/Automat';
        }
        $rowIdEmp = isset($row['id_employee']) ? (int) $row['id_employee'] : 0;
        $isAuto = ($rowIdEmp <= 0 && $employeeDisplay === 'System/Automat');

        // akcja
        $actionCode = isset($row['action']) ? strtoupper(trim((string) $row['action'])) : '';
        if ($actionCode === '') {
            $actionCode = BbOrderManagerLogger::inferActionFromMessage($messageRaw);
        }
        $actionLabel = BbOrderManagerLogger::getActionLabel($actionCode);

        $detailsPretty = '';
        if (!empty($row['details'])) {
            $decoded = json_decode($row['details'], true);
            if (is_array($decoded)) {
                $detailsPretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $detailsPretty = (string) $row['details'];
            }
        }

        $orderLink = '';
        if (!empty($row['id_order'])) {
            $url = $this->context->link->getAdminLink('AdminOrders');
            $url .= '&id_order=' . (int) $row['id_order'] . '&vieworder=1';
            $label = !empty($row['order_reference']) ? $row['order_reference'] : ('#' . (int) $row['id_order']);
            $orderLink = '<a href="' . Tools::safeOutput($url) . '" target="_blank" rel="noopener noreferrer">' . Tools::safeOutput($label) . '</a>';
        }

        $this->context->smarty->assign([
            'bbom_log' => $row,
            'bbom_order_link' => $orderLink,
            'bbom_details_pretty' => $detailsPretty,
            'bbom_employee_display' => $employeeDisplay,
            'bbom_is_auto' => (bool) $isAuto,
            'bbom_action_code' => $actionCode,
            'bbom_action_label' => $actionLabel,
            'bbom_message_display' => $messageDisplay,
            'bbom_back_url' => self::$currentIndex . '&token=' . $this->token,
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'bb_ordermanager/views/templates/admin/log_view.tpl');
    }
}
