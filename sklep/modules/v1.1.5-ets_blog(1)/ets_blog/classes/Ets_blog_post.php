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
class Ets_blog_post extends Ets_blog_obj
{
    public $id_post;
    public $id_shop;
    public $title;
    public $description;
    public $short_description;
	public $enabled;
	public $url_alias;
    public $meta_description;
    public $meta_keywords;
	public $image;
    public $sort_order;
    public $added_by;
    public $is_customer;
    public $modified_by;
    public $thumb;
    public $meta_title;
    public $id_category_default;
    public $blog_categories;
    public $date_add;
    public $date_upd;
    public static $definition = array(
		'table' => 'ets_blog_post',
		'primary' => 'id_post',
		'multilang' => true,
		'fields' => array(
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
			'enabled' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'sort_order' => array('type' => self::TYPE_INT), 
            'id_category_default' => array('type' => self::TYPE_INT),
            'added_by' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'is_customer' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'modified_by' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'date_add' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'),
            'date_upd' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'),
            // Lang fields
            'image' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml','lang'=>true),            
            'thumb' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml','lang'=>true),
            'url_alias' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'lang' => true),
            'meta_description' => array('type' => self::TYPE_STRING, 'lang' => true,'validate' => 'isCleanHtml'),
            'meta_keywords' => array('type' => self::TYPE_STRING, 'lang' => true,'validate' => 'isCleanHtml' ),            
			'title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml' ),			
            'meta_title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'),
            'description' =>	array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'short_description' =>	array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml')
        )
	);
    public	function __construct($id_item = null, $id_lang = null, $id_shop = null)
	{
		parent::__construct($id_item, $id_lang, $id_shop);      
	}
    public static function getInstance()
    {
        if (!(isset(self::$instance)) || !self::$instance) {
            self::$instance = new Ets_blog_post();
        }
        return self::$instance;
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
                    'title' => (int)$this->id ? $this->l('Edit post') : $this->l('Add post'),
                ),
                'input' => array(),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
                'buttons'=> array(
                    array(
                        'href' => Context::getContext()->link->getAdminLink('AdminEtsBlogPost'),
                        'class' => 'pull-left',
                        'icon'=>'process-icon-cancel',
                        'title' => $this->l('Cancel'),
                    ),
                ),
                'name' => 'post',
                'parent' => '',
                'tab_post' => true,
                'img_del_link' => $this->id ? Context::getContext()->link->getAdminLink('AdminEtsBlogPost').'&id_post='.$this->id.'&delpostimage=true':false,
                'thumb_del_link' => $this->id ? Context::getContext()->link->getAdminLink('AdminEtsBlogPost').'&id_post='.$this->id.'&delpostThumb=true':false,
            ),
            'configs' => array(
                'title' => array(
					'type' => 'text',
					'label' => $this->l('Post title'),
					'lang' => true,    
                    'required' => true, 
                    'tab'=>'basic',
                    'class' => 'title',                             
				),
                'meta_title' =>array(
                    'type' => 'text',
					'label' => $this->l('Meta title'),
					'lang' => true,    
                    'tab'=>'seo',   
                    'desc' => $this->l('Should contain your focus keyword and be attractive'),
                ),
                'meta_description' => array(
					'type' => 'textarea',
					'label' => $this->l('Meta description'),
                    'lang' => true,
                    'tab'=>'seo',
                    'desc' => $this->l('Should contain your focus keyword and be attractive. Meta description should be less than 300 characters.'),						
				),
                'meta_keywords' => array(
					'type' => 'tags',
					'label' => $this->l('Meta keywords'),
                    'lang' => true,
                    'tab'=>'seo',
                    'hint' => array(
						$this->l('To add "keywords" click in the field, write something, and then press "Enter."'),
					),
                    'desc'=>$this->l('Enter your focus keyword and minor keywords'),
				),
               'url_alias' => array(
					'type' => 'text',
					'label' => $this->l('Url alias'),
                    'required' => true,
                    'lang'=>true,
                    'tab'=>'seo',
                    'desc' => $this->l('Should be as short as possible and contain your focus keyword'),						
				),
               'short_description' => array(
					'type' => 'textarea',
					'label' => $this->l('Short description'),
					'lang' => true,  
                    'required' => true,
                    'autoload_rte' => true,
                    'tab'=>'basic',
                    'form_group_class' => 'row_short_description',
                    'desc' => $this->l('Short description is displayed in post listing pages'),                      
				),
               'description' => array(
					'type' => 'textarea',
					'label' => $this->l('Post content'),
					'lang' => true,  
                    'autoload_rte' => true,
                    'required' => true,
                    'tab'=>'basic',
                    'form_group_class' => 'row_description',
                    'desc' => $this->l('Post content is displayed in post detail page (single page).'),
				),
               'thumb' => array(
					'type' => 'file_lang',
					'label' => $this->l('Post thumbnail'),
                    'imageType' => 'thumb',
                    'required' => true,
                    'tab'=>'basic',
                    'desc' => sprintf($this->l('Accepted formats: jpg, jpeg, png, gif. Limit: %dMb. Post thumbnail image is required. You should adjust your image to the recommended size before uploading it.'),Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')),
				),
               'image' => array(
					'type' => 'file_lang',
					'label' => $this->l('Blog post main image'),
					'name' => 'image',
                    'tab'=>'basic',
                    'desc' => sprintf($this->l('Accepted formats: jpg, jpeg, png, gif. Limit: %dMb.'),Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE'))						
				),
               'blog_categories'=> array(
					'type' => 'blog_categories',
					'label' => $this->l('Post categories'),
                    'html_content' =>Module::getInstanceByName('ets_blog')->displayBlogCategoryTre($this->getBlogCategoriesTree(0),$this->getSelectedCategories()),
					'categories' => $this->getBlogCategoriesTree(0),
					'name' => 'categories',
                    'required' => true,
                    'tab'=>'basic',
                    'selected_categories' => $this->getSelectedCategories()                                           
	           ),
               'enabled' => array(
					'type' => 'switch',
					'label' => $this->l('Published'),
                    'is_bool' => true,
                    'tab'=>'basic',
                    'default'=>1,
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
					),				
				),
            )
        );
    }
    public function validateCustom(&$errors)
    {
        $blog_categories = Tools::getValue('blog_categories');
        $id_category_default = (int)Tools::getValue('id_category_default');
        if($blog_categories)
        {
            if(!Ets_blog::validateArray($blog_categories,'isInt'))
                $errors[] = $this->l('Post category is not valid');
            if(!$id_category_default)
                $errors[] = $this->l('Main category is required');
            elseif(!in_array($id_category_default,$blog_categories))
                $errors[] = $this->l('Main category is not valid');
        }
        $this->id_category_default= $id_category_default;              
    }
    public static function countPostsWithFilter($filter)
    {
        $context = Context::getContext();
        $req = "SELECT count(DISTINCT p.id_post)
                FROM `"._DB_PREFIX_."ets_blog_post` p
                LEFT JOIN `"._DB_PREFIX_."ets_blog_post_lang` pl ON (p.id_post = pl.id_post AND pl.id_lang='".(int)$context->language->id."')
                LEFT JOIN `"._DB_PREFIX_."ets_blog_post_category` pc ON p.id_post = pc.id_post
                LEFT JOIN `"._DB_PREFIX_."employee` e ON (e.id_employee=p.added_by AND p.is_customer=0)
                WHERE p.id_shop = ".(int)$context->shop->id.($filter ? $filter : '');     
        return Db::getInstance()->getValue($req);
    }
    public static function getPostsWithFilter($filter = false, $sort = false, $start = false, $limit = false)
    { 
        $id_category = (int)Tools::getValue('id_category');
        $context = Context::getContext();
        $req = "SELECT p.*,pc.id_category, pl.image,pl.thumb, pl.title, pl.description, pl.short_description, pl.meta_keywords, pl.meta_description,pc.position,CONCAT(e.firstname,' ',e.lastname) as name_author
                FROM `"._DB_PREFIX_."ets_blog_post` p
                LEFT JOIN `"._DB_PREFIX_."ets_blog_post_lang` pl ON (p.id_post = pl.id_post AND pl.id_lang='".(int)$context->language->id."')
                LEFT JOIN `"._DB_PREFIX_."ets_blog_post_category` pc ON (p.id_post = pc.id_post ".($id_category? ' AND pc.id_category="'.(int)$id_category.'"' :'').") 
                LEFT JOIN `"._DB_PREFIX_."employee` e ON (e.id_employee=p.added_by AND p.is_customer=0)
                WHERE p.id_shop='".(int)$context->shop->id."'".($filter ? $filter : '')."  
                GROUP BY p.id_post
                ORDER BY ".($sort ? $sort : '')." p.id_post DESC " . ($start !== false && $limit ? " LIMIT ".(int)$start.", ".(int)$limit : "");
        $posts = Db::getInstance()->executeS($req);   
        if($posts)
        {
            foreach($posts as &$post)
            {
                $post['thumb_link'] = $post['thumb'] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'post/'.$post['thumb']) ? '<img src="'._PS_ETS_BLOG_IMG_.'post/'.$post['thumb'].'" style="width:40px;"/>':'';
            }
        }     
        return $posts;
    }
    public function getSelectedCategories()
    {
        if(Tools::isSubmit('save_post'))
        {
            $categories = Tools::getValue('blog_categories');
            if(is_array($categories) && Ets_blog::validateArray($categories))
                return $categories;
            else
                return array();
        }            
        $categories = array();
        if($this->id)
        {         
            $rows = self::getCategoriesStrByIdPost($this->id);
            if($rows)
                foreach($rows as $row)
                    $categories[] = $row['id_category'];
        }
        else
            $categories = array();
        return $categories;        
    }
    public static function getCategoriesStrByIdPost($id_post)
    {
        return Db::getInstance()->executeS("
            SELECT DISTINCT cl.id_category, cl.title
            FROM `"._DB_PREFIX_."ets_blog_post_category` pc
            LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON pc.id_category = cl.id_category AND cl.id_lang=".(int)Context::getContext()->language->id."
            WHERE pc.id_post=".(int)$id_post."
        ");
    }
    public function update($null_values=false)
    {
        $this->modified_by = Context::getContext()->employee->id;
        return parent::update($null_values);
    }
    public function add($auto_date=true,$null_values=false)
    {
        $this->sort_order = 1+ (int)Db::getInstance()->getValue('SELECT MAX(sort_order) FROM `'._DB_PREFIX_.'ets_blog_post` WHERE id_shop='.(int)$this->id_shop);
        $this->added_by = Context::getContext()->employee->id;
        $this->modified_by = Context::getContext()->employee->id;
        return parent::add($auto_date,$null_values);
    }
    public function addCategories()
    {
        $categories = Tools::getValue('blog_categories');
        if(is_array($categories) && Ets_blog::validateArray($categories))
        {
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_blog_post_category` WHERE id_post='.(int)$this->id.' AND id_category NOT IN ('.implode(',',array_map('intval',$categories)).')');
            foreach($categories as $category)
            {
                if(!Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'ets_blog_post_category WHERE id_post="'.(int)$this->id.'" AND id_category="'.(int)$category.'"'))
                {
                    $position = 1+ (int)Db::getInstance()->getValue('SELECT MAX(position) FROM '._DB_PREFIX_.'ets_blog_post_category WHERE id_category="'.(int)$category.'"');
                    Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'ets_blog_post_category` (id_post,id_category,position) VALUES("'.(int)$this->id.'","'.(int)$category.'","'.(int)$position.'") ');
                }
            }
        } 
    }
    public static function getYearsHasBlogPost()
    {
        $sql='SELECT count(*) as total_post,YEAR(p.date_add) as year_add 
        FROM `'._DB_PREFIX_.'ets_blog_post` p
        WHERE p.id_shop= "'.(int)Context::getContext()->shop->id.'" AND p.enabled=1 GROUP BY year_add ORDER BY year_add DESC';
        return Db::getInstance()->executeS($sql);
    }
    public static function getMonthsHasBlogPost($year)
    {
        $sql ='SELECT count(*) as total_post, MONTH(p.date_add) as month_add 
                FROM `'._DB_PREFIX_.'ets_blog_post` p
                WHERE p.id_shop="'.(int)Context::getContext()->shop->id.'" AND p.enabled=1 AND YEAR(date_add)="'.pSQL($year).'" GROUP BY month_add ORDER BY month_add DESC';
        return Db::getInstance()->executeS($sql);
    }
    public static function getIdByUrlAlias($post_url_alias,$id_lang=false)
    {
        $id_post = (int)Db::getInstance()->getValue('SELECT p.id_post FROM `'._DB_PREFIX_.'ets_blog_post_lang` pl
        INNER JOIN `'._DB_PREFIX_.'ets_blog_post` p  ON p.id_post=pl.id_post'.($id_lang ? ' AND pl.id_lang="'.(int)$id_lang.'"':'').
        ' WHERE p.id_shop="'.(int)Context::getContext()->shop->id.'"  AND pl.url_alias ="'.pSQL($post_url_alias).'"');
        return (int)$id_post;
    }
    public static function getPostById($id_post, $id_lang = false)
    {
        if(!$id_lang)    
            $id_lang = Context::getContext()->language->id;
        $req = "SELECT p.*, pl.*, e.firstname, e.lastname
                FROM `"._DB_PREFIX_."ets_blog_post` p
                LEFT JOIN `"._DB_PREFIX_."ets_blog_post_lang` pl ON p.id_post = pl.id_post AND pl.id_lang=".(int)$id_lang."
                LEFT JOIN `"._DB_PREFIX_."employee` e ON (e.id_employee=p.added_by)
                WHERE p.id_post = ".(int)$id_post;
        $post= Db::getInstance()->getRow($req);
        if($post)
        {
            $post['pending'] =0; 
            return $post;
        }
        return false;
        
    }
    public function getPost()
    {
        $post = self::getPostById($this->id);
        $context = Context::getContext();
        $module = Module::getInstanceByName('ets_blog');
        if($post)
        {
            $post['id_category'] = Ets_blog_post::getCategoriesStrByIdPost($post['id_post']);
            $post['categories'] = $this->getCategories($context->language->id,true);
            $post['related_posts'] =  $this->getRelatedPosts( $context->language->id); 
            if($post['related_posts'])
            {
                foreach($post['related_posts'] as &$rpost)
                    if($rpost['image'])
                    {
                        $rpost['image'] = $context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/'.$rpost['image']);
                        if($rpost['thumb'])
                            $rpost['thumb'] = $context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/'.$rpost['thumb']);
                        else
                            $rpost['thumb'] = $context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/'.$rpost['image']);            
                        $rpost['link'] =   $module->getLink('blog',array('id_post'=>$rpost['id_post']));
                        $rpost['categories'] = self::getCategoriesByIdPost($rpost['id_post'],true);
                        $rpost['comments_num'] =false; //$module->countCommentsWithFilter(' AND bc.id_post='.$rpost['id_post'].' AND approved=1');                    
                    }
                    else
                    {
                        $rpost['image'] = '';
                        if($rpost['thumb'])
                            $rpost['thumb'] = $context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/'.$rpost['thumb']);
                        else
                            $rpost['thumb'] = '';
                        $rpost['link'] =   $module->getLink('blog',array('id_post'=>$rpost['id_post']));
                        $rpost['categories'] = self::getCategoriesByIdPost($rpost['id_post'],true); 
                        $rpost['comments_num'] =false; //$module->countCommentsWithFilter(' AND bc.id_post='.$rpost['id_post'].' AND approved=1'); 
                    }                        
            }               
            $post['img_name'] = isset($post['image']) ? $post['image'] : '';
            if($post['image'])
                $post['image'] = $context->link->getMediaLink(_PS_ETS_BLOG_IMG_.'post/'.$post['image']);                            
            $post['link'] = $module->getLink('blog',array('id_post'=>$post['id_post']));  
            $params = array(); 
            $params['id_author'] = (int)$post['added_by'];
            $employee = new Employee((int)$post['added_by']);
            if($employee)
            {
                $params['alias'] = str_replace(' ','-',trim($employee->firstname.' '.$employee->lastname)); 
                $post['author_link'] = $module->getLink('blog', $params);
                $post['employee']['name'] = $employee->firstname.' '.$employee->lastname;   
            }
            return $post;
        }
        return false;
    }
    public function getRelatedPosts($id_lang=false)
    {
        if(!$id_lang)
            $id_lang = Context::getContext()->language->id;
        $sql = "SELECT pl.title, pl.short_description,pl.description,pl.image,pl.thumb, p.*
            FROM `"._DB_PREFIX_."ets_blog_post` p
            LEFT JOIN `"._DB_PREFIX_."ets_blog_post_lang` pl ON pl.id_post = p.id_post AND pl.id_lang = ".(int)$id_lang."
            LEFT JOIN `"._DB_PREFIX_."ets_blog_post_category` pc ON (pc.id_post=p.id_post)
            WHERE p.id_shop='".(int)Context::getContext()->shop->id."' AND  p.enabled=1 AND pc.id_category IN (SELECT id_category FROM `"._DB_PREFIX_."ets_blog_post_category` WHERE id_post=".(int)$this->id.") AND p.id_post != ".(int)$this->id."
            GROUP BY p.id_post
            ORDER BY p.sort_order ASC, p.date_add DESC
            LIMIT 0,5";                   
        $posts = Db::getInstance()->executeS($sql);            
        return $posts;
    }
    public static function getCategoriesByIdPost($id_post,$enabled=false)
    {
        $post = new Ets_blog_post($id_post);
        return $post->getCategories(false,$enabled);
    }
    public function getCategories($id_lang = false, $enabled = false)
    {
        if(!$id_lang)    
            $id_lang = Context::getContext()->language->id;
        $req = "SELECT c.*, cl.* 
                FROM `"._DB_PREFIX_."ets_blog_category` c
                LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON c.id_category = cl.id_category AND cl.id_lang=".(int)$id_lang."
                WHERE c.id_shop='".(int)Context::getContext()->shop->id."' AND c.id_category IN (SELECT id_category FROM `"._DB_PREFIX_."ets_blog_post_category` WHERE id_post = ".(int)$this->id.")
                ".($enabled ? " AND c.enabled = 1" : '');
        $categories = Db::getInstance()->executeS($req);
        if($categories)
        {
            foreach($categories as &$cat)
                $cat['link'] = Module::getInstanceByName('ets_blog')->getLink('blog',array('id_category' => $cat['id_category']));
        }
        return $categories;
    }
    public static function getAvgRatingById($id_post)
    {
        $post = new Ets_blog_post($id_post);
        return $post->getAvgRating();
    }
    public static function getTotalReviewsById($id_post)
    {
        $post = new Ets_blog_post($id_post);
        return $post->getTotalReviews();
    }
    public function getAvgRating()
    {
        $avg = (float)Db::getInstance()->getValue('SELECT AVG(rating) FROM `'._DB_PREFIX_.'ets_blog_comment` WHERE id_post="'.(int)$this->id.'" AND approved=1');
        if($avg >0)
            return Tools::ps_round($avg,1);
    }
    public function getTotalReviews()
    {
        return (int)Db::getInstance()->getValue('SELECT COUNT(id_comment) FROM `'._DB_PREFIX_.'ets_blog_comment` WHERE id_post="'.(int)$this->id.'" AND approved=1');
    }
}
