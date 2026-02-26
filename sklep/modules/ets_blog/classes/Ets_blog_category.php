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
class Ets_blog_category extends Ets_blog_obj
{
    static $instance;
    public $id_parent;
    public $id_shop;
    public $title;
    public $meta_title;
    public $description;
    public $meta_description;
    public $meta_keywords;
	public $enabled;
	public $url_alias;
	public $image;
    public $thumb;
    public $sort_order;
    public $added_by;
    public $modified_by;
    public $date_add;
    public $date_upd;
    public static $definition = array(
		'table' => 'ets_blog_category',
		'primary' => 'id_category',
		'multilang' => true,
		'fields' => array(
            'id_parent' => array('type' => self::TYPE_INT),
            'id_shop' => array('type' => self::TYPE_INT),
			'enabled' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'sort_order' => array('type' => self::TYPE_INT),
            'added_by' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'modified_by' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),          
            'date_add' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'),
            'date_upd' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'),
            // Lang fields
            'image' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml','lang' => true), 
            'thumb' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml','lang' => true), 
            'url_alias' =>	array('type' => self::TYPE_STRING, 'lang' => true,'validate' => 'isCleanHtml'),
            'meta_description' => array('type' => self::TYPE_STRING, 'lang' => true,'validate' => 'isCleanHtml'),
            'meta_keywords' => array('type' => self::TYPE_STRING, 'lang' => true,'validate' => 'isCleanHtml'),   
            'meta_title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'),         
			'title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'),			
            'description' =>	array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
        )
	);
    public	function __construct($id_item = null, $id_lang = null, $id_shop = null)
	{
		parent::__construct($id_item, $id_lang, $id_shop);      
	}
    public static function getInstance()
    {
        if (!(isset(self::$instance)) || !self::$instance) {
            self::$instance = new Ets_blog_category();
        }
        return self::$instance;
    }
    public function l($string,$file_name='')
    {
        return Translate::getModuleTranslation('ets_blog', $string, $file_name ? : pathinfo(__FILE__, PATHINFO_FILENAME));
    }
    public function getListFields()
    {
        $module = MOdule::getInstanceByName('ets_blog');
        $blogcategoriesTree= $this->getBlogCategoriesTree(0,true,Context::getContext()->language->id,$this->id);
        $depth_level =-1;
        $module->getBlogCategoriesDropdown($blogcategoriesTree,$depth_level,$this->id_parent,$this->id);  
        $blogCategoryotpionsHtml = $module->blogCategoryDropDown;
        return array(
            'form' => array(
                'legend' => array(
                    'title' => (int)$this->id ? $this->l('Edit category') : $this->l('Add category'),
                ),
                'input' => array(),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
                'buttons'=> array(
                    array(
                        'href' => Context::getContext()->link->getAdminLink('AdminEtsBlogCategory'),
                        'class' => 'pull-left',
                        'icon'=>'process-icon-cancel',
                        'title' => $this->l('Cancel'),
                    ),
                    //array(
//                        'type'=>'submit',
//                        'name' =>'submitSaveAndPreview',
//                        'title' => $this->l('Save and preview'),
//                        'class' => 'pull-right',
//                        'icon'=>'process-icon-save',
//                    )
                ),
                'name' => 'category',
                'parent' => '',
                'tab_category' => true,
                'img_del_link' => $this->id ? Context::getContext()->link->getAdminLink('AdminEtsBlogCategory').'&id_category='.$this->id.'&delCategoryimage=true':false,
                'thumb_del_link' => $this->id ? Context::getContext()->link->getAdminLink('AdminEtsBlogCategory').'&id_category='.$this->id.'&delCategoryThumb=true':false,
            ),
            'configs' => array(
                'id_parent' => array(
                    'type'=>'select_category',
                    'label'=>$this->l('Parent category'),
                    'blogCategoryotpionsHtml'=>$blogCategoryotpionsHtml,
                    'form_group_class'=>'parent_category',
                    'tab'=>'basic',
                ),					
				'title' => array(
					'type' => 'text',
					'label' => $this->l('Category title'),
					'lang' => true,    
                    'required' => true,   
                    'class' => 'title',  
                    'tab'=>'basic', 
                    'desc' => $this->l('Invalid characters: <>;=#{}'),           
				), 
                'meta_title' =>array(
					'type' => 'text',
					'label' => $this->l('Meta title'),
					'lang' => true,        
                    'tab'=>'seo',            
				), 
                'meta_description' =>array(
					'type' => 'textarea',
					'label' => $this->l('Meta description'),
                    'lang' => true,	
                    'tab'=>'seo',
                    'desc' => $this->l('Should contain your focus keyword and be attractive. Meta description should be less than 300 characters.'),				
				),
                'meta_keywords' => array(
					'type' => 'tags',
					'label' => $this->l('Meta keywords'),
					'name' => 'meta_keywords',
                    'lang' => true,
                    'tab'=>'seo',
                    'hint' => array(
						$this->l('To add "keywords" click in the field, write something, and then press "Enter."'),
					),
                    'desc'=>$this->l('Enter your focus keyword and minor keywords'),
				),
                'description' => array(
					'type' => 'textarea',
					'label' => $this->l('Description'),
					'name' => 'description',
					'lang' => true,  
                    'tab'=>'basic',
                    'autoload_rte' => true,                      
				),
				'url_alias' => array(
					'type' => 'text',
					'label' => $this->l('Url alias'),
                    'required' => true,
                    'lang'=>true,
                    'tab'=>'seo',
                    'hint' => $this->l('Only letters and the hyphen (-) character are allowed.'),
                    'desc' => $this->l('Should be as short as possible and contain your focus keyword'),						
				),
                'thumb' => array(
					'type' => 'file_lang',
					'label' => $this->l('Category thumbnail image'),
                    'imageType' => 'thumb',
                    'tab'=>'basic',
                    'desc' =>sprintf($this->l('Accepted formats: jpg, jpeg, png, gif. Limit: %dMb.'),Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE'))						
				),
                'image' => array(
					'type' => 'file_lang',
					'label' => $this->l('Main category image'),
                    'tab'=>'basic',
                    'desc' => sprintf($this->l('Accepted formats: jpg, jpeg, png, gif. Limit: %dMb.'),Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')),               						
				),
               'enabled' => array(
					'type' => 'switch',
					'label' => $this->l('Enabled'),
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
					)					
				)
            )
        );
    }
    public static function countCategoriesWithFilter($filter,$id_parent=0)
    {
        $req = "SELECT COUNT(DISTINCT c.id_category)
                FROM `"._DB_PREFIX_."ets_blog_category` c
                LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON c.id_category = cl.id_category AND  cl.id_lang = ".(int)Context::getContext()->language->id."
                WHERE c.id_parent='".(int)$id_parent."' AND c.id_shop='".(int)Context::getContext()->shop->id."'".($filter ? $filter : '');     
        return Db::getInstance()->getValue($req);
    }
    public static function getCategoriesWithFilter($filter = false, $sort = false, $start = false, $limit = false,$id_parent=0)
    {          
        $req = "SELECT c.*, cl.*
                FROM `"._DB_PREFIX_."ets_blog_category` c
                LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON c.id_category = cl.id_category AND  cl.id_lang = ".(int)Context::getContext()->language->id."
                WHERE c.id_parent='".(int)$id_parent."' AND  c.id_shop = ".(int)Context::getContext()->shop->id.($filter ? $filter : '')." 
                ORDER BY ".($sort ? $sort : '')." c.id_category desc " . ($start !== false && $limit ? " LIMIT ".(int)$start.", ".(int)$limit : "");      
        return Db::getInstance()->executeS($req);
    }
    public function add($auto_date=true,$null_values=false)
    {
        $this->sort_order = 1+ (int)Db::getInstance()->getValue('SELECT MAX(sort_order) FROM `'._DB_PREFIX_.'ets_blog_category` WHERE id_shop='.(int)$this->id_shop.' AND id_parent='.(int)$this->id_parent);
        return parent::add($auto_date,$null_values);
    }
    public static function getCategories($id_category=0)
    {
        $req = "SELECT c.*, cl.*
                FROM `"._DB_PREFIX_."ets_blog_category` c
                LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON c.id_category = cl.id_category
                WHERE c.id_shop='".(int)Context::getContext()->shop->id."' AND cl.id_lang = ".(int)Context::getContext()->language->id.($id_category ? ' AND c.id_category<"'.(int)$id_category.'"':'');
        $categories = Db::getInstance()->executeS($req);
        if($categories)
        {
            foreach($categories as &$category)
            {
                if(Tools::strlen($category['title'])>32)
                    $category['title'] = Tools::substr($category['title'],0,32).'...';
            }
        }
        return $categories;
    }
    public static function getIDCategoryByUrlAlias($category_url_alias)
    {
        $id_category = (int)Db::getInstance()->getValue('SELECT c.id_category FROM `'._DB_PREFIX_.'ets_blog_category_lang` cl
        INNER JOIN `'._DB_PREFIX_.'ets_blog_category` c ON (cl.id_category=c.id_category)
        WHERE c.enabled=1 AND c.id_shop="'.(int)Context::getContext()->shop->id.'" AND cl.url_alias ="'.pSQL($category_url_alias).'"');
        return $id_category;
    }
    public static function getCategoryById($id_category)
    {
        $id_lang = (int)Context::getContext()->language->id;
        $req = "SELECT c.*, cl.*
                FROM `"._DB_PREFIX_."ets_blog_category` c
                LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON (c.id_category = cl.id_category AND cl.id_lang = ".(int)$id_lang.")
                WHERE c.id_shop='".(int)Context::getContext()->shop->id."' AND c.id_category=".(int)$id_category;
        return Db::getInstance()->getRow($req);
    }
}