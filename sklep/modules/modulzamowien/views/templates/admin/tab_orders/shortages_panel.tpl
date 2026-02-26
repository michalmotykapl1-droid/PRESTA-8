{* SEKCJA: KRYTYCZNE BRAKI (TOWAR NIEDOSTĘPNY) *}
{if isset($list_critical_shortages) && count($list_critical_shortages) > 0}
    <div class="panel panel-danger" style="border-left: 5px solid #d9534f; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(217, 83, 79, 0.1);">
        <div class="panel-heading" style="background-color: #f2dede; color: #a94442; font-weight:bold; font-size:16px;">
            <i class="icon-warning-sign"></i> BRAKI KRYTYCZNE ({count($list_critical_shortages)}) 
            <small style="color:#a94442; font-weight:normal;"> - Towar nieznany lub całkowity brak w hurtowniach.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-condensed table-hover" style="margin:0;">
                <thead>
                    <tr style="background:#fff;">
                        <th style="width:130px;">EAN</th>
                        <th>Nazwa Produktu</th>
                        <th style="text-align:center; width:100px;">Chcę kupić</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$list_critical_shortages item=p}
                    <tr>
                        <td style="font-family:monospace; color:#a94442;">{$p.ean}</td>
                        <td>
                            {if $p.name|strstr:"[EXTRA]"}
                                <span class="label label-primary" style="font-size:10px;">EXTRA</span>
                                {$p.name|replace:'[EXTRA] ':''}
                            {else}
                                {$p.name}
                            {/if}
                        </td>
                        <td style="text-align:center; font-weight:bold; font-size:1.2em; color:#a94442;">{$p.qty_buy}</td>
                        <td>{$p.supplier nofilter}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}