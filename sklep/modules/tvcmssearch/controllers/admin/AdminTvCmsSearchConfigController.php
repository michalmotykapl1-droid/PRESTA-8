<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2025 PrestaShop SA
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

// START Debug Logger Inclusion
require_once(_PS_MODULE_DIR_ . 'tvcmssearch/classes/TvcmsSearchLogger.php');
TvcmsSearchLogger::info('AdminTvCmsSearchConfigController loaded.');
// END Debug Logger Inclusion

class AdminTvCmsSearchConfigController extends ModuleAdminController
{
    public function __construct()
    {
        TvcmsSearchLogger::info('Entering AdminTvCmsSearchConfigController constructor.');
        $this->bootstrap = true;
        $this->table = 'configuration';
        $this->className = 'Configuration';
        $this->lang = false;
        $this->display = 'form';

        $this->module = Module::getInstanceByName('tvcmssearch');
        if (!is_object($this->module)) {
            TvcmsSearchLogger::error('Failed to get module instance "tvcmssearch" in AdminTvCmsSearchConfigController constructor.');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome') . '&error=ModuleNotFound');
        }

        parent::__construct();
        $this->meta_title = $this->module->displayName; // Ustaw tytuł strony

        // Definicja pól formularza dla HelperForm
        $this->fields_form = []; // Inicjalizacja, aby zapobiec błędom

        TvcmsSearchLogger::info('AdminTvCmsSearchConfigController constructor finished.');
    }

    public function initContent()
    {
        TvcmsSearchLogger::info('Entering initContent method in AdminTvCmsSearchConfigController.');
        
        // Ustawienie zmiennych dla HelperForm (zgodnie z Twoją sugestią)
        $this->tpl_form_vars = [
            'fields_value'      => $this->getConfigValues(), // Wartości pól formularza
            'languages'         => $this->context->controller->getLanguages(),
            'id_language'       => $this->context->language->id,
            'module_display_name' => $this->module->displayName,
        ];

        parent::initContent(); 
        
        $this->content = $this->renderForm();
        
        $this->context->smarty->assign([
            'content' => $this->content,
            'url_post' => self::$currentIndex . '&token=' . $this->token,
        ]);

        TvcmsSearchLogger::info('initContent finished in AdminTvCmsSearchConfigController.');
    }

    public function postProcess()
    {
        TvcmsSearchLogger::info('Entering postProcess method in AdminTvCmsSearchConfigController.');
        
        if (Tools::isSubmit('submit' . $this->className)) {
            TvcmsSearchLogger::debug('Submit button detected.');

            $dataToSave = [
                'TVCMSSEARCH_DROPDOWN_THEME'    => Tools::getValue('TVCMSSEARCH_DROPDOWN_THEME'),
                'TVCMSSEARCH_DROPDOWN_ALIGN'    => Tools::getValue('TVCMSSEARCH_DROPDOWN_ALIGN'),
                'TVCMSSEARCH_INSTANT_SEARCH'    => (int)Tools::getValue('TVCMSSEARCH_INSTANT_SEARCH'),
                'TVCMSSEARCH_SHOW_PRICES'       => (int)Tools::getValue('TVCMSSEARCH_SHOW_PRICES'),
                'TVCMSSEARCH_SHOW_IMAGES'       => (int)Tools::getValue('TVCMSSEARCH_SHOW_IMAGES'),
                'TVCMSSEARCH_MAX_RESULTS'       => (int)Tools::getValue('TVCMSSEARCH_MAX_RESULTS'),
                'TVCMSSEARCH_SHOW_CATEGORIES'   => (int)Tools::getValue('TVCMSSEARCH_SHOW_CATEGORIES'),
                'TVCMSSEARCH_FUZZY_LEVEL'       => (int)Tools::getValue('TVCMSSEARCH_FUZZY_LEVEL'),
                'TVCMSSEARCH_WITHIN_WORD'       => (int)Tools::getValue('TVCMSSEARCH_WITHIN_WORD'),
                'TVCMSSEARCH_ONLY_AVAILABLE'    => (int)Tools::getValue('TVCMSSEARCH_ONLY_AVAILABLE'),
                'TVCMSSEARCH_CAT_CLICK_MODE'    => Tools::getValue('TVCMSSEARCH_CAT_CLICK_MODE'),
                'TVCMSSEARCH_SHOW_CAT_COUNT'    => (int)Tools::getValue('TVCMSSEARCH_SHOW_CAT_COUNT'),
                'TVCMSSEARCH_SHOW_DIET'         => (int)Tools::getValue('TVCMSSEARCH_SHOW_DIET'),
                'TVCMSSEARCH_SHOW_MANUFACTURER' => (int)Tools::getValue('TVCMSSEARCH_SHOW_MANUFACTURER'),
                'TVCMSSEARCH_SHOW_DIET_FILTER'  => (int)Tools::getValue('TVCMSSEARCH_SHOW_DIET_FILTER'), // DODANE
            
                'TVCMSSEARCH_DEBUG_LOG'    => (int)Tools::getValue('TVCMSSEARCH_DEBUG_LOG'),
];
            TvcmsSearchLogger::debug('Data received from POST: ' . json_encode($dataToSave) . '.');

            $errors = [];

            if (!in_array($dataToSave['TVCMSSEARCH_DROPDOWN_THEME'], ['classic', 'modern', 'finder'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla motywu rozwijanego.');
            }
            if (!in_array($dataToSave['TVCMSSEARCH_DROPDOWN_ALIGN'], ['left', 'right'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla wyrównania rozwijanego.');
            }
            if (!Validate::isUnsignedInt($dataToSave['TVCMSSEARCH_MAX_RESULTS']) || $dataToSave['TVCMSSEARCH_MAX_RESULTS'] <= 0) {
                 $errors[] = $this->l('Maksymalna liczba wyników musi być liczbą dodatnią.');
            }
            if (!Validate::isUnsignedInt($dataToSave['TVCMSSEARCH_FUZZY_LEVEL']) && $dataToSave['TVCMSSEARCH_FUZZY_LEVEL'] !== 0) {
                 $errors[] = $this->l('Poziom rozmycia musi być liczbą nieujemną.');
            }
            if (!in_array($dataToSave['TVCMSSEARCH_CAT_CLICK_MODE'], ['ajax', 'redirect'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla metody kliknięcia w kategorię.');
            }
            if (!Validate::isBool($dataToSave['TVCMSSEARCH_SHOW_CAT_COUNT'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla "Pokaż liczbę produktów przy kategoriach".');
            }
            if (!Validate::isBool($dataToSave['TVCMSSEARCH_SHOW_IMAGES'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla "Pokaż obrazki produktów".');
            }
            if (!Validate::isBool($dataToSave['TVCMSSEARCH_SHOW_CATEGORIES'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla "Pokaż listę kategorii".');
            }
            if (!Validate::isBool($dataToSave['TVCMSSEARCH_SHOW_DIET'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla "Pokaż kategorie dietetyczne".');
            }
            if (!Validate::isBool($dataToSave['TVCMSSEARCH_SHOW_MANUFACTURER'])) { 
                $errors[] = $this->l('Nieprawidłowa wartość dla "Pokaż filtr producenta".');
            }
            if (!Validate::isBool($dataToSave['TVCMSSEARCH_SHOW_DIET_FILTER'])) { // DODANE
                $errors[] = $this->l('Nieprawidłowa wartość dla "Pokaż filtr dietetyczny".');
            }


            if (!empty($errors)) {
                $this->errors = array_merge($this->errors, $errors);
                TvcmsSearchLogger::error('Form validation failed. Errors: ' . json_encode($this->errors) . '.');
            } else {
                foreach ($dataToSave as $key => $val) {
                    
            if (!Validate::isBool($dataToSave['TVCMSSEARCH_DEBUG_LOG'])) {
                $errors[] = $this->l('Nieprawidłowa wartość dla "Włącz zapisywanie do debug.log".');
            }
Configuration::updateValue($key, $val);
                }
                $this->confirmations[] = $this->l('Ustawienia zapisane poprawnie.');
                TvcmsSearchLogger::info('Module settings updated successfully.');
                Tools::redirectAdmin(self::$currentIndex . '&conf=6&token=' . $this->token);
            }
        }
        
        parent::postProcess();
        TvcmsSearchLogger::info('postProcess finished in AdminTvCmsSearchConfigController.');
    }

    public function renderForm()
    {
        TvcmsSearchLogger::info('Entering renderForm method to build HelperForm.');
        
        $this->fields_form = [
            [ // Sekcja 1: Ustawienia wyglądu
                'form' => [
                    'legend' => ['title' => $this->l('Ustawienia wyglądu'), 'icon' => 'icon-cogs'],
                    'input' => [
                        ['type' => 'select', 'label' => $this->l('Motyw listy rozwijanej:'), 'name' => 'TVCMSSEARCH_DROPDOWN_THEME', 'required' => true, 'options' => ['query' => [['id_option' => 'classic', 'name' => $this->l('Klasyczny')], ['id_option' => 'modern', 'name' => $this->l('Nowoczesny')], ['id_option' => 'finder', 'name' => $this->l('Finder-like')]], 'id' => 'id_option', 'name' => 'name']],
                        ['type' => 'select', 'label' => $this->l('Wyrównanie listy rozwijanej:'), 'name' => 'TVCMSSEARCH_DROPDOWN_ALIGN', 'required' => true, 'options' => ['query' => [['id_option' => 'left', 'name' => $this->l('Do lewej')], ['id_option' => 'right', 'name' => $this->l('Do prawej')]], 'id' => 'id_option', 'name' => 'name']],
                    ],
                    'submit' => ['title' => $this->l('Zapisz'), 'name' => 'submit' . $this->className, 'class' => 'btn btn-default pull-right'],
                ],
            ],
            [ // Sekcja 2: Ustawienia listy rozwijanej
                'form' => [
                    'legend' => ['title' => $this->l('Ustawienia listy rozwijanej'), 'icon' => 'icon-list'],
                    'input' => [
                        ['type' => 'switch', 'label' => $this->l('Aktywuj natychmiastowe wyszukiwanie:'), 'name' => 'TVCMSSEARCH_INSTANT_SEARCH', 'is_bool' => true, 'values' => [['id' => 'instant_search_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'instant_search_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                        ['type' => 'switch', 'label' => $this->l('Pokaż ceny podczas natychmiastowego wyszukiwania:'), 'name' => 'TVCMSSEARCH_SHOW_PRICES', 'is_bool' => true, 'values' => [['id' => 'show_prices_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'show_prices_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                        ['type' => 'switch', 'label' => $this->l('Pokaż obrazki produktów'), 'name' => 'TVCMSSEARCH_SHOW_IMAGES', 'is_bool' => true, 'values' => [['id' => 'show_images_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'show_images_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                        ['type' => 'switch', 'label' => $this->l('Pokaż kategorie w liście rozwijanej:'), 'name' => 'TVCMSSEARCH_SHOW_CATEGORIES', 'is_bool' => true, 'values' => [['id' => 'show_cat_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'show_cat_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                        // ZMIANA 1: Zmiana etykiety i dodanie opisu
                        ['type' => 'switch', 'label' => $this->l('Pokaż kategorie dietetyczne'), 'name' => 'TVCMSSEARCH_SHOW_DIET', 'is_bool' => true, 'desc' => $this->l('Wyświetla listę kategorii dietetycznych, do których należą znalezione produkty.'), 'values' => [['id' => 'show_diet_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'show_diet_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                        // ZMIANA 2: Dodanie nowego przełącznika
                        ['type' => 'switch', 'label' => $this->l('Pokaż filtr dietetyczny'), 'name' => 'TVCMSSEARCH_SHOW_DIET_FILTER', 'is_bool' => true, 'desc' => $this->l('W przyszłości włączy interaktywny filtr cech (np. checkboxy) do filtrowania wyników.'), 'values' => [['id' => 'show_diet_filter_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'show_diet_filter_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                        ['type' => 'text', 'label' => $this->l('Maksymalna liczba wyników:'), 'name' => 'TVCMSSEARCH_MAX_RESULTS', 'class' => 'fixed-width-sm', 'required' => true, 'validation' => 'isUnsignedInt'],
                        ['type' => 'select', 'label' => $this->l('Metoda kliknięcia w kategorię'), 'name' => 'TVCMSSEARCH_CAT_CLICK_MODE', 'options' => ['query' => [['id' => 'ajax', 'name' => $this->l('Filtruj w popupie (AJAX)')], ['id' => 'redirect', 'name' => $this->l('Przejdź na stronę wyników')]], 'id' => 'id', 'name' => 'name']],
                        ['type' => 'switch', 'label' => $this->l('Pokaż liczbę produktów przy kategoriach'), 'name' => 'TVCMSSEARCH_SHOW_CAT_COUNT', 'is_bool' => true, 'desc' => $this->l('Wyświetla obok każdej kategorii w wyszukiwarce liczbę dopasowanych produktów'), 'values' => [['id' => 'show_cat_count_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'show_cat_count_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                        ['type' => 'switch', 'label' => $this->l('Pokaż filtr producenta'), 'name' => 'TVCMSSEARCH_SHOW_MANUFACTURER', 'is_bool' => true, 'desc' => $this->l('Wyświetla listę producentów w wyszukiwarce'), 'values' => [['id' => 'show_manufacturer_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'show_manufacturer_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                    ],
                    'submit' => ['title' => $this->l('Zapisz'), 'name' => 'submit' . $this->className, 'class' => 'btn btn-default pull-right'],
                ],
            ],
            [ // Sekcja 3: Ustawienia wyszukiwania
                'form' => [
                    'legend' => ['title' => $this->l('Ustawienia wyszukiwania'), 'icon' => 'icon-search'],
                    'input' => [
                        ['type' => 'select', 'label' => $this->l('Poziom wyszukiwania przybliżonego:'), 'name' => 'TVCMSSEARCH_FUZZY_LEVEL', 'required' => true, 'options' => ['query' => [['id_option' => 0, 'name' => '0'], ['id_option' => 1, 'name' => '1'], ['id_option' => 2, 'name' => '2'], ['id_option' => 3, 'name' => '3'], ['id_option' => 4, 'name' => '4']], 'id' => 'id_option', 'name' => 'name'], 'desc' => $this->l('0 – brak, 4 – najwyższa trafność')],
                        ['type' => 'switch', 'label' => $this->l('Szukaj wewnątrz słowa:'), 'name' => 'TVCMSSEARCH_WITHIN_WORD', 'is_bool' => true, 'desc' => $this->l('Dograne frazy w środku'), 'values' => [['id' => 'within_word_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'within_word_off', 'value' => 0, 'label' => 'Nie']]],
                        ['type' => 'switch', 'label' => $this->l('Pokazuj tylko produkty dostępne:'), 'name' => 'TVCMSSEARCH_ONLY_AVAILABLE', 'is_bool' => true, 'values' => [['id' => 'only_available_on', 'value' => 1, 'label' => $this->l('Tak')], ['id' => 'only_available_off', 'value' => 0, 'label' => $this->l('Nie')]]],
                    ],
                    'submit' => ['title' => $this->l('Zapisz'), 'name' => 'submit' . $this->className, 'class' => 'btn btn-default pull-right'],
                ],
            ],
        
            [ // Sekcja 4: Debugowanie i logi
                'form' => [
                    'legend' => ['title' => $this->l('Debugowanie i logi'), 'icon' => 'icon-bug'],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Włącz zapisywanie do debug.log'),
                            'name' => 'TVCMSSEARCH_DEBUG_LOG',
                            'is_bool' => true,
                            'values' => [
                                ['id' => 'debug_on', 'value' => 1, 'label' => $this->l('Tak')],
                                ['id' => 'debug_off', 'value' => 0, 'label' => $this->l('Nie')]
                            ],
                            'desc' => $this->l('Po włączeniu moduł zapisuje zdarzenia do modules/tvcmssearch/debug.log. Po wyłączeniu nic nie zapisuje.')
                        ]
                    ],
                    'submit' => ['title' => $this->l('Zapisz'), 'name' => 'submit' . $this->className, 'class' => 'btn btn-default pull-right'],
                ],
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->className;
        $helper->currentIndex = self::$currentIndex;
        $helper->token = $this->token;
        $helper->tpl_vars = $this->tpl_form_vars;

        TvcmsSearchLogger::info('HelperForm prepared. Rendering form.');
        return $helper->generateForm($this->fields_form);
    }

    protected function getConfigValues()
    {
        TvcmsSearchLogger::info('Entering getConfigValues method.');
        $values = [
            'TVCMSSEARCH_DROPDOWN_THEME'    => Configuration::get('TVCMSSEARCH_DROPDOWN_THEME', 'classic'),
            'TVCMSSEARCH_DROPDOWN_ALIGN'    => Configuration::get('TVCMSSEARCH_DROPDOWN_ALIGN', 'left'),
            'TVCMSSEARCH_INSTANT_SEARCH'    => (int)Configuration::get('TVCMSSEARCH_INSTANT_SEARCH', 1),
            'TVCMSSEARCH_SHOW_PRICES'       => (int)Configuration::get('TVCMSSEARCH_SHOW_PRICES', 1),
            'TVCMSSEARCH_SHOW_IMAGES'       => (int)Configuration::get('TVCMSSEARCH_SHOW_IMAGES', 1),
            'TVCMSSEARCH_MAX_RESULTS'       => (int)Configuration::get('TVCMSSEARCH_MAX_RESULTS', 8),
            'TVCMSSEARCH_SHOW_CATEGORIES'   => (int)Configuration::get('TVCMSSEARCH_SHOW_CATEGORIES', 1),
            'TVCMSSEARCH_FUZZY_LEVEL'       => (int)Configuration::get('TVCMSSEARCH_FUZZY_LEVEL', 1),
            'TVCMSSEARCH_WITHIN_WORD'       => (int)Configuration::get('TVCMSSEARCH_WITHIN_WORD', 0),
            'TVCMSSEARCH_ONLY_AVAILABLE'    => (int)Configuration::get('TVCMSSEARCH_ONLY_AVAILABLE', 0),
            'TVCMSSEARCH_CAT_CLICK_MODE'    => Configuration::get('TVCMSSEARCH_CAT_CLICK_MODE', 'ajax'),
            'TVCMSSEARCH_SHOW_CAT_COUNT'    => (int)Configuration::get('TVCMSSEARCH_SHOW_CAT_COUNT', 0),
            'TVCMSSEARCH_SHOW_DIET'         => (int)Configuration::get('TVCMSSEARCH_SHOW_DIET', 1),
            'TVCMSSEARCH_SHOW_MANUFACTURER' => (int)Configuration::get('TVCMSSEARCH_SHOW_MANUFACTURER', 1),
            'TVCMSSEARCH_SHOW_DIET_FILTER'  => (int)Configuration::get('TVCMSSEARCH_SHOW_DIET_FILTER', 1), // DODANE
        
            'TVCMSSEARCH_DEBUG_LOG'    => (int)Configuration::get('TVCMSSEARCH_DEBUG_LOG', 0),];
        TvcmsSearchLogger::debug('Config values loaded: ' . json_encode($values) . '.');
        TvcmsSearchLogger::info('getConfigValues finished.');
        return $values;
    }
}