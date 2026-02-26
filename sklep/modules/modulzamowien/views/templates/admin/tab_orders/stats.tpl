{* SEKCJA: STATYSTYKI OPTYMALIZACJI - SLIM VERSION *}
{if isset($global_stats) && $global_stats.total_savings > 0}
    <div style="background: #e8f5e9; border: 1px solid #c8e6c9; border-left: 4px solid #2e7d32; padding: 8px 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        
        {* LEWA STRONA: TYTUŁ I KWOTA *}
        <div style="display: flex; align-items: center;">
            <i class="icon-money" style="color: #2e7d32; font-size: 18px; margin-right: 10px;"></i>
            <span style="font-weight: 600; color: #1b5e20; text-transform: uppercase; font-size: 13px; margin-right: 5px;">
                Zaoszczędzono:
            </span>
            <span style="font-weight: 800; color: #2e7d32; font-size: 18px;">
                {$global_stats.total_savings|string_format:"%.2f"} zł
            </span>
            <span style="font-size: 11px; color: #666; margin-left: 5px;">(netto)</span>
        </div>

        {* ŚRODEK: Ilość Pozycji *}
        <div style="font-size: 13px; color: #333;">
            Lepsze oferty dla: <b style="color: #0277bd;">{$global_stats.optimized_count} poz.</b>
        </div>

        {* PRAWA STRONA: MAŁE INFO *}
        <div style="font-size: 10px; color: #888; font-style: italic;">
            * Porównano ceny Twoich dostawców.
        </div>
    </div>
{/if}