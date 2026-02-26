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

if (!defined('_PS_VERSION_')) {
    exit;
}
require_once(dirname(__FILE__) . '/classes/Ets_blog_defines.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_paggination_class.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_obj.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_post.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_category.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_comment.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_reply.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_link_class.php');
require_once(dirname(__FILE__) . '/classes/Ets_blog_backup.php');
if (!defined('_PS_ETS_BLOG_IMG_DIR_')) {
    define('_PS_ETS_BLOG_IMG_DIR_', _PS_IMG_DIR_.'ets_blog/');
}
if (!defined('_PS_ETS_BLOG_IMG_')) {
    define('_PS_ETS_BLOG_IMG_', __PS_BASE_URI__.'img/ets_blog/');
}
if (!defined('_ETS_BLOG_CACHE_DIR_'))
    define('_ETS_BLOG_CACHE_DIR_', _PS_CACHE_DIR_ . 'ets_blog/');
class Ets_blog extends Module
{
    private $prefix = '-';
    public $depthLevel = false;
    public $blogCategoryDropDown;
    public $is17 = false;
    public $is8e = false;
    public $_html = '';
    public $alerts;
    public $_errors = array();
    public $alias;
    public $friendly;
    public $hooks = array(
        'displayBackOfficeHeader',
        'displayHeader',
        'actionObjectLanguageAddAfter',
        'moduleRoutes',
        'displayHome',
        'displayLeftColumn',
        'displayCustomerAccount',
        'displayMyAccountBlock'
    );
    public function __construct()
    {
        $this->name = 'ets_blog';
        $this->tab = 'content_management';
        $this->version = '1.1.5';
        $this->author = 'PrestaHero';
        $this->need_instance = 0;
        $this->bootstrap = true;
        if (version_compare(_PS_VERSION_, '1.7', '>='))
            $this->is17 = true;
        parent::__construct();
        $this->displayName = $this->l('Simple Blog');
        $this->description = $this->l('Simple Blog is a free PrestaShop blog module that helps you easily add and manage blog posts on your PrestaShop website');
$this->refs = 'https://prestahero.com/';
		$this->module_key = 'bfa72e3bb9b9d83e9375b1f095e41d08';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->is17 = version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
        $this->is8e = version_compare(_PS_VERSION_, '8.0.0', '>=') && Module::isEnabled('ps_edition_basic');
        $this->alias = Configuration::get('ETS_BLOG_ALIAS',$this->context->language->id) ? : Configuration::get('ETS_BLOG_ALIAS',Configuration::get('PS_LANG_DEFAULT'));
        $this->friendly = (int)Configuration::get('ETS_BLOG_FRIENDLY_URL') && (int)Configuration::get('PS_REWRITING_SETTINGS') ? true : false;
    }
    public function install()
    {
        return parent::install() && $this->registerHooks() && $this->_installTabs() && Ets_blog_defines::getInstance()->installDb() &&  $this->installDefaultConfig() && $this->refreshCssCustom()&& $this->_copyForderMail();
    }
    public function uninstall()
    {
        return parent::uninstall() && $this->unregisterHooks() && $this->_uninstallTabs() && $this->unInstallDefaultConfig()&& Ets_blog_defines::getInstance()->uninstallDb() && $this->rrmdir(_PS_ETS_BLOG_IMG_DIR_);
    }
    public function rrmdir($dir) {
        $dir = rtrim($dir,'/');
        if ($dir && is_dir($dir)) {
             if($objects = scandir($dir))
             {
                 foreach ($objects as $object) {
                       if ($object != "." && $object != "..") {
                         if (is_dir($dir."/".$object) && !is_link($dir."/".$object))
                           $this->rrmdir($dir."/".$object);
                         else
                           @unlink($dir."/".$object);
                       }
                 }
             }
             rmdir($dir);
       }
       return true;
    }
    public function registerHooks()
    {
        $ok = true;
        foreach($this->hooks as $hook)
        {
            $this->registerHook($hook);       
        }
        return $ok;
    }
    public function unregisterHooks()
    {
        $ok = true;
        foreach($this->hooks as $hook)
        {
            $this->unregisterHook($hook);
        }
        return $ok;
    }
    public function unInstallDefaultConfig()
    {
        $inputs = Ets_blog_defines::getInstance()->getConfigInputs();
        if($inputs)
        {
            foreach($inputs as $input)
            {
                if($input['type']=='html')
                    Continue;
                Configuration::deleteByName($input['name']);
            }
        }
        return true;          
    }
    public function installDefaultConfig()
    {
        if(!is_dir(_PS_ETS_BLOG_IMG_DIR_))
            @mkdir(_PS_ETS_BLOG_IMG_DIR_);
        if(file_exists(dirname(__FILE__).'/index.php'))
            Tools::copy(dirname(__FILE__).'/index.php',_PS_ETS_BLOG_IMG_DIR_.'index.php');
        if(!is_dir(_PS_ETS_BLOG_IMG_DIR_.'post/'))
            @mkdir(_PS_ETS_BLOG_IMG_DIR_.'/post');
        if(file_exists(dirname(__FILE__).'/index.php'))
            Tools::copy(dirname(__FILE__).'/index.php',_PS_ETS_BLOG_IMG_DIR_.'post/index.php');
        if(!is_dir(_PS_ETS_BLOG_IMG_DIR_.'category/'))
            @mkdir(_PS_ETS_BLOG_IMG_DIR_.'category/');
        if(file_exists(dirname(__FILE__).'/index.php'))
            Tools::copy(dirname(__FILE__).'/index.php',_PS_ETS_BLOG_IMG_DIR_.'category/index.php');
        $inputs = Ets_blog_defines::getInstance()->getConfigInputs();
        $languages = Language::getLanguages(false);
        if($inputs)
        {
            foreach($inputs as $input)
            {
                if($input['type']=='html')
                    Continue;
                if(isset($input['default']) && $input['default'])
                {
                    if(isset($input['lang']) && $input['lang'])
                    {
                        $values = array();
                        foreach($languages as $language)
                        {
                            if(isset($input['default_is_file']) && $input['default_is_file'])
                                $values[$language['id_lang']] = file_exists(dirname(__FILE__).'/default/'.$input['default_is_file'].'_'.$language['iso_code'].'.txt') ? Tools::file_get_contents(dirname(__FILE__).'/default/'.$input['default_is_file'].'_'.$language['iso_code'].'.txt') : Tools::file_get_contents(dirname(__FILE__).'/default/'.$input['default_is_file'].'_en.txt');
                            else
                                $values[$language['id_lang']] = isset($input['default_lang']) && $input['default_lang'] ? $this->getTextLang($input['default_lang'],$language,'Ets_blog_defines') : $input['default'];
                        }
                        Configuration::updateGlobalValue($input['name'],$values,isset($input['autoload_rte']) && $input['autoload_rte'] ? true : false);
                    }
                    else
                        Configuration::updateGlobalValue($input['name'],$input['default']);
                }
            }
        }
        $this->installDefaultCategory();
        return true;
    }
    public function _installTabs()
    {
        $languages = Language::getLanguages(false);
        if(!($blogTabId = Tab::getIdFromClassName('AdminEtsBlog')))
        {
            $tab = new Tab();
            $tab->class_name = 'AdminEtsBlog';
            $tab->module = $this->name;
            $tab->id_parent = 0;
            foreach($languages as $lang){
                $tab->name[$lang['id_lang']] = ($text_lang = $this->getTextLang('Blog',$lang)) ? $text_lang : $this->l('Blog');
            }
            $tab->save();
            $blogTabId = $tab->id;
        }
        if($blogTabId)
        {
            $blog_defines = new Ets_blog_defines();
            foreach($blog_defines->getsubTabs() as $tabArg)
            {
                if(!Tab::getIdFromClassName($tabArg['class_name']))
                {
                    $tab = new Tab();
                    $tab->class_name = $tabArg['class_name'];
                    $tab->module = $this->name;
                    $tab->id_parent = $blogTabId; 
                    $tab->icon=$tabArg['icon'];             
                    foreach($languages as $lang){
                            $tab->name[$lang['id_lang']] = ($text_lang = $this->getTextLang($tabArg['tabname'],$lang,'ets_blog_defines')) ? $text_lang : $tabArg['tab_name'];
                    }
                    $tab->save();
                }
            }                
        }            
        return true;
    }
    private function _uninstallTabs()
    {
        $blog_defines = new Ets_blog_defines();        
        foreach($blog_defines->getsubTabs() as $tab)
        {
            if($tabId = Tab::getIdFromClassName($tab['class_name']))
            {
                $tab = new Tab($tabId);
                if($tab)
                    $tab->delete();
            }                
        }
        if($tabId = Tab::getIdFromClassName('AdminEtsBlog'))
        {
            $tab = new Tab($tabId);
            if($tab)
                $tab->delete();
        }
        return true;
    }
    public function _copyForderMail()
    {
        $languages = Language::getLanguages(false);
        $temp_dir_ltr = dirname(__FILE__) . '/mails/en';
        if ($languages && is_array($languages))
        {
            if (!@file_exists($temp_dir_ltr))
                return true;
            foreach ($languages as $language)
            {
                if(isset($language['iso_code']) && $language['iso_code'] != 'en')
                {
                     if (($new_dir = dirname(__FILE__) . '/mails/'. $language['iso_code']))
                     {
                        $this->recurseCopy($temp_dir_ltr, $new_dir);
                     }
                }
            }
        }
        return true;
    }
    public function recurseCopy($src, $dst)
    {
        if(!@file_exists($src))
            return false;
        $dir = opendir($src);
        if (!@is_dir($dst))
            @mkdir($dst);
        while(false !== ($file = readdir($dir)))
        {
            if (( $file != '.' ) && ($file != '..' ))
            {
                if (is_dir($src . '/' . $file)) {
                    $this->recurseCopy($src . '/' . $file,$dst . '/' . $file);
                }
                elseif (!@file_exists($dst . '/' . $file))
                {
                    @copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
    public function getTextLang($text, $lang,$file_name='')
    {
        if(is_array($lang))
            $iso_code = $lang['iso_code'];
        elseif(is_object($lang))
            $iso_code = $lang->iso_code;
        else
        {
            $language = new Language($lang);
            $iso_code = $language->iso_code;
        }
		$modulePath = rtrim(_PS_MODULE_DIR_, '/').'/'.$this->name;
        $fileTransDir = $modulePath.'/translations/'.$iso_code.'.'.'php';
        if(!@file_exists($fileTransDir)){
            return $text;
        }
        $fileContent = Tools::file_get_contents($fileTransDir);
        $text_tras = preg_replace("/\\\*'/", "\'", $text);
        $strMd5 = md5($text_tras);
        $keyMd5 = '<{' . $this->name . '}prestashop>' . ($file_name ? : $this->name) . '_' . $strMd5;
        preg_match('/(\$_MODULE\[\'' . preg_quote($keyMd5) . '\'\]\s*=\s*\')(.*)(\';)/', $fileContent, $matches);
        if($matches && isset($matches[2])){
           return  $matches[2];
        }
        return $text;
    }
    public function getContent()
	{
	   Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogPost'));
    }
    public function renderSidebar()
    {
        $list = array(
            array(
                'label' => $this->l('Posts'),
                'url' => $this->context->link->getAdminLink('AdminEtsBlogPost'),
                'id' => 'ets_tab_post',
                'controller'=>'AdminEtsBlogPost',
                'icon' => 'icon-AdminPriceRule'
            ),
            array(
                'label' => $this->l('Categories'),
                'url' => $this->context->link->getAdminLink('AdminEtsBlogCategory'),
                'id' => 'ets_tab_category',
                'controller'=>'AdminEtsBlogCategory',
                'icon' => 'icon-AdminCatalog'
            ),
            array(
                'label' => $this->l('Comments'),
                'url' => $this->context->link->getAdminLink('AdminEtsBlogComment'),
                'id' => 'ets_tab_comment',
                'controller'=>'AdminEtsBlogComment',
                'icon' => 'icon-comments',
                'total_result' =>Ets_blog_comment::countCommentsWithFilter(' AND bc.viewed=0'),
            ),
            array(
                'label' => $this->l('Global settings'),
                'url' => $this->context->link->getAdminLink('AdminEtsBlogSetting'),
                'id' => 'ets_tab_config',
                'icon' => 'icon-AdminAdmin',
                'controller'=>'AdminEtsBlogSetting',
            ),
            array(
                'label' => $this->l('Backup'),
                'url' => $this->context->link->getAdminLink('AdminEtsBlogBackup'),
                'id' => 'ets_tab_backup',
                'icon' => 'icon-backup',
                'controller'=>'AdminEtsBlogBackup',
            ),
        );
        $controller = Tools::getValue('controller');
        $this->context->smarty->assign(
    		array(
    			'link' => $this->context->link,
    			'list' => $list,
                'controller' => Validate::isControllerName($controller) ? $controller:'',			
    		)
    	);
        return $this->display(__FILE__, 'sidebar.tpl');
     }
    public function displayText($content=null,$tag=null,$class=null,$id=null,$href=null,$blank=false,$src = null,$name = null,$value = null,$type = null,$data_id_product = null,$rel = null,$attr_datas=null)
    {
        $this->smarty->assign(
            array(
                'content' =>$content,
                'tag' => $tag,
                'tag_class'=> $class,
                'tag_id' => $id,
                'href' => $href,
                'blank' => $blank,
                'src' => $src,
                'attr_name' => $name,
                'value' => $value,
                'type' => $type,
                'data_id_product' => $data_id_product,
                'attr_datas' => $attr_datas,
                'rel' => $rel,
            )
        );
        return $this->display(__FILE__,'html.tpl');
    }
    public function getUrlExtra($field_list)
    {
        $params = '';
        $sort = Tools::strtolower(Tools::getValue('sort'));
        $sort_type = Tools::strtolower(Tools::getValue('sort_type','desc'));
        if(!in_array($sort_type,array('desc','asc')))
            $sort_type = 'desc';
        if($sort && isset($field_list[trim($sort)]))
        {
            $params .= '&sort='.trim($sort).'&sort_type='.(trim($sort_type) =='asc' ? 'asc' : 'desc');
        }
        if($field_list)
        {
            foreach($field_list as $key => $val)
            {
                if(($value = Tools::getValue($key))!='' && Validate::isCleanHtml($value))
                {
                    $params .= '&'.$key.'='.urlencode($value);
                }
            }
            unset($val);
        }
        return $params;
    }
    public function getFilterParams($field_list)
    {
        $params = '';        
        if($field_list)
        {
            foreach($field_list as $key => $val)
            {
                if(($value = Tools::getValue($key))!='' && Validate::isCleanHtml($value))
                {
                    $params .= '&'.$key.'='.urlencode($value);
                }
            }
            unset($val);
        }
        return $params;
    }
    public function renderList($listData)
    { 
        if(isset($listData['fields_list']) && $listData['fields_list'])
        {
            foreach($listData['fields_list'] as $key => &$val)
            {
                $value_key = (string)Tools::getValue($key);
                $value_key_max = (string)Tools::getValue($key.'_max');
                $value_key_min = (string)Tools::getValue($key.'_min');
                if(isset($val['filter']) && $val['filter'] && ($val['type']=='int' || $val['type']=='date'))
                {
                    if(Tools::isSubmit('ets_blog_submit_'.$listData['name']))
                    {
                        $val['active']['max'] =  trim($value_key_max);   
                        $val['active']['min'] =  trim($value_key_min); 
                    }
                    else
                    {
                        $val['active']['max']='';
                        $val['active']['min']='';
                    }  
                }  
                elseif(!Tools::isSubmit('del') && Tools::isSubmit('ets_blog_submit_'.$listData['name']))               
                    $val['active'] = trim($value_key);
                else
                    $val['active']='';
            }
        }  
        if(!isset($listData['class']))
            $listData['class']='';  
        $this->smarty->assign($listData);
        return $this->display(__FILE__, 'list_helper.tpl');
    }
    public function displayBlogCategoryTre($blockCategTree,$selected_categories,$name='',$disabled_categories=array())
    {
        if($id_post = (int)Tools::getValue('id_post'))
        {
            $post = new Ets_blog_post($id_post);
            $id_category_default = (int)Tools::getValue('id_category_default',$post->id_category_default);
        }
        else
            $id_category_default= (int)Tools::getValue('id_category_default');
        $this->context->smarty->assign(
            array(
                'blockCategTree'=> $blockCategTree,
                'branche_tpl_path_input'=> _PS_MODULE_DIR_.'ets_blog/views/templates/hook/category-tree-blog.tpl',
                'selected_categories'=>$selected_categories,
                'disabled_categories' => $disabled_categories,
                'id_category_default' => (int)Tools::getValue('main_category',$id_category_default) ,
                'name'=>$name ? $name :'blog_categories',
            )
        );
        return $this->display(__FILE__, 'categories_blog.tpl');
    }
    public function hookActionObjectLanguageAddAfter()
    {
       Ets_blog_defines::duplicateRowsFromDefaultShopLang(_DB_PREFIX_.'ets_blog_category_lang',$this->context->shop->id,'id_category');
       Ets_blog_defines::duplicateRowsFromDefaultShopLang(_DB_PREFIX_.'ets_blog_post_lang',$this->context->shop->id,'id_post');
       $this->_copyForderMail();
    }
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/admin_all.css');
        $controller = Tools::getValue('controller');
        if(in_array($controller,array('AdminEtsBlogPost','AdminEtsBlogCategory','AdminEtsBlogSetting','AdminEtsBlogComment','AdminEtsBlogBackup')))
        {
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
            if ($this->is8e) {
                $this->context->controller->addCSS($this->_path.'views/css/admin8e.css');
            }
            $this->context->controller->addJquery();
            $this->context->controller->addJqueryUI('ui.widget');
            $this->context->controller->addJqueryPlugin('tagify');
            $this->context->controller->addJqueryUI('ui.sortable');
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
        }
        $this->context->smarty->assign(
            array(
                'ets_blog_link_ajax_comment' => $this->context->link->getAdminLink('AdminEtsBlogComment'),
            )
        );
        return $this->display(__FILE__,'admin_header.tpl');
    }
    public static function validateArray($array,$validate='isCleanHtml')
    {
        if($array)
        {
            if(!is_array($array))
            return false;
            if(method_exists('Validate',$validate))
            {
                if($array && is_array($array))
                {
                    $ok= true;
                    foreach($array as $val)
                    {
                        if(!is_array($val))
                        {
                            if($val && !Validate::$validate($val))
                            {
                                $ok= false;
                                break;
                            }
                        }
                        else
                            $ok = self::validateArray($val,$validate);
                    }
                    return $ok;
                }
            }
        }
        return true;
    }
    public function getBlogCategoriesDropdown($blogcategories, &$depth_level = -1,$selected_blog_category=0)
   {        
        if($blogcategories)
        {
            $depth_level++;
            foreach($blogcategories as $category)
            {
                if((!$this->depthLevel || $this->depthLevel && (int)$depth_level <= $this->depthLevel))
                {
                    $levelSeparator = '';
                    if($depth_level >= 1)
                    {
                        for($i = 0; $i <= $depth_level-1; $i++)
                        {
                            $levelSeparator .= $this->prefix;
                        }
                    }       
                    if($category['id_category'] >=0)
                        $this->blogCategoryDropDown .= $this->displayBlogOption((int)$selected_blog_category,(int)$category['id_category'],$depth_level,$levelSeparator,$category['title']);
                    if(isset($category['children']) && $category['children'])
                    {                        
                        $this->getBlogCategoriesDropdown($category['children'], $depth_level,$selected_blog_category);
                    }   
                }                                 
            } 
            $depth_level--;           
        }
    }
    public function displayBlogOption($selected_blog_category,$id_category,$depth_level,$levelSeparator,$title)
    {
        $this->context->smarty->assign(array(
            'selected_blog_category' => $selected_blog_category,
            'id_category' => $id_category,
            'depth_level' => $depth_level,
            'levelSeparator' => $levelSeparator,
            'title' => Tools::strlen($title) > 52 ? Tools::substr($title,0,52).'...':$title,
        ));
        return $this->display(__FILE__,'blogoption.tpl');
    }
    public function _postValidation($inputs)
    {
        $languages = Language::getLanguages(false);
        $id_lang_default = Configuration::get('PS_LANG_DEFAULT');
        foreach($inputs as $input)
        {
            if($input['type']=='html')
                continue;
            if(isset($input['lang']) && $input['lang'])
            {
                if(isset($input['required']) && $input['required'])
                {
                    $val_default = Tools::getValue($input['name'].'_'.$id_lang_default);
                    if(!$val_default)
                    {
                        $this->_errors[] = sprintf($this->l('%s is required'),$input['label']);
                    }
                    elseif($val_default && isset($input['validate']) && ($validate = $input['validate']) && method_exists('Validate',$validate) && !Validate::{$validate}($val_default,true))
                        $this->_errors[] = sprintf($this->l('%s is not valid'),$input['label']);
                    elseif($val_default && !Validate::isCleanHtml($val_default,true))
                        $this->_errors[] = sprintf($this->l('%s is not valid'),$input['label']);
                    else
                    {
                        foreach($languages as $language)
                        {
                            if(($value = Tools::getValue($input['name'].'_'.$language['id_lang'])) && isset($input['validate']) && ($validate = $input['validate']) && method_exists('Validate',$validate)  && !Validate::{$validate}($value,true))
                                $this->_errors[] = sprintf($this->l('%s is not valid in %s'),$input['label'],$language['iso_code']);
                            elseif($value && !Validate::isCleanHtml($value,true))
                                $this->_errors[] = sprintf($this->l('%s is not valid in %s'),$input['label'],$language['iso_code']);
                        }
                    }
                }
                else
                {
                    foreach($languages as $language)
                    {
                        if(($value = Tools::getValue($input['name'].'_'.$language['id_lang'])) && isset($input['validate']) && ($validate = $input['validate']) && method_exists('Validate',$validate)  && !Validate::{$validate}($value,true))
                            $this->_errors[] = sprintf($this->l('%s is not valid in %s'),$input['label'],$language['iso_code']);
                        elseif($value && !Validate::isCleanHtml($value,true))
                            $this->_errors[] = sprintf($this->l('%s is not valid in %s'),$input['label'],$language['iso_code']);
                    }
                }
            }
            else
            {
                if($input['type']=='file')
                {
                    
                    if(isset($input['required']) && $input['required'] && (!isset($_FILES[$input['name']]) || !isset($_FILES[$input['name']]['name']) ||!$_FILES[$input['name']]['name']))
                    {
                        $this->_errors[] = sprintf($this->l('%s is required'),$input['label']);
                    }
                    elseif(isset($_FILES[$input['name']]) && isset($_FILES[$input['name']]['name'])  && $_FILES[$input['name']]['name'])
                    {
                        $file_name = $_FILES[$input['name']]['name'];
                        $file_size = $_FILES[$input['name']]['size'];
                        $max_file_size = Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')*1024*1024;
                        $type = Tools::strtolower(Tools::substr(strrchr($file_name, '.'), 1));
                        if(isset($input['is_image']) && $input['is_image'])
                            $file_types = array('jpg', 'png', 'gif', 'jpeg');
                        else
                            $file_types = array('jpg', 'png', 'gif', 'jpeg','zip','doc','docx');
                        if(!in_array($type,$file_types))
                            $this->_errors[] = sprintf($this->l('The file name "%s" is not in the correct format, accepted formats: %s'),$file_name,'.'.trim(implode(', .',$file_types),', .'));
                        $max_file_size = $max_file_size ? : Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')*1024*1024;
                        if($file_size > $max_file_size)
                            $this->_errors[] = sprintf($this->l('The file name "%s" is too large. Limit: %s'),$file_name,Tools::ps_round($max_file_size/1048576,2).'Mb');
                    }
                }
                else
                {
                    $val = Tools::getValue($input['name']);
                    if($input['type']!='checkbox')
                    {
                       
                        if($val===''&& isset($input['required']) && $input['required'])
                        {
                            $this->_errors[] = sprintf($this->l('%s is required'),$input['label']);
                        }
                        if($val!=='' && isset($input['validate']) && ($validate = $input['validate']) && $validate=='isColor' && !self::isColor($val))
                        {
                            $this->_errors[] = sprintf($this->l('%s is not valid'),$input['label']);
                        }
                        elseif($val!=='' && isset($input['validate']) && ($validate = $input['validate']) && method_exists('Validate',$validate) && !Validate::{$validate}($val))
                        {
                            $this->_errors[] = sprintf($this->l('%s is not valid'),$input['label']);
                        }
                        elseif($val!==''&& !Validate::isCleanHtml($val))
                            $this->_errors[] = sprintf($this->l('%s is not valid'),$input['label']);
                    }
                    else
                    {
                        if(!$val&& isset($input['required']) && $input['required'] )
                        {
                            $this->_errors[] = sprintf($this->l('%s is required'),$input['label']);
                        }
                        elseif($val && !self::validateArray($val,isset($input['validate']) ? $input['validate']:''))
                            $this->_errors[] = sprintf($this->l('%s is not valid'),$input['label']);
                    }
                }
                
            }
        }
        if($captcha_type = (string)Tools::getValue('ETS_BLOG_CAPTCHA_TYPE'))
        {
            if($captcha_type=='google')
            {
                $ETS_BLOG_CAPTCHA_SITE_KEY = Tools::getValue('ETS_BLOG_CAPTCHA_SITE_KEY');
                if(!$ETS_BLOG_CAPTCHA_SITE_KEY)
                    $this->_errors[] = $this->l('Site key is required');
                elseif(!Validate::isCleanHtml($ETS_BLOG_CAPTCHA_SITE_KEY))
                    $this->_errors[] = $this->l('Site key is not valid');
                $ETS_BLOG_CAPTCHA_SECRET_KEY = Tools::getValue('ETS_BLOG_CAPTCHA_SECRET_KEY');
                if(!$ETS_BLOG_CAPTCHA_SECRET_KEY)
                    $this->_errors[] = $this->l('Secret key is required');
                elseif(!Validate::isCleanHtml($ETS_BLOG_CAPTCHA_SECRET_KEY))
                    $this->_errors[] = $this->l('Secret key is not valid'); 
            }
            elseif($captcha_type=='google3')
            {
                $ETS_BLOG_CAPTCHA_SITE_KEY = Tools::getValue('ETS_BLOG_CAPTCHA_SITE_KEY3');
                if(!$ETS_BLOG_CAPTCHA_SITE_KEY)
                    $this->_errors[] = $this->l('Site key is required');
                elseif(!Validate::isCleanHtml($ETS_BLOG_CAPTCHA_SITE_KEY))
                    $this->_errors[] = $this->l('Site key is not valid');
                $ETS_BLOG_CAPTCHA_SECRET_KEY = Tools::getValue('ETS_BLOG_CAPTCHA_SECRET_KEY3');
                if(!$ETS_BLOG_CAPTCHA_SECRET_KEY)
                    $this->_errors[] = $this->l('Secret key is required');
                elseif(!Validate::isCleanHtml($ETS_BLOG_CAPTCHA_SECRET_KEY))
                    $this->_errors[] = $this->l('Secret key is not valid'); 
            }
        }
    }
    public function saveSubmit($inputs)
    {
        $this->_postValidation($inputs);
        if (!count($this->_errors)) {
            $languages = Language::getLanguages(false);
            $id_lang_default = Configuration::get('PS_LANG_DEFAULT');
            if($inputs)
            {
                foreach($inputs as $input)
                {
                    if($input['type']!='html')
                    {
                        if(isset($input['lang']) && $input['lang'])
                        {
                            $values = array();
                            foreach($languages as $language)
                            {
                                $value_default = Tools::getValue($input['name'].'_'.$id_lang_default);
                                $value = Tools::getValue($input['name'].'_'.$language['id_lang']);
                                $values[$language['id_lang']] = ($value && Validate::isCleanHtml($value,true)) || !isset($input['required']) ? $value : (Validate::isCleanHtml($value_default,true) ? $value_default :'');
                            }
                            Configuration::updateValue($input['name'],$values,isset($input['autoload_rte']) && $input['autoload_rte'] ? true : false);
                        }
                        else
                        {
                            
                            if($input['type']=='checkbox')
                            {
                                $val = Tools::getValue($input['name'],array());
                                if(is_array($val) && self::validateArray($val))
                                {
                                    Configuration::updateValue($input['name'],implode(',',$val));
                                }
                            }
                            else
                            {
                                $val = Tools::getValue($input['name']);
                                if(Validate::isCleanHtml($val))
                                    Configuration::updateValue($input['name'],$val);
                            }
                           
                        }
                    }
                    
                }
            }
            if(!$this->_errors)
            {
                $this->refreshCssCustom();
                return true;
            }
        }
    }
    public function getLink($controller = 'blog', $params = array(),$id_lang=0)
    {
        $context = Context::getContext();      
        $id_lang =  $id_lang ? $id_lang : $context->language->id;
        $alias = $this->alias;
        $friendly = $this->friendly;
        $blogLink = new Ets_blog_link_class();
        $subfix = '';
        $page = isset($params['page']) && $params['page'] ? $params['page'] : '';
        if(trim($page)!='')
        {
            $page = $page.'/';
        }
        else
            $page='';        
        if($friendly && $alias)
        {    
            $url = $blogLink->getBaseLinkFriendly(null, null).$blogLink->getLangLinkFriendly($id_lang, null, null).$alias.'/';
            if($controller=='category')
            {
                $url .= 'categories'.($page ? '/'.rtrim($page,'/') : '');
                return $url;
            }
            elseif($controller=='comment')
            {
                $url .= 'comments'.($page ? '/'.rtrim($page,'/') : '');
                return $url;
            }
            elseif($controller=='blog')
            {
                if(isset($params['edit_comment']) && (int)$params['edit_comment'] && isset($params['id_post']) && $params['id_post'] && $postAlias = $this->getPostAlias((int)$params['id_post'],$id_lang))
                {
                    $url .= 'post/'.(int)$params['id_post'].'-'.(int)$params['edit_comment'].'-'.$postAlias.$subfix;
                }
                elseif( isset($params['all_comment']) && $params['all_comment'] &&  isset($params['id_post']) && $postAlias = $this->getPostAlias((int)$params['id_post'],$id_lang))
                {
                    $url .= 'post/allcomments/'.(int)$params['id_post'].'-'.$postAlias.$subfix;
                }
                elseif(isset($params['id_post']) && $postAlias = $this->getPostAlias((int)$params['id_post'],$id_lang))
                {
                    $url .= 'post/'.$params['id_post'].'-'.$postAlias.$subfix;
                }
                elseif(isset($params['id_category']) && $categoryAlias = $this->getCategoryAlias((int)$params['id_category'],$id_lang))
                {
                    $url .= 'category'.($page ? '/'.rtrim($page) : '/').$params['id_category'].'-'.$categoryAlias.$subfix;
                }
                elseif(isset($params['search']))
                {
                    $url .= $page.'search/'.(string)$params['search'];
                }
                elseif(isset($params['latest']))
                {
                    $url .= 'latest'.($page ? '/'.rtrim($page,'/') : '');
                }
                elseif(isset($params['month']) && isset($params['year']))
                {
                    $url .= 'month/'.$params['month'].'/'.$params['year'].($page ? '/'.rtrim($page,'/') : '');
                }
                elseif(isset($params['year']))
                {
                    $url .= 'year/'.$params['year'].($page ? '/'.rtrim($page,'/') : '');
                }
                else
                {
                    if($page)
                        $url .= trim($page,'/');
                    else
                        $url = rtrim($url,'/');
                }
                return $url;            
            }            
        }
        return $this->context->link->getModuleLink($this->name,$controller,$params,null,$id_lang);
    }
    public function hookModuleRoutes()
    {
        $subfix = '';
        $blogAlias = Configuration::get('ETS_BLOG_ALIAS',$this->context->language->id) ? : Configuration::get('ETS_BLOG_ALIAS',Configuration::get('PS_LANG_DEFAULT'));
        if(!$blogAlias)
            return array();
        $routes = array(
            'etsblogmainpage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias,
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'ybcblogfeaturedpostspage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias . '/{page}',
                'keywords' => array(
                    'page' => array('regexp' => '[0-9]+', 'param' => 'page'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogpost2' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/post/{id_post}-{url_alias}'.$subfix,
                'keywords' => array(
                    'url_alias'       =>   array('regexp' => '[_a-zA-Z0-9-\pL]+','param' => 'url_alias'),
                    'id_post' =>    array('regexp' => '[0-9]+', 'param' => 'id_post'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),         
            'etsblogpost' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/post/{post_url_alias}'.$subfix,
                'keywords' => array(
                    'post_url_alias'       =>   array('regexp' => '[_a-zA-Z0-9-\pL]+','param' => 'post_url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogcategorypostpage2' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/category/{page}/{id_category}-{url_alias}'.$subfix,
                'keywords' => array(
                    'id_category' =>    array('regexp' => '[0-9]+', 'param' => 'id_category'),
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                    'url_alias'       =>   array('regexp' => '[_a-zA-Z0-9-\pL]+','param' => 'url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogcategorypostpage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/category/{page}/{category_url_alias}'.$subfix,
                'keywords' => array(
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                    'category_url_alias'       =>   array('regexp' => '[_a-zA-Z0-9-\pL]+','param' => 'category_url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogcategorypost2' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/category/{id_category}-{url_alias}'.$subfix,
                'keywords' => array(
                    'id_category' =>    array('regexp' => '[0-9]+', 'param' => 'id_category'),
                    'url_alias'       =>   array('regexp' => '[_a-zA-Z0-9-\pL]+','param' => 'url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogcategorypost' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/category/{category_url_alias}'.$subfix,
                'keywords' => array(
                    'category_url_alias'       =>   array('regexp' => '[_a-zA-Z0-9-\pL]+','param' => 'category_url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogcategorylatestpage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/latest/{page}',
                'keywords' => array(                       
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                    'latest' => 'true'
                ),
            ),
            'etsblogcategorylatest' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/latest',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                    'latest' => 'true'
                ),
            ),
            'etsblogcategoriespage' => array(
                'controller' => 'category',
                'rule' => $blogAlias.'/categories/{page}',
                'keywords' => array(
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogcategories' => array(
                'controller' => 'category',
                'rule' => $blogAlias.'/categories',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogauthorpost' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/author/{id_author}-{author_name}',
                'keywords' => array(
                    'id_author' =>    array('regexp' => '[0-9]+', 'param' => 'id_author'),
                    'author_name'       =>   array('regexp' => '(.)+','param' => 'author_name'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogauthorpostpage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/author/{page}/{id_author}-{author_name}',
                'keywords' => array(
                    'id_author' =>    array('regexp' => '[0-9]+', 'param' => 'id_author'),
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                    'author_name'       =>   array('regexp' => '(.)+','param' => 'author_name'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogpostinyearpage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/year/{year}/{page}',
                'keywords' => array(
                    'year'       =>   array('regexp' => '[0-9]+','param' => 'year'),
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogpostinyear' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/year/{year}',
                'keywords' => array(
                    'year'       =>   array('regexp' => '[0-9]+','param' => 'year'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogpostinmonthpage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/month/{month}/{year}/{page}',
                'keywords' => array(
                    'month'       =>   array('regexp' => '[0-9]+','param' => 'month'),
                    'year'       =>   array('regexp' => '[0-9]+','param' => 'year'),
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogpostinmonth' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/month/{month}/{year}',
                'keywords' => array(
                    'month'       =>   array('regexp' => '[0-9]+','param' => 'month'),
                    'year'       =>   array('regexp' => '[0-9]+','param' => 'year'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogpostallcomments' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/post/allcomments/{id_post}-{url_alias}'.$subfix,
                'keywords' => array(
                    'id_post' =>    array('regexp' => '[0-9]+', 'param' => 'id_post'),
                    'url_alias'       =>   array('regexp' => '[_a-zA-Z0-9-\pL]+','param' => 'url_alias'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                    'all_comment'=>1,
                ),
            ),
            'etsblogsearchpage' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/{page}/search/{search}',
                'keywords' => array(
                    'search'       =>   array('regexp' => '.+','param' => 'search'),
                    'page' =>    array('regexp' => '[0-9]+', 'param' => 'page'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),
            'etsblogsearch' => array(
                'controller' => 'blog',
                'rule' => $blogAlias.'/search/{search}',
                'keywords' => array(
                    'search'       =>   array('regexp' => '.+','param' => 'search'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ets_blog',
                ),
            ),  
        );
        return $routes;
    }
    public function hookDisplayCustomerAccount($params)
    {
        return $this->display(__FILE__, 'my-account.tpl');
    }
    public function hookDisplayMyAccountBlock($params)
    {
    	return $this->hookDisplayCustomerAccount($params);
    }
    private function getPostAlias($id_post,$id_lang=0)
    {
        if(!$id_lang)
            $id_lang = $this->context->language->id;
        $post = new Ets_blog_post($id_post,$id_lang);
        return $post->url_alias;
    }
    private function getCategoryAlias($id_category,$id_lang=0)
    {
        if(!$id_lang)
            $id_lang = $this->context->language->id;
        $category = new Ets_blog_category($id_category,$id_lang);
        return $category->url_alias;
    }
    public function hookDisplayHeader()
    {
        $controller = Tools::getValue('controller'); 
        $module = Tools::getValue('module');
        $this->context->controller->addJS($this->_path.'views/js/owl.carousel.js');
        $this->context->controller->addCSS($this->_path.'views/css/owl.carousel.css');
        $this->context->controller->addCSS($this->_path.'views/css/owl.theme.css');
        $this->context->controller->addCSS($this->_path.'views/css/owl.transitions.css');
        if($controller=='category' || $controller=='myaccount' || $module== $this->name){
            $this->context->controller->addJS($this->_path.'views/js/blog.js'); 
            $this->context->controller->addCSS($this->_path.'views/css/blog.css');
        }
        if(!file_exists(dirname(__FILE__).'/views/css/custom.css'))
            $this->refreshCssCustom();
        $this->context->controller->addCSS($this->_path.'views/css/custom.css');            
        $this->context->controller->addJS($this->_path.'views/js/home_blog.js');
        $this->context->controller->addCSS($this->_path.'views/css/blog_home.css');
        if(($id_post = (int)Tools::getValue('id_post')) && $module==$this->name && $controller=='blog')
        {
            $post = Ets_blog_post::getPostById($id_post);
            if($post)
            {
                $post['img_name'] = isset($post['image']) ? $post['image'] : '';
                if($post['image'])
                    $post['image'] = $this->context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/'.$post['image']);
                if($post['thumb'])
                    $post['thumb'] = $this->context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/thumb/'.$post['thumb']);
                $post['link'] = $this->getLink('blog',array('id_post'=>$post['id_post']));
                $this->context->smarty->assign(
                    array(
                        'ets_blog_post_header'=>$post,
                    )
                );
            }
            return $this->display(__FILE__,'head.tpl');
        }

    }
    public function hookDisplayHome()
    {
        if(Configuration::get('ETS_BLOG_DISPLAY_HOME_PAGE'))
        {
            return $this->displayBlogNewsBlock(array('page'=>'home'));
        }
    }

    public function hookDisplayWrapperBottom($params)
    {
        return $this->displayBlogNewsBlock(array('page'=>'home'));
    }
    public function hookDisplayLeftColumn()
{
    // Tutaj podajemy nazwę naszego nowego pliku sidebar
    $html = $this->displayBlogNewsBlock(array(), 'latest_posts_block_sidebar.tpl'); 
    $html .= $this->displayBlogCategoriesBlock();
    $html .= $this->displayBlogSearchBlock();
    $html .= $this->displayblogArchivesBlock();
    return $html;
}
    public function displayBlogNewsBlock($params = array(), $template = 'latest_posts_block.tpl')
{
    if (isset($params['page']) && $params['page'] === 'home') {
        $limit = 5;
    } else {
        $limit = 5; // Możesz tu ustawić 3 jeśli wolisz mniej w sidebarze
    }

    $posts = Ets_blog_post::getPostsWithFilter(' AND p.enabled=1','p.date_add DESC,', 0, $limit);
    
    if($posts)
    {
        foreach($posts as $key => &$post)
        {
            $post['link'] = $this->getLink('blog',array('id_post' => $post['id_post']));
            if($post['thumb'])
                $post['thumb'] = $this->context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/'.$post['thumb']);
            $post['comments_num'] =0;    
        }
        unset($key); 
    }
    
    if(isset($this->context->language->is_rtl) && $this->context->language->is_rtl)
        $rtl = true;
    else
        $rtl = false;

    $this->smarty->assign(
        array(
            'posts' => $posts,
            'ETS_BLOG_RTL_CLASS' => $rtl ? 'ets_blog_rtl_mode' : 'ets_blog_ltr_mode',
            'ets_blog_text_Readmore'=> Configuration::get('ETS_BLOG_TEXT_READMORE',$this->context->language->id),
            'latest_link' => $this->getLink('blog',array('latest' => 'true')),
            'allowComments' => true,
            'show_views' => false,
            'allow_like' => false,
            'sidebar_post_type' => 'carousel',
            'date_format' => $this->context->language->date_format_lite,
            'hook' => 'homeblog',
            'page' => isset($params['page']) && $params['page'] ? $params['page'] : false,
            'blockCategTree' => Ets_blog_category::getInstance()->getBlogCategoriesTree(0),
        )
    );
    // TU JEST ZMIANA - UŻYCIE ZMIENNEJ $template
    return $this->display(__FILE__, $template);
}
    public function displayBlogCategoriesBlock()
    {
        if(isset($this->context->language->is_rtl) && $this->context->language->is_rtl)
            $rtl = true;
         else
            $rtl = false; 
        $this->smarty->assign(
            array(
                'active' => 0,
                'link_view_all'=> $this->getLink('category'),
                'ETS_BLOG_RTL_CLASS' => $rtl ? 'ets_blog_rtl_mode' : 'ets_blog_ltr_mode',
            )
        );
        $blockCategTree = Ets_blog_category::getInstance()->getBlogCategoriesTree(0);
        $this->context->smarty->assign('blockCategTree', $blockCategTree);
        $this->smarty->assign('branche_tpl_path', _PS_MODULE_DIR_.'ets_blog/views/templates/hook/category-tree-branch.tpl');
        return $this->display(__FILE__, 'categories_block.tpl');
    }
    public function displayBlogSearchBlock()
    {
        $search = trim(Tools::getValue('search'));
        $this->smarty->assign(
            array(
                'action' => $this->getLink('blog'),
                'ETS_BLOG_RTL_CLASS' => isset($this->context->language->is_rtl) && $this->context->language->is_rtl ? 'ets_blog_rtl_mode' : 'ets_blog_ltr_mode',
                'search' => Validate::isCleanHtml($search) ? urldecode($search):'',
                'id_lang' => $this->context->language->id
            )
        );
        return $this->display(__FILE__, 'search_block.tpl');
    }
    public function getMonthName($month)
    {
        switch ($month) {
            case 1:
                return $this->l('January');
            case 2:
                return $this->l('February');
            case 3:
                return $this->l('March');
            case 4:
                return $this->l('April');
            case 5:
                return $this->l('May');
            case 6:
                return $this->l('June');
            case 7:
                return $this->l('July');
            case 8:
                return $this->l('August');
            case 9:
                return $this->l('September');
            case 10:
                return $this->l('October');
            case 11:
                return $this->l('November');
            case 12:
                return $this->l('December');
        }
    }
    public function displayblogArchivesBlock()
    {
        $years = Ets_blog_post::getYearsHasBlogPost();
        if($years)
        {
            foreach($years as &$year)
            {
                
                $year['months'] = Ets_blog_post::getMonthsHasBlogPost($year['year_add']);
                $year['link'] = $this->getLink('blog',array('year'=>$year['year_add']));
                if($year['months'])
                {
                    foreach($year['months'] as &$month)
                    {
                        $month['link'] = $this->getLink('blog',array('month'=>$month['month_add'],'year'=>$year['year_add']));
                        $month['month_add'] = $this->getMonthName($month['month_add']); 
                    }
                }
            }
        }
        $this->context->smarty->assign(
            array(
                'years'=>$years,
                'ETS_BLOG_RTL_CLASS' => isset($this->context->language->is_rtl) && $this->context->language->is_rtl ? 'ets_blog_rtl_mode' : 'ets_blog_ltr_mode',
            )
        );
        return $this->display(__FILE__,'block_archives.tpl');
    }
    public function assignConfig()
    {          
          $assign = array();
          $defines = Ets_blog_defines::getInstance()->getConfigInputs();
          foreach($defines as $val)
          {
                $key = $val['name'];
                $assign[$key] = isset($val['lang']) && $val['lang'] ? Configuration::get($key, $this->context->language->id) : ($val['type']=='checkbox' || $val['type']=='blog_categories' ? explode(',',Configuration::get($key)) : Configuration::get($key));
          }
          $assign['ETS_BLOG_RTL_CLASS'] = $this->context->language->is_rtl ? 'ets_blog_rtl_mode' : 'ets_blog_ltr_mode'; 
        $assign['ETS_BLOG_SHOP_URI'] = _PS_BASE_URL_.__PS_BASE_URI__;  
          $this->context->smarty->assign(
                array('ets_blog_config' => $assign)
          );
    }
    public function getBreadCrumb()
    {
        $id_post = (int)Tools::getValue('id_post');
        $id_category = (int)Tools::getValue('id_category');
        $controller = Tools::getValue('controller');
        $nodes[] = array(
            'title' => $this->l('Home'),
            'url' => $this->context->link->getPageLink('index', true),
        );
        $nodes[] = array(
            'title' => $this->l('Blog'),
            'url' => $this->getLink('blog')
        );
        if($controller=='category')
        {
            $nodes[] = array(
                'title' => $this->l('All categories'),
                'url' => $this->getLink('category')
            );
        }
        if($controller=='comment')
        {
            $nodes[] = array(
                'title' => $this->l('All Comments'),
                'url' => $this->getLink('comment')
            );
        }
        if($id_category && $category = Ets_blog_category::getCategoryById($id_category))
        {
            $nodes[] = array(
                'title' => $category['title'],
                'url' => $this->getLink('blog',array('id_category' => $id_category)),
            );
        }
        if($id_post && ($post = new Ets_blog_post($id_post,$this->context->language->id)) && Validate::isLoadedObject($post)) {
            if (($id_category_default = $post->id_category_default) && ($category = new Ets_blog_category($id_category, $this->context->language->id)) && Validate::isLoadedObject($category))
            {
                $nodes[] = array(
                    'title' => $category->title,
                    'url' => $this->getLink('blog',array('id_category' => $category->id)),
                );
            }
            $nodes[] = array(
                'title' => $post->title,
                'url' => $this->getLink('blog',array('id_post' => $id_post)),
            );
        }
        if($controller == 'blog' && ($latest = Tools::getValue('latest')) && Validate::isCleanHtml($latest))
        {
            $nodes[] = array(
                'title' => $this->l('Latest posts'),
                'url' => $this->getLink('blog',array('latest' => true)),
            );
        }
        return array('links' => $nodes,'count' => count($nodes));
    }
    public function displayConfirmation($ets_blog_string,$view_text='')
    {
        $this->context->smarty->assign(
            array(
                'ets_blog_string' => $ets_blog_string,
                'link_view' => $this->context->cookie->link_view ? : false,
                'view_text' => $view_text,
            )
        );
        $this->context->cookie->link_view = '';
        $this->context->cookie->write();
        return $this->display(__FILE__,'confirmation.tpl');
    }
    public function refreshCssCustom()
    {
        $color = Configuration::get('ETS_BLOG_CUSTOM_COLOR');
        if(!$color) 
            $color = '#FF4C65';
        $color_hover= Configuration::get('ETS_BLOG_CUSTOM_COLOR_HOVER');
        if(!$color_hover)
            $color_hover='#FF4C65';
        $css = file_exists(dirname(__FILE__).'/views/css/dynamic_style.css') ? Tools::file_get_contents(dirname(__FILE__).'/views/css/dynamic_style.css') : ''; 
        if($css)
            $css = str_replace(array('[color]','[color_hover]'),array($color,$color_hover),$css);
        file_put_contents(dirname(__FILE__).'/views/css/custom.css',$css);
        return true;
    }
    public function sendMailRepyCustomer($id_comment,$replier,$comment_reply=''){
        if(Configuration::get('ETS_BLOG_ENABLE_MAIL_REPLY_CUSTOMER'))
        {
            $comment = new Ets_blog_comment($id_comment);
            if($comment->email && ($id_customer = Customer::customerExists($comment->email,true)) && ($customer = new Customer($id_customer)) && $customer->id_lang)
                $id_lang = $customer->id_lang;
            else
                $id_lang = $this->context->language->id;
            $post = new Ets_blog_post($comment->id_post,$id_lang);
            $reply_comwent_text = Tools::getValue('reply_comwent_text');
            $template_reply_comment=array(
                '{customer_name}' => $comment->name,
                '{customer_email}' => $comment->email,
                '{comment}' =>$comment->comment,
                '{comment_reply}' => $comment_reply ? $comment_reply : (Validate::isCleanHtml($reply_comwent_text) ?  $reply_comwent_text :''),
                '{post_link}' => $this->getLink('blog',array('id_post'=>$comment->id_post)),
                '{post_title}'=>$post->title,
                '{replier}' => $replier,
                '{color_main}'=>Configuration::get('ETS_BLOG_CUSTOM_COLOR'),
                '{color_hover}'=>Configuration::get('ETS_BLOG_CUSTOM_COLOR_HOVER')
            );
            Mail::Send(
    			$id_lang,
    			'admin_reply_comment_to_customer',
    			$id_lang == $this->context->language->id ?  $this->l('New reply to your comment') : $this->getTextLang('New reply to your comment',$id_lang),
    			$template_reply_comment,
    	        $comment->email,
    			$comment->name,
    			null,
    			null,
    			null,
    			null,
    			dirname(__FILE__).'/mails/'
            );
        }
        
    }
    public function setMetas()
    {
        $meta = array();
        $module = Tools::getValue('module');
        if($module!= $this->name)
            return;

        $id_lang = $this->context->language->id;
        $id_category = (int)Tools::getValue('id_category');
        $id_post = (int)Tools::getValue('id_post');
        $controller = Tools::getValue('controller');
        if(!$id_post && ($post_url_alias = Tools::getValue('post_url_alias')) && Validate::isLinkRewrite($post_url_alias))
        {
            $id_post = Ets_blog_post::getIdByUrlAlias($post_url_alias,$id_lang);
        }
        if(!$id_category && ($category_url_alias = Tools::getValue('category_url_alias')) && Validate::isLinkRewrite($category_url_alias))
        {
            $id_category = Ets_blog_category::getIDCategoryByUrlAlias($category_url_alias);
        }
        if($id_category)
        {
            if(($category = new Ets_blog_category($id_category,$id_lang)) && Validate::isLoadedObject($category))
            {
                $meta['meta_title'] = $category->meta_title ? : $category->title;
                $meta['meta_description'] = $category->meta_description ? : Tools::substr(strip_tags($category->description),0,300);
                $meta['meta_keywords'] = $category->meta_keywords;
            }
            else
                $meta['meta_title'] = $this->l('Page not found');
                     
        }
        elseif($id_post)
        {
            if(($post = new Ets_blog_post($id_post,$id_lang)) && Validate::isLoadedObject($post))
            {
                $meta['meta_title'] = $post->meta_title ? :$post->title;
                $meta['meta_description'] = $post->meta_description ? : Tools::substr(strip_tags($post->short_description),0,300);
                $meta['meta_keywords'] = $post->meta_keywords;
            }
            else
                $meta['meta_title'] = $this->l('Page not found');  
        }
        elseif(($tag = Tools::getValue('tag')) && Validate::isCleanHtml($tag))
        {
            $meta['meta_title'] = $this->l('Tag: ').' "'.$tag.'"';
        }  
        elseif(($latest = Tools::getValue('latest')) && Validate::isCleanHtml($latest))
        {
            $meta['meta_title'] = $this->l('Latest posts');        
        }
        elseif(($search = Tools::getValue('search')) && Validate::isCleanHtml($search))
        {
            $meta['meta_title'] = $this->l('Search:').' "'.str_replace('+',' ',$search).'"';                    
        } 
        elseif(($year = (int)Tools::getValue('year')) && ($month = (int)Tools::getValue('month')))
          $meta['meta_title'] = $this->l('Posted in :').' "'.$year.' - '.$this->getMonthName($month).'"';  
        elseif($year)
          $meta['meta_title'] = $this->l('Posted in :').' "'.$year.'"';  
        elseif($id_author = (int)Tools::getValue('id_author'))
        {
            $employee = new Employee($id_author);
            if($employee && Validate::isLoadedObject($employee))                
            {
                $meta['meta_title'] = $this->l('Author: ').$employee->firstname.' '.$employee->lastname;
            }
            else
                $meta['meta_title'] = $this->l('Page not found');   
        } 
        elseif($controller=='category')
        {
            $meta['meta_title'] = $this->l('All categories');                              
        }
        elseif($controller=='blog')
        {
            $meta['meta_title'] = Configuration::get('ETS_BLOG_META_TITLE',$id_lang);
            $meta['meta_description'] = Configuration::get('ETS_BLOG_META_DESCRIPTION',$id_lang);
            $meta['meta_keywords'] = Configuration::get('ETS_BLOG_META_KEYWORDS',$id_lang);
        }
        if(!isset($meta['meta_title']))
            $meta['meta_title']='';
        if(!isset($meta['meta_description']))
            $meta['meta_description']='';
        if(!isset($meta['meta_keywords']))
            $meta['meta_keywords']='';          
        $body_classes = array(
            'lang-'.$this->context->language->iso_code => true,
            'lang-rtl' => (bool) $this->context->language->is_rtl,
            'country-'.$this->context->country->iso_code => true,                                   
        );
        $page = array(
            'title' => '',
            'canonical' => '',
            'meta' => array(
                'title' => $meta['meta_title'],
                'description' => $meta['meta_description'],
                'keywords' => $meta['meta_keywords'],
                'robots' => 'index',
            ),
            'page_name' => 'ets_blog_page',
            'body_classes' => $body_classes,
            'admin_notifications' => array(),
        ); 
        $this->context->smarty->assign(array('page' => $page));                 
    }
    public function installDefaultCategory()
    {
        $category = new Ets_blog_category();
        $category->id_parent=0;
        $category->enabled = 1;
        $category->id_shop = $this->context->shop->id;
        $category->added_by = $this->context->employee->id;
        $languages = Language::getLanguages();
        foreach($languages as $lang)
        {
            $category->title[$lang['id_lang']] = $lang['id_lang']== $this->context->language->id ? $this->l('Default category') : $this->getTextLang('Default category',$lang['id_lang']);
            $category->url_alias[$lang['id_lang']] = $lang['id_lang']== $this->context->language->id ? $this->l('default-category') : $this->getTextLang('default-category',$lang['id_lang']);
        }
        return $category->add();
    }
    public function displayIframe()
    {
        switch($this->context->language->iso_code) {
          case 'en':
            $url = 'https://cdn.prestahero.com/prestahero-product-feed?utm_source=feed_'.$this->name.'&utm_medium=iframe';
            break;
          case 'it':
            $url = 'https://cdn.prestahero.com/it/prestahero-product-feed?utm_source=feed_'.$this->name.'&utm_medium=iframe';
            break;
          case 'fr':
            $url = 'https://cdn.prestahero.com/fr/prestahero-product-feed?utm_source=feed_'.$this->name.'&utm_medium=iframe';
            break;
          case 'es':
            $url = 'https://cdn.prestahero.com/es/prestahero-product-feed?utm_source=feed_'.$this->name.'&utm_medium=iframe';
            break;
          default:
            $url = 'https://cdn.prestahero.com/prestahero-product-feed?utm_source=feed_'.$this->name.'&utm_medium=iframe';
        }
        $this->smarty->assign(
            array(
                'url_iframe' => $url
            )
        );
        return $this->display(__FILE__,'iframe.tpl');
    }
}