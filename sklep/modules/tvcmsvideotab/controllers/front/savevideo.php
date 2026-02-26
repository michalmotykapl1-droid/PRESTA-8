<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
class TvcmsVideoTabSaveVideoModuleFrontController extends ModuleFrontController
{
    public function run()
    {
        $ok = '';
        $id_lang_default = Configuration::get('PS_LANG_DEFAULT');
        $ob_lang_default = new Language($id_lang_default);
        $name_lang_default = $ob_lang_default->name;
        $id_shop = (int) Tools::getValue('id_shop');
        $name_shop = pSQL(Tools::getValue('name_shop'));
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $url = $_SERVER['SCRIPT_FILENAME'];
        $url = rtrim($url, 'index.php');
        $languages = Language::getLanguages();
        $type_video = pSQL(Tools::getValue('type_video'));
        $id_product = (int) Tools::getValue('id_product');
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'product_lang WHERE id_product="' . (int) $id_product . '"';
        $show = $db->ExecuteS($sql);

        $sql1 = 'SELECT * FROM ' . _DB_PREFIX_ . 'url_video WHERE id_product="' . (int) $id_product . '" ';
        $sql1 .= ' AND id_store="' . (int) $id_shop . '" AND id_lang="' . (int) $id_lang_default . '"';
        $data_video_default = $db->ExecuteS($sql1);
        $sql2 = 'SELECT * FROM ' . _DB_PREFIX_ . 'url_video WHERE id_product="' . (int) $id_product . '" AND id_store="' . (int) $id_shop . '"';
        $data_video_product = $db->ExecuteS($sql2);
        array_filter($data_video_product); // xoa phan tu rong trong mang
        $name_product = $show[0]['name'];
        $name_product = str_replace("'", "\'", $name_product);
        if (1 == $type_video) {
            $video_url_array = $_FILES['fileToUpload']['name'];
            foreach ($video_url_array as $key1 => $value1) {
                if (!empty($value1)) {
                    $videoFileType = pathinfo($value1, PATHINFO_EXTENSION);
                    $videoextension = Configuration::get('videoextension', null, '', $id_shop);
                    $pos = strpos($videoextension, $videoFileType);
                    if (false === $pos) {
                        $ok = '2';
                        echo $ok;
                        exit;
                    }
                }
            }
            $max_size = 5000000000000;
            $size = $_FILES['fileToUpload']['size'][$id_lang_default];
            if ($size > $max_size) {
                $ok = '1';
                echo $ok;
                exit;
            }
            $this->createIndexInFolder($id_product, $id_shop);
            // /// Product chua tung co du lieu
            if (empty($data_video_product)) {
                $video_upload_default = basename($_FILES['fileToUpload']['name'][$id_lang_default]);
                if ('' == $video_upload_default) {
                    $ok = 5;
                    echo $ok;
                    exit;
                }
                $url_video_tmp = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $video_upload_default;
                move_uploaded_file($_FILES['fileToUpload']['tmp_name'][$id_lang_default], $url_video_tmp);
                foreach ($languages as $key_languages => $value) {
                    $video_url = basename($_FILES['fileToUpload']['name'][$value['id_lang']]);
                    if ($value['id_lang'] == $id_lang_default) {// // insert + luu video ngon ngu chinh
                        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'url_video ';
                        $sql .= '(id_video,id_product,id_lang,id_store,text_url,language,shop,name_product,type)';
                        $sql .= " VALUES ('','" . (int) $id_product . "','" . (int) $id_lang_default . "','" . (int) $id_shop . "','";
                        $sql .= '' . (int) $video_upload_default . "','" . (int) $name_lang_default . "','" . (int) $name_shop . "','";
                        $sql .= '' . (int) $name_product . "','" . (int) $type_video . "')";
                        $db->query($sql);
                        $url_save_video = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/';
                        $url_save_video .= $id_lang_default . '/' . $video_upload_default;
                        copy($url_video_tmp, $url_save_video);
                    } else {
                        if ('' == $video_url) {// // insert + luu video ngon ngu phu rong
                            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'url_video ';
                            $sql .= '(id_video,id_product,id_lang,id_store,text_url,language,shop,name_product,type)';
                            $sql .= " VALUES ('','" . (int) $id_product . "','" . (int) $value['id_lang'] . "','" . (int) $id_shop . "','";
                            $sql .= '' . (int) $video_upload_default . "','" . pSQL($value['name']) . "','" . pSQL($name_shop) . "','";
                            $sql .= '' . pSQL($name_product) . "','" . pSQL($type_video) . "')";
                            $db->query($sql);
                            $url_save_video = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/';
                            $url_save_video .= $value['id_lang'] . '/' . $video_upload_default;
                            copy($url_video_tmp, $url_save_video);
                        }
                        if ('' != $video_url) {// // insert + luu video ngon ngu phu co du lieu
                            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'url_video ';
                            $sql .= '(id_video,id_product,id_lang,id_store,text_url,language,shop,name_product,type)';
                            $sql .= " VALUES ('','" . (int) $id_product . "','" . (int) $value['id_lang'] . "','" . (int) $id_shop . "','";
                            $sql .= '' . pSQL($video_url) . "','" . pSQL($value['name']) . "','" . pSQL($name_shop) . "','";
                            $sql .= '' . pSQL($name_product) . "','" . pSQL($type_video) . "')";
                            $db->query($sql);
                            $url_save_video = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/';
                            $url_save_video .= $value['id_lang'] . '/' . $video_url;
                            move_uploaded_file($_FILES['fileToUpload']['tmp_name'][$value['id_lang']], $url_save_video);
                        }
                    }
                }

                // xoa video tmp
                $url_dele = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/';
                $file = $url_dele . $video_upload_default;
                @unlink($file);
            }

            // /// Product co du lieu
            if (!empty($data_video_product)) {
                $video_upload_default = basename($_FILES['fileToUpload']['name'][$id_lang_default]);
                if (empty($data_video_default) && '' == $video_upload_default) {
                    $ok = 5;
                    echo $ok;
                    exit;
                }

                foreach ($languages as $key_languages => $value) {
                    $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'url_video WHERE id_product="' . (int) $id_product . '" ';
                    $sql .= ' AND id_store="' . (int) $id_shop . '" AND id_lang="' . (int) $value['id_lang'] . '"';
                    $data_video_lang = $db->ExecuteS($sql);
                    $id_video = '';
                    if (!empty($data_video_lang)) {
                        $id_video = $data_video_lang['0']['id_video'];
                    }
                    $video_url = basename($_FILES['fileToUpload']['name'][$value['id_lang']]);
                    if ('' != $video_url) {
                        // xoa video cu
                        if (!empty($data_video_lang)) {
                            $url_dele = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/';
                            $url_dele .= $value['id_lang'] . '/';
                            $file = $url_dele . $data_video_lang['0']['text_url'];
                            @unlink($file);
                        }

                        // update lai thong tin + video moi
                        $sql = 'REPLACE INTO ' . _DB_PREFIX_ . 'url_video ';
                        $sql .= '(id_video,id_product,id_lang,id_store,text_url,language,shop,name_product,type)';
                        $sql .= " VALUES ('" . (int) $id_video . "','" . (int) $id_product . "','" . (int) $value['id_lang'] . "','";
                        $sql .= '' . (int) $id_shop . "','" . pSQL($video_url) . "','" . pSQL($value['name']) . "','" . pSQL($name_shop) . "','";
                        $sql .= '' . pSQL($name_product) . "','" . pSQL($type_video) . "')";
                        $db->query($sql);
                        $url_save_video = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/';
                        $url_save_video .= $value['id_lang'] . '/' . $video_url;
                        move_uploaded_file($_FILES['fileToUpload']['tmp_name'][$value['id_lang']], $url_save_video);
                    }
                }
            }
            $ok = 3;
        } else {
            $languages = [];
            $name_url_array = [];
            $languages = Language::getLanguages();
            $name_url_array = Tools::getValue('name_url');
            foreach ($languages as $key_lang => $value_lang) {
                if ($id_lang_default == $value_lang['id_lang']) {
                    if (empty($name_url_array[$value_lang['id_lang']])) {
                        $ok = '0';
                        echo $ok;

                        return false;
                    } else {
                        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
                        $sql .= " AND id_lang='" . (int) $value_lang['id_lang'] . "' AND id_store='" . (int) $id_shop . "'";
                        $data = $db->ExecuteS($sql);
                        if (empty($data)) {
                            $id_video = '';
                        } else {
                            $id_video = $data[0]['id_video'];
                        }
                        $sql = 'REPLACE INTO ' . _DB_PREFIX_ . 'url_video ';
                        $sql .= '(id_video,id_product,id_store,text_url,language,shop,name_product,type,id_lang)';
                        $sql .= " VALUES ('" . (int) $id_video . "','" . (int) $id_product . "','" . (int) $id_shop . "','";
                        $sql .= '' . pSQL(trim($name_url_array[$value_lang['id_lang']]), PDO::PARAM_STR) . "','" . pSQL($value_lang['name']) . "','";
                        $sql .= '' . pSQL($name_shop) . "','" . pSQL($name_product) . "','" . pSQL($type_video) . "','" . (int) $value_lang['id_lang'] . "')";
                        $db->query($sql);
                        $ok = '3';
                    }
                } else {
                    if (empty($name_url_array[$value_lang['id_lang']])) {
                        $name_url_array[$value_lang['id_lang']] = $name_url_array[$id_lang_default];
                        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
                        $sql .= " AND id_lang='" . $value_lang['id_lang'] . "' AND id_store='" . (int) $id_shop . "'";
                        $data = $db->ExecuteS($sql);
                        if (empty($data)) {
                            $id_video = '';
                        } else {
                            $id_video = $data[0]['id_video'];
                        }
                        $sql = 'REPLACE INTO ' . _DB_PREFIX_ . 'url_video ';
                        $sql .= '(id_video,id_product,id_store,text_url,language,shop,name_product,type,id_lang)';
                        $sql .= " VALUES ('" . (int) $id_video . "','" . (int) $id_product . "','" . (int) $id_shop . "','";
                        $sql .= '' . pSQL(trim($name_url_array[$value_lang['id_lang']]), PDO::PARAM_STR) . "','" . pSQL($value_lang['name']) . "','";
                        $sql .= '' . pSQL($name_shop) . "','" . pSQL($name_product) . "','" . pSQL($type_video) . "','" . (int) $value_lang['id_lang'] . "')";
                        $db->query($sql);
                        $ok = '3';
                    } else {
                        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . $id_product . "'";
                        $sql .= " AND id_lang='" . (int) $value_lang['id_lang'] . "' AND id_store='" . (int) $id_shop . "'";
                        $data = $db->ExecuteS($sql);
                        if (empty($data)) {
                            $id_video = '';
                        } else {
                            $id_video = $data[0]['id_video'];
                        }
                        $sql = 'REPLACE INTO ' . _DB_PREFIX_ . 'url_video ';
                        $sql .= '(id_video,id_product,id_store,text_url,language,shop,name_product,type,id_lang)';
                        $sql .= " VALUES ('" . (int) $id_video . "','" . (int) $id_product . "','" . (int) $id_shop . "','";
                        $sql .= '' . pSQL(trim($name_url_array[$value_lang['id_lang']]), PDO::PARAM_STR) . "','" . $value_lang['name'] . "','";
                        $sql .= '' . pSQL($name_shop) . "','" . pSQL($name_product) . "','" . pSQL($type_video) . "','" . (int) $value_lang['id_lang'] . "')";
                        $db->query($sql);
                        $ok = '3';
                    }
                }
            }
        }
        echo $ok;
    }

    public function createIndexInFolder($id_product, $id_shop)
    {
        $languages = Language::getLanguages();
        $url_savevideo = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product;
        if (!file_exists($url_savevideo)) {
            @mkdir($url_savevideo, 0777, true);
        }
        $logMedia = '';
        $outputFlie = _PS_ROOT_DIR_ . '/media//index.php';
        file_put_contents($outputFlie, $logMedia, FILE_APPEND);
        $outputFlie = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/index.php';
        file_put_contents($outputFlie, $logMedia, FILE_APPEND);
        $outputFlie = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/index.php';
        file_put_contents($outputFlie, $logMedia, FILE_APPEND);
        foreach ($languages as $key_languages => $value_languages) {
            $url_savevideo = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/' . $value_languages['id_lang'];
            if (!file_exists($url_savevideo)) {
                @mkdir($url_savevideo, 0777, true);
            }
            $outputFlie = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/' . $value_languages['id_lang'] . '/index.php';
            file_put_contents($outputFlie, $logMedia, FILE_APPEND);
        }
    }
}
