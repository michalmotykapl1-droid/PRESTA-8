<?php
/*
* PrestaShop module created by VEKIA
* Modified for BigBio: Cleaned up history logic + Added Voucher Exchange History
*/

if (!defined('_PS_VERSION_'))
	exit;

require_once(dirname(__FILE__) . '/LoyaltyStateModule.php');

class LoyaltyModule extends ObjectModel
{
	public $id_loyalty_state;
	public $id_customer;
	public $id_order;
	public $id_cart_rule;
	public $points;
	public $date_add;
	public $date_upd;

	public static $definition = array(
		'table' => 'loyalty',
		'primary' => 'id_loyalty',
		'fields' => array(
			'id_loyalty_state' =>	array('type' => self::TYPE_INT, 'validate' => 'isInt'),
			'id_customer' =>		array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
			'id_order' =>			array('type' => self::TYPE_INT, 'validate' => 'isInt'),
			'id_cart_rule' =>		array('type' => self::TYPE_INT, 'validate' => 'isInt'),
			'points' =>				array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
			'date_add' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			'date_upd' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
		)
	);

	public function save($nullValues = false, $autodate = true)
	{
		parent::save($nullValues, $autodate);
		$this->historize();
	}

	public static function getByOrderId($id_order)
	{
		if (!Validate::isUnsignedId($id_order))
			return false;

		$result = Db::getInstance()->getRow('
		SELECT f.id_loyalty
		FROM `'._DB_PREFIX_.'loyalty` f
		WHERE f.id_order = '.(int)($id_order));

		return isset($result['id_loyalty']) ? $result['id_loyalty'] : false;
	}

	public static function getOrderNbPoints($order)
	{
		if (!Validate::isLoadedObject($order))
			return false;
		return self::getCartNbPoints(new Cart((int)$order->id_cart));
	}

	public static function getCartNbPoints($cart, $newProduct = NULL)
	{
		$total = 0;
		if (Validate::isLoadedObject($cart))
		{
			$currentContext = Context::getContext();
			$context = clone $currentContext;
			$context->cart = $cart;
			if(!$context->customer->isLogged(true))
				$context->customer = new Customer($context->cart->id_customer);
			$context->language = new Language($context->cart->id_lang);
			$context->shop = new Shop($context->cart->id_shop);
			$context->currency = new Currency($context->cart->id_currency, null, $context->shop->id);

			$cartProducts = $cart->getProducts();
			$taxesEnabled = Product::getTaxCalculationMethod();
			if (isset($newProduct) AND !empty($newProduct))
			{
				$cartProductsNew['id_product'] = (int)$newProduct->id;
				if ($taxesEnabled == PS_TAX_EXC)
					$cartProductsNew['price'] = number_format($newProduct->getPrice(false, (int)$newProduct->getIdProductAttributeMostExpensive()), 2, '.', '');
				else
					$cartProductsNew['price_wt'] = number_format($newProduct->getPrice(true, (int)$newProduct->getIdProductAttributeMostExpensive()), 2, '.', '');
				$cartProductsNew['cart_quantity'] = 1;
				$cartProducts[] = $cartProductsNew;
			}
			foreach ($cartProducts AS $product)
			{
				if (!(int)(Configuration::get('PS_LOYALTY_NONE_AWARD')) AND Product::isDiscounted((int)$product['id_product']))
				{
					if (isset(Context::getContext()->smarty) AND is_object($newProduct) AND $product['id_product'] == $newProduct->id)
						Context::getContext()->smarty->assign('no_pts_discounted', 1);
					continue;
				}
				$total += ($taxesEnabled == PS_TAX_EXC ? $product['price'] : $product['price_wt'])* (int)($product['cart_quantity']);
			}
			foreach ($cart->getCartRules(false) AS $cart_rule)
				if ($taxesEnabled == PS_TAX_EXC)
					$total -= $cart_rule['value_tax_exc'];
				else
					$total -= $cart_rule['value_real'];

		}

		return self::getNbPointsByPrice($total);
	}

	public static function getVoucherValue($nbPoints, $id_currency = NULL)
	{
		$currency = $id_currency ? new Currency($id_currency) : Context::getContext()->currency->id;

		return (int)$nbPoints * (float)Tools::convertPrice(Configuration::get('PS_LOYALTY_POINT_VALUE'), $currency);
	}

	public static function getNbPointsByPrice($price)
	{
		if (Configuration::get('PS_CURRENCY_DEFAULT') != Context::getContext()->currency->id)
		{
			if (Context::getContext()->currency->conversion_rate)
				$price = $price / Context::getContext()->currency->conversion_rate;
		}

		$points = 0;
		if ($pointRate = (float)(Configuration::get('PS_LOYALTY_POINT_RATE')))
			$points = floor(number_format($price, 2, '.', '') / $pointRate);

		return (int)$points;
	}

	public static function getPointsByCustomer($id_customer)
	{
		$validity_period = Configuration::get('PS_LOYALTY_VALIDITY_PERIOD');
		$sql_period = '';
		if ((int)$validity_period > 0)
			$sql_period = ' AND datediff(NOW(),f.date_add) <= '.$validity_period;

		return
			Db::getInstance()->getValue('
		SELECT SUM(f.points) points
		FROM `'._DB_PREFIX_.'loyalty` f
		WHERE f.id_customer = '.(int)($id_customer).'
		AND f.id_loyalty_state IN ('.(int)(LoyaltyStateModule::getValidationId()).', '.(int)(LoyaltyStateModule::getNoneAwardId()).')
		'.$sql_period)
			+
			Db::getInstance()->getValue('
		SELECT SUM(f.points) points
		FROM `'._DB_PREFIX_.'loyalty` f
		WHERE f.id_customer = '.(int)($id_customer).'
		AND f.id_loyalty_state = '.(int)LoyaltyStateModule::getCancelId().'
		AND points < 0
		'.$sql_period);
	}

	public static function getAllByIdCustomer($id_customer, $id_lang, $onlyValidate = false, $pagination = false, $nb = 10, $page = 1)
	{
		$validity_period = Configuration::get('PS_LOYALTY_VALIDITY_PERIOD');
		$sql_period = '';
		if ((int)$validity_period > 0)
			$sql_period = ' AND datediff(NOW(),f.date_add) <= '.$validity_period;

        // --- ZMIANA: UNION ALL, aby pobrać i zamówienia, i wymiany punktów na bony ---

		// 1. Historia zamówień (punkty dodane lub anulowane)
		$queryOrders = '
		SELECT f.id_order AS id, o.reference, f.date_add AS date, (o.total_paid - o.total_shipping) total_without_shipping, f.points, f.id_loyalty, f.id_loyalty_state, fsl.name state,
        cr.code as voucher_code, cr.reduction_amount as voucher_amount, cr.reduction_percent as voucher_percent
		FROM `'._DB_PREFIX_.'loyalty` f
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (f.id_order = o.id_order)
		LEFT JOIN `'._DB_PREFIX_.'loyalty_state_lang` fsl ON (f.id_loyalty_state = fsl.id_loyalty_state AND fsl.id_lang = '.(int)($id_lang).')
        LEFT JOIN `'._DB_PREFIX_.'cart_rule` cr ON (f.id_cart_rule = cr.id_cart_rule)
		WHERE f.id_customer = '.(int)($id_customer).$sql_period.'
        AND (f.points != 0 OR f.id_cart_rule > 0)';

		if ($onlyValidate === true) {
			$queryOrders .= ' AND f.id_loyalty_state = '.(int)LoyaltyStateModule::getValidationId();
            $queryOrders .= ' GROUP BY f.id_loyalty ORDER BY f.date_add DESC '.
			($pagination ? 'LIMIT '.(((int)($page) - 1) * (int)($nb)).', '.(int)($nb) : '');
            return Db::getInstance()->executeS($queryOrders);
        }

        $queryOrders .= ' GROUP BY f.id_loyalty ';

        // 2. Historia wymiany (wiersze wirtualne)
        // Pobieramy punkty, które zostały 'skonwertowane' (state 4) i pokazujemy je jako ujemne
        $convertedStateId = (int)LoyaltyStateModule::getConvertId();
        
        $queryVouchers = '
        SELECT 
            0 AS id, 
            cr.code AS reference, 
            f.date_upd AS date, 
            0 AS total_without_shipping, 
            SUM(f.points) * -1 AS points, 
            0 AS id_loyalty, 
            '.$convertedStateId.' AS id_loyalty_state, 
            "Wymiana na bon" AS state,
            cr.code AS voucher_code, 
            cr.reduction_amount AS voucher_amount, 
            cr.reduction_percent AS voucher_percent
        FROM `'._DB_PREFIX_.'loyalty` f
        INNER JOIN `'._DB_PREFIX_.'cart_rule` cr ON (f.id_cart_rule = cr.id_cart_rule)
        WHERE f.id_customer = '.(int)($id_customer).'
        AND f.id_cart_rule > 0
        AND f.id_loyalty_state = '.$convertedStateId.'
        GROUP BY f.id_cart_rule
        ';

        // 3. Łączymy wyniki
        $finalQuery = '
        SELECT * FROM (
            (' . $queryOrders . ')
            UNION ALL
            (' . $queryVouchers . ')
        ) AS combined_history
        ORDER BY date DESC
        '. ($pagination ? 'LIMIT '.(((int)($page) - 1) * (int)($nb)).', '.(int)($nb) : '');

		return Db::getInstance()->executeS($finalQuery);
	}

	public static function getDiscountByIdCustomer($id_customer, $last = false)
	{
		$query = '
		SELECT f.id_cart_rule AS id_cart_rule, f.date_upd AS date_add
		FROM `'._DB_PREFIX_.'loyalty` f
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (f.`id_order` = o.`id_order`)
		INNER JOIN `'._DB_PREFIX_.'cart_rule` cr ON (cr.`id_cart_rule` = f.`id_cart_rule`)
		WHERE f.`id_customer` = '.(int)($id_customer).'
		AND f.`id_cart_rule` > 0
		GROUP BY f.id_cart_rule';
		if ($last)
			$query.= ' ORDER BY f.id_loyalty DESC LIMIT 0,1';

		return Db::getInstance()->executeS($query);
	}

	public static function registerDiscount($cartRule)
	{
		if (!Validate::isLoadedObject($cartRule))
			die(Tools::displayError('Incorrect object CartRule.'));
		$items = self::getAllByIdCustomer((int)$cartRule->id_customer, NULL, true);
		$associated = false;
		foreach ($items AS $item)
		{
			$lm = new LoyaltyModule((int)$item['id_loyalty']);

			$negativePoints = (int)Db::getInstance()->getValue('SELECT SUM(points) points FROM '._DB_PREFIX_.'loyalty WHERE id_order = '.(int)$item['id'].' AND id_loyalty_state = '.(int)LoyaltyStateModule::getCancelId().' AND points < 0');

			if ($lm->points + $negativePoints <= 0)
				continue;

			$lm->id_cart_rule = (int)$cartRule->id;
			$lm->id_loyalty_state = (int)LoyaltyStateModule::getConvertId();
			$lm->save();
			$associated = true;
		}
		return $associated;
	}

	public static function getOrdersByIdDiscount($id_cart_rule)
	{
		$items = Db::getInstance()->executeS('
		SELECT f.id_order AS id_order, f.points AS points, f.date_upd AS date
		FROM `'._DB_PREFIX_.'loyalty` f
		WHERE f.id_cart_rule = '.(int)$id_cart_rule.' AND f.id_loyalty_state = '.(int)LoyaltyStateModule::getConvertId());

		if (!empty($items) AND is_array($items))
		{
			foreach ($items AS $key => $item)
			{
				$order = new Order((int)$item['id_order']);
				$items[$key]['id_currency'] = (int)$order->id_currency;
				$items[$key]['id_lang'] = (int)$order->id_lang;
				$items[$key]['total_paid'] = $order->total_paid;
				$items[$key]['total_shipping'] = $order->total_shipping;
			}
			return $items;
		}

		return false;
	}

	private function historize()
	{
		Db::getInstance()->execute('
		INSERT INTO `'._DB_PREFIX_.'loyalty_history` (`id_loyalty`, `id_loyalty_state`, `points`, `date_add`)
		VALUES ('.(int)($this->id).', '.(int)($this->id_loyalty_state).', '.(int)($this->points).', NOW())');
	}

}