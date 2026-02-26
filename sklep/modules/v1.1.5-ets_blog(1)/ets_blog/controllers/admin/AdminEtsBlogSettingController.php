<?php
/**
  * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
 */

if (!defined('_PS_VERSION_'))
    	exit;

/**
 * Class AdminEtsBlogSettingController
 * @property Ets_blog $module
 */
class AdminEtsBlogSettingController extends ModuleAdminController
{
    public function __construct()
    {
       parent::__construct();
       $this->context= Context::getContext();
       $this->bootstrap = true;
    }
    public function init()
    {
        parent::init();
        if(Tools::isSubmit('btnSubmitSettings'))
        {
            $inputs = Ets_blog_defines::getInstance()->getConfigInputs();
            if($this->module->saveSubmit($inputs))
            {
                $this->context->cookie->success_message = $this->l('Settings updated');
            }
        }
    }
    public function renderList()
    {
        $html ='';
        if($this->context->cookie->success_message)
        {
            $html .= $this->module->displayConfirmation($this->context->cookie->success_message);
            $this->context->cookie->success_message ='';
        }
        if($this->module->_errors)
            $html .= $this->module->displayError($this->module->_errors);
        $this->context->smarty->assign(
            array(
                'ets_blog_sidebar' => $this->module->renderSideBar(),
                'ets_blog_content' => $this->renderFormSettings(),
            )
        );
        return $html.$this->module->display(_PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.$this->module->name.'.php', 'admin.tpl');
    }
    public function renderFormSettings()
    {
        $inputs = Ets_blog_defines::getInstance()->getConfigInputs();
        $fields_form = array(
    		'form' => array(
    			'legend' => array(
    				'title' => $this->l('Settings'),
    				'icon' => 'icon-AdminAdmin',
    			),
    			'input' => $inputs,
                'submit' => array(
    				'title' => $this->l('Save'),
    			)
            ),
    	);
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->id = $this->module->id;
        $helper->module = $this->module;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmitSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminEtsBlogSetting', false);
        $helper->token = Tools::getAdminTokenLite('AdminEtsBlogSetting');
        $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $language->id;
        //$helper->override_folder ='/';
        $helper->tpl_vars = array(
            'base_url' => $this->context->shop->getBaseURL(),
			'language' => array(
				'id_lang' => $language->id,
				'iso_code' => $language->iso_code
			),
            'PS_ALLOW_ACCENTED_CHARS_URL', (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'),
            'fields_value' => $this->getFieldsValues($inputs),
            'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
            'link' => $this->context->link,
            'current_currency'=> $this->context->currency,
        );
        $this->fields_form = array();
        return $helper->generateForm(array($fields_form));
    }
    public function getFieldsValues($inputs)
    {
        $languages = Language::getLanguages(false);
        $fields = array();
        $inputs = $inputs;
        if($inputs)
        {
            foreach($inputs as $input)
            {
                if(!isset($input['lang']))
                {
                    $fields[$input['name']] = Tools::getValue($input['name'],Configuration::get($input['name']));
                }
                else
                {
                    foreach($languages as $language)
                    {
                        $fields[$input['name']][$language['id_lang']] = Tools::getValue($input['name'].'_'.$language['id_lang'],Configuration::get($input['name'],$language['id_lang']));
                    }
                }
            }
        }
        return $fields;
    }
}