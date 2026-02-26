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
class Ets_blog_comment extends Ets_blog_obj
{
    public $id_comment;
    public $id_user;
    public $name;
    public $email;
    public $id_post;
    public $subject;
    public $comment;
    public $reply;
    public $customer_reply;
	public $approved;
	public $reported;
    public $rating;
    public $viewed;
    public $replied_by;
    public $date_add;
    public static $definition = array(
		'table' => 'ets_blog_comment',
		'primary' => 'id_comment',
		'multilang' => false,
		'fields' => array(			
            'id_comment' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'replied_by' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'customer_reply'=> array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'id_user' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'name' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 5000),
            'email' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 5000),
            'rating' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'id_post' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'approved' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'reported' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'subject' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 5000),
            'comment' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 99000),
            'reply' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 99000),
            'viewed' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'date_add' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 500),  
        )
	);
    public	function __construct($id_item = null, $id_lang = null, $id_shop = null)
	{
		parent::__construct($id_item, $id_lang, $id_shop);
	}
    public function l($string,$file_name='')
    {
        return Translate::getModuleTranslation('ets_blog', $string, $file_name ? : pathinfo(__FILE__, PATHINFO_FILENAME));
    }
    public function getListFields()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => (int)$this->id ? $this->l('Edit comment') : $this->l('Add comment'),
                ),
                'input' => array(),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
                'buttons'=> array(
                    array(
                        'href' => Context::getContext()->link->getAdminLink('AdminEtsBlogComment'),
                        'class' => 'pull-left',
                        'icon'=>'process-icon-cancel',
                        'title' => $this->l('Cancel'),
                    ),
                ),
                'name' => 'comment',
                'parent' => '',
            ),
            'configs' => array(
               'subject'=> array(
					'type' => 'text',
					'label' => $this->l('Subject'),					 
                    'required' => true,
                    'validate' => 'isCleanHtml',
                    'desc' =>$this->displayCommentInfo(),	                    
				), 
                'rating' => array(
					'type' => 'select',
					'label' => $this->l('Rating'),
                    'options' => array(
            			 'query' => array(                                
                                array(
                                    'id_option' => '0', 
                                    'name' => $this->l('No ratings')
                                ),
                                array(
                                    'id_option' => '1', 
                                    'name' => '1 '. $this->l('rating')
                                ),
                                array(
                                    'id_option' => '2', 
                                    'name' => '2 '. $this->l('ratings')
                                ),
                                array(
                                    'id_option' => '3', 
                                    'name' => '3 '. $this->l('ratings')
                                ),
                                array(
                                    'id_option' => '4', 
                                    'name' => '4 '. $this->l('ratings')
                                ),
                                array(
                                    'id_option' => '5', 
                                    'name' => '5 '. $this->l('ratings')
                                )
                            ),                             
                         'id' => 'id_option',
            			 'name' => 'name'  
                    )                
				),
                'comment'=>array(
					'type' => 'textarea',
					'label' => $this->l('Comment'),
					'name' => 'comment',                            
                    'required' => true	,
                    'validate' => 'isCleanHtml',					
				),                      
                'approved' => array(
					'type' => 'switch',
					'label' => $this->l('Approved'),
                    'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Yes')
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('No')
						)
					)					
				),
            )
        );
    }
    public function duplicate()
    {
        $this->id = null; 
        if($this->add())
        {
            return $this->id;
        }
        return false;        
    }
    public static function countCommentsWithFilter($filter = false,$fontend=true)
    { 
        $req = "SELECT COUNT(bc.id_comment) as total_comment
                FROM `"._DB_PREFIX_."ets_blog_comment` bc
                INNER JOIN `"._DB_PREFIX_."ets_blog_post` p ON (p.id_post=bc.id_post)
                LEFT JOIN `"._DB_PREFIX_."ets_blog_post_lang` pl ON (p.id_post=pl.id_post AND pl.id_lang='".(int)Context::getContext()->language->id."')
                LEFT JOIN `"._DB_PREFIX_."employee` e ON (e.id_employee=p.added_by)
                WHERE ".($fontend ? ' p.enabled=1 AND ':"")."  p.id_shop=".(int)Context::getContext()->shop->id." ".($filter ? $filter : '');
         $row = Db::getInstance()->getRow($req);
         return isset($row['total_comment']) ?  (int)$row['total_comment'] : 0;
    }
    public static function getCommentsWithFilter($filter = false, $sort = false, $start = false, $limit = false,$fontend=true)
    {          
        $req = "SELECT bc.*,pl.thumb,pl.title
                FROM `"._DB_PREFIX_."ets_blog_comment` bc
                INNER JOIN `"._DB_PREFIX_."ets_blog_post` p ON (p.id_post=bc.id_post)
                LEFT JOIN `"._DB_PREFIX_."ets_blog_post_lang` pl ON (p.id_post=pl.id_post AND pl.id_lang='".(int)Context::getContext()->language->id."')
                LEFT JOIN `"._DB_PREFIX_."employee` e ON (e.id_employee=p.added_by)
                WHERE ".($fontend ? "p.enabled=1 AND ":"")." p.id_shop=".(int)Context::getContext()->shop->id." ".($filter ? $filter : '')."
                GROUP BY bc.id_comment
                ORDER BY ".($sort ? $sort : '')." bc.id_comment desc " . ($start !== false && $limit ? " LIMIT ".(int)$start.", ".(int)$limit : "");
        $comments= Db::getInstance()->executeS($req);
        if($comments)
        {
            foreach($comments as &$comment)
            {
                $employee= Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'employee` WHERE id_employee='.(int)$comment['replied_by']);
                if($employee)
                {
                    $comment['efirstname']= $employee['firstname'];
                    $comment['elastname']= $employee['lastname'];
                }
                $comment['replies'] = Ets_blog_reply::getRepliesByIdComment($comment['id_comment'],1);
                if($comment['replies'])
                {
                    foreach($comment['replies'] as &$reply)
                    {
                        if($reply['id_employee'])
                        {
                            $employee = new Employee($reply['id_employee']);
                            $reply['name'] = $employee->firstname.' '.$employee->lastname;
                        }
                    }
                    
                }
                $comment['comment'] = Tools::nl2br($comment['comment']);
            }
        }
        return $comments;
    }
    public function displayCommentInfo()
    {
        if($this->id_user)
        {
            if(version_compare(_PS_VERSION_, '1.7.6', '>='))
            {
                $sfContainer = call_user_func(array('\PrestaShop\PrestaShop\Adapter\SymfonyContainer','getInstance'));
            	if (null !== $sfContainer) {
            		$sfRouter = $sfContainer->get('router');
            		$customerLink= $sfRouter->generate(
            			'admin_customers_view',
            			array(
                            'customerId' => $this->id_user
                        )
            		);
            	}
                else
                    $customerLink = $this->context->link->getAdminLink('AdminCustomers').'&id_customer='.(int)$this->id_user.'&viewcustomer';
            }
            else
                $customerLink = $this->context->link->getAdminLink('AdminCustomers').'&id_customer='.(int)$this->id_user.'&viewcustomer';
        }
        else
            $customerLink='#';
        $post = new Ets_blog_post($this->id,Context::getContext()->language->id);
        Context::getContext()->smarty->assign(array(
            'comment' => $this,
            'customerLink' => $customerLink,
            'postLink' =>  Module::getInstanceByName('ets_blog')->getLink('blog',array('id_post'=>$this->id_post)),
            'post_title' => $post->title
        ));
        return Context::getContext()->smarty->fetch(_PS_MODULE_DIR_.'ets_blog/views/templates/hook/comment_info.tpl');
    }
}