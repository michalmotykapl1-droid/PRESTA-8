<?php

class AdminAzadaCronLogsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'azada_wholesaler_pro_cron_log';
        $this->className = 'AzadaCronLog'; // atrapa (nie ObjectModel) - listujemy bez edycji
        $this->identifier = 'id_cron_log';
        $this->lang = false;
        $this->list_no_link = true;

        parent::__construct();

        // Domyślne sortowanie
        $this->_orderBy = 'id_cron_log';
        $this->_orderWay = 'DESC';

        $this->fields_list = [
            'id_cron_log' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'started_at' => [
                'title' => 'Start',
                'type' => 'datetime',
                'class' => 'fixed-width-lg',
                'filter_key' => 'a!started_at',
            ],
            'task' => [
                'title' => 'Task',
                'filter_key' => 'a!task',
            ],
            'status' => [
                'title' => 'Status',
                'align' => 'center',
                'type' => 'select',
                'list' => [
                    'OK' => 'OK',
                    'ERR' => 'ERR',
                    'SKIP' => 'SKIP',
                    'RUNNING' => 'RUNNING',
                ],
                'callback' => 'printStatusBadge',
                'class' => 'fixed-width-sm',
                'filter_key' => 'a!status',
            ],
            'duration_ms' => [
                'title' => 'Czas',
                'align' => 'center',
                'callback' => 'printDuration',
                'search' => false,
                'filter_key' => 'a!duration_ms',
                'class' => 'fixed-width-sm',
            ],
            'ok_count' => [
                'title' => 'OK',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!ok_count',
            ],
            'err_count' => [
                'title' => 'ERR',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!err_count',
            ],
            'skip_count' => [
                'title' => 'SKIP',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!skip_count',
            ],
            'source_table' => [
                'title' => 'Źródło',
                'filter_key' => 'a!source_table',
                'class' => 'fixed-width-lg',
            ],
            'message' => [
                'title' => 'Info',
                'filter_key' => 'a!message',
            ],
            'output' => [
                'title' => 'Log',
                'align' => 'center',
                'callback' => 'printOutputBtn',
                'search' => false,
                'orderby' => false,
            ],
        ];

        // Ukrywamy dodawanie/edycję
        $this->allow_export = true;
        $this->actions = [];
    }

    public function initContent()
    {
        // Oznacz logi jako przeczytane tylko przy normalnym wejściu (nie ajax)
        if (!Tools::getValue('ajax')) {
            require_once(dirname(__FILE__) . '/../../classes/services/AzadaCronLogSeen.php');
            AzadaCronLogSeen::markAllSeen((int)$this->context->employee->id);
        }

        parent::initContent();

        // Dodatkowy opis na górze
        $this->informations[] = 'To jest historia wykonań CRON. Badge w menu pokazuje liczbę nowych logów od ostatniej wizyty.';
    }

    /**
     * AJAX: zwraca liczbę nowych logów od ostatniej wizyty.
     * URL: controller=AdminAzadaCronLogs&ajax=1&action=GetNewCount
     */
    public function ajaxProcessGetNewCount()
    {
        require_once(dirname(__FILE__) . '/../../classes/services/AzadaCronLogSeen.php');
        $count = (int)AzadaCronLogSeen::getNewCount((int)$this->context->employee->id);

        header('Content-Type: application/json');

        $payload = [
            'count' => $count,
        ];

        // PrestaShop 8+ may not have Tools::jsonEncode (it was removed in some versions)
        if (method_exists('Tools', 'jsonEncode')) {
            die(Tools::jsonEncode($payload));
        }

        die(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function printStatusBadge($value)
    {
        $v = strtoupper((string)$value);
        if ($v === 'ERR') {
            return '<span class="label label-danger">ERR</span>';
        }
        if ($v === 'SKIP') {
            return '<span class="label label-warning">SKIP</span>';
        }
        if ($v === 'RUNNING') {
            return '<span class="label label-info">RUNNING</span>';
        }
        return '<span class="label label-success">OK</span>';
    }

    public function printDuration($value)
    {
        $ms = (int)$value;
        if ($ms <= 0) {
            return '-';
        }
        $sec = $ms / 1000;
        return number_format($sec, 2, '.', '') . 's';
    }

    public function printOutputBtn($output, $row)
    {
        $output = (string)$output;
        if ($output === '') {
            return '-';
        }

        $id = (int)$row['id_cron_log'];

        return '
        <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#cronLogModal' . $id . '">
            <i class="icon-search"></i> Pokaż
        </button>
        <div class="modal fade" id="cronLogModal' . $id . '" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">CRON Log #' . $id . '</h4>
                    </div>
                    <div class="modal-body">
                        <pre style="max-height:550px; overflow:auto; font-size:11px; background:#111; color:#eee;">' . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . '</pre>
                    </div>
                </div>
            </div>
        </div>';
    }

    public function initToolbar()
    {
        // Brak przycisku "Dodaj"
    }
}
