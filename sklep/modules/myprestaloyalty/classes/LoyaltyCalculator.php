<?php
/*
* Helper class for Loyalty Logic
* Location: /modules/myprestaloyalty/classes/LoyaltyCalculator.php
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/../LoyaltyModule.php');

class LoyaltyCalculator
{
    public static function processAjaxRequest()
    {
        if (Tools::getValue('ajax') == true && Tools::getValue('loyalty_ajax_query') == 'get_points') {
            
            $cart = Context::getContext()->cart;
            $points = 0;
            $voucher = 0;
            
            if (Validate::isLoadedObject($cart)) {
                $points = (int)LoyaltyModule::getCartNbPoints($cart);
                $voucher = LoyaltyModule::getVoucherValue((int)$points);
            }
            
            $currency = Context::getContext()->currency;
            $voucher_formatted = Tools::displayPrice($voucher, $currency);

            die(json_encode(array(
                'points' => $points,
                'voucher' => $voucher,
                'voucher_formatted' => $voucher_formatted
            )));
        }
    }

    /**
     * ZMODYFIKOWANA METODA: Dodano sprawdzanie minimalnej kwoty zamówienia
     */
    public static function getCustomerVouchers($id_customer, $id_lang)
    {
        $activeVouchers = array();
        $cart = Context::getContext()->cart;
        
        // Pobieramy wartość koszyka (produkty + ew. rabaty, bez wysyłki) do sprawdzenia minimum
        $cartTotal = 0;
        if (Validate::isLoadedObject($cart)) {
            // Używamy kwoty brutto (z podatkiem) lub netto w zależności od konfiguracji, 
            // ale zazwyczaj minima w PrestaShop są definiowane brutto.
            $useTax = Product::getTaxCalculationMethod() != PS_TAX_EXC; 
            $cartTotal = $cart->getOrderTotal($useTax, Cart::BOTH_WITHOUT_SHIPPING);
        }

        $discounts_raw = LoyaltyModule::getDiscountByIdCustomer($id_customer);
        
        if ($discounts_raw && is_array($discounts_raw)) {
            foreach ($discounts_raw as $discount_row) {
                $cartRule = new CartRule((int)$discount_row['id_cart_rule'], $id_lang);
                
                if (Validate::isLoadedObject($cartRule) 
                    && $cartRule->active 
                    && $cartRule->quantity > 0 
                    && $cartRule->quantity_per_user > 0 
                    && strtotime($cartRule->date_to) > time()
                ) {
                    $seconds_left = strtotime($cartRule->date_to) - time();
                    $days_left = ceil($seconds_left / (60 * 60 * 24));

                    $value_formatted = '';
                    if ($cartRule->reduction_percent > 0) {
                        $value_formatted = '-' . (float)$cartRule->reduction_percent . '%';
                    } elseif ($cartRule->reduction_amount > 0) {
                        $value_formatted = '-' . Tools::displayPrice($cartRule->reduction_amount);
                    } else {
                        $value_formatted = 'Darmowa wysyłka';
                    }

                    // --- LOGIKA MINIMUM ---
                    $is_usable = true;
                    $reason = '';
                    $missing_amount = 0;

                    // Sprawdzamy minimalną kwotę (jeśli jest ustawiona > 0)
                    if ($cartRule->minimum_amount > 0) {
                        // Przeliczamy walutę jeśli inna
                        $minimum_amount = $cartRule->minimum_amount;
                        if ($cartRule->minimum_amount_currency != Context::getContext()->currency->id) {
                            $minimum_amount = Tools::convertPriceFull($cartRule->minimum_amount, new Currency($cartRule->minimum_amount_currency), Context::getContext()->currency);
                        }

                        if ($cartTotal < $minimum_amount) {
                            $is_usable = false;
                            $missing_amount = $minimum_amount - $cartTotal;
                            $reason = 'Min. zamówienie: ' . Tools::displayPrice($minimum_amount);
                        }
                    }
                    // ----------------------

                    $activeVouchers[] = array(
                        'code' => $cartRule->code,
                        'value' => $value_formatted,
                        'days_left' => $days_left,
                        'name' => $cartRule->name,
                        'is_usable' => $is_usable,      // Nowe pole
                        'reason' => $reason,            // Powód blokady
                        'missing_amount' => $missing_amount // Brakująca kwota
                    );
                }
            }
        }
        
        return $activeVouchers;
    }

    public static function getDataForProductPage($id_product, $cart)
    {
        $product = new Product((int)$id_product);
        
        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        $context = Context::getContext();
        $isLogged = $context->customer->isLogged();
        $customerPoints = 0;
        $loginUrl = $context->link->getPageLink('my-account', true);
        $activeVouchers = array();

        if ($isLogged) {
            $id_customer = (int)$context->customer->id;
            $id_lang = (int)$context->language->id;
            $customerPoints = (int)LoyaltyModule::getPointsByCustomer($id_customer);
            $activeVouchers = self::getCustomerVouchers($id_customer, $id_lang);
        }

        $points = 0;
        $pointsBefore = 0;
        $pointsAfter = 0;
        $no_pts_discounted = 0;

        if (!(int)Configuration::get('PS_LOYALTY_NONE_AWARD') && Product::isDiscounted((int)$product->id)) {
            $no_pts_discounted = 1;
        }

        $id_product_attribute = (Tools::getValue('id_product_attribute', 'false') != 'false' ? Tools::getValue('id_product_attribute') : 0);
        if (Tools::getValue('group', 'false') != 'false') {
            $id_product_attribute = Product::getIdProductAttributeByIdAttributes((int)$product->id, Tools::getValue('group'));
        }
        $price_amount = $product->getPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? false : true, $id_product_attribute);

        if ($no_pts_discounted == 1) {
            $points = 0;
            if (Validate::isLoadedObject($cart)) {
                $pointsBefore = (int)LoyaltyModule::getCartNbPoints($cart);
            }
            $pointsAfter = $pointsBefore;
        } else {
            if (Validate::isLoadedObject($cart)) {
                $pointsBefore = (int)LoyaltyModule::getCartNbPoints($cart);
                $pointsAfter = (int)LoyaltyModule::getCartNbPoints($cart, $product);
                $points = (int)($pointsAfter - $pointsBefore);
            } else {
                $points = (int)LoyaltyModule::getNbPointsByPrice($price_amount);
                $pointsAfter = $points;
                $pointsBefore = 0;
            }
        }

        return array(
            'points' => (int)$points,
            'total_points' => (int)$pointsAfter,
            'point_rate' => Configuration::get('PS_LOYALTY_POINT_RATE'),
            'point_value' => Configuration::get('PS_LOYALTY_POINT_VALUE'),
            'points_in_cart' => (int)$pointsBefore,
            'customer_points' => (int)$customerPoints,
            'active_vouchers' => $activeVouchers,
            'is_logged' => $isLogged,
            'login_url' => $loginUrl,
            'voucher' => LoyaltyModule::getVoucherValue((int)$pointsAfter),
            'none_award' => Configuration::get('PS_LOYALTY_NONE_AWARD'),
            'no_pts_discounted' => $no_pts_discounted,
            'price_amount' => $price_amount,
            'product' => $product
        );
    }
}