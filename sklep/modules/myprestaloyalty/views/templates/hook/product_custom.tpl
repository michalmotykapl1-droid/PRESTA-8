{*
* Custom Loyalty View for BigBio - Hook: displayCustomLoyalty
* Wersja 23.0 - Nowy design sekcji kodów rabatowych
*}

<div id="loyalty_product_box" class="loyalty-box" 
     data-unit-price="{$price_amount}" 
     data-point-rate="{$point_rate}" 
     data-point-value="{$point_value}" 
     data-points-in-cart="{$points_in_cart|intval}" 
     data-no-award="{$no_pts_discounted|default:0}">
     
    {* GÓRNA CZĘŚĆ *}
    <div class="loyalty-top-row">
        <div class="loyalty-left-col">
            <div class="loyalty-header-flex">
                <i class="fa-solid fa-piggy-bank loyalty-icon"></i>
                <div>
                    <span class="loyalty-title">Zbieraj punkty i wymieniaj na rabaty!</span>
                    <div class="loyalty-rule">
                        Zasada: 1 pkt za każde wydane 20 zł
                    </div>
                </div>
            </div>
        </div>
        <div class="loyalty-right-col js-loyalty-content">Wczytywanie...</div>
    </div>

    {* DOLNA CZĘŚĆ *}
    <div class="loyalty-bottom-row">
        {if $is_logged}
            {* STAN PUNKTÓW *}
            <div class="loyalty-points-status">
                <div>
                    Twoje zebrane punkty: <strong style="color: #2fb5d2;">{$customer_points} pkt</strong> 
                    {if $customer_points > 0}
                        (wartość: {Tools::displayPrice(LoyaltyModule::getVoucherValue($customer_points))})
                    {/if}
                </div>
                {if $customer_points > 0}
                    <a href="{$link->getModuleLink('myprestaloyalty', 'default', ['process' => 'transformpoints'])}" 
                       class="loyalty-convert-link" 
                       title="Wymień punkty na kod rabatowy"
                       onclick="return confirm('Czy na pewno chcesz wymienić punkty na kod rabatowy?');">
                       Wymień na kod rabatowy &raquo;
                    </a>
                {/if}
            </div>

            {* LISTA KODÓW - NOWY DESIGN *}
            {if isset($active_vouchers) && $active_vouchers|@count > 0}
                <div class="lv-container">
                    <div class="lv-header">Dostępne kody rabatowe:</div>
                    {foreach from=$active_vouchers item=voucher}
                        <div class="lv-item js-copy-code" data-code="{$voucher.code}" title="Kliknij, aby skopiować kod">
                            <div class="lv-icon"><i class="fa-solid fa-ticket"></i></div>
                            <div class="lv-content">
                                <div class="lv-code-text">{$voucher.code}</div>
                                <div class="lv-subtext">Wartość: <strong>{$voucher.value}</strong> • Ważny: {$voucher.days_left} dni</div>
                            </div>
                            <div class="lv-action"><i class="fa-regular fa-copy"></i></div>
                        </div>
                    {/foreach}
                </div>
            {/if}

        {else}
            Punkty są dostępne dla zarejestrowanych klientów. 
            <a href="{$login_url}" class="loyalty-login-link">Zaloguj się lub zarejestruj</a>, aby zbierać punkty
        {/if}
    </div>
</div>

<style>
    .loyalty-box { margin: 15px 0; padding: 15px 20px; background-color: #ffffff; border: 1px solid #f1f1f1; border-radius: 6px; }
    .loyalty-top-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 12px; }
    .loyalty-header-flex { display: flex; align-items: center; }
    .loyalty-icon { color: #2fb5d2; margin-right: 12px; font-size: 28px; }
    .loyalty-title { display: block; font-weight: 700; color: #333; font-size: 14px; line-height: 1.2; margin-bottom: 3px; }
    .loyalty-rule { font-size: 11px; color: #888; }
    .loyalty-right-col { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; flex-grow: 1; text-align: right; }
    .loyalty-stat-label { font-size: 10px; text-transform: uppercase; color: #999; font-weight: 600; display: block; margin-bottom: 2px; }
    .loyalty-stat-value { font-size: 22px; font-weight: 800; color: #2fb5d2; line-height: 1; }
    .loyalty-stat-sub { font-size: 11px; color: #555; display: block; margin-top: 2px; }
    .loyalty-bottom-row { border-top: 1px dashed #e0e0e0; padding-top: 8px; font-size: 11px; color: #666; text-align: left; }
    .loyalty-points-status { display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 5px; }
    .loyalty-login-link { color: #2fb5d2; text-decoration: underline; font-weight: 600; margin-left: 3px; }
    .loyalty-convert-link { color: #2fb5d2 !important; font-weight: 600; text-decoration: underline !important; margin-left: 10px; font-size: 11px; cursor: pointer; }
    .loyalty-convert-link:hover { color: #259bb5 !important; }

    /* NOWY DESIGN KODÓW RABATOWYCH */
    .lv-container { margin-top: 12px; }
    .lv-header { font-size: 10px; text-transform: uppercase; color: #999; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px; }
    
    .lv-item {
        display: flex;
        align-items: center;
        background: #fdfdfd;
        border: 1px solid #eee;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 5px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .lv-item:hover { border-color: #2fb5d2; background: #f0fbff; }
    
    .lv-icon { color: #2fb5d2; font-size: 14px; margin-right: 10px; }
    .lv-content { flex-grow: 1; }
    
    .lv-code-text { 
        font-family: monospace; 
        font-size: 13px; 
        font-weight: 700; 
        color: #333; 
        letter-spacing: 1px;
    }
    .lv-subtext { font-size: 10px; color: #777; margin-top: 1px; }
    
    .lv-action { 
        color: #ccc; 
        font-size: 14px; 
        transition: color 0.2s;
    }
    .lv-item:hover .lv-action { color: #2fb5d2; }

    @media (max-width: 768px) {
        .loyalty-top-row { flex-direction: column; align-items: flex-start; gap: 15px; }
        .loyalty-right-col { width: 100%; align-items: flex-start; text-align: left; padding-left: 40px; border-top: 1px solid #f5f5f5; padding-top: 10px; }
        .loyalty-points-status { flex-direction: column; align-items: flex-start; gap: 5px; } 
        .loyalty-convert-link { margin-left: 0; display: inline-block; margin-top: 2px; }
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var $ = jQuery;
    
    // --- OBSŁUGA KOPIOWANIA ---
    $('body').on('click', '.js-copy-code', function() {
        var code = $(this).attr('data-code');
        var $el = $(this);
        var $icon = $el.find('.lv-action i'); // Znajdź ikonę
        
        // Funkcja wizualna (zmiana ikony na "ptaszek")
        function showSuccess() {
            $icon.removeClass('fa-copy').addClass('fa-check').css('color', '#2fb5d2');
            setTimeout(function() {
                $icon.removeClass('fa-check').addClass('fa-copy').css('color', '');
            }, 1500);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(code).then(showSuccess);
        } else {
            var textArea = document.createElement("textarea");
            textArea.value = code;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("Copy");
            textArea.remove();
            showSuccess();
        }
    });

    // --- LOGIKA PUNKTÓW ---
    function initLoyaltyUpdate() {
        var box = $('#loyalty_product_box');
        if(box.length === 0) return;

        var unitPrice = parseFloat(box.attr('data-unit-price')) || 0;
        var pointRate = parseFloat(box.attr('data-point-rate')) || 20; 
        var pointValue = parseFloat(box.attr('data-point-value')) || 0.5; 
        var pointsInCart = parseInt(box.attr('data-points-in-cart')) || 0;
        var noAward = parseInt(box.attr('data-no-award')) === 1;

        function formatCurrency(val) {
            return val.toFixed(2).replace('.', ',') + ' zł';
        }

        function calculatePoints(val) {
            if (noAward) return 0;
            return Math.floor(val / pointRate);
        }

        function updateProductPoints() {
            var quantityInput = $('#quantity_wanted');
            var quantity = 1;
            if (quantityInput.length > 0) {
                quantity = parseInt(quantityInput.val());
                if (isNaN(quantity) || quantity < 1) quantity = 1;
            }

            var htmlContent = '';

            if (noAward) {
                htmlContent = '<span class="loyalty-stat-sub" style="color:#999;">Produkt w promocji<br>bez punktów</span>';
            } else {
                var currentProductValue = unitPrice * quantity;
                var pointsForCurrentSelection = calculatePoints(currentProductValue);
                var totalPredictedPoints = pointsInCart + pointsForCurrentSelection;
                var rebateValueTotal = totalPredictedPoints * pointValue;
                
                if (totalPredictedPoints === 0) {
                     var remainder = currentProductValue % pointRate;
                     var missingForNextPoint = pointRate - remainder;
                     htmlContent = '<span class="loyalty-stat-label">Brakuje</span>' +
                                   '<span class="loyalty-stat-value" style="font-size:16px; color:#555;">' + formatCurrency(missingForNextPoint) + '</span>' +
                                   '<span class="loyalty-stat-sub">do 1 pkt</span>';
                } else {
                    htmlContent = '<span class="loyalty-stat-label">Zyskujesz łącznie</span>' +
                                  '<span class="loyalty-stat-value">' + totalPredictedPoints + ' pkt</span>' +
                                  '<span class="loyalty-stat-sub">Rabat: <strong>' + formatCurrency(rebateValueTotal) + '</strong></span>';
                }
            }
            box.find('.js-loyalty-content').html(htmlContent);
        }

        updateProductPoints();
        $('body').on('change keyup input', '#quantity_wanted', function() { updateProductPoints(); });
        $('body').on('click', '.cart-qty-plus, .cart-qty-minus, .bootstrap-touchspin-up, .bootstrap-touchspin-down, .bb-qty-btn', function() {
            setTimeout(updateProductPoints, 100);
        });
        
        if (typeof prestashop !== 'undefined') {
            prestashop.on('updateCart', function (event) {
                if (typeof loyalty_ajax_url !== 'undefined') {
                    $.ajax({
                        type: 'POST',
                        url: loyalty_ajax_url,
                        dataType: 'json',
                        success: function(data) {
                            if (data && typeof data.points !== 'undefined') {
                                pointsInCart = parseInt(data.points);
                                box.attr('data-points-in-cart', pointsInCart);
                                updateProductPoints();
                            }
                        }
                    });
                }
            });
            prestashop.on('updatedProduct', function (event) { setTimeout(updateProductPoints, 200); });
        }
    }
    
    initLoyaltyUpdate();
});
</script>