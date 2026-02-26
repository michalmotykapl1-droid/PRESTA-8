{*
 * Często kupowane razem – WIDOK W MODALU (Marketing: Razem tylko + Dodaj polecane)
 * Hook: displayCartModalFooter
 *}
{if isset($fbt_products) && $fbt_products}

<div class="fbt-modal-wrapper">
    
    {* --- STYLE KOMPAKTOWE DLA MODALA --- *}
    <style>
        /* Tytuł do lewej */
        .fbt-modal-title {
            text-align: left !important;
            margin-bottom: 8px !important;
            padding-left: 2px; 
            font-size: 13px !important;
            color: #444;
            text-transform: uppercase;
            font-weight: 700;
        }

        /* Kontener bez marginesów */
        .fbt-modal-wrapper .fbt-box-container {
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            width: 100%;
        }

        /* --- UKŁAD GŁÓWNY (DESKTOP + MOBILE ROW) --- */
        .fbt-modal-wrapper .fbt-grid-wrapper {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;   /* Zawsze jedna linia */
            align-items: stretch !important;
            justify-content: space-between !important;
            gap: 0 !important; 
            width: 100% !important;
        }

        /* Pojedyncza komórka produktu */
        .fbt-modal-wrapper .fbt-product-cell {
            padding: 5px 2px !important;
            position: relative;
            
            /* KLUCZOWE: Pozwala elementom wystawać (plusy) */
            overflow: visible !important; 
            z-index: 1;
            
            min-width: 130px; 
            
            border-right: 1px dashed #eee; 
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
            text-align: center;
        }
        
        .fbt-modal-wrapper .fbt-product-cell:last-of-type {
             border-right: none !important;
        }

        /* Zdjęcia - Kontener */
        .fbt-modal-wrapper .fbt-img-wrap {
            height: 60px !important;
            width: 100% !important;
            margin-bottom: 5px !important;
            padding: 0 !important;
            
            /* Flexbox do centrowania */
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            
            overflow: hidden; 
        }
        
        /* Poprawka dla linków otaczających zdjęcia */
        .fbt-modal-wrapper .fbt-img-wrap a {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Sam obrazek */
        .fbt-modal-wrapper .fbt-img-wrap img {
            max-height: 100% !important;
            max-width: 100% !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain;
            display: block !important;
            margin: 0 auto !important; 
        }

        /* Nazwa produktu */
        .fbt-modal-wrapper .fbt-name {
            font-size: 10px !important;
            height: 24px !important; 
            line-height: 1.2 !important;
            margin-bottom: 2px !important;
            overflow: hidden;
            text-align: center;
            width: 100%;
            padding: 0 2px;
        }
        
        /* Cena */
        .fbt-modal-wrapper .fbt-price {
            font-size: 11px !important;
            font-weight: 700;
            margin-top: auto !important;
            text-align: center;
            color: #333;
            display: block !important;
            width: 100%;
        }

        /* --- PLAKIETKI (BADGES) --- */
        .fbt-modal-wrapper .fbt-badge {
            font-size: 8px !important;
            padding: 2px 4px;
            border-radius: 3px;
            margin-bottom: 4px;
            display: inline-block !important;
            text-transform: uppercase;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        .badge-in-cart {
            background-color: #eee;
            color: #777;
            border: 1px solid #ddd;
        }

        .badge-recommended {
            color: #2fb5d2;
            background: #eefbfc;
            border: 1px solid #ccecf3;
        }

        /* Przycisk plusa */
        .fbt-modal-wrapper .fbt-plus-absolute {
            width: 18px !important;
            height: 18px !important;
            line-height: 16px !important;
            font-size: 14px !important;
            
            position: absolute;
            right: -9px !important; 
            top: 40% !important; 
            z-index: 100 !important;
            
            background: #fff;
            color: #ccc;
            border: 1px solid #eee;
            border-radius: 50%;
            text-align: center;
            display: block !important;
        }

        /* --- PODSUMOWANIE --- */
        .fbt-modal-wrapper .fbt-summary-cell {
            padding: 10px !important;
            min-width: 150px;
            
            background: #fafafa;
            border-radius: 6px;
            margin-left: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 1px solid #eee;
        }
        
        .fbt-modal-wrapper .fbt-total-label {
            font-size: 10px !important;
            color: #777;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px !important;
            text-align: center;
        }

        .fbt-modal-wrapper .fbt-total-val {
            font-size: 16px !important;
            margin-bottom: 5px !important;
            color: #ea7404;
            font-weight: 800;
            text-align: center;
        }
        
        .fbt-modal-wrapper .fbt-btn-action {
            padding: 8px 5px !important;
            font-size: 10px !important;
            width: 100%;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
            
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
        }

        /* --- MOBILE FIXES --- */
        @media (max-width: 768px) {
            
            .fbt-modal-wrapper .fbt-grid-wrapper {
                flex-wrap: wrap !important;
            }

            .fbt-modal-wrapper .fbt-product-cell {
                flex: 1 1 0 !important;
                min-width: 0 !important; 
                width: auto !important;
                border-bottom: 1px solid #f0f0f0; 
                padding-bottom: 10px !important;
            }
            
            .fbt-modal-wrapper .fbt-plus-absolute {
                right: -10px !important;
                width: 20px !important;
                height: 20px !important;
                line-height: 18px !important;
                font-size: 14px !important;
                background: #fff !important; 
                border: 1px solid #ddd !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .fbt-modal-wrapper .fbt-name {
                font-size: 9px !important;
                height: 22px !important;
            }
            .fbt-modal-wrapper .fbt-img-wrap {
                height: 50px !important;
                justify-content: center !important;
                text-align: center !important;
            }
            
            .fbt-modal-wrapper .fbt-img-wrap img {
                margin: 0 auto !important;
            }

            .fbt-modal-wrapper .fbt-summary-cell {
                flex: 1 1 100% !important;
                width: 100% !important;
                margin-left: 0 !important;
                margin-top: 5px !important;
                border: none !important;
                background: #fff !important;
                border-top: 1px solid #eee !important;
                
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 10px 5px !important;
            }
            
            .fbt-modal-wrapper .fbt-total-label {
                margin: 0 !important;
                text-align: left;
                margin-right: 5px !important;
            }
            .fbt-modal-wrapper .fbt-total-val {
                margin: 0 !important;
                margin-right: 15px !important;
                font-size: 18px !important;
            }
            .fbt-modal-wrapper .fbt-btn-action {
                width: auto !important;
                padding: 8px 20px !important;
            }
        }
    </style>

    <h4 class="fbt-modal-title">
        {$fbt_title|escape:'html':'UTF-8'}
    </h4>

    <section class="fbt-box-container">
        <div class="fbt-grid-wrapper">
          
          {* 1. GŁÓWNY PRODUKT (W KOSZYKU) *}
          <div class="fbt-product-cell" data-price="{$product.price_amount}">
            <div style="width:100%; text-align:center;">
                <span class="fbt-badge badge-in-cart">W KOSZYKU</span>
            </div>
            
            <div class="fbt-img-wrap">
               <img src="{$product.default_image.medium.url}" 
                    alt="{$product.name|escape:'html':'UTF-8'}" 
                    loading="lazy">
            </div>
            
            <h4 class="fbt-name">
                {$product.name|truncate:30:'...'}
            </h4>
            <div class="fbt-price">{$product.price}</div>
            
            <div class="fbt-plus-absolute">+</div>
          </div>

          {* PĘTLA PO POLECANYCH *}
          {foreach from=$fbt_products item="acc" name=fbtLoop}
            
            <div class="fbt-product-cell" data-id-product="{$acc.id_product}" data-price="{$acc.price_amount}">
                
                <div style="width:100%; text-align:center;">
                    <span class="fbt-badge badge-recommended">POLECAMY</span>
                </div>
                
                <div class="fbt-img-wrap">
                    <a href="{$acc.url}">
                        <img src="{$acc.cover.bySize.medium_default.url}" 
                             alt="{$acc.name|escape:'html':'UTF-8'}" 
                             loading="lazy">
                    </a>
                </div>
                
                <h4 class="fbt-name">
                     <a href="{$acc.url}" title="{$acc.name}">{$acc.name|truncate:30:'...'}</a>
                </h4>
                <div class="fbt-price">{$acc.price}</div>
                
                {* --- FIX: POPRAWIONY FORMULARZ DLA ZALOGOWANYCH --- *}
                <form action="{$urls.pages.cart}" method="post" class="fbt-hidden-form">
                    <input type="hidden" name="token" value="{$static_token}">
                    <input type="hidden" name="id_product" value="{$acc.id_product}">
                    <input type="hidden" name="qty" value="1">
                    {* Dodane kluczowe pola dla PrestaShop 8 *}
                    <input type="hidden" name="add" value="1">
                    <input type="hidden" name="action" value="update">
                </form>
                {* -------------------------------------------------- *}

                {* Nie wyświetlaj plusa po ostatnim produkcie *}
                {if !$smarty.foreach.fbtLoop.last}
                   <div class="fbt-plus-absolute">+</div>
                {/if}
            </div>
          {/foreach}

          {* PODSUMOWANIE *}
          <div class="fbt-summary-cell">
                 <div style="text-align:center;">
                     <span class="fbt-total-label">RAZEM TYLKO:</span>
                     <div class="fbt-total-val" id="fbt-total-price-modal">...</div>
                 </div>
                 
                 <button class="btn btn-primary fbt-btn-action" id="fbt-add-all-btn-modal">
                   DODAJ POZOSTAŁE
                 </button>
          </div>

        </div>
    </section>
</div>

{* SKRYPTY *}
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Obliczanie ceny łącznej
    function fbtModalCalculate() {
        var wrapper = document.querySelector('.fbt-modal-wrapper');
        if(!wrapper) return;

        var total = 0;
        var cells = wrapper.querySelectorAll('.fbt-product-cell');
        cells.forEach(function(cell) {
            var price = parseFloat(cell.getAttribute('data-price'));
            if(!isNaN(price)) total += price;
        });
        var formatted = total.toFixed(2).replace('.', ',') + ' zł';
        var totalEl = document.getElementById('fbt-total-price-modal');
        if(totalEl) totalEl.innerText = formatted;
    }

    // 2. Obsługa przycisku DODAJ POZOSTAŁE
    var addAllBtn = document.getElementById('fbt-add-all-btn-modal');
    if (addAllBtn) {
        addAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Zmień tekst na "Dodawanie..." i zablokuj przycisk
            var originalText = this.innerText;
            this.innerText = 'DODAWANIE...';
            this.disabled = true;
            this.style.opacity = '0.7';

            var forms = document.querySelectorAll('.fbt-hidden-form');
            var promises = [];

            // Wysyłamy każdy produkt asynchronicznie
            forms.forEach(function(form) {
                var formData = new FormData(form);
                var promise = fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                promises.push(promise);
            });

            // Gdy wszystkie zostaną dodane -> odśwież stronę
            Promise.all(promises).then(function() {
                window.location.reload();
            }).catch(function(err) {
                console.error('Błąd dodawania:', err);
                window.location.reload(); // I tak odświeżamy, żeby pokazać co weszło
            });
        });
    }

    setTimeout(fbtModalCalculate, 500);
    setTimeout(fbtModalCalculate, 1500);
});
</script>
{/if}