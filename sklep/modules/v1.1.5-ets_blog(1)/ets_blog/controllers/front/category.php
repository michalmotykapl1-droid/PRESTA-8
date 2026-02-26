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
class Ets_blogCategoryModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $errors= array();
    public $success = '';
    public function __construct()
	{
		parent::__construct();
	}
	public function init()
	{
		parent::init(); 
    }
    public function initContent()
	{
		parent::initContent();
        $this->module->setMetas();
        $this->context->smarty->assign(
            array(
                'blog_content' => $this->_initContent(),
                'blog_left_sidebar' => $this->module->hookDisplayLeftColumn(),
            )
        );
        if($this->module->is17)
            $this->setTemplate('module:ets_blog/views/templates/front/blog.tpl');     
    }
    public function _initContent()
    {
        $categoryData = $this->getCategories();
        if(isset($categoryData['categories']) && $categoryData['categories'])
        {
            foreach($categoryData['categories'] as &$category)
            {
                $category['link']=$this->module->getLink('blog',array('id_category'=>$category['id_category'])); 
                if($category['image'])
                     $category['image'] = $this->context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'category/'.$category['image']);  
                if($category['thumb'])
                    $category['thumb'] = $this->context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'category/'.$category['thumb']);
                $category['count_posts'] =(int)Ets_blog_post::countPostsWithFilter(' AND pc.id_category="'.(int)$category['id_category'].'" AND p.enabled=1');
                $category['sub_categogires'] = Ets_blog_category::getCategoriesWithFilter(' AND c.enabled=1',false,false,false,$category['id_category']);
                if($category['sub_categogires'])
                {
                    foreach($category['sub_categogires'] as &$sub)
                    {
                        $sub['link'] = $this->module->getLink('blog',array('id_category'=>$sub['id_category']));
                    }
                }    
            }
        }
        $this->context->smarty->assign(
            array(
                'blog_categories' => $categoryData['categories'],
                'blog_paggination' => $categoryData['paggination'],
                'path' => $this->module->getBreadCrumb(),
                'blog_layout' => Tools::strtolower(Configuration::get('ETS_BLOG_LAYOUT')),                 
                'breadcrumb' => $this->module->is17 ? $this->module->getBreadCrumb() : false,
                'show_date' => (int)Configuration::get('ETS_BLOG_SHOW_POST_DATE') ? true : false,
                'date_format' => $this->context->language->date_format_lite,
                'image_folder' => _PS_ETS_BLOG_IMG_.'category/',
            )
        );
        return $this->module->display($this->module->name,'list-category.tpl');
    }
    public function getCategories()
    {
        $filter = ' AND c.enabled = 1 AND id_parent=0';            
        $sort = ' c.sort_order asc, c.id_category asc, ';
        $page = (int)Tools::getValue('page');
        if($page < 1)
            $page = 1;
        $totalRecords = (int)Ets_blog_category::countCategoriesWithFilter($filter);
        $paggination = new Ets_blog_paggination_class();            
        $paggination->total = $totalRecords;
        $paggination->url = $this->module->getLink('category', array('page'=>"_page_"));
        $paggination->limit =  8;
        $totalPages = ceil($totalRecords / $paggination->limit);
        if($page > $totalPages)
            $page = $totalPages;
        $paggination->page = $page;
        $start = $paggination->limit * ($page - 1);
        if($start < 0)
            $start = 0;
        $categories = Ets_blog_category::getCategoriesWithFilter($filter, $sort, $start, $paggination->limit);       
        return array(
            'categories' => $categories , 
            'paggination' => $paggination->render()
        );
    }
}