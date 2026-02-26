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
class Ets_blogcommentsModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $_errros= array();
    public $_sussecfull;
    public function __construct()
	{
		parent::__construct();
        $this->display_column_right = false;
        $this->display_column_left = false;
        
	}
	public function init()
	{
		parent::init();
	}
	public function initContent()
	{
	    parent::initContent();
        //$this->module->setMetas();
        if (!$this->context->customer->isLogged())
		{  
            Tools::redirect('index.php?controller=authentication');
        }
        if(Tools::isSubmit('submitComment') || Tools::isSubmit('submitCommentStay'))
            $this->_saveComment();
        if(Tools::isSubmit('del') && ($id_comment=(int)Tools::getValue('id_comment')) && ($comment = new Ets_blog_comment($id_comment)))
        {
            if($comment->id_user == $this->context->customer->id)
            {
                $comment->delete();
                if(Tools::isSubmit('ajax'))
                {
                    die(
                        json_encode(
                            array(
                                'success' => $this->module->l('You have just deleted the comment successfully','comments'),
                            )
                        )
                    );
                }
                else
                    Tools::redirectLink($this->context->link->getModuleLink($this->module->name,'comments',array('deletedcomment'=>1)));
            }
            else
            {
                if(Tools::isSubmit('ajax'))
                {
                    die(
                        json_encode(
                            array(
                                'error' => $this->module->l('Sorry, you do not have permission','comments'),
                            )
                        )
                    );
                }
                else
                    $this->_errros[]=$this->module->l('Sorry, you do not have permission','comments');
            }
               
        }   
        if(Tools::isSubmit('deletedcomment'))
            $this->_sussecfull = $this->module->l('You have just deleted the comment successfully','comments');
        if(Tools::isSubmit('updateComment'))
            $this->_sussecfull = $this->module->l('Comment updated','comments');
        $this->context->smarty->assign(
            array(
                'html_content' => $this->renderComments(),
                'errors_html'=>$this->_errros ? $this->module->displayError($this->_errros) : false,
                'sucsecfull_html' => $this->_sussecfull ? $this->module->displayConfirmation($this->_sussecfull):'',
                'breadcrumb' => $this->module->is17 ? $this->getBreadCrumb() : false, 
                'path' => $this->getBreadCrumb(),
            )
        );
        $this->setTemplate('module:ets_blog/views/templates/front/management_comments.tpl');      
    }
    public function _saveComment()
    {
        $id_comment = (int)Tools::getValue('id_comment');
        $commentObj= new Ets_blog_comment($id_comment);
        if(!Validate::isLoadedObject($commentObj) || $commentObj->id_user!=$this->context->customer->id)
            $this->_errros[] = $this->module->l('Comment is not valid');
        if(!($subject = Tools::getValue('subject')))
            $this->_errros[]= $this->module->l('Subject is required','comments');
        elseif(!Validate::isCleanHtml($subject))
            $this->_errros[]= $this->module->l('Subject is not valid','comments');
        else
            $commentObj->subject = $subject;
        if(!($comment = Tools::getValue('comment')))
            $this->_errros[] = $this->module->l('Comment is required','comments');
        elseif(Tools::strlen($comment)<20)
            $this->_errros[]=$this->module->l('Comment need to be at least 20 characters','comments');
        elseif(!Validate::isCleanHtml($comment,true))
            $this->_errros[] = $this->module->l('Comment is not valid','comments');
        else
            $commentObj->comment = Tools::getValue('comment');
        $rating = (int)Tools::getValue('rating');
        if($rating<1 || $rating > 5)
            $this->_errros[] = $this->module->l('Rating is not valid','comments');
        else
            $commentObj->rating = $rating;
        if(!$this->_errros)
        {
            $commentObj->update();
            if(Tools::isSubmit('submitComment'))
                Tools::redirectLink($this->context->link->getModuleLink($this->module->name,'comments',array('updateComment'=>1)));
            else
                $this->_sussecfull = $this->module->l('Comment updated','comments');
        }
    }
    public function getBreadCrumb()
    {
        $nodes=array();
        $nodes[] = array(
            'title' => $this->module->l('Home','comment'),
            'url' => $this->context->link->getPageLink('index', true),
        );
        $nodes[] = array(
            'title' => $this->module->l('Your account','comments'),
            'url' => $this->context->link->getPageLink('my-account'),
        );
        $nodes[] = array(
            'title' => $this->module->l('My blog comments','comments'),
            'url' => $this->context->link->getModuleLink('ets_blog','comments'),
        );
        if($this->module->is17)
                return array('links' => $nodes,'count' => count($nodes));
        return $this->module->displayBreadcrumb($nodes);
    }
    public function renderComments()
    {
        if(Tools::isSubmit('editcomment') && ($id_comment = (int)Tools::getValue('id_comment')))
        {
            $commentObj = new Ets_blog_comment($id_comment);
            if($commentObj->id_user!=$this->context->customer->id)
            {
                return $this->module->displayError($this->module->l('Comment is not valid','comments'));
            }
            else
            {
                $id_comment = (int)Tools::getValue('id_comment');
                $this->context->smarty->assign(
                    array(
                        'ets_comment'=> $commentObj,
                        'link_back_list' => $this->context->link->getModuleLink($this->module->name,'comments'),
                        'edit_approved' => false,
                    )
                );
                return $this->module->display($this->module->name,'form_comment_customer.tpl');
            }
        }
        $fields_list = array(
            'id_comment' => array(
                'title' => $this->module->l('Id','comments'),
                'width' => 40,
                'type' => 'text',
                'sort' => true,
                'filter' => true,
            ),
            'subject' => array(
                'title' => $this->module->l('Subject','comments'),
                //'width' => 140,
                'type' => 'text',
                'sort' => true,
                'filter' => true,                        
            ),                    
            'rating' => array(
                'title' => $this->module->l('Rating','comments'),
                //'width' => 100,
                'type' => 'select',
                'sort' => true,
                'filter' => true,
                'rating_field' => true,
                'filter_list' => array(
                    'id_option' => 'rating',
                    'value' => 'stars',
                    'list' => array(
                        0 => array(
                            'rating' => 0,
                            'stars' => $this->module->l('No reviews','comments')
                        ),
                        1 => array(
                            'rating' => 1,
                            'stars' => $this->module->l('1 star','comments')
                        ),
                        2 => array(
                            'rating' => 2,
                            'stars' => $this->module->l('2 stars','comments')
                        ),
                        3 => array(
                            'rating' => 3,
                            'stars' =>$this->module->l('3 stars','comments')
                        ),
                        4 => array(
                            'rating' => 4,
                            'stars' => $this->module->l('4 stars','comments')
                        ),
                        5 => array(
                            'rating' => 5,
                            'stars' => $this->module->l('5 stars','comments')
                        ),
                    )
                )
            ),
            'title'=>array(
                'title'=>$this->module->l('Blog post','comments'),
                'type' => 'text',
                'filter' => true,  
                'strip_tag'=>false,
            ),
            'approved' => array(
                'title' => $this->module->l('Status','comments'),
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
                            'title' => $this->module->l('Approved','comments')
                        ),
                        1 => array(
                            'enabled' => 0,
                            'title' => $this->module->l('Pending','comments')
                        )
                    )
                )
            ),
        );
        //Filter
        $filter = " AND bc.id_user='".(int)$this->context->customer->id."'";
        $show_reset = false;
        if(($id = trim(Tools::getValue('id_comment')))!='' && Validate::isCleanHtml($id))
        {
            $filter .= " AND bc.id_comment = ".(int)$id;
            $show_reset = true;
        }
        if(($com = trim(Tools::getValue('comment')))!='' && Validate::isCleanHtml($com))
        {
            $filter .= " AND bc.comment like '%".pSQL($com)."%'";
            $show_reset = true;
        }
        if(($subject = trim(Tools::getValue('subject')))!='' && Validate::isCleanHtml($subject))
        {
            $filter .= " AND (bc.subject LIKE '%".pSQL($subject)."%' OR bc.comment LIKE '%".pSQL($subject)."%')";
            $show_reset = true;
        }
        if(($rating = trim(Tools::getValue('rating')))!='' && Validate::isCleanHtml($rating))
        {
            $filter .= " AND bc.rating = ".(int)$rating; 
            $show_reset = true;
        }                   
        if(($name = trim(Tools::getValue('name')))!='' && Validate::isCleanHtml($name))
        {
            $filter .= " AND bc.name like '%".pSQL($name)."%'";
            $show_reset = true;
        }    
        if(($approved = trim(Tools::getValue('approved')))!='' && Validate::isCleanHtml($approved))
        {
            $filter .= " AND bc.approved = ".(int)$approved;
            $show_reset = true;
        }
        if(($reported = trim(Tools::getValue('reported')))!='' && Validate::isCleanHtml($reported))
        {
            $filter .= " AND bc.reported = ".(int)$reported;
            $show_reset = true;
        }
        if(($title = trim(Tools::getValue('title')))!='' && Validate::isCleanHtml($title))
        {
            $filter .= " AND pl.title like '%".pSQL($title)."%'";
            $show_reset = true;
        }    
        //Sort
        $sort_post = Tools::strtolower(Tools::getValue('sort'));
        $sort_type = Tools::strtolower(Tools::getValue('sort_type','desc'));
        if(!in_array($sort_type,array('desc','asc')))
            $sort_type ='desc';
        if($sort_post && isset($fields_list[$sort_post]))
        {
            $sort = $sort_post." ".($sort_type=='asc' ? ' ASC ' :' DESC ')." , ";
        }
        else
            $sort = 'bc.id_comment desc,';
        //Paggination
        $page = (int)Tools::getValue('page');
        if($page <1)
            $page=1;
        $totalRecords = (int)Ets_blog_comment::countCommentsWithFilter($filter,false);
        $paggination = new Ets_blog_paggination_class();            
        $paggination->total = $totalRecords;
        $paggination->url = $this->context->link->getModuleLink($this->module->name, 'comments',array('list'=>1)).'&page=_page_'.$this->module->getUrlExtra($fields_list);
        $paggination->limit = (int)Tools::getValue('paginator_comment_select_limit',20);
        $paggination->name ='comment';
        $totalPages = ceil($totalRecords / $paggination->limit);
        if($page > $totalPages)
            $page = $totalPages;
        $paggination->page = $page;
        $start = $paggination->limit * ($page - 1);
        if($start < 0)
            $start = 0;
        $comments = Ets_blog_comment::getCommentsWithFilter($filter, $sort, $start, $paggination->limit,false);
        if($comments)
        {
            foreach($comments as &$comment)
            {
                $comment['view_url'] = $this->module->getLink('blog', array('id_post' => $comment['id_post'])).'#blog_comment_line_'.$comment['id_comment'];
                $comment['title'] = '<a target="_blank" href="'.$this->module->getLink('blog',array('id_post'=>$comment['id_post'])).'" title="'.$comment['title'].'">'.$comment['title'].'</a>';
                $comment['action_edit_approved'] = false;
            }
        }
        $paggination->text =  $this->module->l('Showing {start} to {end} of {total} ({pages} Pages)','comments');
        $paggination->style_links = $this->module->l('links','comments');
        $paggination->style_results = $this->module->l('results','comments');
        $listData = array(
            'name' => 'comment',
            'actions' => array('view','edit','delete'),
            'currentIndex' => $this->context->link->getModuleLink($this->module->name, 'comments',array('list'=>1)).($paggination->limit!=20 ? '&paginator_comment_select_limit='.$paggination->limit:''),
            'postIndex' => $this->context->link->getModuleLink($this->module->name, 'comments',array('list'=>1)),
            'identifier' => 'id_comment',
            'show_toolbar' => true,
            'show_action' => true,
            'title' => $this->module->l('Comments','comments'),
            'fields_list' => $fields_list,
            'field_values' => $comments,
            'paggination' => $paggination->render(),
            'filter_params' => $this->module->getFilterParams($fields_list),
            'show_reset' => $show_reset,
            'totalRecords' => $totalRecords,
            'show_add_new' => false,
            'sort'=> $sort_post ?: 'id_comment',
            'sort_type'=> $sort_type,
        );            
        return $this->module->renderList($listData);
    }
}