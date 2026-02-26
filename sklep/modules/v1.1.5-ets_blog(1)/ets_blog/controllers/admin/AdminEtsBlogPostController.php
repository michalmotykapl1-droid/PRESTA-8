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
class AdminEtsBlogPostController extends ModuleAdminController
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
        if(Tools::isSubmit('save_post'))
        {
            if($id_post = (int)Tools::getValue('itemId'))
            {
                $post = new Ets_blog_post($id_post);
            }
            else
                $post = new Ets_blog_post();
            $result = $post->saveData();
            if(isset($result['errors']) && $result['errors'])
                $this->module->_errors = $result['errors'];
            elseif(isset($result['success']) && $result['success'])
            {
                $this->context->cookie->success_message = $result['success'];
                $this->context->cookie->link_view = $this->module->getLink('blog',array('id_post' => $post->id));
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogPost'));
            }
        }
        if(Tools::isSubmit('del') && ($id_post = (int)Tools::getValue('id_post')))
        {
            $post = new Ets_blog_post($id_post);
            if($post->delete())
            {
                $this->context->cookie->success_message = $this->l('Deleted post successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogPost'));
            }
        }
        if(Tools::isSubmit('change_enabled') && ($id_post = (int)Tools::getValue('id_post')))
        {
            $post = new Ets_blog_post($id_post);
            $post->enabled = (int)Tools::getValue('change_enabled');
            if($post->update())
            {
                $this->context->cookie->success_message = $this->l('Changed status successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogPost'));
            }
        }
        if(Tools::isSubmit('delpostimage') && ($id_lang = (int)Tools::getValue('id_lang')) && ($id_post = (int)Tools::getValue('id_post')))
        {
            $post = new Ets_blog_post($id_post);
            $old_image = $post->image[$id_lang];
            $post->image[$id_lang] = '';
            if($post->update())
            {
                if(!in_array($old_image,$post->image))
                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'post/'.$old_image);
                $this->context->cookie->success_message = $this->l('Deleted image successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogPost').'&editpost=1&id_post='.$post->id);
                
            }
        }
    }
    public function renderList()
    {
        $html ='';
        if($this->context->cookie->success_message)
        {
            $html .= $this->module->displayConfirmation($this->context->cookie->success_message,$this->l('View post'));
            $this->context->cookie->success_message ='';
        }
        if($this->module->_errors)
            $html .= $this->module->displayError($this->module->_errors);
        $this->context->smarty->assign(
            array(
                'ets_blog_sidebar' => $this->module->renderSideBar(),
                'ets_blog_content' => $this->renderPosts(),
            )
        );
        return $html.$this->module->display(_PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.$this->module->name.'.php', 'admin.tpl');
    }
    public function renderPosts()
    {
        if(Tools::isSubmit('addNewPost') || (Tools::isSubmit('editpost') && ($id_post = (int)Tools::getValue('id_post'))))
        {
            if(isset($id_post) && $id_post)
                $post = new Ets_blog_post($id_post);
            else
                $post = new Ets_blog_post();
            return $post->renderForm();
        }
        $fields_list = array(
            'id_post' => array(
                'title' => $this->l('Id'),
                'width' => 40,
                'type' => 'text',
                'sort' => true,
                'filter' => true,
            ),
            'thumb_link'=>array(
                'title'=> $this->l('Image'),
                //'width' => 40,
                'type' => 'text',
                'strip_tag'=>false,
            ),
            'title' => array(
                'title' => $this->l('Title'),
                //'width' => 100,
                'type' => 'text',
                'sort' => true,
                'filter' => true
            ),
            'id_category' => array(
                'title' => $this->l('Categories'),
                //'width' => 100,
                'type' => 'select',
                'sort' => true,
                'filter' => true,
                'strip_tag' => false,
                'filter_list' => array(
                    'id_option' => 'id_category',
                    'value' => 'title',
                    'list' => Ets_blog_category::getCategories()
                )
            ),
            'name_author'=> array(
                'title'=>$this->l('Author'),
                //'width' => 40,
                'type' => 'text',
                //'width' => 100,
                'filter'=>true,
                'strip_tag'=>false,
             ),
            'sort_order' => array(
                'title' => $this->l('Sort order'),
                //'width' => 40,
                'type' => 'text',
                'sort' => true,
                'filter' => true,
                'update_position' => true,
            ),
            'position' => array(
                'title' => $this->l('Sort order'),
                //'width' => 40,
                'type' => 'text',
                'sort' => true,
                'filter' => true,
                'update_position' => true,
            ),
            'enabled' => array(
                'title' => $this->l('Status'),
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
                            'title' => $this->l('Published')
                        ),
                        2 => array(
                            'enabled' => 0,
                            'title' => $this->l('Disabled')
                        ),
                    )
                )
            )
        );
        if(($idCategory = trim(Tools::getValue('id_category')))!='' && Validate::isInt($idCategory))
            unset($fields_list['sort_order']);
        else
            unset($fields_list['position']);
        //Filter
        $filter ='';
        if(($idPost = trim(Tools::getValue('id_post')))!='' && Validate::isCleanHtml($idPost))
            $filter .= " AND p.id_post = ".(int)$idPost;
        if(($sort_order = trim(Tools::getValue('sort_order')))!='' && Validate::isCleanHtml($sort_order))
            $filter .= " AND p.sort_order = ".(int)$sort_order;          
        if(($title = trim(Tools::getValue('title')))!='' && Validate::isCleanHtml($title))
            $filter .= " AND pl.title like '%".pSQL($title)."%'";
        if(($description = trim(Tools::getValue('description')))!='' && Validate::isCleanHtml($description))
            $filter .= " AND pl.description like '%".pSQL($description)."%'";
        if(($id_category = trim(Tools::getValue('id_category')))!='' && Validate::isCleanHtml($id_category))
            $filter .= " AND pc.id_category = ".(int)$id_category." ";
        if(($enabled = trim(Tools::getValue('enabled')))!='' && Validate::isCleanHtml($enabled))
            $filter .= " AND p.enabled = ".(int)$enabled;
        if(($name_author = trim(Tools::getValue('name_author')))!='' && Validate::isCleanHtml($name_author))
            $filter .=" AND (CONCAT(e.firstname,' ', e.lastname) like '%".pSQL($name_author)."%'";
        //Sort
        $sort = 'p.id_post DESC,';
        $sort_post = Tools::strtolower(trim(Tools::getValue('sort')));
        $sort_type = Tools::strtolower(Tools::getValue('sort_type','desc'));
        if($sort_post && isset($fields_list[$sort_post]))
        {
            $sort = $sort_post." ".($sort_type=='asc' ? ' ASC ' :' DESC ')." , ";
        }
        if($filter)
            $show_reset=true;
        else
             $show_reset=false;
        //Paggination
        $page = (int)Tools::getValue('page');
        if($page<=1)
            $page =1;
        $totalRecords = (int)Ets_blog_post::countPostsWithFilter($filter,false);
        $paggination = new Ets_blog_paggination_class();            
        $paggination->total = $totalRecords;
        $paggination->url = $this->context->link->getAdminLink('AdminEtsBlogPost', true).'&page=_page_'.$this->module->getUrlExtra($fields_list);
        $paggination->limit = (int)Tools::getValue('paginator_post_select_limit',20);
        $paggination->name ='post';
        $totalPages = ceil($totalRecords / $paggination->limit);
        if($page > $totalPages)
            $page = $totalPages;
        $paggination->page = $page;
        $start = $paggination->limit * ($page - 1);
        if($start < 0)
            $start = 0;
        $posts = Ets_blog_post::getPostsWithFilter($filter, $sort, $start, $paggination->limit,false);        
        if($posts)
        {
            foreach($posts as &$post)
            {
                $post['id_category'] = $this->getCategoriesStrByIdPost($post['id_post']);
                $url = $this->module->getLink('blog',array('id_post'=>$post['id_post']));
                $post['view_url'] = $url;
            }
        }
        $paggination->text =  $this->l('Showing {start} to {end} of {total} ({pages} Pages)');
        $paggination->style_links = $this->l('links');
        $paggination->style_results = $this->l('results');
        $listData = array(
            'name' => 'post',
            'actions' => array('edit','view', 'delete'),
            'currentIndex' => $this->context->link->getAdminLink('AdminEtsBlogPost').($paggination->limit!=20 ? '&paginator_post_select_limit='.$paggination->limit:''),
            'postIndex' => $this->context->link->getAdminLink('AdminEtsBlogPost'),
            'identifier' => 'id_post',
            'show_toolbar' => true,
            'show_action' => true,
            'title' => $this->l('Posts'),
            'fields_list' => $fields_list,
            'field_values' => $posts,
            'paggination' => $paggination->render(),
            'filter_params' => $this->module->getFilterParams($fields_list),
            'show_reset' =>  $show_reset,
            'totalRecords' => $totalRecords,
            'preview_link' => false,// $this->getLink('blog'),
            'sort' => $sort_post ? : 'id_post',   
            'sort_type' => $sort_type,  
            'show_add_new' => true,
            'link_new' => $this->context->link->getAdminLink('AdminEtsBlogPost').'&addNewPost'           
        );            
        return $this->module->renderList($listData); 
    }
    public function getCategoriesStrByIdPost($id_post)
    {
        $categories = Ets_blog_post::getCategoriesStrByIdPost($id_post);
        $this->context->smarty->assign(array('categories' => $categories));
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.$this->module->name.'/views/templates/hook/categories_str.tpl');
    }
}