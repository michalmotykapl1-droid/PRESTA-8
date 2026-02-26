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

class Ets_blog_defines
{
    public static $instance;
    public function __construct()
    {
        $this->context = Context::getContext();
        if (is_object($this->context->smarty)) {
            $this->smarty = $this->context->smarty;
        }
    }
    public static function getInstance()
    {
        if (!(isset(self::$instance)) || !self::$instance) {
            self::$instance = new Ets_blog_defines();
        }
        return self::$instance;
    }
    public function l($string)
    {
        return Translate::getModuleTranslation('ets_blog', $string, pathinfo(__FILE__, PATHINFO_FILENAME));
    }
    public function installDb()
    {
        $sqls = array();
        $sqls[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ets_blog_post` (
          `id_post` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `id_shop` INT(11),
          `id_category_default` INT(11),
          `added_by` int(11) NOT NULL,
          `is_customer` INT(1) NOT NULL,
          `modified_by` int(11) NOT NULL,
          `enabled` tinyint(1) NOT NULL DEFAULT '1',
          `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
          `sort_order` int(11) NOT NULL DEFAULT '1',
          PRIMARY KEY (`id_post`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=UTF8";
        $sqls[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ets_blog_post_category` (
              `id_post` int(11) NOT NULL,
              `id_category` int(11) NOT NULL,
              `position` INT (11),
              PRIMARY KEY (`id_post`,`id_category`)
            ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=UTF8";
        $sqls[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ets_blog_post_lang` (
          `id_post` int(11) NOT NULL,
          `id_lang` int(11) NOT NULL,
          `title` varchar(2000) NOT NULL,
          `url_alias` varchar(700) NOT NULL,
          `meta_title` varchar(700) NOT NULL,
          `description` text,
          `short_description` text,
          `meta_keywords` varchar(5000) NOT NULL,
          `meta_description` text,
          `thumb` varchar(1000) NOT NULL,
          `image` varchar(500) NOT NULL,
          PRIMARY KEY (`id_post`,`id_lang`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=UTF8";
        $sqls[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ets_blog_category` (
          `id_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `id_shop` INT(11) NOT NULL,
          `id_parent` INT(11) NOT NULL,
          `added_by` int(11) NOT NULL,
          `modified_by` int(11) NOT NULL,
          `enabled` tinyint(1) NOT NULL DEFAULT '1',
          `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
          `sort_order` int(11) NOT NULL DEFAULT '1',
          PRIMARY KEY (`id_category`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=UTF8";
        $sqls[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ets_blog_category_lang` (
          `id_category` int(11) NOT NULL,
          `id_lang` int(11) NOT NULL,
          `meta_title` varchar(2000)  NOT NULL,
          `title` varchar(2000)  NOT NULL,
          `description` text ,
          `url_alias` varchar(700) NOT NULL,
          `meta_keywords` varchar(5000)  NOT NULL,
          `meta_description` text,
          `image` varchar(500) NULL,
          `thumb` varchar(500) NULL,
          PRIMARY KEY (`id_category`,`id_lang`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=UTF8";
        $sqls[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ets_blog_comment` (
          `id_comment` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `id_user` int(11) NOT NULL,
          `name` varchar(5000)  NOT NULL,
          `email` varchar(5000)  NOT NULL,
          `id_post` int(11) NOT NULL,
          `subject` varchar(2000)  NOT NULL,
          `comment` text ,
          `reply` text,
          `replied_by` int(11) NOT NULL,
          `customer_reply` int(11) NOT NULL,
          `rating` int(11) NOT NULL DEFAULT '0',
          `viewed` int(11) NOT NULL DEFAULT '0',
          `approved` tinyint(1) NOT NULL DEFAULT '1',
          `date_add` datetime NOT NULL,
          `reported` tinyint(1) NOT NULL DEFAULT '0',
          INDEX(id_user),INDEX(id_post),INDEX(approved), PRIMARY KEY (`id_comment`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=UTF8";
        $sqls[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."ets_blog_reply` (
          `id_reply` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `id_comment` int(11) NOT NULL,
          `id_user` int(11) NOT NULL,
          `name` varchar(5000) NOT NULL,
          `email` varchar(5000) NOT NULL,
          `reply` text,
          `id_employee` int(11) NOT NULL,
          `approved` INT(1),
          `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
          INDEX(id_comment,id_user,id_employee,approved), PRIMARY KEY (`id_reply`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=UTF8";
        foreach($sqls as $sql)
        {
            Db::getInstance()->execute($sql);
        }
        return true;
    }
    public function uninstallDb()
    {
        $tables = array(
            'ets_blog_post',
            'ets_blog_post_category',
            'ets_blog_post_lang',
            'ets_blog_category',
            'ets_blog_category_lang',
            'ets_blog_comment',
            'ets_blog_reply',
        );
        foreach($tables as $table)
            Db::getInstance()->execute("DROP TABLE IF EXISTS `"._DB_PREFIX_.pSQL($table)."` ");
        return true;
    }
    public static $subtabs;
    public function getsubTabs()
    {
        if(!self::$subtabs)
        {
            self::$subtabs = array(
                array(
                    'class_name' => 'AdminEtsBlogPost',
                    'tab_name' => $this->l('Posts'),
                    'tabname' => 'Posts',
                    'icon'=>'icon icon-AdminPriceRule',
                ),
                array(
                    'class_name' => 'AdminEtsBlogCategory',
                    'tab_name' => $this->l('Categories'),
                    'tabname' => 'Categories',
                    'icon' => 'icon icon-AdminCatalog',
                ),
                array(
                    'class_name' => 'AdminEtsBlogComment',
                    'tab_name' => $this->l('Comments'),
                    'tabname' => 'Comments',
                    'icon' => 'icon icon-comments',
                ),
                array(
                    'class_name' => 'AdminEtsBlogSetting',
                    'tab_name' => $this->l('Global settings'),
                    'tabname' => 'Global settings',
                    'icon' => 'icon icon-AdminAdmin',
                ),
                array(
                    'class_name' => 'AdminEtsBlogBackup',
                    'tab_name' => $this->l('Backup'),
                    'tabname' => 'Backup',
                    'icon' => 'icon icon-backup',
                ),
            );
        }
        return self::$subtabs;
    }
    public static $configInputs;
    public function getConfigInputs()
    {
        if(!self::$configInputs)
        {
            $module = Module::getInstanceByName('ets_blog');
            self::$configInputs = array(
                array(
                    'label' => $this->l('Blog layout'),
                    'name' => 'ETS_BLOG_LAYOUT',
                    'type' => 'select',  
                    'tab' => 'general',                                   
    				'options' => array(
            			 'query' => array( 
                                array(
                                    'id_option' => 'grid', 
                                    'name' => $this->l('Grid')
                                ),
                                array(
                                    'id_option' => 'list', 
                                    'name' => $this->l('List')
                                ),
                            ),                             
                         'id' => 'id_option',
            			 'name' => 'name'  
                    ),    
                    'default' => 'list',
                    'desc' => $this->l('Layout type for post listing pages such as main blog page, blog category pages, author pages, etc.'),
                ),            
                array(
                    'label' => $this->l('Main color'),
                    'name' => 'ETS_BLOG_CUSTOM_COLOR',
                    'type' => 'color',
                    'default' => '#2fb5d2',
                    'tab' => 'general',
                    'desc' => $this->l('Used for buttons, link, highlight text, etc.'),
                ),
                array(
                    'label' => $this->l('Hover color'),
                    'name' => 'ETS_BLOG_CUSTOM_COLOR_HOVER',
                    'type' => 'color',
                    'default' => '#00cefd',
                    'desc' => $this->l('Used for buttons, link, highlight text, etc.'),
                    'tab' => 'general',
                ),
                array(
                    'label' => $this->l('\'\'Read more\'\' text'),
                    'name' => 'ETS_BLOG_TEXT_READMORE',
                    'type' => 'text',
                    'default_lang' => 'Read more',
                    'default' => $this->l('Read more'),
                    'lang'=>true,
                    'tab' => 'general',
                ),
                array(
                    'label' => $this->l('Send notification email to customers when their comment is approved'),
                    'name' => 'ETS_BLOG_ENABLE_MAIL_APPROVED',
                    'type' =>'switch',
                    'default' =>1,
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
                    'tab' => 'general',  
                ), 
                array(
                    'label' => $this->l('Send notification email to customer when admin/other users replied to his/her comment'),
                    'name' => 'ETS_BLOG_ENABLE_MAIL_REPLY_CUSTOMER',
                    'type' =>'switch',
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
                    'tab' => 'general', 
                    'default' =>1,
                ),
                array(
                    'name' =>'ETS_BLOG_COMMENT_AUTO_APPROVED',
                    'label' => $this->l('Auto-approve comments'),
                    'type' => 'switch',
                    'default' => 0,                
                    'tab' => 'general',
                    'form_group_class' => 'comment',
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
                array(
                    'name' => 'ETS_BLOG_FRIENDLY_URL',
                    'label' => $this->l('Enable blog friendly URL'),
                    'type' => 'switch',
                    'default' => 1,
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
                array(
                    'name' => 'ETS_BLOG_ALIAS',
                    'label' => $this->l('Blog alias'),
                    'type' => 'text',
                    'default' => $this->l('blog'),
                    'default_lang' => 'blog',
                    'desc' => $this->l('Your blog main page:').' <a href="'.$module->getLink().'" class="ets-link-desc">'.$module->getLink().'</a><br/>'.$this->l('Copy this link and paste it to your top menu or somewhere in order to link the blog area with your website'),
                    'required' => true,
                    'lang'=>true,
                ),
                array(
                    'name' => 'ETS_BLOG_META_TITLE',
                    'label' => $this->l('Blog meta title'),
                    'type' => 'text',
                    'default' => $this->l('BLOG'),
                    'default_lang' => 'BLOG',
                    'lang' => true,
                    'required' => true,
                ),
                array(
                    'name' => 'ETS_BLOG_META_KEYWORDS',
                    'label' => $this->l('Blog meta keywords'),
                    'type' => 'tags',
                    'default' => $this->l('lorem,ipsum,dolor'),
                    'default_lang' => 'lorem,ipsum,dolor',
                    'lang' => true,                
                    'desc' => $this->l('Separated by a comma (,)'),               
                ),
                array(
                    'name' => 'ETS_BLOG_META_DESCRIPTION',
                    'label' => $this->l('Blog meta description'),
                    'type' => 'textarea',
                    'default' => $this->l('The most powerful, flexible and feature-rich blog module for PrestaShop. BLOG provides everything you need to create a professional blog area for your website.'),
                    'default_lang' => 'The most powerful, flexible and feature-rich blog module for PrestaShop. BLOG provides everything you need to create a professional blog area for your website.',
                    'lang' => true,                
                ), 
                array(
                    'label' => $this->l('Display blog post in home page'),
                    'name' => 'ETS_BLOG_DISPLAY_HOME_PAGE',
                    'type' =>'switch',
                    'default' =>1,
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
            );
        }
        return self::$configInputs;
    }
    public static function duplicateRowsFromDefaultShopLang($tableName, $shopId,$identifier)
    {
        $shopDefaultLangId = Configuration::get('PS_LANG_DEFAULT');
        $fields = array();
        $shop_field_exists = $primary_key_exists = false;
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . $tableName . '`');
        foreach ($columns as $column) {
            $fields[] = '`' . $column['Field'] . '`';
            if ($column['Field'] == 'id_shop') {
                $shop_field_exists = true;
            }
            if ($column['Field'] == $identifier) {
                $primary_key_exists = true;
            }
        }
        $fields = implode(',', $fields);

        if (!$primary_key_exists) {
            return true;
        }

        $sql = 'INSERT IGNORE INTO `' . $tableName . '` (' . $fields . ') (SELECT ';

        // For each column, copy data from default language
        reset($columns);
        $selectQueries = array();
        foreach ($columns as $column) {
            if ($identifier != $column['Field'] && $column['Field'] != 'id_lang') {
                $selectQueries[] = '(
							SELECT `' . bqSQL($column['Field']) . '`
							FROM `' . bqSQL($tableName) . '` tl
							WHERE tl.`id_lang` = ' . (int) $shopDefaultLangId . '
							' . ($shop_field_exists ? ' AND tl.`id_shop` = ' . (int) $shopId : '') . '
							AND tl.`' . bqSQL($identifier) . '` = `' . bqSQL(str_replace('_lang', '', $tableName)) . '`.`' . bqSQL($identifier) . '`
						)';
            } else {
                $selectQueries[] = '`' . bqSQL($column['Field']) . '`';
            }
        }
        $sql .= implode(',', $selectQueries);
        $sql .= ' FROM `' . _DB_PREFIX_ . 'lang` CROSS JOIN `' . bqSQL(str_replace('_lang', '', $tableName)) . '` ';

        // prevent insert with where initial data exists
        $sql .= ' WHERE `' . bqSQL($identifier) . '` IN (SELECT `' . bqSQL($identifier) . '` FROM `' . bqSQL($tableName) . '`) )';
        return Db::getInstance()->execute($sql);
    }
}