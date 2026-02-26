<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ExtraHtmlGenerator
{
    public static function generateRows()
    {
        $extraItems = Db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."modulzamowien_extra_items` ORDER BY id_extra DESC");
        $html = '';
        
        if ($extraItems && count($extraItems) > 0) {
            foreach ($extraItems as $item) {
                $html .= '<tr data-id="'.$item['id_extra'].'">
                    <td>'.$item['ean'].'</td>
                    <td>'.$item['name'].'</td>
                    <td class="text-center" style="font-weight:bold; font-size:1.2em;">'.$item['qty'].'</td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-xs btn-remove-extra" data-db-id="'.$item['id_extra'].'">
                            <i class="icon-trash"></i> USUŃ
                        </button>
                    </td>
                </tr>';
            }
        } else {
            $html = '<tr class="empty-row"><td colspan="4" class="text-center text-muted">Brak dodatkowych pozycji.</td></tr>';
        }
        
        return $html;
    }
}