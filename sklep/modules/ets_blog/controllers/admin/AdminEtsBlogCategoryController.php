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
 * Class AdminEtsBlogCategoryController
 * @property Etshelpdesk $module;
 */
class AdminEtsBlogCategoryController extends ModuleAdminController
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
        if(Tools::isSubmit('save_category'))
        {
            if($id_category = (int)Tools::getValue('itemId'))
            {
                $category = new Ets_blog_category($id_category);
            }
            else
                $category = new Ets_blog_category();
            $result = $category->saveData();
            if(isset($result['errors']) && $result['errors'])
                $this->module->_errors = $result['errors'];
            elseif(isset($result['success']) && $result['success'])
            {
                $this->context->cookie->success_message = $result['success'];
                $this->context->cookie->link_view = $this->module->getLink('blog',array('id_category' => $category->id));
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogCategory').'&id_parent='.$category->id_parent);
            }
        }
        if(Tools::isSubmit('change_enabled') && ($id_category = (int)Tools::getValue('id_category')))
        {
            $category = new Ets_blog_category($id_category);
            $category->enabled = (int)Tools::getValue('change_enabled');
            if($category->update())
            {
                $this->context->cookie->success_message = $this->l('Changed status successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogCategory').'&id_parent='.$category->id_parent);
            }
        }
        if(Tools::isSubmit('delCategoryimage') && ($id_lang = (int)Tools::getValue('id_lang')) && ($id_category = (int)Tools::getValue('id_category')))
        {
            $category = new Ets_blog_category($id_category);
            $old_image = $category->image[$id_lang];
            $category->image[$id_lang] = '';
            if($category->update())
            {
                if(!in_array($old_image,$category->image))
                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'category/'.$old_image);
                $this->context->cookie->success_message = $this->l('Deleted image successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogCategory').'&editcategory=1&id_category='.$category->id);
                
            }
        }
        if(Tools::isSubmit('delCategoryThumb') && ($id_lang = (int)Tools::getValue('id_lang')) && ($id_category = (int)Tools::getValue('id_category')))
        {
            $category = new Ets_blog_category($id_category);
            $old_thumb = $category->thumb[$id_lang];
            $category->thumb[$id_lang] = '';
            if($category->update())
            {
                if(!in_array($old_thumb,$category->thumb))
                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'category/'.$old_thumb);
                $this->context->cookie->success_message = $this->l('Deleted thumbnail successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogCategory').'&editcategory=1&id_category='.$category->id);
                
            }
        }
        if(Tools::isSubmit('del') && ($id_category = (int)Tools::getValue('id_category')))
        {
            $category = new Ets_blog_category($id_category);
            if($category->delete())
            {
                $this->context->cookie->success_message = $this->l('Deleted category successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogCategory').'&id_parent='.$category->id_parent);
            }
        }
    }
    public function renderList()
    {
        $html ='';
        if($this->context->cookie->success_message)
        {
            $html .= $this->module->displayConfirmation($this->context->cookie->success_message,$this->l('View category'));
            $this->context->cookie->success_message ='';
        }
        if($this->module->_errors)
            $html .= $this->module->displayError($this->module->_errors);
        $this->context->smarty->assign(
            array(
                'ets_blog_sidebar' => $this->module->renderSideBar(),
                'ets_blog_content' => $this->renderCategories(),
            )
        );
        return $html.$this->module->display(_PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.$this->module->name.'.php', 'admin.tpl');
    }
    public function renderCategories()
    {
        if(Tools::isSubmit('addNewCategory') || (Tools::isSubmit('editcategory') && ($id_category = Tools::getValue('id_category'))) )
        {
            if(isset($id_category) && $id_category)
                $category = new Ets_blog_category($id_category);
            else
                $category = new Ets_blog_category();
            return $category->renderForm();
        }
        $fields_list = array(
            'id_category' => array(
                'title' => $this->l('Id'),
                'width' => 40,
                'type' => 'text',
                'sort' => true,
                'filter' => true,
            ),
            'thumb_link'=>array(
                'title'=> $this->l('Image'),
                'type' => 'text',
                'strip_tag'=>false,
            ),
            'title' => array(
                'title' => $this->l('Name'),
                'type' => 'text',
                'sort' => true,
                'filter' => true
            ),
            'description' => array(
                'title' => $this->l('Description'),
                'type' => 'text',
                'sort' => true,
                'filter' => true
            ),
            'sort_order' => array(
                'title' => $this->l('Sort order'),
                'type' => 'text',
                'sort' => true,
                'filter' => true,
                'update_position' => true,
            ),
            'enabled' => array(
                'title' => $this->l('Enabled'),
                'type' => 'active',
                'sort' => true,
                'filter' => true,
                'strip_tag' => false,
                'filter_list' => array(
                    'id_option' => 'enabled',
                    'value' => 'title',
                    'list' => array(
                        0 => array(
                            'enabled' => 1,
                            'title' => $this->l('Yes')
                        ),
                        1 => array(
                            'enabled' => 0,
                            'title' => $this->l('No')
                        )
                    )
                )
            ),
        );
        //Filter
        $filter = "";
        if(($idCategory = trim(Tools::getValue('id_category')))!='' && Validate::isCleanHtml($idCategory))
            $filter .= " AND c.id_category = ".(int)$idCategory;
        if(($sort_order = trim(Tools::getValue('sort_order')))!='' && Validate::isCleanHtml($sort_order))
            $filter .= " AND c.sort_order = ".(int)trim(urldecode(Tools::getValue('sort_order')));
        if(($title = trim(Tools::getValue('title')))!='' && Validate::isCleanHtml($title))
            $filter .= " AND cl.title like '%".pSQL($title)."%'";
        if(($description =trim(Tools::getValue('description')))!='' && Validate::isCleanHtml($description))
            $filter .= " AND cl.description like '%".pSQL($description)."%'";
         if(($enabled = trim(Tools::getValue('enabled')))!='' && Validate::isCleanHtml($enabled))
            $filter .= " AND c.enabled =".(int)Tools::getValue('enabled');
        if($filter)
            $show_reset = true;
        else
            $show_reset =false;
        //Sort
        $sort = "";
        $sort_post = Tools::strtolower(trim(Tools::getValue('sort')));
        $sort_type = Tools::strtolower(Tools::getValue('sort_type','desc'));
        if(!in_array($sort_type,array('desc','asc')))
            $sort_type ='desc';
        if($sort_post && isset($fields_list[$sort_post]))
        {
            $sort .= $sort_post." ".($sort_type=='asc' ? ' ASC ' :' DESC ')." , ";
        }
        else
            $sort = "c.sort_order ASC,";
        
        //Paggination
        $id_parent = (int)Tools::getValue('id_parent');
        $page = (int)Tools::getValue('page');
        if($page<=1)
            $page =1;
        $totalRecords = (int)Ets_blog_category::countCategoriesWithFilter($filter,$id_parent);
        $paggination = new Ets_blog_paggination_class();            
        $paggination->total = $totalRecords;
        $paggination->url = $this->context->link->getAdminLink('AdminEtsBlogCategory', true).($id_parent ? '&id_parent='.(int)$id_parent:'').'&page=_page_'.$this->module->getUrlExtra($fields_list);
        $paggination->limit = (int)Tools::getValue('paginator_category_select_limit',20);
        $paggination->name ='category';
        $totalPages = ceil($totalRecords / $paggination->limit);
        if($page > $totalPages)
            $page = $totalPages;
        $paggination->page = $page;
        $start = $paggination->limit * ($page - 1);
        if($start < 0)
            $start = 0;
        $categories = Ets_blog_category::getCategoriesWithFilter($filter, $sort, $start, $paggination->limit,$id_parent);        
        if($categories)
        {
            foreach($categories as &$cat)
            {
                $cat['view_url'] = $this->module->getLink('blog',array('id_category' => $cat['id_category']));
                if(Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_blog_category` WHERE id_parent='.(int)$cat['id_category']))
                {
                    $cat['child_view_url'] = $this->context->link->getAdminLink('AdminEtsBlogCategory', true).'&id_parent='.(int)$cat['id_category'];
                }
                if($cat['thumb'] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$cat['thumb']))
                    $cat['thumb_link'] = '<img src="'._PS_ETS_BLOG_IMG_.'category/'.$cat['thumb'].'" style="width:40px;"/>';
                elseif($cat['image'] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$cat['image']))
                    $cat['thumb_link'] = '<img src="'._PS_ETS_BLOG_IMG_.'category/'.$cat['image'].'" style="width:40px;"/>';
                else
                    $cat['thumb_link']='';
            }
        }
        $paggination->text =  $this->l('Showing {start} to {end} of {total} ({pages} Pages)');
        $paggination->style_links = $this->l('links');
        $paggination->style_results = $this->l('results');
        $thumb='';
        $lever=0;
        $listData = array(
            'name' => 'category',
            'actions' => array('edit','view', 'delete'),
            'currentIndex' => $this->context->link->getAdminLink('AdminEtsBlogCategory').($id_parent ? '&id_parent='.(int)$id_parent:'').($paggination->limit!=20 ? '&paginator_post_select_limit='.$paggination->limit:''),
            'postIndex' => $this->context->link->getAdminLink('AdminEtsBlogCategory').($id_parent ? '&id_parent='.(int)$id_parent:''),
            'identifier' => 'id_category',
            'show_toolbar' => true,
            'show_action' => true,
            'title' => ($id_parent ? $this->module->displayText($this->l('Categories'),'a',null,null,$this->context->link->getAdminLink('AdminEtsBlogCategory', true)) :$this->l('Categories')). ( $id_parent ?  $this->getThumbCategory($id_parent,$thumb,$lever):''),
            'fields_list' => $fields_list,
            'field_values' => $categories,
            'paggination' => $paggination->render(),
            'filter_params' => $this->module->getFilterParams($fields_list),
            'show_reset' =>  $show_reset,
            'totalRecords' => $totalRecords,
            'preview_link' => false,// $this->getLink('blog'),
            'sort' => $sort_post ? : 'id_category',   
            'sort_type' => $sort_type,  
            'show_add_new' => true,
            'link_new' => $this->context->link->getAdminLink('AdminEtsBlogCategory').'&addNewCategory'           
        );            
        return $this->module->renderList($listData); 
    }
    public function getThumbCategory($id_category,&$thumb,&$lever)
    {
        $category = new Ets_blog_category($id_category,$this->context->language->id);
        if($lever>=1)
            $thumb = ' > '.$this->module->displayText($category->title,'a',null,null,$this->context->link->getAdminLink('AdminEtsBlogCategory')).$thumb;
        else
            $thumb = ' > '.$category->title.$thumb;
        $lever++;
        if($category->id_parent)
            $this->getThumbCategory($category->id_parent,$thumb,$lever);
        return $thumb;
    }
}