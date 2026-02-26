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
 * Class AdminEtsBlogCommentController
 * @property $module;
 */
class AdminEtsBlogCommentController extends ModuleAdminController
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
        if(($action = Tools::getValue('action')) && $action=='getCountCommentsNoViewed')
        {
            die(
                json_encode(
                    array(
                        'count' => Ets_blog_comment::countCommentsWithFilter(' AND bc.viewed=0'),
                    )
                )
            );
        }
        if(Tools::isSubmit('save_comment'))
        {
            if($id_comment = (int)Tools::getValue('itemId'))
            {
                $comment = new Ets_blog_comment($id_comment);
            }
            else
                $comment = new Ets_blog_comment();
            $result = $comment->saveData();
            if(isset($result['errors']) && $result['errors'])
                $this->module->_errors = $result['errors'];
            elseif(isset($result['success']) && $result['success'])
            {
                $this->context->cookie->success_message = $result['success'];
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogComment'));
            }
        }
        if(Tools::isSubmit('change_comment_approved') && ($id_comment = (int)Tools::getValue('id_comment')))
        {
            $comment = new Ets_blog_comment($id_comment);
            $approved = (int)Tools::getValue('change_comment_approved');
            $comment->approved = $approved;
            if($comment->update())
            {
                $this->sendMailApproveCommentCustomer($comment);
                $this->context->cookie->success_message = $this->l('The status has been successfully updated.');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogComment').'&viewComment=1&id_comment='.(int)$comment->id);
            }
        }
        if(Tools::isSubmit('change_approved') && ($id_reply = (int)Tools::getValue('id_reply')))
        {
            $approved = (int)Tools::getValue('change_approved');
            $reply = new Ets_blog_reply($id_reply);
            $reply->approved = $approved;
            if($reply->update())
            {
                $this->context->cookie->success_message = $this->l('Changed successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogComment').'&viewComment=1&id_comment='.(int)$reply->id_comment);
            }
        }
        if(Tools::isSubmit('change_enabled') && ($id_comment = (int)Tools::getValue('id_comment')))
        {
            $field = Tools::getValue('field');
            $change_enabled = (int)Tools::getValue('change_enabled');
            if(in_array($field,array('approved','reported')))
            {
                $comment = new Ets_blog_comment($id_comment);
                $comment->{$field} = (int)$change_enabled;
                if($comment->update())
                {
                    if($field=='approved' && $change_enabled==1 && Configuration::get('ETS_BLOG_ENABLE_MAIL_APPROVED'))
                    {
                        $this->sendMailApproveCommentCustomer($comment);
                    }
                    $this->context->cookie->success_message = $this->l('Changed successfully');
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogComment'));
                }
            }
            
        }
        if(Tools::isSubmit('delreply') && ($id_reply= (int)Tools::getValue('id_reply')))
        {
            $reply = new Ets_blog_reply($id_reply);
            if($reply->delete())
            {
                $this->context->cookie->success_message = $this->l('Deleted reply successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogComment').'&viewComment=1&id_comment='.(int)$reply->id_comment);
            }
        }
        if(Tools::isSubmit('del') && ($id_comment = (int)Tools::getValue('id_comment')))
        {
            $comment = new Ets_blog_comment($id_comment);
            if($comment->delete())
            {
                $this->context->cookie->success_message = $this->l('Deleted comment successfully');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminEtsBlogComment'));
            }
        }
        if(Tools::isSubmit('addReplyComment'))
        {
            $reply_comwent_text = Tools::getValue('reply_comwent_text');
            if(Tools::strlen($reply_comwent_text) < 20)
                $this->module->_errors[] = $this->l('Reply needs to be at least 20 characters');
            if(!Validate::isCleanHtml($reply_comwent_text,false))
                $this->module->_errors[] = $this->l('Reply needs to be clean HTML');
            if(Tools::strlen($reply_comwent_text) >2000)
                $this->module->_errors[] = $this->l('Reply cannot be longer than 2000 characters');
            $id_comment = (int)Tools::getValue('id_comment');
            if(!$id_comment)
                $this->module->_errors[] = $this->l('Comment is required');
            elseif(($comment = new Ets_blog_comment($id_comment)) && !Validate::isLoadedObject($comment))
                $this->module->_errors[] = $this->l('Comment is not valid');
            if(!$this->module->_errors)
            {
                $reply = new Ets_blog_reply();
                $reply->id_employee = $this->context->employee->id;
                $reply->approved =1;
                $reply->id_comment = $id_comment;
                $reply->reply = $reply_comwent_text;
                $reply->add();
                $this->module->sendMailRepyCustomer($id_comment,$this->context->employee->firstname.' '.$this->context->employee->lastname);
                $this->context->cookie->success_message =$this->l('Reply has been submitted');
                
            }
            else
            {
                $this->context->smarty->assign(
                    array(
                        'replyCommentsave' => $id_comment,
                        'reply_comwent_text' => $reply_comwent_text,
                    )
                );
            }
        }
    }
    public function sendMailApproveCommentCustomer($comment)
    {
        if(Configuration::get('ETS_BLOG_ENABLE_MAIL_APPROVED'))
        {
            $post = new Ets_blog_post($comment->id_post,$this->context->language->id);
            $templateVars = array(
            '{customer_name}' => $comment->name, 
            '{email}' => $comment->email,
            '{rating}' => $comment->rating.' '.($comment->rating != 1 ? $this->l('stars','blog') : $this->l('star','blog')),
            '{subject}' => $comment->subject, 
            '{comment}'=>$comment->comment,
            '{post_title}'=>$post->title,
            '{post_link}' => $this->module->getLink('blog', array('id_post' => $comment->id_post)),
            '{color_main}'=>Configuration::get('ETS_BLOG_CUSTOM_COLOR'),
            '{color_hover}'=>Configuration::get('ETS_BLOG_CUSTOM_COLOR_HOVER')
            );
            Mail::Send(
                $this->context->language->id, 
                'approved_comment',
                $this->l('Your comment has been approved'),
                $templateVars,  
                $comment->email, null, null, null, null, null, 
                dirname(__FILE__).'/../../mails/', 
                false, $this->context->shop->id
            ); 
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
                'ets_blog_content' => $this->renderComments(),
            )
        );
        return $html.$this->module->display(_PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.$this->module->name.'.php', 'admin.tpl');
    }
    public function renderComments()
    {
        if(Tools::isSubmit('viewComment') && ($id_comment = (int)Tools::getValue('id_comment')))
        {
            return $this->renderReplyComment($id_comment);
        }
        elseif(Tools::isSubmit('editcomment') && ($id_comment = (int)Tools::getValue('id_comment')) && ($comment = new Ets_blog_comment($id_comment)) && Validate::isLoadedObject($comment))
        {
            $comment = new Ets_blog_comment($id_comment);
            return $comment->renderForm();
        }
        $fields_list = array(
            'id_comment' => array(
                'title' => $this->l('Id'),
                'width' => 40,
                'type' => 'text',
                'sort' => true,
                'filter' => true,
            ),
            'subject' => array(
                'title' => $this->l('Subject'),
                //'width' => 140,
                'type' => 'text',
                'sort' => true,
                'filter' => true,                        
            ),                    
            'rating' => array(
                'title' => $this->l('Rating'),
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
                            'stars' => $this->l('No reviews')
                        ),
                        1 => array(
                            'rating' => 1,
                            'stars' => $this->l('1 star')
                        ),
                        2 => array(
                            'rating' => 2,
                            'stars' => $this->l('2 stars')
                        ),
                        3 => array(
                            'rating' => 3,
                            'stars' =>$this->l('3 stars')
                        ),
                        4 => array(
                            'rating' => 4,
                            'stars' => $this->l('4 stars')
                        ),
                        5 => array(
                            'rating' => 5,
                            'stars' => $this->l('5 stars')
                        ),
                    )
                )
            ),
            'name' => array(
                'title' => $this->l('Customer'),
                //'width' => 100,
                'type' => 'text',
                'sort' => true,
                'filter' => true
            ),
            'title'=>array(
                'title'=>$this->l('Blog post'),
                'type' => 'text',
                'filter' => true,  
                'strip_tag'=>false,
            ),
            'count_reply'=>array(
                'title'=>$this->l('Replies'),
                'type' => 'text',
            ),
            'approved' => array(
                'title' => $this->l('Status'),
                //'width' => 50,
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
                            'title' => $this->l('Approved')
                        ),
                        1 => array(
                            'enabled' => 0,
                            'title' => $this->l('Pending')
                        )
                    )
                )
            )
        );
        //Filter
        $filter = "";
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
        $paggination->url = $this->context->link->getAdminLink('AdminEtsBlogComment', true).'&page=_page_'.$this->module->getUrlExtra($fields_list);
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
                $comment['view_url'] = $this->context->link->getAdminLink('AdminEtsBlogComment').'&viewComment&id_comment='.(int)$comment['id_comment'];
                $comment['post_url'] = $this->module->getLink('blog', array('id_post' => $comment['id_post'])).'#blog_comment_line_'.$comment['id_comment'];
                if(!$comment['approved'])
                    $comment['approved_url'] = $this->context->link->getAdminLink('AdminEtsBlogComment').'&id_comment='.(int)$comment['id_comment'].'&change_enabled=1&field=approved';
                //$comment['child_view_url'] = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&control=comment_reply&id_comment='.(int)$comment['id_comment'];
                $replies = Ets_blog_reply::getCountRepliesByIdComment($comment['id_comment']);
                $replies_no_approved = Ets_blog_reply::getCountRepliesByIdComment($comment['id_comment'],0);
                if($replies)
                    $comment['count_reply'] = $replies. ($replies_no_approved ? ' ('.$replies_no_approved.' '.$this->l('pending').')':'');
                else
                    $comment['count_reply']=0;
                $comment['title'] = '<a target="_blank" href="'.$this->module->getLink('blog',array('id_post'=>$comment['id_post'])).'" title="'.$comment['title'].'">'.$comment['title'].'</a>';
            }
        }
        $paggination->text =  $this->l('Showing {start} to {end} of {total} ({pages} Pages)');
        $paggination->style_links = $this->l('links');
        $paggination->style_results = $this->l('results');
        $listData = array(
            'name' => 'comment',
            'actions' => array('view','edit','view_post','delete','approve'),
            'currentIndex' => $this->context->link->getAdminLink('AdminEtsBlogComment', true).($paggination->limit!=20 ? '&paginator_comment_select_limit='.$paggination->limit:''),
            'postIndex' => $this->context->link->getAdminLink('AdminEtsBlogComment'),
            'identifier' => 'id_comment',
            'show_toolbar' => true,
            'show_action' => true,
            'title' => $this->l('Comments'),
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
    public function renderReplyComment($id_comment)
    {
        if($id_comment)
        {
            $comment= new Ets_blog_comment($id_comment);
            if(!Validate::isLoadedObject($comment))
            {
                return $this->module->displayWarning($this->l('Comment does not exist'));
            }
            else
            {
                $comment->viewed=1;
                $comment->update();
                $replies= Ets_blog_reply::getRepliesByIdComment($comment->id);
                if($replies)
                {
                    foreach($replies as &$reply)
                    {
                        if($reply['id_employee'])
                        {
                            $employee = new Employee($reply['id_employee']);
                            $reply['name']= $employee->firstname.' '.$employee->lastname;
                        }
                        if($reply['id_user'])
                        {
                            $customer = new Customer($reply['id_user']);
                            $reply['name']= $customer->firstname.' '.$customer->lastname;
                        }
                    }    
                }
                $this->context->smarty->assign(
                    array(
                        'comment'=>$comment,
                        'replies'=>$replies,
                        'post_class' => new Ets_blog_post($comment->id_post,$this->context->language->id),
                        'curenturl' => $this->context->link->getAdminLink('AdminEtsBlogComment').'&viewComment=1&id_comment='.(int)$id_comment,
                        'link_back'=> $this->context->link->getAdminLink('AdminEtsBlogComment'),
                        'post_link' => $this->module->getLink('blog',array('id_post'=>$comment->id_post)),
                        'link_delete' => $this->context->link->getAdminLink('AdminEtsBlogComment').'&id_comment='.(int)$id_comment.'&del=yes',
                    )
                );
            }
            return $this->context->smarty->fetch(_PS_MODULE_DIR_.$this->module->name.'/views/templates/hook/reply_comment.tpl');
        }
        
    }
}