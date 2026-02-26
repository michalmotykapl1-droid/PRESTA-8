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
class Ets_blog_obj extends ObjectModel 
{
    public function getFieldVals()
    {
        if(!$this->id)
            return array();
        $vals = array();
        $this->fields = $this->getListFields();
        foreach($this->fields['configs'] as $key => $config)
        {
            if(property_exists($this,$key))
            {
                if(isset($config['lang'])&&$config['lang'])
                {
                    $val_lang= $this->$key;
                    $vals[$key]=$val_lang[Context::getContext()->language->id];

                }
                else
                    $vals[$key] = $this->$key;
                    
            }
                
        }
        $vals['id_'.$this->fields['form']['name']] = (int)$this->id;
        unset($config);
        return $vals;
    }
    public function getFieldLangVals()
    {
        $vals = array();
        $this->fields = $this->getListFields();
        foreach($this->fields['configs'] as $key => $config)
        {
            if(property_exists($this,$key))
            {
                if(isset($config['lang'])&&$config['lang'])
                {
                    $val_lang= $this->$key;
                    $vals[$key]=$val_lang[Context::getContext()->language->id];
                    $languages = Language::getLanguages();
                    foreach($languages as $language)
                    {
                        $id_lang = $language['id_lang'];
                        $vals[$key.'_'.$id_lang] = $val_lang[$id_lang];
                    }

                }
                else
                    $vals[$key] = $this->$key;
                    
            }
                
        }
        $vals['id_'.$this->fields['form']['name']] = (int)$this->id;
        unset($config);
        return $vals;
    }
    public function renderForm()
    {
        $this->fields = $this->getListFields();
        $helper = new HelperForm();
        $helper->module = new Ets_blog();
        $configs = $this->fields['configs'];
        $fields_form = array();
        $fields_form['form'] = $this->fields['form'];               
        if($configs)
        {
            foreach($configs as $key => $config)
            {                
                if(isset($config['type']) && in_array($config['type'],array('sort_order')))
                    continue;
                $confFields = array(
                    'name' => $key,
                    'blogCategoryotpionsHtml' => isset($config['blogCategoryotpionsHtml']) ? $config['blogCategoryotpionsHtml']:'',
                    'tab' => isset($config['tab']) ? $config['tab'] :'',
                    'type' => $config['type'],
                    'form_group_class' => isset($config['form_group_class']) ? $config['form_group_class']:'',
                    'class'=>isset($config['class'])?$config['class']:'',
                    'label' => $config['label'],
                    'desc' => isset($config['desc']) ? $config['desc'] : false,
                    'required' => isset($config['required']) && $config['required'] ? true : false,
                    'readonly' => isset($config['readonly']) ? $config['readonly'] : false,
                    'autoload_rte' => isset($config['autoload_rte']) && $config['autoload_rte'] ? true : false,
                    'options' => isset($config['options']) && $config['options'] ? $config['options'] : array(),
                    'suffix' => isset($config['suffix']) && $config['suffix'] ? $config['suffix']  : false,
                    'values' => isset($config['values']) ? $config['values'] : false,
                    'lang' => isset($config['lang']) ? $config['lang'] : false,
                    'showRequired' => isset($config['showRequired']) && $config['showRequired'],
                    'hide_delete' => isset($config['hide_delete']) ? $config['hide_delete'] : false,
                    'placeholder' => isset($config['placeholder']) ? $config['placeholder'] : false,
                    'display_img' => $this->id && isset($config['type']) && $config['type']=='file' && $this->$key!='' && @file_exists(_PS_ETS_BLOG_IMG_DIR_.$this->$key) ? _PS_ETS_BLOG_IMG_.$this->$key : false,
                    'img_del_link' => $this->id && isset($config['type']) && $config['type']=='file' && $this->$key!='' && @file_exists(_PS_ETS_BLOG_IMG_DIR_.$this->$key) ? $this->context->link->getAdminBaseLink('AdminModules').'&configure=ets_prettymenu&deleteimage='.$key.'&itemId='.(isset($this->id)?$this->id:'0').'&prmn_object=PRMN_'.Tools::ucfirst($fields_form['form']['name']) : false,
                    'min' => isset($config['min']) ? $config['min'] : false,
                    'max' => isset($config['max']) ? $config['max'] : false, 
                    'data_suffix' => isset($config['data_suffix']) ? $config['data_suffix'] :'',
                    'data_suffixs' => isset($config['data_suffixs']) ? $config['data_suffixs'] :'',
                    'multiple' => isset($config['multiple']) ? $config['multiple']: false,
                    'html_content' => isset($config['html_content']) ? $config['html_content']:false,
                    'categories' => isset($config['categories']) ? $config['categories']:false,
                    'selected_categories' => isset($config['selected_categories']) ? $config['selected_categories']:false,
                );
                if(isset($config['tree']) && $config['tree'])
                {
                    $confFields['tree'] = $config['tree'];
                    if(isset($config['tree']['use_checkbox']) && $config['tree']['use_checkbox'])
                        $confFields['tree']['selected_categories'] = explode(',',$this->$key);
                    else
                        $confFields['tree']['selected_categories'] = array($this->$key);
                }                    
                if(!$confFields['suffix'])
                    unset($confFields['suffix']);                
                $fields_form['form']['input'][] = $confFields;
            }
        }        
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();		
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'save_'.$this->fields['form']['name'];
		$helper->currentIndex ='';
		$language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $fields = array();        
        $languages = Language::getLanguages(false);
        //$helper->override_folder = '/';        
        if($configs)
        {
            foreach($configs as $key => $config)
            {
                if($config['type']=='checkbox' || (isset($config['multiple']) && $config['multiple']))
                    $fields[$key] = Tools::getValue($key,$this->id ? explode(',',$this->$key) : (isset($config['default']) && $config['default'] ? $config['default'] : array()));
                elseif(isset($config['lang']) && $config['lang'])
                {                    
                    foreach($languages as $l)
                    {
                        $temp = $this->$key;
                        $fields[$key][$l['id_lang']] = Tools::getValue($key.'_'.$l['id_lang'],$this->id ? $temp[$l['id_lang']] : (isset($config['default']) && $config['default'] ? $config['default'] : null));
                    }
                }
                elseif(!isset($config['tree']))
                    $fields[$key] = Tools::getValue($key,$this->id ? $this->$key : (isset($config['default']) && $config['default'] ? $config['default'] : null));                            
            }
        }
        $helper->tpl_vars = array(
			'base_url' => Context::getContext()->shop->getBaseURL(),
			'language' => array(
				'id_lang' => $language->id,
				'iso_code' => $language->iso_code
			),
			'fields_value' => $fields,
			'languages' => Context::getContext()->controller->getLanguages(),
			'id_language' => Context::getContext()->language->id, 
            'key_name' => 'id_'.$fields_form['form']['name'],
            'item_id' => $this->id,  
            'tab_category' => isset($fields_form['form']['tab_category']) ? $fields_form['form']['tab_category'] : false,
            'tab_post' => isset($fields_form['form']['tab_post']) ? $fields_form['form']['tab_post'] : false,
            'img_del_link' => isset($fields_form['form']['img_del_link']) ? $fields_form['form']['img_del_link'] : false,
            'thumb_del_link' => isset($fields_form['form']['thumb_del_link']) ? $fields_form['form']['thumb_del_link'] : false,
            'blog_object' => 'Ets_blog_'.$fields_form['form']['name'],
            'list_item' => true,
            'image_baseurl' => _PS_ETS_BLOG_IMG_.$fields_form['form']['name'].'/',                 
        );        
        return str_replace(array('id="ets_blog_form"','id="fieldset_0"'),'',$helper->generateForm(array($fields_form)));	
    }
    public function saveData($datas = array())
    {
        if(!$datas)
            $datas = Tools::getAllValues();
        $this->fields = $this->getListFields();
        $errors = array();
        $success = '';
        $languages = Language::getLanguages(false);
        $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
        $configs = $this->fields['configs'];       
        if($configs)
        {
            foreach($configs as $key => $config)
            {
                $value_key = isset($datas[$key]) ? $datas[$key] :'';
                if(isset($config['lang']) && $config['lang'])
                {
                    $key_value_lang_default = isset($datas[$key.'_'.$id_lang_default]) ? $datas[$key.'_'.$id_lang_default]:'';
                    if(isset($config['required']) && $config['required'] && $config['type']!='switch' && $key_value_lang_default == '')
                    {
                        $errors[] = sprintf($this->l('%s is required','Ets_blog_obj'),$config['label']);
                    }
                    elseif($key_value_lang_default && !Validate::isCleanHtml($key_value_lang_default,true))
                        $errors[] = sprintf($this->l('%s is not valid','Ets_blog_obj'),$config['label']);                   
                }
                else
                {
                    if($config['type']!='file' && $config['type']!='file_lang')
                    {
                        if(isset($config['required']) && $config['required'] && $config['type']!='switch' && !is_array($value_key) && trim($value_key) == '')
                        {
                            $errors[] = sprintf($this->l('%s is required','Ets_blog_obj'),$config['label']);
                        }
                        elseif(!is_array($value_key) && isset($config['validate']) && method_exists('Validate',$config['validate']))
                        {
                            $validate = $config['validate'];
                            if(!Validate::$validate(trim($value_key)))
                                $errors[] = sprintf($this->l('%s is not valid','Ets_blog_obj'),$config['label']);
                            unset($validate);
                        }
                        elseif(!is_array($value_key)  && !Validate::isCleanHtml(trim($value_key,true)))
                        {
                            $errors[] = sprintf($this->l('%s is required','Ets_blog_obj'),$config['label']);
                        } 
                    }                          
                }                    
            }
        }            
        //Custom validation
        if(method_exists($this,'validateCustom'))
            $this->validateCustom($errors);
        if(!$errors)
        {    
            $new_files = array();
            $old_files = array();
            if($configs)
            {
                foreach($configs as $key => $config)
                {
                    $value_key = isset($datas[$key]) ? $datas[$key] :'';
                    if(isset($config['lang']) && $config['lang'])
                    {
                        $valules = array();
                        $key_value_lang_default = isset($datas[$key.'_'.$id_lang_default]) ? $datas[$key.'_'.$id_lang_default]:'';
                        foreach($languages as $lang)
                        {
                            $key_value_lang =  isset($datas[$key.'_'.$lang['id_lang']]) ? $datas[$key.'_'.$lang['id_lang']]:'';
                            if($config['type']=='switch')                                                           
                                $valules[$lang['id_lang']] = (int)$key_value_lang ? 1 : 0;                                
                            elseif(Validate::isCleanHtml($key_value_lang,true))
                                $valules[$lang['id_lang']] = $key_value_lang ? : (Validate::isCleanHtml($key_value_lang_default) ? $key_value_lang_default:'');
                        }
                        $this->$key = $valules;
                    }
                    elseif($config['type']=='switch')
                    {                           
                        $this->$key = (int)$value_key ? 1 : 0;                                                      
                    }
                    elseif($config['type']=='file')
                    {
                        if(isset($_FILES[$key]['tmp_name']) && isset($_FILES[$key]['name']) && $_FILES[$key]['name'])
                        {
                            $_FILES[$key]['name'] = str_replace(' ','_',$_FILES[$key]['name']);
                            $salt = Tools::substr(sha1(microtime()),0,10);
                            $type = Tools::strtolower(Tools::substr(strrchr($_FILES[$key]['name'], '.'), 1));
                            $imageName = @file_exists(_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.Tools::strtolower($_FILES[$key]['name']))|| Tools::strtolower($_FILES[$key]['name'])==$this->$key ? $salt.'-'.Tools::strtolower($_FILES[$key]['name']) : Tools::strtolower($_FILES[$key]['name']);
                            $fileName = _PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$this->fields['form']['name'].$imageName;  
                            $max_file_size = Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')*1024*1024;
                            if(!Validate::isFileName($_FILES[$key]['name']))
                                $errors[] =sprintf($this->l('%s is not valid','Ets_blog_obj'),$config['label']); 
                            elseif($_FILES[$key]['size'] > $max_file_size)
                                $errors[] = sprintf($this->l('%s file is too large','Ets_blog_obj'),$config['label']);          
                            elseif(file_exists($fileName))
                            {
                                $errors[] = sprintf($this->l('%s file existed','Ets_blog_obj'),$config['label']);
                            }
                            else
                            {                                    
                    			$imagesize = @getimagesize($_FILES[$key]['tmp_name']);                                    
                                if (!$errors && isset($_FILES[$key]) &&				
                    				!empty($_FILES[$key]['tmp_name']) &&
                    				!empty($imagesize) &&
                    				in_array($type, array('jpg', 'gif', 'jpeg', 'png'))
                    			)
                    			{
                    				$temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');    				
                    				if ($error = ImageManager::validateUpload($_FILES[$key]))
                    					$errors[] = $error;
                    				elseif (!$temp_name || !move_uploaded_file($_FILES[$key]['tmp_name'], $temp_name))
                    					$errors[] = sprintf($this->l('%s cannot upload file','Ets_blog_obj'),$config['label']);
                    				elseif (!ImageManager::resize($temp_name, $fileName, null, null, $type))
                    					$errors[] = sprintf($this->l('%s an error occurred during the image upload process','Ets_blog_obj'),$config['label']);
                    				if (isset($temp_name))
                    					@unlink($temp_name);
                                    if(!$errors)
                                    {
                                        if($this->$key!='')
                                        {
                                            $old_files[] = $this->$key;
                                        }  
                                        $this->$key = $imageName;
                                        $new_files[] = $imageName;
                                    }
                                }
                                else
                                    $errors[] = sprintf($this->l('%s file is not in the correct format, accepted formats: jpg, gif, jpeg, png','Ets_blog_obj'),$config['label']);
                            }
                        }
                        //End upload file                       
                    }
                    elseif($config['type']=='file_lang')
                    {
                        foreach($languages as $l)
                        {
                            $key_lang = $key.'_'.$l['id_lang'];
                            if(isset($_FILES[$key_lang]['tmp_name']) && isset($_FILES[$key_lang]['name']) && $_FILES[$key_lang]['name'])
                            {
                                $_FILES[$key_lang]['name'] = str_replace(' ','_',$_FILES[$key_lang]['name']);
                                $salt = Tools::substr(sha1(microtime()),0,10);
                                $type = Tools::strtolower(Tools::substr(strrchr($_FILES[$key_lang]['name'], '.'), 1));
                                $imageName = @file_exists(_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.Tools::strtolower($_FILES[$key_lang]['name']))|| Tools::strtolower($_FILES[$key_lang]['name'])==$this->$key ? $salt.'-'.Tools::strtolower($_FILES[$key_lang]['name']) : Tools::strtolower($_FILES[$key_lang]['name']);
                                $fileName = _PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$imageName;  
                                $max_file_size = Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE')*1024*1024;
                                if(!Validate::isFileName($_FILES[$key_lang]['name']))
                                    $errors[] =sprintf($this->l('%s is not valid','Ets_blog_obj'),$config['label']); 
                                elseif($_FILES[$key_lang]['size'] > $max_file_size)
                                    $errors[] = sprintf($this->l('%s file is too large','Ets_blog_obj'),$config['label']);          
                                elseif(file_exists($fileName))
                                {
                                    $errors[] = sprintf($this->l('%s file existed','Ets_blog_obj'),$config['label']);
                                }
                                else
                                {                                    
                        			$imagesize = @getimagesize($_FILES[$key_lang]['tmp_name']);                                    
                                    if (!$errors && isset($_FILES[$key_lang]) &&				
                        				!empty($_FILES[$key_lang]['tmp_name']) &&
                        				!empty($imagesize) &&
                        				in_array($type, array('jpg', 'gif', 'jpeg', 'png'))
                        			)
                        			{
                        				$temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');    				
                        				if ($error = ImageManager::validateUpload($_FILES[$key_lang]))
                        					$errors[] = $error;
                        				elseif (!$temp_name || !move_uploaded_file($_FILES[$key_lang]['tmp_name'], $temp_name))
                        					$errors[] = sprintf($this->l('%s cannot upload file','Ets_blog_obj'),$config['label']);
                        				elseif (!ImageManager::resize($temp_name, $fileName, null, null, $type))
                        					$errors[] = sprintf($this->l('%s an error occurred during the image upload process','Ets_blog_obj'),$config['label']);
                        				else
                                        {
                                            if(isset($this->$key[$l['id_lang']]) && $this->$key[$l['id_lang']]!='')
                                            {
                                                $old_file = $this->$key[$l['id_lang']];
                                            }
                                            else
                                                $old_file = '';
                                            $this->$key[$l['id_lang']] = $imageName;
                                            $new_files[] = $imageName;
                                            if($old_file && !in_array($old_file,$this->$key))
                                                $old_files[] = $old_file;
                                        }
                                        if (isset($temp_name))
                        					@unlink($temp_name);
                                        
                                    }
                                    else
                                        $errors[] = sprintf($this->l('%s file is not in the correct format, accepted formats: jpg, gif, jpeg, png','Ets_blog_obj'),$config['label']);
                                }
                            }
                        }
                        if(isset($config['required']) && $config['required'])
                        {
                            if(!isset($this->$key[$id_lang_default]) || !$this->$key[$id_lang_default])
                                $errors[] = sprintf($this->l('%s is required','Ets_blog_obj'),$config['label']);
                            else
                            {
                                foreach($languages as $l)
                                {
                                    if(!isset($this->$key[$l['id_lang']]) || !$this->$key[$l['id_lang']])
                                        $this->$key[$l['id_lang']] = $this->$key[$id_lang_default];
                                }
                            }
                        }
                        else
                        {
                            foreach($languages as $l)
                            {
                                if(!isset($this->$key[$l['id_lang']]) || !$this->$key[$l['id_lang']])
                                    $this->$key[$l['id_lang']] = isset($this->$key[$id_lang_default]) ? $this->$key[$id_lang_default]:'';
                            }
                        }
                    }                                                 
                    elseif(!is_array($value_key) && Validate::isCleanHtml($value_key))
                        $this->$key = trim($value_key);   
                    }
                }
        }     
        if (!count($errors))
        { 
           if($this->id)
           {
                if($this->update())
                {
                    if(method_exists($this,'addCategories'))
                        $this->addCategories();
                    if($old_files)
                    {
                        foreach($old_files as $old_file)
                            @unlink(_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$old_file);
                    }
                    $success = $this->l('Updated successfully','Ets_blog_obj');
                }
                else
                {
                    if($new_files)
                    {
                        foreach($new_files as $new_file)
                        {
                            @unlink(_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$new_file);
                        }
                    }
                    $errors[]= $this->l('Update failed','Ets_blog_obj');
                }
           }
           else
           {
                $this->id_shop = (int)Context::getContext()->shop->id;
                if($this->add())
                {
                    if(method_exists($this,'addCategories'))
                        $this->addCategories();
                    $success = $this->l('Added successfully','Ets_blog_obj');
                }
                else
                {
                    if($new_files)
                    {
                        foreach($new_files as $new_file)
                        {
                            @unlink(_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$new_file);
                        }
                    }
                    $errors[]= $this->l('Adding failed','Ets_blog_obj');
                }
           }                     
        } 
        return array('errors' =>$errors,'success' => $success);  
    }
    public function deleteObj()
    {        
        $errors = array();
        $success = array();
        $this->fields = $this->getListFields();
        $configs = $this->fields['configs'];
        $parent=isset($this->fields['form']['parent'])?$this->fields['form']['parent']:'1';
        $images = array();
        foreach($configs as $key => $config)
        {
            if($config['type']=='file' && $this->$key && @file_exists(_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$this->$key) ) // && !self::imageExits($this->$key,$this->id)
                $images[] = _PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$this->$key;
        }        
        if(!$this->delete())
            $errors[] = $this->l('An error occurred while deleting the data','Ets_blog_obj');
        else
        {
            foreach($images as $image)
                @unlink($image);
            $success[] = $this->l('Item deleted successfully','Ets_blog_obj');
            if(isset($configs['sort_order']) && $configs['sort_order'])
            {
                Db::getInstance()->execute("
                    UPDATE "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['name'])."
                    SET sort_order=sort_order-1 
                    WHERE sort_order>".(int)$this->sort_order." ".(isset($configs['sort_order']['order_group'][$parent]) && ($orderGroup = $configs['sort_order']['order_group'][$parent]) ? " AND ".pSQL($orderGroup)."=".(int)$this->$orderGroup : "")."
                ");
            }
            if($this->id && isset($this->fields['form']['connect_to2']) && $this->fields['form']['connect_to2'] 
                && ($subs = Db::getInstance()->executeS("SELECT id_".pSQL($this->fields['form']['connect_to2'])." FROM "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['connect_to2']). " WHERE id_".pSQL($this->fields['form']['name'])."=".(int)$this->id)))
            {
                foreach($subs as $sub)
                {
                    $className = 'Ets_blog_'.Tools::ucfirst(Tools::strtolower($this->fields['form']['connect_to2']));
                    if(class_exists($className))
                    {
                        $obj = new $className((int)$sub['id_'.$this->fields['form']['connect_to2']]);
                        $obj->deleteObj();
                    }                    
                }
            }
            if($this->id && isset($this->fields['form']['connect_to']) && $this->fields['form']['connect_to'] 
                && ($subs = Db::getInstance()->executeS("SELECT id_".pSQL($this->fields['form']['connect_to'])." FROM "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['connect_to']). " WHERE id_".pSQL($this->fields['form']['name'])."=".(int)$this->id)))
            {
                foreach($subs as $sub)
                {
                    $className = 'Ets_blog_'.Tools::ucfirst(Tools::strtolower($this->fields['form']['connect_to']));
                    if(class_exists($className))
                    {
                        $obj = new $className((int)$sub['id_'.$this->fields['form']['connect_to']]);
                        $obj->deleteObj();
                    }                    
                }
            }

        }            
        return array('errors' => $errors,'success' => $success);
    }
    public function duplicateItem($id_parent = false,$id_parent2=false)
    {
        $this->fields = $this->getListFields();
        $oldId = $this->id;
        $this->id = null;  
        if($id_parent && isset($this->fields['form']['parent']) && ($parent = 'id_'.$this->fields['form']['parent']) && property_exists($this,$parent))
            $this->$parent = $id_parent;
        if($id_parent2 && isset($this->fields['form']['parent2']) && ($parent2 = 'id_'.$this->fields['form']['parent2']) && property_exists($this,$parent2))
            $this->$parent2 = $id_parent2;
        if(property_exists($this,'sort_order'))
        {
            if(!isset($this->fields['form']['parent'])|| !isset($this->fields['configs']['sort_order']['order_group'][$this->fields['form']['parent']]) || isset($this->fields['configs']['sort_order']['order_group'][$this->fields['form']['parent']]) && !$this->fields['configs']['sort_order']['order_group'][$this->fields['form']['parent']])
                $this->sort_order = $this->maxVal('sort_order')+1;
            else
            {
                $tempName = $this->fields['configs']['sort_order']['order_group'][$this->fields['form']['parent']];
                $this->sort_order = $this->maxVal('sort_order',$tempName,(int)$this->$tempName)+1;
                $groupId = $this->$tempName;
            }  
            $oldOrder = $this->sort_order;              
        }
        if(property_exists($this,'image') && $this->image && file_exists(_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$this->image))
        {
            $salt = $this->maxVal('id_'.$this->fields['form']['name'])+1;
            $oldImage = _PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$this->image;
            $this->image = $salt.'_'.$this->image;            
        }
        if($this->add())
        {
            if(isset($oldImage) && $oldImage)
            {
                @copy($oldImage,_PS_ETS_BLOG_IMG_DIR_.$this->fields['form']['name'].'/'.$this->image);
            }
            if(isset($oldOrder) && $oldOrder)
                $this->updateOrder($oldId,isset($groupId) ? (int)$groupId : 0); 
            if(isset($this->fields['form']['connect_to']) && $this->fields['form']['connect_to']
                && ($subs = Db::getInstance()->executeS("SELECT id_".pSQL($this->fields['form']['connect_to'])." FROM "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['connect_to']). " WHERE id_".pSQL($this->fields['form']['name'])."=".(int)$oldId)))
            {
                foreach($subs as $sub)
                {
                    $className = 'Ets_blog_'.Tools::ucfirst(Tools::strtolower($this->fields['form']['connect_to']));
                    if(class_exists($className))
                    {
                        $obj = new $className((int)$sub['id_'.$this->fields['form']['connect_to']]);
                        $obj->duplicateItem($this->id);
                    }                    
                }
            }   
            return $this;
        }
        return false;
    }
    public function updateOrder($previousId = 0, $groupdId = 0,$parentObj='')
    {   
        $this->fields = $this->getListFields();
        $group = isset($this->fields['configs']['sort_order']['order_group'][$parentObj]) && $this->fields['configs']['sort_order']['order_group'][$parentObj] ? $this->fields['configs']['sort_order']['order_group'][$parentObj] : false;
        if(!$groupdId && $group)
            $groupdId = $this->$group;
        $oldOrder = $this->sort_order;
        if($group && $groupdId && property_exists($this,$group) && $this->$group != $groupdId)
        {            
            Db::getInstance()->execute("
                    UPDATE "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['name'])."
                    SET sort_order=sort_order-1 
                    WHERE sort_order>".(int)$this->sort_order." AND id_".pSQL($this->fields['form']['name'])."!=".(int)$this->id."
                          ".($group && $groupdId ? " AND ".pSQL($group)."=".(int)$this->$group : ""));
            $this->$group = $groupdId;
            $changeGroup = true;
        }
        else
            $changeGroup = false;                    
        if($previousId > 0)
        {
            $objName = 'Ets_blog_'.Tools::ucfirst($this->fields['form']['name']);
            $obj = new $objName($previousId);
            if($obj->sort_order > 0)
                $this->sort_order = $obj->sort_order+1;
            else
                $this->sort_order = 1;
        }
        else
            $this->sort_order = 1;
        if($this->update())
        {      
            Db::getInstance()->execute("
                    UPDATE "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['name'])."
                    SET sort_order=sort_order+1 
                    WHERE sort_order>=".(int)$this->sort_order." AND id_".pSQL($this->fields['form']['name'])."!=".(int)$this->id."
                          ".($group && $groupdId ? " AND ".pSQL($group)."=".(int)$this->$group : ""));
            
            if(!$changeGroup && $this->sort_order!=$oldOrder)
            {                
                
                $rs = Db::getInstance()->execute("
                        UPDATE "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['name'])."
                        SET sort_order=sort_order-1
                        WHERE sort_order>".($this->sort_order > $oldOrder ? (int)($oldOrder) : (int)($oldOrder+1)).($group && $groupdId ? " AND ".pSQL($group)."=".(int)$this->$group : ""));
                return $rs;
            } 
            return true;
        }               
        return false;       
    }
    public function update($null_value=false)
    {
        $ok = parent::update($null_value);
        return $ok;
    }  
    public function maxVal($key,$group = false, $groupval=0)
    {  
       return ($max = Db::getInstance()->getValue("SELECT max(".pSQL($key).") FROM "._DB_PREFIX_."ets_blog_".pSQL($this->fields['form']['name']).($group && ($groupval > 0) ? " WHERE ".pSQL($group)."=".(int)$groupval : ''))) ? (int)$max : 0;
    }
    public function getBlogCategoriesTree($id_root,$active=true,$id_lang=null,$id_category=0,$link=true)
    {
        if(is_null($id_lang))
            $id_lang = (int)Context::getContext()->language->id;
        $tree=array();
        if($id_root==0)
        {
            $cat = array(
                'id_category' => 0,
                'title' => $this->l('Root','Ets_blog_obj'),
            );            
            $children = $this->getChildrenBlogCategories($id_root, $active, $id_lang,$id_category);
            $temp = array();
            if($children)
            {
                foreach($children as &$child)
                {
                    $arg = $this->getBlogCategoriesTree($child['id_category'], $active, $id_lang,$id_category,$link);
                    if($arg && isset($arg[0]))
                    {
                        if($link)
                        {
                            $arg[0]['link'] = Module::getInstanceByName('ets_blog')->getLink('blog',array('id_category'=>$child['id_category']));
                        }
                        else
                        {
                            $arg[0]['link']='#';
                        }
                        if($child['thumb'] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$child['thumb']))
                            $arg[0]['thumb_link'] = '<img src="'._PS_ETS_BLOG_IMG_.'category/'.$child['thumb'].'" style="width:10px;"/>';
                        elseif($child['image'] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$child['image']))
                            $arg[0]['thumb_link'] = '<img src="'._PS_ETS_BLOG_IMG_.'category/'.$child['image'].'" style="width:10px;"/>';
                        $temp[] = $arg[0];
                    }
                        
                }                    
            }
            $cat['children'] = $temp;
            $tree[] = $cat;
        }
        else
        {
            $sql = "SELECT c.id_category, cl.title,cl.image
                FROM `"._DB_PREFIX_."ets_blog_category` c
                LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON c.id_category = cl.id_category AND cl.id_lang = ".(int)$id_lang."
                WHERE c.id_category = ".(int)$id_root." ".($active ? " AND  c.enabled = 1" : "")." GROUP BY c.id_category";
            if($category = Db::getInstance()->getRow($sql))
            {            
                $cat = array(
                                'id_category' => $id_root,
                                'title' => $category['title'],
                                'count_posts' =>Ets_blog_post::countPostsWithFilter(' AND pc.id_category="'.(int)$id_root.'" AND p.enabled=1'),
                            );  
                $children = $this->getChildrenBlogCategories($id_root, $active, $id_lang,$id_category);
                $temp = array();
                if($children)
                {
                    foreach($children as &$child)
                    {
                        $arg = $this->getBlogCategoriesTree($child['id_category'], $active, $id_lang,$id_category,$link);
                        if($arg && isset($arg[0]))
                        {
                            if($link)
                            {
                                $arg[0]['link'] = Module::getInstanceByName('ets_blog')->getLink('blog',array('id_category'=>$child['id_category']));
                                
                            }
                            else
                            {
                                $arg[0]['link'] ='#';
                            }
                            if($child['thumb'] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$child['thumb']))
                                $arg[0]['thumb_link'] = '<img src="'._PS_ETS_BLOG_IMG_.'category/'.$child['thumb'].'" style="width:10px;"/>';
                            elseif($child['image'] && file_exists(_PS_ETS_BLOG_IMG_DIR_.'category/'.$child['image']))
                                $arg[0]['thumb_link'] = '<img src="'._PS_ETS_BLOG_IMG_.'category/'.$child['image'].'" style="width:10px;"/>';
                            $temp[] = $arg[0];
                        }
                            
                    }                    
                }
                $cat['children'] = $temp;
                $tree[] = $cat;
            }
        }
        return $tree; 
   }
   public function getChildrenBlogCategories($id_root, $active=true, $id_lang=null,$id_category=0)
   {
        if(is_null($id_lang))
            $id_lang = (int)$this->context->language->id;
        $sql = "SELECT c.id_category, cl.title,cl.image,cl.thumb
                FROM `"._DB_PREFIX_."ets_blog_category` c
                LEFT JOIN `"._DB_PREFIX_."ets_blog_category_lang` cl ON c.id_category = cl.id_category AND cl.id_lang = ".(int)$id_lang."
                WHERE c.id_parent = ".(int)$id_root." ".($active ? " AND  c.enabled = 1" : "").($id_category?' AND c.id_category <'.(int)$id_category :'')." AND c.id_shop='".(int)Context::getContext()->shop->id."' GROUP BY c.id_category ORDER BY c.sort_order";
        return Db::getInstance()->executeS($sql);
   }
   
}