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
class Ets_blog_reply extends ObjectModel
{
    public $id_comment;
    public $id_user;
    public $name;
    public $email;
    public $reply;
    public $id_employee;
	public $approved;
    public $date_add;
    public $date_upd;
    public static $definition = array(
		'table' => 'ets_blog_reply',
		'primary' => 'id_reply',
		'multilang' => false,
		'fields' => array(			
            'id_comment' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => false),
            'id_user' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'name' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 5000),
            'email' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 5000),
            'approved' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'reply' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 99000),
            'date_add' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 500),
            'date_upd' =>	array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 500),  
        )
	);
    public	function __construct($id_item = null, $id_lang = null, $id_shop = null)
	{
		parent::__construct($id_item, $id_lang, $id_shop);
	}
    public static function getRepliesByIdComment($id_comment,$approved= false)
    {
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_blog_reply` WHERE id_comment='.(int)$id_comment.($approved!==false ? ' AND approved="'.(int)$approved.'"':''));
    }
    public static function getCountRepliesByIdComment($id_comment,$approved= false)
    {
        return Db::getInstance()->getValue('SELECT COUNT(id_reply) FROM `'._DB_PREFIX_.'ets_blog_reply` WHERE id_comment='.(int)$id_comment.($approved!==false ? ' AND approved="'.(int)$approved.'"':''));
    }
}