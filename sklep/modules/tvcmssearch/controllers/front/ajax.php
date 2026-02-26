<?php
class TvcmssearchAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        // Pobranie parametrów
        $search = Tools::getValue('search_words');
        $catId  = (int)Tools::getValue('category_id');

        // Twoja logika wyszukiwania:
        $html = $this->module->getAjaxResult($search, $catId);

        // Zwróć czysty HTML
        die($html);
    }
}
