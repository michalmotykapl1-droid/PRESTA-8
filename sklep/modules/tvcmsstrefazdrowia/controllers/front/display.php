<?php
/**
 * 2007-2025 PrestaShop
 * Strefa Zdrowia - Inteligentny Kontroler z obsługą Koszyka
 */

class TvcmsStrefaZdrowiaDisplayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $page_type = Tools::getValue('strona');
        $meta_title = 'Strefa Zdrowia & Równowagi';
        
        switch ($page_type) {
            case 'fizjoterapia':
                $template = 'display_fizjo.tpl';
                $meta_title = 'Fizjoterapia i Rehabilitacja - Strefa Zdrowia';
                break;
            
            case 'uroda':
                $template = 'display_uroda.tpl';
                $meta_title = 'Strefa Urody i Kosmetologia - Strefa Zdrowia';
                break;

            case 'naturopatia':
                $template = 'display_natur.tpl';
                $meta_title = 'Naturopatia i Suplementacja - Strefa Zdrowia';
                break;

            default:
                $template = 'display_main.tpl';
                break;
        }

        $this->context->smarty->assign([
            'meta_title' => $meta_title,
            'strefa_main_title' => 'Strefa Zdrowia',
            'static_token' => Tools::getToken(false), // <--- TO JEST KLUCZOWE DLA KOSZYKA
        ]);

        $this->setTemplate('module:tvcmsstrefazdrowia/views/templates/front/' . $template);
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        
        $breadcrumb['links'][] = [
            'title' => 'Strefa Zdrowia',
            'url' => $this->context->link->getModuleLink('tvcmsstrefazdrowia', 'display'),
        ];

        $page_type = Tools::getValue('strona');
        if ($page_type) {
            $breadcrumb['links'][] = [
                'title' => ucfirst($page_type),
                'url' => '#',
            ];
        }

        return $breadcrumb;
    }
}