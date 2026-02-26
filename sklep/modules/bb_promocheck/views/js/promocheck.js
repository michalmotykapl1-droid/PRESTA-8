document.addEventListener("DOMContentLoaded", function() {
    
    function initPromoBox(box) {
        if (box.classList.contains('js-initialized')) return;
        box.classList.add('js-initialized');

        var input = box.querySelector('.js-bb-promo-qty-input');
        var btnMinus = box.querySelector('.bb-btn-minus');
        var btnPlus = box.querySelector('.bb-btn-plus');
        var addBtn = box.querySelector('.js-bb-promo-add-btn');
        var idProductInput = box.querySelector('.js-bb-id-product');
        var tokenInput = box.querySelector('.js-bb-token');

        if (input && btnMinus && btnPlus) {
            btnMinus.addEventListener('click', function() {
                var val = parseInt(input.value);
                if (val > 1) input.value = val - 1;
            });

            btnPlus.addEventListener('click', function() {
                var val = parseInt(input.value);
                var max = parseInt(input.max);
                if (val < max) input.value = val + 1;
            });
        }

        if (addBtn && idProductInput && tokenInput) {
            addBtn.addEventListener('click', function() {
                var idProduct = idProductInput.value;
                var qty = input ? input.value : 1;
                var token = tokenInput.value;
                
                addBtn.style.opacity = '0.7';
                addBtn.classList.add('disabled');

                $.ajax({
                    type: 'POST',
                    url: prestashop.urls.pages.cart,
                    async: true,
                    data: {
                        action: 'update',
                        add: 1,
                        id_product: idProduct,
                        qty: qty,
                        token: token,
                        ajax: true
                    },
                    success: function(resp) {
                        prestashop.emit('updateCart', {
                            reason: {
                                idProduct: idProduct,
                                linkAction: 'add-to-cart',
                                cart: resp.cart
                            },
                            resp: resp
                        });
                        addBtn.style.opacity = '1';
                        addBtn.classList.remove('disabled');
                    },
                    error: function(err) {
                        console.error("Błąd koszyka", err);
                        addBtn.style.opacity = '1';
                        addBtn.classList.remove('disabled');
                    }
                });
            });
        }
    }

    var boxes = document.querySelectorAll('.js-bb-promo-box');
    boxes.forEach(initPromoBox);

    if (typeof prestashop !== 'undefined') {
        prestashop.on('updateCart', function (event) {
            try {
                var allBoxes = document.querySelectorAll('.js-bb-promo-box');
                allBoxes.forEach(function(box) {
                    initPromoBox(box);

                    var promoId = parseInt(box.dataset.promoId);
                    var totalStock = parseInt(box.dataset.promoTotal);
                    var currentInCart = 0;

                    if (event && event.resp && event.resp.cart && event.resp.cart.products) {
                        var products = event.resp.cart.products;
                        for (var i = 0; i < products.length; i++) {
                            if (parseInt(products[i].id_product) === promoId) {
                                currentInCart = parseInt(products[i].cart_quantity);
                                break;
                            }
                        }
                    } else {
                        var oldVal = parseInt(box.dataset.inCart);
                        currentInCart = oldVal + 1; 
                    }

                    box.dataset.inCart = currentInCart;
                    var left = totalStock - currentInCart;
                    if (left < 0) left = 0;

                    var elLeft = box.querySelector('.js-bb-qty-left');
                    var elCurrent = box.querySelector('.js-bb-qty-current');
                    var selectorDiv = box.querySelector('.js-bb-qty-selector');
                    var input = box.querySelector('.js-bb-promo-qty-input');
                    var btnMinus = box.querySelector('.bb-btn-minus');
                    var btnPlus = box.querySelector('.bb-btn-plus');
                    var msgInCart = box.querySelector('.js-bb-in-cart-msg');
                    var msgFull = box.querySelector('.js-bb-full-msg');
                    var btn = box.querySelector('.js-bb-promo-add-btn');

                    if(elLeft) elLeft.innerText = left;
                    if(elCurrent) elCurrent.innerText = currentInCart;

                    if (input && selectorDiv) {
                        input.max = left;
                        input.value = 1; 
                        
                        if (left <= 0) {
                            selectorDiv.style.setProperty('display', 'none', 'important');
                        } else {
                            selectorDiv.style.display = 'flex';
                            if(btnMinus) btnMinus.disabled = false;
                            if(btnPlus) btnPlus.disabled = false;
                        }
                    }

                    if (msgInCart) {
                        if (currentInCart > 0) {
                            msgInCart.style.display = 'inline-flex';
                        } else {
                            msgInCart.style.setProperty('display', 'none', 'important');
                        }
                    }

                    if (msgFull && btn) {
                        if (left <= 0) {
                            msgFull.style.display = 'inline-flex';
                            btn.classList.add('hidden-btn');
                            btn.disabled = true;
                        } else {
                            msgFull.style.setProperty('display', 'none', 'important');
                            btn.classList.remove('hidden-btn');
                            btn.disabled = false;
                        }
                    }
                });
            } catch (e) {
                console.error("BB PromoCheck JS Error:", e);
            }
        });
    }
});