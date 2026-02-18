<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\DataProvider\CategoriesProvider;
use x13allegro\Api\DataProvider\CategoriesParametersProvider;
use x13allegro\Form\CategoryParameters\CategoryParameters;
use x13allegro\Form\CategoryParameters\ParametersForm;
use x13allegro\Form\CategoryParameters\ParametersMapForm;
use x13allegro\Repository\PrestaShop\ManufacturerRepository;
use x13allegro\Repository\PrestaShop\ProductAttributeRepository;
use x13allegro\Repository\PrestaShop\ProductFeatureRepository;

final class AdminXAllegroAssocCategoriesController extends XAllegroController
{
    protected $allegroAutoLogin = true;

    private $allegroCategory;
    private $allegroCategoryPath = array(null);
    private $allegroCategoryNotExists = false;

    /** @var XAllegroCategory */
    public $object;

    /** @var CategoriesProvider */
    private $categoriesProvider;

    /** @var CategoriesParametersProvider */
    private $categoriesParametersProvider;

    public function __construct()
    {
        $this->table = 'xallegro_category';
        $this->identifier = 'id_xallegro_category';
        $this->className = 'XAllegroCategory';
        $this->multiple_fieldsets = true;

        parent::__construct();
        
        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroAssocCategories'));
        $this->tpl_folder = 'x_allegro_categories/';

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );
    }

    public function init()
    {
        parent::init();

        if (!$this->allegroApi) {
            return;
        }

        $this->loadObject(true);
        $this->categoriesProvider = new CategoriesProvider($this->allegroApi);
        $this->categoriesParametersProvider = new CategoriesParametersProvider($this->allegroApi);

        $is_mapped = false;
        $categoryId = Tools::getValue('id_allegro_category');

        if (is_array($categoryId)) {
            $categoryId = end($categoryId);
        }

        if (!$categoryId && Validate::isLoadedObject($this->object)) {
            $categoryId = $this->object->id_allegro_category;

            if ($categoryId) {
                $is_mapped = true;
            }
        }

        if ($categoryId && !$this->ajax) {
            $this->allegroCategory = $this->categoriesProvider->getCategoryDetails($categoryId);

            if ($this->allegroCategory) {
                // Inject allegroCategoryId to object
                $this->object->id_allegro_category = $this->allegroCategory->id;
                $this->allegroCategoryPath = array();

                foreach ($this->categoriesProvider->getCategoriesPath($this->allegroCategory->id) as $id => $list) {
                    $this->allegroCategoryPath[] = array(
                        'id' => $id,
                        'name' => $this->categoriesProvider->getCategoryDetails($id)->name,
                        'list' => $list
                    );
                }
            }
            else if ($is_mapped) {
                $this->allegroCategoryNotExists = true;
            }
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryPlugin('autocomplete');
        $this->addJqueryUI('ui.sortable');

        $this->addCSS($this->module->getPathUri() . 'views/js/select2/css/select2.min.css');
        $this->addJS($this->module->getPathUri() . 'views/js/select2/js/select2.full.min.js');
    }

    public function initToolbar()
    {
        if ($this->display == 'add' || $this->display == 'edit') {
            $this->toolbar_btn['save_and_stay'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Zapisz i zostań'),
                'class' => 'process-icon-save-and-stay '
            );
        }

        parent::initToolbar();
    }

    public function renderList()
    {
        if (Tools::getValue('controller') == 'AdminXAllegroAssocCategories' && empty($this->errors) && empty($this->confirmations)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAssoc'));
        }

        $this->initToolbar();

        if (method_exists($this, 'initPageHeaderToolbar')) {
            $this->initPageHeaderToolbar();
        }

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = array(
            'id_xallegro_category' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 50,
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Nazwa powiązania'),
                'search' => false,
            ),
            'path' => array(
                'title' => $this->l('Kategoria Allegro'),
                'search' => false,
                'filter' => false
            ),
            'active' => array(
                'title' => $this->l('Aktywny'),
                'width' => 70,
                'align' => 'center',
                'active' => 'active',
                'type' => 'bool',
                'class' => 'fixed-width-sm'
            ),
            'id_categories' => array(
                'title' => $this->l('Ilość przypisanych kategorii'),
                'align' => 'center',
                'search' => false,
                'filter' => false,
                'callback_object' => $this,
                'callback' => 'countCategories',
            ),
            'categories' => array(
                'title' => $this->l('Kategorie'),
                'align' => 'center',
                'search' => false,
                'callback_object' => $this,
                'callback' => 'renderCategories',
                'tmpTableFilter' => true,
                'filter_key' => 'categories'
            )
        );

        return parent::renderList();
    }

    public function countCategories($value, $row)
    {
        return (!empty($row['id_categories']) ? count(explode(',', $row['id_categories'])) : '--');
    }

    public function renderCategories($value, $row)
    {
        if (empty($row['id_categories'])) {
            return '';
        }
        $categories = Db::getInstance()->executeS('
            SELECT cl.`name` category 
            FROM `'._DB_PREFIX_.'category_lang` cl 
            WHERE cl.`id_category` IN('.$row['id_categories'].') 
                AND cl.`id_lang` = '.$this->context->language->id.' 
                AND `id_shop` = '.$this->context->shop->id);

        if (!$categories) {
            return '';
        }

        $categories = array_map(function ($row) {
            return $row['category'];
        }, $categories);

        $suffix = '';

        if (count($categories) > 2) {
            $suffix = ' [...]';
            $categories = array_splice($categories, 0, 2);
        }

        return implode(', ', $categories).$suffix;
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $this->_select = 'a.id_allegro_category as categories';

        // if (Tools::isSubmit('submitFilter'.$this->list_id) && !Tools::isSubmit('submitResetxallegro_category')) {
        //     if (Tools::getValue('xallegro_categoryFilter_name')) {
        //         $this->_where = 'AND a.name LIKE "%'.pSQL(Tools::getValue('xallegro_categoryFilter_name')).'%"';
        //     }
        // }

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function renderForm()
    {
        if (!Validate::isLoadedObject($this->object)) {
            $this->warnings[] = $this->l('Musisz zapisać tę ścieżkę kategorii przed mapowaniem parametrów i tagów.');
        }

        $allegroCategories = $this->categoriesProvider->getCategoriesList();
        foreach ($this->allegroCategoryPath as $category) {
            if (isset($category['list'])) {
                $allegroCategories = array_merge($allegroCategories, $category['list']);
            }
        }

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Kategoria Allegro')
            ),
            'warning' => ($this->allegroCategoryNotExists ? $this->l('Zmapowana kategoria Allegro już nie istnieje.') : ''),
            'input' => array(
                array(
                    'type' => 'category',
                    'name' => 'id_allegro_category',
                    'categories' => $allegroCategories,
                    'path' =>  $this->allegroCategoryPath
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        $existingAssociations[] = [
            'id' => 0,
            'name' => '-- Wybierz kategorie --'
        ];
        if (Validate::isLoadedObject($this->object)) {
            foreach (XAllegroCategory::getExistingAssociations() as $association) {
                if ($association['id_xallegro_category'] != $this->object->id) {
                    $existingAssociations[] = [
                        'id' => $association['id_xallegro_category'],
                        'name' => $association['id_xallegro_category'] .
                            (!empty($association['name']) ? ' (' . $association['name'] . ')' : '') . ': ' . $association['path']
                    ];
                }
            }
        }

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Ustawienia podstawowe')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'name' => 'name',
                    'label' => $this->l('Nazwa pomocnicza powiązania'),
                    'class' => 'fixed-width-xxl',
                ),
                array(
                    'label' => $this->l('Aktywny'),
                    'type' => 'switch',
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => array(
                        array('value' => 1, 'label' => $this->l('Tak')),
                        array('value' => 0, 'label' => $this->l('Nie'))
                    ),
                    'default_value' => 1
                ),
                array(
                    'label' => $this->l('Wybierz kategorie'),
                    'type' => 'select',
                    'name' => 'copy_parameters',
                    'copy_parameters' => true,
                    'class' => 'fixed-width-xxl no-chosen',
                    'options' => array(
                        'query' => $existingAssociations,
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'label' => $this->l('Tryb kopiowania'),
                    'type' => 'select',
                    'name' => 'copy_parameters_mode',
                    'copy_parameters' => true,
                    'class' => 'fixed-width-xxl no-chosen',
                    'options' => array(
                        'query' => array(
                            array('id' => XAllegroCategory::COPY_MODE_EMPTY, 'name' => $this->l('dodaj tylko parametry do niezmapowanych/nieprzypisanych parametrów z wybranej kategorii')),
                            array('id' => XAllegroCategory::COPY_MODE_ALL, 'name' => $this->l('dodaj wszystkie pasujące parametry z wybranej kategorii')),
                            array('id' => XAllegroCategory::COPY_MODE_OVERRIDE, 'name' => $this->l('nadpisz wszystkie parametry według wybranej kategorii'))
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        $parameters = array();
        if ($this->allegroCategory && $this->allegroCategory->leaf) {
            try {
                $parameters = $this->categoriesParametersProvider->getParameters($this->allegroCategory->id);
            }
            catch (Exception $ex) {
                $this->errors[] = (string)$ex;
            }
        }

        $form = (new ParametersForm())
            ->setController($this)
            ->setLanguage($this->allegroApi->getAccount()->id_language)
            ->setCategory($this->object)
            ->setParameters($parameters)
            ->setMapButton(true)
            ->setFieldsValues(Tools::getValue('category_fields', []), Tools::getValue('category_ambiguous_fields', []));

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Parametry kategorii')
            ),
            'input' => array(),
            'category_parameters' => $form->buildForm(),
            'submit' => array(
                'title' => $this->l('Zapisz'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        $tagManager = new XAllegroHelperTagManager();
        $tagManager->setMapType(XAllegroTagManager::MAP_CATEGORY);
        $tagManager->setContainer('xallegro_category_form');

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Tagi kategorii'),
            ),
            'input' => array(
                array(
                    'type' => 'tag-manager',
                    'name' => 'tag-manager',
                    'content' => (Validate::isLoadedObject($this->object) ? $tagManager->renderTagManager($this->object->tags) : '')
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        $root_category = Category::getRootCategory();
        $root_category = array('id_category' => $root_category->id, 'name' => $root_category->name);

        $cat_input = array(
            'type' => 'categories',
            'label' => $this->l('Kategoria'),
            'name' => 'categoryBox',
            'tree' => array(
                'id' => 'categoryBox',
                'root_category' => $root_category['id_category'],
                'use_search' => true,
                'use_checkbox' => true,
                'use_radio' => false,
                'selected_categories' => $this->object->id_categories
            )
        );

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->l('Kategoria w sklepie internetowym'),
            ),
            'input' => array(
                $cat_input
            ),
            'submit' => array(
                'title' => $this->l('Zapisz'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Zapisz i zostań'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        $this->tpl_form_vars['allegro_category_input'] = ($this->allegroCategory ? $this->allegroCategory->id : 0);

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitCopyParameters')) {
            if (!Validate::isLoadedObject($this->object)) {
                $this->errors[] = $this->l('Musisz najpierw zapisać tę ścieżkę kategorii.');
                return false;
            }

            $xAllegroCategory = new XAllegroCategory(Tools::getValue('copy_parameters'));
            if (!Validate::isLoadedObject($xAllegroCategory)) {
                $this->errors[] = $this->l('Wybrano nieprawidłowe powiązanie kategorii.');
                return false;
            }

            $parameters = [];
            if ($this->allegroCategory) {
                try {
                    $parameters = $this->categoriesParametersProvider->getParameters($this->allegroCategory->id);
                }
                catch (Exception $ex) {
                    $this->errors[] = (string)$ex;
                    return false;
                }
            }

            if ($this->object->copyParameters($xAllegroCategory, $parameters, Tools::getValue('copy_parameters_mode'))) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAssocCategories') .
                    '&conf=4&update' . $this->table . '&' . $this->identifier . '=' . $this->object->id);
            }
            else {
                $this->errors[] = $this->l('Wystąpił błąd podczas kopiowania parametrów.');
            }
        }
        else if (Tools::isSubmit('submitAdd' . $this->table)
            || Tools::isSubmit('submitAdd' . $this->table . 'AndStay')
        ) {
            if (!$this->allegroCategory) {
                $this->errors[] = $this->l('Nie wybrano kategorii Allegro.');
                return false;
            }
            else if (!$this->allegroCategory->leaf) {
                $this->errors[] = $this->l('Wybrana kategoria Allegro nie jest kategorią najniższego rzędu.');
                return false;
            }

            $this->object->fields_values = [];
            foreach (Tools::getValue('category_fields', []) as $id => $value) {
                $this->object->fields_values[(int)$id] = $value;
            }

            $this->object->fields_ambiguous_values = [];
            foreach (Tools::getValue('category_ambiguous_fields', []) as $id => $value) {
                $this->object->fields_ambiguous_values[(int)$id] = $value;
            }

            foreach ($this->allegroCategoryPath as $categoryPath) {
                $path[] = $categoryPath['name'];
            }

            foreach (Tools::getValue('xallegro_tag', array()) as $user_id => $tags) {
                $this->object->tags[$user_id] = $tags;
            }

            $name = '';
            if (Tools::getValue('name', 0) && Validate::isGenericName(Tools::getValue('name'))) {
                $name = Tools::getValue('name');
            }

            $this->object->name = $name;
            $this->object->path = (isset($path) ? implode(' > ', $path) : '');
            $this->object->id_allegro_category = $this->allegroCategory->id;
            $this->object->id_categories = Tools::getValue('categoryBox', []);
            $this->object->active = Tools::getValue('active');
            $this->object->save();

            if (Tools::isSubmit('submitAdd' . $this->table . 'AndStay')) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAssocCategories') .
                    '&conf=4&update' . $this->table . '&' . $this->identifier . '=' . $this->object->id);
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAssoc') . '&conf=4');
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        if (!empty($this->errors)) {
            $this->display = 'edit';
        }

        parent::initContent();
    }

    public function ajaxProcessGetCategories()
    {
        $parameters =
        $categories =
        $categoriesPath = array();
        $isLeaf =
        $categoriesFields = false;

        $categoryId = Tools::getValue('id_allegro_category');
        $category = $this->categoriesProvider->getCategoryDetails($categoryId);

        if ((bool)Tools::getValue('full_path')) {
            foreach ($this->categoriesProvider->getCategoriesPath($categoryId) as $id => $list) {
                $categoriesPath[] = array(
                    'id' => $id,
                    'list' => $list
                );
            }
        }
        else {
            $categories = $this->categoriesProvider->getCategoriesList($categoryId);
        }

        if ($category) {
            $isLeaf = $category->leaf;
        }

        if ($isLeaf) {
            try {
                $parameters = $this->categoriesParametersProvider->getParameters($categoryId);
            }
            catch (Exception $ex) {}

            if (!empty($parameters)) {
                $categoriesFields = (new ParametersForm())
                    ->setController($this)
                    ->setCategory($this->object)
                    ->setParameters($parameters)
                    ->setMapButton(true)
                    ->setFieldsValues()
                    ->buildForm();
            }
        }

        die(json_encode(array(
            'last_node' => (int)$isLeaf,
            'fields' => $categoriesFields,
            'categories' => $categories,
            'categories_array' => $categoriesPath
        )));
    }

    public function ajaxProcessGetParameterMapForm()
    {
        $parameterId = Tools::getValue('parameterId');

        try {
            if (!Validate::isLoadedObject($this->object)) {
                throw new Exception($this->l('Musisz zapisać tą sieżkę kategorii przed mapowaniem parametrów.'));
            }

            $category = $this->categoriesProvider->getCategoryDetails($this->object->id_allegro_category);

            if ($category->leaf) {
                $parameters = $this->categoriesParametersProvider->getParameters($this->object->id_allegro_category);
                $parameter = array_reduce($parameters, function ($carry, $object) use ($parameterId) {
                    return $carry === null && $object->id == $parameterId ? $object : $carry;
                });

                if (!$parameter) {
                    throw new Exception($this->l('Nie znaleziono parametru w wybranej kategorii.'));
                }
            } else {
                throw new Exception($this->l('Wybrana kategoria nie jest najniższego rzędu.'));
            }
        }
        catch (Exception $ex) {
            die(json_encode([
                'success' => false,
                'message' => (string)$ex
            ]));
        }

        $defaultParameterForm = (new ParametersForm())
            ->setController($this)
            ->setCategory($this->object)
            ->setParameters([$parameter])
            ->setFieldsValues()
            ->buildForm();

        $isRangeValue = (isset($parameter->restrictions->range) && $parameter->restrictions->range);
        $hasAmbiguousValue = CategoryParameters::hasAmbiguousValue($parameter);
        $parameterDictionary = false;
        $parameterRangeMapRules = false;
        $parameterAmbiguousMapRules = false;

        if ($parameter->type == 'dictionary') {
            $parameterDictionary = $parameter->dictionary;
            $parameterMapRules = ParametersMapForm::getDictionaryMapRules($parameter->name);
            $parameterAmbiguousMapRules = ($hasAmbiguousValue ? ParametersMapForm::getAmbiguousMapRules() : false);
        } else {
            $parameterMapRules = ParametersMapForm::getTextMapRules();

            if ($isRangeValue) {
                $parameterRangeMapRules = ParametersMapForm::getRangeMapRules();
            }
        }

        if ($hasAmbiguousValue) {
            $ambiguousValue = CategoryParameters::getDictionaryByValueId($parameter->dictionary, $parameter->options->ambiguousValueId);
        }

        $tplModal = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/form/parameter-map-form-modal.tpl');
        $tplModal->assign([
            'parameterId' => $parameter->id,
            'parameterName' => $parameter->name,
            'parameterType' => $parameter->type,
            'parameterUnit' => $parameter->unit,
            'isDictionary' => ($parameter->type == 'dictionary'),
            'isRangeValue' => $isRangeValue,
            'hasAmbiguousValue' => $hasAmbiguousValue,
            'ambiguousValue' => (isset($ambiguousValue) ? $ambiguousValue->value : ''),
            'categoryId' => $this->object->id,
            'categoryPath' => $this->object->path,
            'defaultParameterForm' => $defaultParameterForm,
            'mapValuesForm' => (isset($this->object->fields_mapping[$parameter->id])
                ? ParametersMapForm::getMapValues($parameter, $this->object->fields_mapping[$parameter->id], $this->allegroApi->getAccount()->id_language) : [])
        ]);

        die(json_encode([
            'success' => true,
            'html' => $tplModal->fetch(),
            'parameterDictionary' => $parameterDictionary,
            'parameterMapRules' => $parameterMapRules,
            'parameterRangeMapRules' => $parameterRangeMapRules,
            'parameterAmbiguousMapRules' => $parameterAmbiguousMapRules,
            'parameterAmbiguousValueId' => ($hasAmbiguousValue ? $parameter->options->ambiguousValueId : false),
            'searchCollection' => [
                ParametersMapForm::RULE_DICTIONARY_MANUFACTURER => ManufacturerRepository::getAll(true, true),
                ParametersMapForm::RULE_TEXT_ATTRIBUTE_GROUP => ProductAttributeRepository::getAllAttributeGroups($this->allegroApi->getAccount()->id_language, true),
                ParametersMapForm::RULE_DICTIONARY_ATTRIBUTE_VALUE => ProductAttributeRepository::getAllAttributeValues($this->allegroApi->getAccount()->id_language, true, ': '),
                ParametersMapForm::RULE_TEXT_FEATURE_GROUP => ProductFeatureRepository::getAllFeatureGroups($this->allegroApi->getAccount()->id_language, true),
                ParametersMapForm::RULE_DICTIONARY_FEATURE_VALUE => ProductFeatureRepository::getAllFeatureValues($this->allegroApi->getAccount()->id_language, true, ': ')
            ]
        ]));
    }

    public function ajaxProcessSubmitParameterMap()
    {
        try {
            if (!Validate::isLoadedObject($this->object)) {
                throw new Exception($this->l('Musisz zapisać tą sieżkę kategorii przed mapowaniem parametrów.'));
            }

            foreach (Tools::getValue('category_fields', []) as $id => $value) {
                $this->object->fields_values[(int)$id] = $value;
            }

            foreach (Tools::getValue('category_ambiguous_fields', []) as $id => $value) {
                $this->object->fields_ambiguous_values[(int)$id] = $value;
            }

            $parameterMapId = (int)Tools::getValue('xallegro_parameter_id');
            $this->object->fields_mapping[$parameterMapId] = [];

            foreach (Tools::getValue('xallegro_parameter_map', []) as $map) {
                $this->object->fields_mapping[$parameterMapId][] = $map;
            }

            $this->object->save();

            $category = $this->categoriesProvider->getCategoryDetails($this->object->id_allegro_category);

            if ($category->leaf) {
                $parameters = $this->categoriesParametersProvider->getParameters($this->object->id_allegro_category);
                $parameter = array_reduce($parameters, function ($carry, $object) use ($parameterMapId) {
                    return $carry === null && $object->id == $parameterMapId ? $object : $carry;
                });

                if (!$parameter) {
                    throw new Exception($this->l('Nie znaleziono parametru w wybranej kategorii.'));
                }
            } else {
                throw new Exception($this->l('Wybrana kategoria nie jest najniższego rzędu.'));
            }
        }
        catch (Exception $ex) {
            die(json_encode([
                'success' => false,
                'message' => (string)$ex
            ]));
        }

        $parameterForm = (new ParametersForm())
            ->setController($this)
            ->setCategory($this->object)
            ->setParameters([$parameter])
            ->setFieldsValues()
            ->setMapButton(true)
            ->buildForm();

        die(json_encode([
            'success' => true,
            'message' => $this->l('Zapisano mapowanie parametru'),
            'parameterForm' => $parameterForm
        ]));
    }

    public function ajaxProcessSubmitCategoryFieldsValues()
    {
        try {
            if (!Validate::isLoadedObject($this->object)) {
                throw new Exception($this->l('Musisz zapisać tą sieżkę kategorii przed mapowaniem parametrów.'));
            }

            $this->object->fields_values = [];
            foreach (Tools::getValue('category_fields', []) as $id => $value) {
                $this->object->fields_values[(int)$id] = $value;
            }

            $this->object->fields_ambiguous_values = [];
            foreach (Tools::getValue('category_ambiguous_fields', []) as $id => $value) {
                $this->object->fields_ambiguous_values[(int)$id] = $value;
            }

            $this->object->save();
        }
        catch (Exception $ex) {
            die(json_encode([
                'success' => false,
                'message' => (string)$ex
            ]));
        }

        die(json_encode([
            'success' => true,
            'message' => $this->l('Zapisano zmmiany w parametrach kategorii')
        ]));
    }
}
