<?php

require_once(dirname(__FILE__) . '/AzadaSettingsB2BOrders.php');
require_once(dirname(__FILE__) . '/AzadaSettingsB2BInvoices.php');

class AzadaSettingsB2B
{
    public static function getForm()
    {
        $token = Configuration::get('AZADA_CRON_KEY');
        $shopUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__;
        
        $cronUrlOrders = $shopUrl . 'modules/azada_wholesaler_pro/cron_b2b.php?token=' . $token;
        $cronUrlInvoices = $shopUrl . 'modules/azada_wholesaler_pro/cron_invoices.php?token=' . $token;

        $widgetOrders = self::getCronWidget('ZAMÓWIENIA (ZK/WZ)', $cronUrlOrders, 'CRON_B2B', false);
        $widgetInvoices = self::getCronWidget('FAKTURY (FS/KFS)', $cronUrlInvoices, 'CRON_FV', true);

        // --- STYLE CSS I JS ---
        $jsLogic = '<script>$(document).ready(function(){$(".js-cron-copy").on("click",function(){$(this).select();document.execCommand("copy");var originalBg=$(this).css("background-color");$(this).css("background-color","#dff0d8").val("SKOPIOWANO!");var that=this;setTimeout(function(){$(that).css("background-color",originalBg).val($(that).data("original"));},1500);});});</script>';
        $customStyles = '<style>.section-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 2px solid #25b9d7; margin: 30px -20px 20px -20px; color: #333; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; } .cron-widget-box { background: #fff; border: 1px solid #dce1e5; border-left: 4px solid #25b9d7; border-radius: 3px; padding: 15px 20px; margin-bottom: 25px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); width: 100%; } .cron-widget-box.invoice-mode { border-left-color: #ff9f43; } .cron-widget-box.invoice-mode .cron-header i { color: #ff9f43; } .cron-header { display: flex; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; } .cron-header i { font-size: 18px; color: #25b9d7; margin-right: 10px; } .cron-header h4 { margin: 0; font-size: 14px; font-weight: 700; color: #555; text-transform: uppercase; } .cron-desc { color: #777; font-size: 13px; margin-bottom: 15px; line-height: 1.4; } .cron-input-wrapper input { font-family: monospace; font-size: 12px; color: #333; background-color: #fff !important; cursor: pointer; border: 1px solid #ccc; box-shadow: inset 0 1px 1px rgba(0,0,0,.075); } .cron-status-row { display: flex; align-items: center; background-color: #fbfbfb; border: 1px solid #eee; border-radius: 3px; padding: 10px 15px; margin-top: 10px; } .cron-stat-separator { width: 1px; height: 16px; background: #ddd; margin: 0 15px; } .label-text { color: #999; margin-right: 6px; text-transform: uppercase; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; } .value-text { color: #333; font-weight: 600; font-size: 12px; }</style>';

        $inputs = [];
        $inputs[] = ['type' => 'html', 'name' => 'assets', 'html_content' => $customStyles . $jsLogic];
        
        $inputs[] = ['type' => 'html', 'name' => 'h_orders', 'html_content' => '<div class="section-header"><i class="icon-dropbox"></i> Zamówienia Hurtowe (Dokumenty CSV)</div>'];
        $inputs[] = ['type' => 'html', 'name' => 'cron_box_orders', 'html_content' => $widgetOrders];
        $inputs = array_merge($inputs, AzadaSettingsB2BOrders::getInputs());

        $inputs[] = ['type' => 'html', 'name' => 'h_invoices', 'html_content' => '<div class="section-header" style="border-bottom-color:#ff9f43;"><i class="icon-file-text-alt"></i> Faktury Zakupu (Księgowość)</div>'];
        $inputs[] = ['type' => 'html', 'name' => 'cron_box_invoices', 'html_content' => $widgetInvoices];
        $inputs = array_merge($inputs, AzadaSettingsB2BInvoices::getInputs());

        $inputs[] = ['type' => 'html', 'name' => 'h_system', 'html_content' => '<div class="section-header" style="border-bottom: 1px solid #ccc; margin-top:40px;"><i class="icon-cogs"></i> Ustawienia Systemowe</div>'];
        $inputs[] = [
            'type' => 'text',
            'label' => 'Okres przechowywania logów',
            'name' => 'AZADA_LOGS_RETENTION',
            'class' => 'fixed-width-sm',
            'suffix' => 'dni',
            'desc' => 'Po ilu dniach czyścić historię logów.',
        ];

        return [
            'form' => [
                'legend' => ['title' => 'Konfiguracja B2B (Zamówienia i Faktury)', 'icon' => 'icon-exchange'],
                'input' => $inputs,
                'submit' => ['title' => 'Zapisz Konfigurację B2B', 'class' => 'btn btn-primary pull-right']
            ]
        ];
    }

    private static function getCronWidget($title, $url, $logSource, $isInvoice = false)
    {
        $lastLog = false;
        
        // Zabezpieczenie dla Zamówień (szukamy obu nazw: starej i nowej)
        $whereClause = "`source` = '".pSQL($logSource)."'";
        if ($logSource == 'CRON_B2B') {
            $whereClause = "`source` IN ('CRON', 'CRON_B2B')";
        }

        // UŻYWAMY executeS ZAMIAST getRow, ABY UNIKNĄĆ BŁĘDU "LIMIT 1"
        // (getRow czasem dodaje swój LIMIT, co powoduje konflikt składni w niektórych wersjach MySQL/PS)
        $sql = "SELECT `date_add`, `title`, `severity` 
                FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_logs` 
                WHERE $whereClause 
                ORDER BY `id_log` DESC 
                LIMIT 1";
                
        $result = Db::getInstance()->executeS($sql);
        
        if ($result && isset($result[0])) {
            $lastLog = $result[0];
        }

        if ($lastLog && isset($lastLog['date_add'])) {
            $color = ($lastLog['severity'] == 3) ? '#e74c3c' : '#27ae60';
            $icon = ($lastLog['severity'] == 3) ? 'icon-times-circle' : 'icon-check-circle';
            $date = date('d.m.Y H:i', strtotime($lastLog['date_add']));
            
            $statusHtml = '
            <div class="cron-status-row">
                <div class="cron-stat-item"><span class="label-text">OSTATNIE URUCHOMIENIE:</span><span class="value-text"><i class="icon-clock-o"></i> '.$date.'</span></div>
                <div class="cron-stat-separator"></div>
                <div class="cron-stat-item"><span class="label-text">WYNIK:</span><span class="value-text" style="color: '.$color.';"><i class="'.$icon.'"></i> '.$lastLog['title'].'</span></div>
            </div>';
        } else {
            $statusHtml = '<div class="cron-status-row"><div class="cron-stat-item" style="color: #f39c12;"><i class="icon-warning"></i> Status: <strong>Oczekuje na pierwsze uruchomienie...</strong> (Brak wpisów dla '.$logSource.')</div></div>';
        }

        $extraClass = $isInvoice ? 'invoice-mode' : '';

        return '
        <div class="cron-widget-box '.$extraClass.'">
            <div class="cron-header"><i class="icon-cogs"></i><h4>Automatyzacja: '.$title.'</h4></div>
            <p class="cron-desc">Skopiuj poniższy link i dodaj go do zadań harmonogramu (CRON) na serwerze (np. co 1 godzinę).</p>
            <div class="cron-input-wrapper">
                <div class="input-group">
                    <span class="input-group-addon"><i class="icon-link"></i> Link</span>
                    <input type="text" class="form-control js-cron-copy" value="'.$url.'" readonly data-original="'.$url.'">
                    <span class="input-group-addon" style="cursor:help;" title="Kliknij w pole, aby skopiować"><i class="icon-copy"></i></span>
                </div>
            </div>
            '.$statusHtml.'
        </div>';
    }
}