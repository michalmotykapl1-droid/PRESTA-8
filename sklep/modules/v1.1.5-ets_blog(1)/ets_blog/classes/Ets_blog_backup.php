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
class Ets_blog_backup extends Module
{
    public $name;
    public $module;
    public $defines;
	public	function __construct()
	{
		$this->name = 'ets_blog';
		parent::__construct();
        $this->module= new Ets_blog();
        $this->defines = new Ets_blog_defines();
	}
    public function getCategories($id_root,&$categories)
    {
        $sql ='SELECT * FROM `'._DB_PREFIX_.'ets_blog_category` c 
        WHERE c.id_category ="'.(int)$id_root.'"'.($id_root ? ' AND c.id_shop="'.(int)Context::getContext()->shop->id.'"':'');
        $category= Db::getInstance()->getRow($sql);
        if($category)
            $categories[]=$category;
        $childs= $this->getChildCategory($id_root);
        if($childs)
        {
            foreach($childs as $child)
            {
                $this->getCategories($child['id_category'],$categories);
            }
        }
        return $categories;
    }
    public function getChildCategory($id_parent)
    {
        $sql ='SELECT * FROM `'._DB_PREFIX_.'ets_blog_category` c
        WHERE c.id_parent ="'.(int)$id_parent.'" AND c.id_shop="'.(int)Context::getContext()->shop->id.'"';
        return  Db::getInstance()->executeS($sql);
    }
    public function getCategoryAllLanguage($id_category)
    {
        $sql ='SELECT c.id_category, cl.title,cl.description,cl.meta_keywords,cl.meta_description,cl.url_alias,cl.meta_title,cl.image,cl.thumb,l.iso_code,l.id_lang FROM `'._DB_PREFIX_.'ets_blog_category` c
                LEFT JOIN `'._DB_PREFIX_.'ets_blog_category_lang` cl on (c.id_category = cl.id_category)
                LEFT JOIN `'._DB_PREFIX_.'lang` l ON (l.id_lang= cl.id_lang)
                WHERE c.id_category="'.(int)$id_category.'"';
        return Db::getInstance()->executeS($sql);
    }
    public function getPostAllLanguage($id_post)
    {
        $sql = 'SELECT p.id_post,pl.title,pl.meta_title,pl.description,pl.short_description,pl.meta_keywords,pl.meta_description,pl.url_alias,pl.image,pl.thumb,l.iso_code,l.id_lang FROM `'._DB_PREFIX_.'ets_blog_post` p
            LEFT JOIN `'._DB_PREFIX_.'ets_blog_post_lang` pl on (p.id_post = pl.id_post)
            LEFT JOIN `'._DB_PREFIX_.'lang` l on (pl.id_lang=l.id_lang)
            WHERE p.id_post ="'.(int)$id_post.'"';
        return Db::getInstance()->executeS($sql);
    }
    public function getPosts()
    {
        $sql ='SELECT * FROM `'._DB_PREFIX_.'ets_blog_post` p 
        WHERE p.id_shop='.(int)Context::getContext()->shop->id;
        return Db::getInstance()->executeS($sql);
    }
    public function getCategoryByIDPost($id_post)
    {
        $sql='SELECT c.* FROM `'._DB_PREFIX_.'ets_blog_category` c,`'._DB_PREFIX_.'ets_blog_post_category` pc 
        WHERE c.id_category =pc.id_category AND pc.id_post= "'.(int)$id_post.'"';
        return Db::getInstance()->executeS($sql);
    }
    public function getComments($id_post)
    {
        $sql='SELECT * FROM `'._DB_PREFIX_.'ets_blog_comment` WHERE id_post='.(int)$id_post.' AND subject!="" AND comment!=""';
        return Db::getInstance()->executeS($sql);
    }
    public function exportBlog()
    {
        $xml_output = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml_output .= '<entity_profile>'."\n";
        $categories=array();
        $categories =$this->getCategories(0,$categories);
        if($categories)
        {
            foreach($categories as $category)
            {
                $xml_output .='<category id_parent="'.$category['id_parent'].'" id_category="'.$category['id_category'].'" added_by="'.$category['added_by'].'" modified_by="'.$category['modified_by'].'" enabled="'.$category['enabled'].'" datetime_added="'.$category['date_add'].'" datetime_modified="'.$category['date_upd'].'" sort_order="'.$category['sort_order'].'">'."\n";
                $categoryLanguages = $this->getCategoryAllLanguage($category['id_category']);
                if($categoryLanguages)
                {
                    foreach($categoryLanguages as $categoryLanguage)
                    {
                        $xml_output .='<categorylanguage iso_code="'.$categoryLanguage['iso_code'].'" default="'.($categoryLanguage['id_lang']==Configuration::get('PS_LANG_DEFAULT') ? 1 :0).'">'."\n";
                        $xml_output .='<title><![CDATA['.$categoryLanguage['title'].']]></title>'."\n";
                        $xml_output .='<meta_title><![CDATA['.$categoryLanguage['meta_title'].']]></meta_title>'."\n";
                        $xml_output .='<url_alias><![CDATA['.$categoryLanguage['url_alias'].']]></url_alias>'."\n";
                        $xml_output .='<description><![CDATA['.$categoryLanguage['description'].']]></description>'."\n";
                        $xml_output .='<meta_keywords><![CDATA['.$categoryLanguage['meta_keywords'].']]></meta_keywords>'."\n";
                        $xml_output .='<meta_description><![CDATA['.$categoryLanguage['meta_description'].']]></meta_description>'."\n";
                        $xml_output .='<image><![CDATA['.$categoryLanguage['image'].']]></image>'."\n";
                        $xml_output .='<thumb><![CDATA['.$categoryLanguage['thumb'].']]></thumb>'."\n";
                        $xml_output .='</categorylanguage>'."\n";
                    }
                }
                $xml_output .='</category>'."\n";
            }
        }
        $posts = $this->getPosts();
        if($posts)
            foreach($posts as $post)
            {
                $id_post= $post['id_post'];
                $xml_output .= '<post id_category_default="'.(int)$post['id_category_default'].'" id_post="'.$post['id_post'].'" is_featured="0" products="" added_by ="'.(int)$post['added_by'].'" modified_by="'.(int)$post['modified_by'].'" enabled="'.$post['enabled'].'" datetime_added ="'.$post['date_add'].'" datetime_modified="'.$post['date_upd'].'" datetime_active="" sort_order="'.$post['sort_order'].'" click_number="0" likes ="0" is_customer="0" >'."\n";
                $postAllLanguage = $this->getPostAllLanguage($id_post);
                if($postAllLanguage)
                {
                    foreach($postAllLanguage as $language)
                    {
                        $xml_output .='<language iso_code ="'.$language['iso_code'].'" default="'.($language['id_lang']==Configuration::get('PS_LANG_DEFAULT')?1 :0 ).'">'."\n";
                        $xml_output .='<title><![CDATA['.$language['title'].']]></title>'."\n";
                        $xml_output .='<meta_title><![CDATA['.$language['meta_title'].']]></meta_title>'."\n";
                        $xml_output .='<url_alias><![CDATA['.$language['url_alias'].']]></url_alias>'."\n";
                        $xml_output .='<description><![CDATA['.$language['description'].']]></description>'."\n";
                        $xml_output .='<short_description><![CDATA['.$language['short_description'].']]></short_description>'."\n";
                        $xml_output .='<meta_keywords><![CDATA['.$language['meta_keywords'].']]></meta_keywords>'."\n";
                        $xml_output .='<meta_description><![CDATA['.$language['meta_description'].']]></meta_description>'."\n";
                        $xml_output .='<image><![CDATA['.$language['image'].']]></image>'."\n";
                        $xml_output .='<thumb><![CDATA['.$language['thumb'].']]></thumb>'."\n";
                        $xml_output .='</language>'."\n";
                    }
                }
                $categories = $this->getCategoryByIDPost($id_post);
                if($categories)
                    foreach($categories as $category)
                    {
                        $xml_output .='<category id_category="'.$category['id_category'].'">'."\n";
                        $xml_output .='</category>'."\n";
                    }
                $comments = $this->getComments($id_post);
                if($comments)
                {
                    foreach($comments as $comment)
                    {
                        $xml_output .='<comment id_user="'.$comment['id_user'].'" name="'.$comment['name'].'" email="'.$comment['email'].'" subject="'.$comment['subject'].'" replied_by="'.$comment['replied_by'].'" rating="'.$comment['rating'].'" approved="'.$comment['approved'].'" datetime_added="'.$comment['date_add'].'" reported="'.$comment['reported'].'">'."\n";
                        $xml_output .='<comment_text><![CDATA['.$comment['comment'].']]></comment_text>'."\n";
                        $replies= Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'ets_blog_reply` WHERE id_comment='.(int)$comment['id_comment']);
                        if($replies)
                        {
                            foreach($replies as $reply)
                            {
                                $xml_output .='<reply id_comment="'.$reply['id_comment'].'" id_user="'.$comment['id_user'].'" name="'.$reply['name'].'" email="'.$reply['email'].'" id_employee="'.$reply['id_employee'].'" approved="'.$reply['approved'].'" datetime_added="'.$reply['date_add'].'" datetime_updated="'.$reply['date_upd'].'">'."\n";
                                $xml_output .='<reply_text><![CDATA['.$reply['reply'].']]></reply_text>';
                                $xml_output .='</reply>'."\n";
                            }
                        }

                        $xml_output .='</comment>'."\n";
                    }
                }
                $xml_output .= '</post>'."\n";
            }
        $xml_output .='<configuration>'."\n";
        $xml_output .= $this->exportXmlConfiguration($this->defines->getConfigInputs());
        $xml_output .='</configuration>'."\n";
        $xml_output .= '</entity_profile>'."\n";
        return str_replace('&','and',$xml_output);
    }
    public function exportXmlConfiguration($configs)
    {
        $languages = Language::getLanguages(false);
        $id_lang_default= Configuration::get('PS_LANG_DEFAULT');
        $xml_output ='';
        if($configs)
        {
            foreach($configs as $config)
            {
                $key = $config['name'];
                if(isset($config['lang']) && $config['lang'])
                {
                    $xml_output .= '<'.$key.'>'."\n";
                    foreach($languages as $language)
                    {
                        $xml_output .= '<language iso_code="'.$language['iso_code'].'"'.($language['id_lang']==$id_lang_default ? ' default="1"':' default="0"').'>'."\n";
                        $xml_output .='<content><![CDATA['.Configuration::get($key,$language['id_lang'],null,null,false).']]></content>'."\n";
                        $xml_output .= '</language>'."\n";
                    }
                    $xml_output .='</'.$key.'>'."\n";
                }
                else
                {
                    $xml_output .='<'.$key.'><![CDATA['.Configuration::get($key).']]></'.$key.'>'."\n";
                }
            }
        }
        return $xml_output;
    }

    /**
     * @param ZipArchive $obj
     * @param $file
     * @param $server_path
     * @param $archive_path
     */
    private function archiveThisFile($obj, $file, $server_path, $archive_path)
    {
        if (is_dir($server_path.$file)) {
            $dir = scandir($server_path.$file);
            foreach ($dir as $row) {
                if ($row[0] != '.') {
                    $this->archiveThisFile($obj, $row, $server_path.$file.'/', $archive_path.$file.'/');
                }
            }
        } else $obj->addFile($server_path.$file, $archive_path.$file);
    }
    public function generateArchive()
    {
        $errors = array();
        $zip = new ZipArchive();
        $cacheDir = _ETS_BLOG_CACHE_DIR_;
        $zip_file_name = 'ets_blog_'.date('dmYHis').'.zip';
        if(!is_dir($cacheDir))
        {
            @mkdir($cacheDir, 0755,true);
            if ( @file_exists(dirname(__file__).'/index.php')){
                @copy(dirname(__file__).'/index.php', $cacheDir.'index.php');
            }
        }
        if ($zip->open($cacheDir.$zip_file_name, ZipArchive::OVERWRITE | ZipArchive::CREATE) === true) {
            if (!$zip->addFromString('blog-data.xml', $this->exportBlog())) {
                $errors[] = $this->l('Cannot create blog-data.xml');
            }
            $this->archiveThisFile($zip,'category', _PS_ETS_BLOG_IMG_DIR_, 'img/');
            $this->archiveThisFile($zip,'post', _PS_ETS_BLOG_IMG_DIR_, 'img/');
            $zip->close();
            if (!is_file($cacheDir.$zip_file_name)) {
                $errors[] = $this->l(sprintf('Could not create %1s', _PS_CACHE_DIR_.$zip_file_name));
            }
            if (!$errors) {
                if (ob_get_length() > 0) {
                    ob_end_clean();
                }

                ob_start();
                header('Pragma: public');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: public');
                header('Content-Description: File Transfer');
                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.$zip_file_name.'"');
                header('Content-Transfer-Encoding: binary');
                ob_end_flush();
                if(file_exists($cacheDir.$zip_file_name))
                {
                    readfile($cacheDir.$zip_file_name);
                    @unlink($cacheDir.$zip_file_name);
                }

                exit;
            }
        }
        return $errors;
    }
    public static function isImage($image)
    {
        $image = str_replace(array(' ','(',')','!','@','#','+'),'-',$image);
        if(Validate::isFileName((string)$image))
        {
            $type = Tools::strtolower(Tools::substr(strrchr((string)$image, '.'), 1));
            if(in_array($type, array('jpg', 'gif', 'jpeg', 'png')))
                return true;
            else
                return false;
        }
        return false;
    }
    /**
     * @param $file_xml
     * @param array $params
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function importData($file_xml,$params = array())
    {
        if (file_exists($file_xml))
        {
            $languages = Language::getLanguages(false);
            $xml = simplexml_load_file($file_xml);
            $categories=array();
            if(isset($params['data_import']) &&  ($data_imports= $params['data_import']) && is_array($data_imports))
            {
                $importoverride = isset($params['importoverride']) ? (int)$params['importoverride']:0;
                $keepcommenter = isset($params['keepcommenter']) ? (int)$params['keepcommenter']:0;
                $keepauthorid = isset($params['keepauthorid']) ? (int)$params['keepauthorid']:0;
                if(in_array('posts_categories',$data_imports))
                {
                    if(isset($xml->category) && $xml->category)
                    {
                        foreach($xml->category as $category_xml)
                        {
                            $oldImages = array();
                            $oldThumbs = array();
                            if($importoverride && isset($category_xml['id_category']) && ($category = new Ets_blog_category((int)$category_xml['id_category'])) && Validate::isLoadedObject($category) && $category->id_shop == Context::getContext()->shop->id)
                            {
                                $oldImages = $category->image;
                                $oldThumbs = $category->thumb;
                            }
                            else
                            {
                                $category = new Ets_blog_category();
                                if(isset($category_xml['id_parent'])&&(int)$category_xml['id_parent']!=0)
                                    $id_parent = isset($categories[(int)$category_xml['id_parent']]) ? $categories[(int)$category_xml['id_parent']] : 0;
                                else
                                    $id_parent=0;
                                $category->id_parent =$id_parent;
                                $category->sort_order = 1+(int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'ets_blog_category` c WHERE c.id_parent="'.(int)$id_parent.'" AND c.id_shop='.(int)$this->context->shop->id);
                            }
                            $category->enabled = (int)$category_xml['enabled'];
                            if(isset($category_xml['image']) && self::isImage((string)$category_xml['image']))
                            {
                                foreach($languages as $language)
                                    $category->image[$language['id_lang']] = (string)$category_xml['image'];
                            }
                            if(isset($category_xml['thumb']) && self::isImage((string)$category_xml['thumb']))
                            {
                                foreach($languages as $language)
                                {
                                    $category->thumb[$language['id_lang']] =(string)$category_xml['thumb'];
                                }
                            }
                            $category->date_add = Validate::isDate((string)$category_xml['datetime_added']) ? (string)$category_xml['datetime_added'] :date('Y-m-d H:i:s');
                            $category->date_upd = Validate::isDate((string)$category_xml['datetime_modified']) ? (string)$category_xml['datetime_modified'] : date('Y-m-d H:i:s');;
                            $category->added_by = (int)Context::getContext()->employee->id;
                            $category->modified_by = (int)Context::getContext()->employee->id;
                            $category->id_shop = Context::getContext()->shop->id;
                            $languageCategoryImported=array();
                            if($category_xml->categorylanguage)
                            {
                                foreach($category_xml->categorylanguage as $categorylanguage)
                                {
                                    if((string)$categorylanguage['iso_code'])
                                    {
                                        $id_lang= Language::getIdByIso((string)$categorylanguage['iso_code']);
                                        if(isset($categorylanguage['default']) && (int)$categorylanguage['default'])
                                            $categoryLanguageDefault = $categorylanguage;
                                        if($id_lang)
                                        {
                                            $category->title[$id_lang] = Validate::isCleanHtml((string)$categorylanguage->title) ? (string)$categorylanguage->title:'';
                                            $category->meta_title[$id_lang] = Validate::isCleanHtml((string)$categorylanguage->meta_title) ? (string)$categorylanguage->meta_title:'';
                                            $category->url_alias[$id_lang] = Validate::isLinkRewrite((string)$categorylanguage->url_alias) ? (string)$categorylanguage->url_alias:'';
                                            $category->description[$id_lang] = Validate::isCleanHtml((string)$categorylanguage->description,true) ? (string)$categorylanguage->description:'';
                                            $category->meta_description[$id_lang] = Validate::isCleanHtml((string)$categorylanguage->meta_description) ? (string)$categorylanguage->meta_description :'';
                                            $category->meta_keywords[$id_lang] = Validate::isCleanHtml((string)$categorylanguage->meta_keywords) ? (string)$categorylanguage->meta_keywords:'';
                                            if(isset($categorylanguage->image) && self::isImage((string)$categorylanguage->image))
                                                $category->image[$id_lang] = (string)$categorylanguage->image;
                                            if(isset($categorylanguage->thumb) && self::isImage((string)$categorylanguage->thumb))
                                                $category->thumb[$id_lang] = (string)$categorylanguage->thumb;
                                            $languageCategoryImported[]=$id_lang;
                                        }
                                    }
                                }
                            }
                            if(isset($categoryLanguageDefault))
                            {
                                foreach($languages as $lang)
                                {
                                    if(!in_array($lang['id_lang'],$languageCategoryImported))
                                    {
                                        $category->title[$lang['id_lang']] = Validate::isCleanHtml((string)$categoryLanguageDefault->title) ? (string)$categoryLanguageDefault->title:'';
                                        $category->meta_title[$lang['id_lang']] = Validate::isCleanHtml((string)$categoryLanguageDefault->meta_title) ? (string)$categoryLanguageDefault->meta_title:'';
                                        $category->url_alias[$lang['id_lang']] = Validate::isLinkRewrite((string)$categoryLanguageDefault->url_alias) ? (string)$categoryLanguageDefault->url_alias :'';
                                        $category->description[$lang['id_lang']] = Validate::isCleanHtml((string)$categoryLanguageDefault->description,true) ? (string)$categoryLanguageDefault->description :'';
                                        $category->meta_description[$lang['id_lang']] = Validate::isCleanHtml((string)$categoryLanguageDefault->meta_description) ? (string)$categoryLanguageDefault->meta_description :'';
                                        $category->meta_keywords[$lang['id_lang']] = Validate::isCleanHtml((string)$categoryLanguageDefault->meta_keywords) ? (string)$categoryLanguageDefault->meta_keywords:'';
                                        if(isset($categoryLanguageDefault->image) && self::isImage((string)$categoryLanguageDefault->image))
                                            $category->image[$lang['id_lang']] = (string)$categoryLanguageDefault->image;
                                        if(isset($categoryLanguageDefault->thumb) && self::isImage((string)$categoryLanguageDefault->thumb))
                                            $category->thumb[$lang['id_lang']] = (string)$categoryLanguageDefault->thumb;
                                    }
                                }
                            }
                            if($category->save())
                            {
                                if($category->image)
                                {
                                    foreach($category->image as $id_lang=> $image)
                                    {
                                        if($image)
                                        {
                                            if(file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$image))
                                            {
                                                $category->image[$id_lang] = time().$image;
                                                $category->update();
                                            }
                                            if($image && file_exists(_ETS_BLOG_CACHE_DIR_.'img/category/'.$image))
                                            {
                                                copy(_ETS_BLOG_CACHE_DIR_.'img/category/'.$image,_PS_ETS_BLOG_IMG_DIR_.'category/'.$category->image[$id_lang]);
                                                if(isset($oldImages[$id_lang]) && $oldImages[$id_lang] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$oldImages[$id_lang]))
                                                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'category/'.$oldImages[$id_lang]);

                                            }
                                        }
                                    }
                                }
                                if($category->thumb)
                                {
                                    foreach($category->thumb as $id_lang=> $thumb)
                                    {
                                        if($thumb)
                                        {
                                            if(file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/thumb/'.$thumb))
                                            {
                                                $category->thumb[$id_lang] = time().$thumb;
                                                $category->update();
                                            }
                                            if($thumb && file_exists(_ETS_BLOG_CACHE_DIR_.'img/category/thumb/'.$thumb))
                                            {
                                                copy(_ETS_BLOG_CACHE_DIR_.'img/category/thumb/'.$thumb,_PS_ETS_BLOG_IMG_DIR_.'category/thumb/'.$category->thumb[$id_lang]);
                                                if(isset($oldThumbs[$id_lang]) && $oldThumbs[$id_lang] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/thumb/'.$oldThumbs[$id_lang]))
                                                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'category/thumb/'.$oldThumbs[$id_lang]);
                                            }
                                        }

                                    }
                                }

                                $categories[(int)$category_xml['id_category']] = $category->id;
                            }
                        }
                    }
                    if(isset($xml->post) && $xml->post)
                    {
                        foreach ($xml->post as $post_xml)
                        {
                            $oldImages = array();
                            $oldThumbs = array();
                            if($importoverride && isset($post_xml['id_post']) && ($post = new Ets_blog_post((int)$post_xml['id_post'])) && Validate::isLoadedObject($post) && $post->id_shop == Context::getContext()->shop->id)
                            {
                                $oldImages = $post->image;
                                $oldThumbs = $post->thumb;
                            }
                            else
                            {
                                $post = new Ets_blog_post();
                                $post->sort_order =1+ (int)Db::getInstance()->getValue('SELECT MAX(sort_order) FROM `'._DB_PREFIX_.'ets_blog_post` WHERE id_shop='.(int)$this->context->shop->id);
                            }
                            $post->enabled = (int)$post_xml['enabled'];
                            $post->date_upd = Validate::isDate((string)$post_xml['datetime_added']) ? (string)$post_xml['datetime_added'] :date('Y-m-d H:i:s');
                            $post->date_upd = Validate::isDate((string)$post_xml['datetime_modified']) ? (string)$post_xml['datetime_modified']: date('Y-m-d H:i:s');
                            $post->id_shop = Context::getContext()->employee->id;
                            if($keepauthorid && isset($post_xml['added_by']) && (int)$post_xml['added_by'])
                            {
                                if(Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'employee` WHERE id_employee='.(int)$post_xml['added_by']))
                                {
                                    $ok=true;
                                }
                                else
                                    $ok=false;
                                if($ok)
                                {
                                    $post->added_by = (int)$post_xml['added_by'];
                                    $post->modified_by = (int)$post_xml['modified_by'];
                                }
                                else
                                {
                                    $post->added_by = (int)Context::getContext()->employee->id;
                                    $post->modified_by = (int)Context::getContext()->employee->id;
                                }
                            }
                            else
                            {
                                $post->added_by = (int)Context::getContext()->employee->id;
                                $post->modified_by = (int)Context::getContext()->employee->id;
                            }
                            if(isset($post_xml['thumb']) && self::isImage((string)$post_xml['thumb']))
                            {
                                foreach($languages as $language)
                                {
                                    $post->thumb[$language['id_lang']] = (string)$post_xml['thumb'];
                                }
                            }
                            if(isset($post_xml['image']) && self::isImage((string)$post_xml['image']))
                            {
                                foreach($languages as $language)
                                    $post->image[$language['id_lang']] = (string)$post_xml['image'];
                            }
                            if(isset($post_xml['id_category_default']))
                                $post->id_category_default = isset($categories[(int)$post_xml['id_category_default']])? $categories[(int)$post_xml['id_category_default']]:0;
                            $languagePostImported = array();
                            if($post_xml->language)
                                foreach($post_xml->language as $language)
                                {
                                    if((string)$language['iso_code'])
                                    {
                                        $id_lang = Language::getIdByIso((string)$language['iso_code']);
                                        if(isset($language['default']) && (int)$language['default'])
                                            $languagePostDefault=$language;
                                        if($id_lang)
                                        {
                                            $post->title[$id_lang] = Validate::isCleanHtml((string)$language->title) ? (string)$language->title :'';
                                            $post->url_alias[$id_lang] = Validate::isLinkRewrite((string)$language->url_alias) ? (string)$language->url_alias : '';
                                            $post->short_description[$id_lang] = Validate::isCleanHtml((string)$language->short_description,true) ? (string)$language->short_description :'';
                                            $post->description[$id_lang] = Validate::isCleanHtml((string)$language->description,true) ? (string)$language->description : '';
                                            $post->meta_description[$id_lang] = Validate::isCleanHtml((string)$language->meta_description) ? (string)$language->meta_description :'';
                                            $post->meta_keywords[$id_lang] = Validate::isCleanHtml((string)$language->meta_keywords) ? (string)$language->meta_keywords :'';
                                            if(isset($language->image) && self::isImage((string)$language->image) )
                                                $post->image[$id_lang] = (string)$language->image;
                                            if(isset($language->thumb) && self::isImage((string)$language->thumb))
                                                $post->thumb[$id_lang] = (string)$language->thumb;
                                            $languagePostImported[]=$id_lang;
                                        }
                                    }
                                }
                            if(isset($languagePostDefault))
                            {
                                foreach($languages as $lang)
                                {
                                    if(!in_array($lang['id_lang'],$languagePostImported))
                                    {
                                        $post->title[$lang['id_lang']] = Validate::isCleanHtml((string)$languagePostDefault->title) ? (string)$languagePostDefault->title :'';
                                        $post->meta_title[$lang['id_lang']] = Validate::isCleanHtml((string)$languagePostDefault->meta_title) ? (string)$languagePostDefault->meta_title: '';
                                        $post->url_alias[$lang['id_lang']] = Validate::isLinkRewrite((string)$languagePostDefault->url_alias) ? (string)$languagePostDefault->url_alias: "";
                                        $post->short_description[$lang['id_lang']] = Validate::isCleanHtml((string)$languagePostDefault->short_description,true) ? (string)$languagePostDefault->short_description :'';
                                        $post->description[$lang['id_lang']] = Validate::isCleanHtml((string)$languagePostDefault->description,true) ? (string)$languagePostDefault->description : '';
                                        $post->meta_description[$lang['id_lang']] = Validate::isCleanHtml((string)$languagePostDefault->meta_description) ? (string)$languagePostDefault->meta_description :'';
                                        $post->meta_keywords[$lang['id_lang']] = Validate::isCleanHtml((string)$languagePostDefault->meta_keywords) ? (string)$languagePostDefault->meta_keywords :'';
                                        if(isset($languagePostDefault->image) && self::isImage((string)$languagePostDefault->image))
                                            $post->image[$lang['id_lang']] = (string)$languagePostDefault->image;
                                        if(isset($languagePostDefault->thumb) && self::isImage((string)$languagePostDefault->thumb) )
                                            $post->thumb[$lang['id_lang']] = (string)$languagePostDefault->thumb;
                                    }
                                }
                            }
                            if($post->save())
                            {
                                if($post->image)
                                {
                                    foreach($post->image as $id_lang=>$image)
                                    {
                                        if($image)
                                        {
                                            if(file_exists(_PS_ETS_BLOG_IMG_DIR_.'post/'.$image))
                                            {
                                                $post->image[$id_lang] = time().$image;
                                                $post->update();
                                            }
                                            if($image && file_exists(_ETS_BLOG_CACHE_DIR_.'img/post/'.$image))
                                            {
                                                copy(_ETS_BLOG_CACHE_DIR_.'img/post/'.$image,_PS_ETS_BLOG_IMG_DIR_.'post/'.$post->image[$id_lang]);
                                                if(isset($oldImages[$id_lang]) && $oldImages[$id_lang] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'post/'.$oldImages[$id_lang]))
                                                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'post/'.$oldImages[$id_lang]);
                                            }
                                        }
                                    }
                                }
                                if($post->thumb)
                                {
                                    foreach($post->thumb as $id_lang=>$thumb)
                                    {
                                        if($thumb)
                                        {
                                            if(file_exists(_PS_ETS_BLOG_IMG_DIR_.'post/'.$thumb))
                                            {
                                                $post->thumb[$id_lang] = time().$thumb;
                                                $post->update();
                                            }
                                            if($thumb && file_exists(_ETS_BLOG_CACHE_DIR_.'img/post/'.$thumb))
                                            {
                                                copy(_ETS_BLOG_CACHE_DIR_.'img/post/'.$thumb,_PS_ETS_BLOG_IMG_DIR_.'post/'.$post->thumb[$id_lang]);
                                                if(isset($oldThumbs[$id_lang]) && $oldThumbs[$id_lang] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'post/'.$oldThumbs[$id_lang]))
                                                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'post/'.$oldThumbs[$id_lang]);

                                            }
                                            elseif($thumb && file_exists(_ETS_BLOG_CACHE_DIR_.'img/post/thumb/'.$thumb))
                                            {
                                                copy(_ETS_BLOG_CACHE_DIR_.'img/post/thumb/'.$thumb,_PS_ETS_BLOG_IMG_DIR_.'post/'.$post->thumb[$id_lang]);
                                                if(isset($oldThumbs[$id_lang]) && $oldThumbs[$id_lang] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'post/'.$oldThumbs[$id_lang]))
                                                    @unlink(_PS_ETS_BLOG_IMG_DIR_.'post/'.$oldThumbs[$id_lang]);
                                            }
                                        }

                                    }
                                }
                                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_blog_post_category` WHERE id_post='.(int)$post->id);
                                if($post->id_category_default)
                                {
                                    if(!Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_blog_post_category` WHERE id_category="'.(int)$post->id_category_default.'" AND id_post="'.(int)$post->id.'"'))
                                    {
                                        Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'ets_blog_post_category` (id_post,id_category,position) values("'.(int)$post->id.'","'.(int)$post->id_category_default.'","1")');
                                    }
                                }
                                if(isset($post_xml->category) && $post_xml->category)
                                {
                                    foreach($post_xml->category as $category_xml)
                                    {
                                        $id_category = isset($categories[(int)$category_xml['id_category']])? $categories[(int)$category_xml['id_category']]:0;
                                        if($id_category && !Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'ets_blog_post_category` WHERE id_category="'.(int)$id_category.'" AND id_post="'.(int)$post->id.'"'))
                                        {
                                            $position = 1+ (int)Db::getInstance()->getValue('SELECT MAX(position) FROM `'._DB_PREFIX_.'ets_blog_post_category` WHERE id_category='.(int)$id_category);
                                            Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'ets_blog_post_category` (id_post,id_category,position) values("'.(int)$post->id.'","'.(int)$id_category.'","'.(int)$position.'")');
                                            if(!$post->id_category_default)
                                            {
                                                $post->id_category_default = $id_category;
                                                $post->update();
                                            }
                                        }
                                    }
                                }
                                if(in_array('posts_comments',$data_imports))
                                {
                                    if($importoverride)
                                    {
                                        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ets_blog_comment` WHERE id_post='.(int)$post->id);
                                    }
                                    if(isset($post_xml->comment) && $post_xml->comment)
                                    {
                                        foreach($post_xml->comment as $comment_xml)
                                        {
                                            $comment= new Ets_blog_comment();
                                            if($keepcommenter)
                                                $comment->id_user= (int)$comment_xml['id_user'];
                                            else
                                                $comment->id_user=0;
                                            $comment->name = Validate::isName((string)$comment_xml['name']) ? (string)$comment_xml['name'] :'';
                                            $comment->email = Validate::isEmail((string)$comment_xml['email']) ? (string)$comment_xml['email'] :'';
                                            $comment->id_post=(int)$post->id;
                                            $comment->subject = Validate::isCleanHtml((string)$comment_xml['subject']) ? (string)$comment_xml['subject'] :'';
                                            $comment->comment = Validate::isCleanHtml((string)$comment_xml->comment_text) ? (string)$comment_xml->comment_text :'';
                                            $comment->reply = Validate::isCleanHtml((string)$comment_xml->reply) ? (string)$comment_xml->reply :'';
                                            $comment->replied_by = (int)Context::getContext()->employee->id;
                                            $comment->rating = (int)$comment_xml['rating'];
                                            $comment->approved = (int)$comment_xml['approved'];
                                            $comment->date_add = Validate::isDate((string)$comment_xml['datetime_added']) ? (string)$comment_xml['datetime_added'] : date('Y-m-d H:i:s');
                                            $comment->reported = (int)$comment_xml['reported'];
                                            if($comment->subject && $comment->name && $comment->comment)
                                            {
                                                $comment->save();
                                                if($comment->id && isset($comment_xml->reply) && $comment_xml->reply)
                                                {
                                                    foreach($comment_xml->reply as $reply_xml)
                                                    {
                                                        $name = Validate::isName((string)$reply_xml['name']) ? (string)$reply_xml['name'] :'';
                                                        $email = Validate::isEmail((string)$reply_xml['email']) ? (string)$reply_xml['email'] :'';
                                                        $reply_text = Validate::isCleanHtml((string)$reply_xml->reply_text) ? (string)$reply_xml->reply_text :'';
                                                        $date_add = Validate::isDate((string)$reply_xml['datetime_added']) ? (string)$reply_xml['datetime_added']: date('Y-m-d H:i:s');
                                                        $date_upd = Validate::isDate((string)$reply_xml['datetime_updated']) ? (string)$reply_xml['datetime_updated'] : date('Y-m-d H:i:s');
                                                        $sql= "INSERT INTO `"._DB_PREFIX_."ets_blog_reply`(id_comment,id_user,name,email,reply,id_employee,approved,date_add,date_upd) values('".(int)$comment->id."', '".(int)$reply_xml['id_user']."','".pSQL($name)."','".pSQL($email)."','".pSQL($reply_text)."','".(int)$reply_xml['id_employee']."','".(int)$reply_xml['approved']."','".pSQL($date_add)."','".pSQL($date_upd)."')";
                                                        Db::getInstance()->execute($sql);
                                                    }
                                                }
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if(in_array('module_configuration',$data_imports))
                {
                    if(isset($xml->configuration) && $config_xml = $xml->configuration)
                    {
                        $this->importXmlConfiguration($this->defines->getConfigInputs(),$config_xml);
                    }
                }
            }
        }
    }
    public function importXmlConfiguration($configs,$xml)
    {
        $languages = Language::getLanguages(false);
        foreach($configs as $config)
        {
            $key = $config['name'];
            if(isset($config['lang']) && $config['lang'])
            {
                $values = array();
                $xmlKey = $xml->{$key};
                $default= '';
                $languageimporteds= array();
                if($xmlKey->language)
                {
                    foreach($xmlKey->language as $language_xml)
                    {
                        if((int)$language_xml['default'])
                            $default= (string)$language_xml->content;
                        if($id_lang= Language::getIdByIso((string)$language_xml['iso_code']))
                        {
                            $languageimporteds[]=$id_lang;
                            $values[$id_lang] = (string)$language_xml->content;
                        }
                    }
                }
                foreach($languages as $lang)
                {
                    if(!in_array($lang['id_lang'],$languageimporteds))
                    {
                        $values[$lang['id_lang']] = $default;
                    }
                }
                Configuration::updateValue($key, $values);
            }
            else
            {
                if(isset($xml->{$key}))
                {
                    Configuration::updateValue($key,(string)$xml->{$key});
                }
            }

        }
    }
    public function removeFolderCache($forder)
    {
        $files = glob($forder.'/*');
        foreach($files as $file){
            if(is_file($file)){
                if(file_exists($file))
                    @unlink($file);
            }
            else
                $this->removeFolderCache($file);
        }
        rmdir($forder);
    }
    public function processImport($zipfile = false,$params = array())
    {
        $errors = array();
        if(isset($params['data_import']) && $params['data_import'])
        {
            if(!$zipfile)
            {
                $savePath = _ETS_BLOG_CACHE_DIR_;
                if(@file_exists($savePath.'ets_blog.data.zip'))
                    @unlink($savePath.'ets_blog.data.zip');
                $uploader = new Uploader('blogdata');
                $uploader->setMaxSize(1048576000);
                $uploader->setAcceptTypes(array('zip'));
                $uploader->setSavePath($savePath);
                $file = $uploader->process('ets_blog.data.zip');
                if ($file[0]['error'] === 0) {
                    if (!Tools::ZipTest($savePath.'ets_blog.data.zip'))
                        $errors[] = $this->l('Zip file seems to be broken');
                } else {
                    $errors[] = $file[0]['error'];
                }
                $extractUrl = $savePath.'ets_blog.data.zip';
            }
            else
                $extractUrl = $zipfile;
            if(!@file_exists($extractUrl))
                $errors[] = $this->l('Zip file doesn\'t exist');
            if(!$errors)
            {
                $zip = new ZipArchive();
                if($zip->open($extractUrl) === true)
                {
                    if ($zip->locateName('blog-data.xml') === false)
                    {
                        $errors[] = $this->l('blog-data.xml doesn\'t exist');
                        if($extractUrl && file_exists($extractUrl) && !$zipfile)
                        {

                            @unlink($extractUrl);
                        }
                    }
                }
                else
                    $errors[] = $this->l('Cannot open zip file. It might be broken or damaged');
            }
            if(!$errors)
            {
                if(!Tools::ZipExtract($extractUrl, _ETS_BLOG_CACHE_DIR_))
                    $errors[] = $this->l('Cannot extract zip data');
                if(!@file_exists(_ETS_BLOG_CACHE_DIR_.'blog-data.xml'))
                    $errors[] = $this->l('Neither blog-data.xml exist');
            }
            if(!$errors)
            {
                if(@file_exists(_ETS_BLOG_CACHE_DIR_.'blog-data.xml'))
                {
                    $this->importData(_ETS_BLOG_CACHE_DIR_.'blog-data.xml',$params);
                    @unlink(_ETS_BLOG_CACHE_DIR_.'blog-data.xml');
                    $this->removeFolderCache(_ETS_BLOG_CACHE_DIR_.'img');
                }
                $zip->close();
                if(@file_exists($extractUrl))
                    @unlink($extractUrl);
            }
        }
        else
        {
            $errors[]= $this->l('Data for import is null');
        }
        return $errors;
    }
}