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
 * Class AdminEtsBlogBackupController
 * @property Ets_blog $module;
 */
class AdminEtsBlogBackupController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
        $this->bootstrap = true;
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
                'ets_blog_content' => $this->renderExportForm(),
            )
        );
        return $html.$this->module->display(_PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.$this->module->name.'.php', 'admin.tpl');
    }
    public function renderExportForm()
    {
        return $this->module->display($this->module->getLocalPath(),'export.tpl');
    }
    public function postProcess()
    {
        if(Tools::isSubmit('submitExportBlog'))
        {
            $import= new Ets_blog_backup();
            $import->generateArchive();
        }
        if(Tools::isSubmit('submitImportBlog'))
        {
            if(!is_dir(_ETS_BLOG_CACHE_DIR_))
                mkdir(_ETS_BLOG_CACHE_DIR_,'0755');
            $import= new Ets_blog_backup();
            $data_import = Tools::getValue('data_import');
            $params = array(
                'data_import'=> $data_import && is_array($data_import) && Ets_blog::validateArray($data_import) ? $data_import : array(),
                'importoverride' => (int)Tools::getValue('importoverride'),
                'keepauthorid' => (int)Tools::getValue('keepauthorid'),
                'keepcommenter' => (int)Tools::getValue('keepcommenter'),
            );
            $this->context->smarty->assign($params);
            $errors = $import->processImport(false,$params);
            if($errors)
                $this->context->controller->errors = $errors;
            else
            {
                $this->context->smarty->assign(
                    array(
                        'import_ok' => true,
                    )
                );
            }
        }
    }
}