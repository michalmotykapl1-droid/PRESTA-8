<?php

class AdminAzadaLogsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'azada_wholesaler_pro_logs';
        $this->className = 'AzadaLogger'; // Używamy klasy serwisu jako atrapy modelu
        $this->identifier = 'id_log';
        $this->lang = false;
        $this->list_no_link = true; // Wyłącza linkowanie wierszy (edycję)
        
        parent::__construct();

        $this->_orderBy = 'date_add';
        $this->_orderWay = 'DESC';

        $this->fields_list = [
            'date_add' => [
                'title' => 'Data',
                'align' => 'left',
                'type' => 'datetime',
                'class' => 'fixed-width-lg'
            ],
            'severity' => [
                'title' => 'Status',
                'align' => 'center',
                'type' => 'select',
                'list' => [
                    1 => 'Info',
                    2 => 'Sukces',
                    3 => 'Błąd'
                ],
                'callback' => 'printSeverityIcon',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!severity'
            ],
            'source' => [
                'title' => 'Źródło',
                'width' => 100,
                'filter_key' => 'a!source'
            ],
            'title' => [
                'title' => 'Komunikat',
                'width' => 'auto',
                'filter_key' => 'a!title'
            ],
             'details' => [
                'title' => 'Szczegóły',
                'align' => 'center',
                'callback' => 'printDetailsBtn',
                'search' => false,
                'orderby' => false
            ]
        ];
    }

    public function printSeverityIcon($value)
    {
        switch ($value) {
            case 2: return '<span class="label label-success">OK</span>';
            case 3: return '<span class="label label-danger">BŁĄD</span>';
            default: return '<span class="label label-info">INFO</span>';
        }
    }

    public function printDetailsBtn($details, $row)
    {
        if (empty($details)) return '-';
        // Prosty modal w HTML wstrzyknięty w przycisk
        $id = $row['id_log'];
        return '
        <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#logModal'.$id.'">
            <i class="icon-search"></i> Pokaż
        </button>
        <div class="modal fade" id="logModal'.$id.'" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Log #'.$id.' - '.$row['source'].'</h4>
                    </div>
                    <div class="modal-body">
                        <pre style="max-height:500px; overflow:auto; font-size:11px;">'.$details.'</pre>
                    </div>
                </div>
            </div>
        </div>';
    }

    public function initToolbar()
    {
        // Ukryj przycisk "Dodaj nowy"
    }
}