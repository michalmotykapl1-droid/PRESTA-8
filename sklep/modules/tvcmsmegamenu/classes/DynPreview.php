<?php
if (!defined('_PS_VERSION_')) { exit; }
class TvcmsDynPreview
{
    public static function render(Context $context, array $cfg)
    {
        $products = TvcmsDynProductsProvider::present($context, TvcmsDynProductsProvider::fetchRaw($context, $cfg));
        $context->smarty->assign([
            'products'       => $products,
            'layout'         => isset($cfg['layout']) ? $cfg['layout'] : 'grid',
            'showPrice'      => isset($cfg['show_price']) ? (bool)$cfg['show_price'] : true,
            'showPromoBadge' => isset($cfg['show_badge']) ? (bool)$cfg['show_badge'] : true,
        ]);
        return $context->smarty->fetch(_PS_MODULE_DIR_.'tvcmsmegamenu/views/templates/hook/_dynproducts.tpl');
    }
}