{* PANEL WYBORU STRATEGII ZAMAWIANIA *}
<div class="row" style="margin-bottom: 25px;">
    
    {* PRZYCISK 1: STRATEGIA STANDARDOWA (NIEBIESKA) *}
    <div class="col-md-6">
        <div class="mz-strategy-card {if $current_strategy == 'std'}active{/if}" onclick="switchStrategy('std')">
            <div class="mz-strategy-icon">
                <i class="icon-money"></i>
            </div>
            <div class="mz-strategy-info">
                <div class="mz-strategy-title">NAJNIŻSZA CENA</div>
                <div class="mz-strategy-desc">Wybiera najtańszego dostawcę (Standard)</div>
                <div class="mz-strategy-total">SUMA: {$total_val_std|string_format:"%.2f"} zł</div>
                
                {* REALNY WYDATEK DLA STD *}
                {if isset($real_cash_std) && $real_cash_std > $total_val_std}
                    <div style="margin-top:5px; padding-top:5px; border-top:1px solid #ddd; font-size:12px; color:#d9534f; line-height:1.2;">
                        <strong>REALNY KOSZT: {displayPrice price=$real_cash_std}</strong>
                        <br>
                        <span style="color:#777; font-size:11px;">(Zapas: +{$surplus_items_std} szt.)</span>
                    </div>
                {/if}
            </div>
            {if $current_strategy == 'std'}
                <div class="mz-strategy-check"><i class="icon-check"></i></div>
            {/if}
        </div>
    </div>

    {* PRZYCISK 2: STRATEGIA ALTERNATYWNA (FIOLETOWA) *}
    <div class="col-md-6">
        <div class="mz-strategy-card {if $current_strategy == 'alt'}active{/if}" onclick="switchStrategy('alt')">
            <div class="mz-strategy-icon" style="background: #9c27b0;">
                <i class="icon-lightbulb"></i>
            </div>
            <div class="mz-strategy-info">
                <div class="mz-strategy-title">STRATEGIA ALTERNATYWNA</div>
                <div class="mz-strategy-desc">Twoja nowa logika zamawiania</div>
                <div class="mz-strategy-total">SUMA: {$total_val_alt|string_format:"%.2f"} zł</div>

                {* REALNY WYDATEK DLA ALT *}
                {if isset($real_cash_alt) && $real_cash_alt > $total_val_alt}
                    <div style="margin-top:5px; padding-top:5px; border-top:1px solid #ddd; font-size:12px; color:#d9534f; line-height:1.2;">
                        <strong>REALNY KOSZT: {displayPrice price=$real_cash_alt}</strong>
                        <br>
                        <span style="color:#777; font-size:11px;">(Zapas: +{$surplus_items_alt} szt.)</span>
                    </div>
                {/if}
            </div>
            {if $current_strategy == 'alt'}
                <div class="mz-strategy-check"><i class="icon-check"></i></div>
            {/if}
        </div>
    </div>

</div>

{* --- MODAL AUTOMATYCZNEGO WYBORU (WIDOCZNY TYLKO GDY SYSTEM SAM ZMIENIŁ DECYZJĘ) --- *}
{if isset($show_auto_modal) && $show_auto_modal}
<div class="modal fade" id="optModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
  <div class="modal-dialog" style="margin-top: 10%; max-width: 450px;">
    <div class="modal-content modern-modal">
      
      <div class="modal-header-modern {if $current_strategy == 'alt'}theme-purple{else}theme-blue{/if}">
        <button type="button" class="close-modern" data-dismiss="modal" aria-hidden="true">&times;</button>
        <div class="icon-circle">
            <i class="icon-trophy"></i>
        </div>
        <h4 class="modal-title-modern">MAMY LEPSZĄ OPCJĘ!</h4>
      </div>

      <div class="modal-body text-center" style="padding: 30px 25px 15px 25px;">
        <p style="font-size: 15px; color: #666; margin-bottom: 20px;">
            Przeanalizowałem koszty, minima logistyczne i stany magazynowe. Bardziej opłaca się:
        </p>
        
        <div class="strategy-name-box {if $current_strategy == 'alt'}text-purple{else}text-blue{/if}">
            {$better_strategy_name}
        </div>

        <div class="savings-box">
            <div class="savings-label">DZIĘKI TEMU ZAOSZCZĘDZISZ:</div>
            <div class="savings-amount">{displayPrice price=$auto_savings}</div>
            <div class="savings-note">realnej gotówki przy zamówieniu</div>
        </div>
      </div>

      <div class="modal-footer-modern">
        <button type="button" class="btn-modern {if $current_strategy == 'alt'}btn-purple{else}btn-blue{/if}" data-dismiss="modal">
            <i class="icon-check"></i> ŚWIETNIE, AKCEPTUJĘ
        </button>
      </div>
    </div>
  </div>
</div>

<script>
    $(document).ready(function() {
        $('#optModal').modal({
            backdrop: 'static',
            keyboard: false
        });
    });
</script>
{/if}

{* --- STYLE CSS (ZABEZPIECZONE LITERALEM PRZED BŁĘDAMI SMARTY) --- *}
{literal}
<style>
    /* KAFELKI WYBORU */
    .mz-strategy-card {
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .mz-strategy-card:hover {
        border-color: #bbb;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    
    .mz-strategy-card.active {
        border-color: #007aff;
        background: #f0f7ff;
        box-shadow: 0 0 0 2px rgba(0,122,255,0.2);
    }
    
    .mz-strategy-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #007aff;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-right: 20px;
    }
    
    .mz-strategy-info { flex: 1; }
    .mz-strategy-title { 
        font-weight: 800;
        font-size: 16px; 
        color: #333; 
        text-transform: uppercase; 
    }
    .mz-strategy-desc { 
        font-size: 12px; 
        color: #777; 
        margin-bottom: 5px;
    }
    .mz-strategy-total { 
        font-weight: bold; 
        font-size: 18px; 
        color: #333;
    }
    
    .mz-strategy-check {
        position: absolute;
        top: 10px; right: 15px; font-size: 24px; color: #007aff;
    }

    /* MODAL */
    .modern-modal {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow: hidden;
        font-family: 'Open Sans', sans-serif;
    }
    .modal-header-modern {
        padding: 40px 20px 20px 20px;
        text-align: center;
        position: relative;
        color: white;
    }
    .theme-purple { background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); }
    .theme-blue { background: linear-gradient(135deg, #007aff 0%, #42a5f5 100%); }

    .modal-title-modern {
        font-size: 20px;
        font-weight: 800;
        margin-top: 15px;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .icon-circle {
        width: 70px; height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
        font-size: 32px;
        border: 2px solid rgba(255,255,255,0.4);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .close-modern {
        position: absolute; top: 15px; right: 15px;
        background: none; border: none; color: white;
        font-size: 28px; opacity: 0.7; cursor: pointer;
    }
    .close-modern:hover { opacity: 1; }

    .strategy-name-box {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 25px;
        text-transform: uppercase;
    }
    .text-purple { color: #9c27b0; }
    .text-blue { color: #007aff; }

    .savings-box {
        background: #f9fbe7;
        border: 2px dashed #c0ca33;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
    }
    .savings-label {
        font-size: 11px; font-weight: bold; color: #827717; text-transform: uppercase;
    }
    .savings-amount {
        font-size: 32px; font-weight: 800; color: #33691e;
        margin: 5px 0;
    }
    .savings-note {
        font-size: 11px; color: #9e9d24;
    }

    .modal-footer-modern {
        padding: 20px;
        text-align: center;
        background: #fff;
    }
    .btn-modern {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 30px;
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        color: white;
        cursor: pointer;
        transition: transform 0.1s, box-shadow 0.2s;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
    .btn-purple { background: linear-gradient(to right, #9c27b0, #ba68c8); }
    .btn-blue { background: linear-gradient(to right, #007aff, #42a5f5); }
</style>
{/literal}

<script>
function switchStrategy(mode) {
    var currentScroll = $(window).scrollTop();
    // Pobieramy bazowy URL odświeżania z globalnej zmiennej (musi być w głównym pliku)
    if(typeof ajax_refresh_url !== 'undefined') {
        var url = ajax_refresh_url + '&strategy=' + mode;
        $('#orders').html('<div style="text-align:center; padding:80px;"><i class="icon-refresh icon-spin icon-4x"></i><br><h3>Przełączanie widoku...</h3></div>');
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(html) {
                $('#orders').html(html);
                $(window).scrollTop(currentScroll);
            }
        });
    } else {
        alert('Błąd: Brak zdefiniowanego URL odświeżania.');
    }
}
</script>