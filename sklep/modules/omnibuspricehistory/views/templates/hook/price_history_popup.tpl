{*
 * 2007-2023 PrestaShop
 *
 * Szablon dla pop-upu wyświetlającego pełną historię cen produktu.
 * Wersja z poprawionym ładowaniem skryptu JS.
 *}

<div id="omnibusPriceHistoryPopup" class="omnibus-modal-overlay" style="display: none;">
    <div class="omnibus-modal-content">
        <div class="omnibus-modal-header">
            <h2 class="omnibus-modal-title" id="omnibusChartTitle">Historia cen produktu</h2>
            <p class="omnibus-modal-subtitle" id="omnibusChartSubtitle"></p>
            <button type="button" class="omnibus-modal-close">&times;</button>
        </div>
        <div class="omnibus-modal-body">
            <div id="omnibusHistoryChartContainer" style="position: relative; height:300px;"></div>
            <div id="omnibusLowestInfo" style="text-align:center; margin-top:1rem; font-size:1rem; color:#555;"></div>
        </div>
        <div class="omnibus-modal-footer">
            <button type="button" class="btn btn-secondary omnibus-modal-close">Zamknij</button>
        </div>
    </div>
</div>

{* Ładujemy Chart.js oraz frontowy skrypt WYŁĄCZNIE tutaj *}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{$module_dir}views/js/front_omnibuspricehistory.js"></script>
