<?php
/**
 * 2007-2025 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

// Image_upload class is use for image uploading
class TvcmsSliderOfferBannerImageUpload extends Module
{
    // Image_uploading function is use to upload image in to particular folder.
    // If That folder has same name of image then this function is rename that image file.
    public function imageUploading($image_src_1, $old_file)
    {
        $returnData = [];
        $errorMessage = '';
        $successUpload = false;
        $imgName = $image_src_1['name'];
        $this->name = 'tvcmssliderofferbanner';

        // resize settings
        // $savePath = _PS_BASE_URL_._MODULE_DIR_.'tvcmsmultibanner/views/img/';
        $savePath = _PS_MODULE_DIR_ . 'tvcmssliderofferbanner/views/img/';
        $resultType = $this->imageConditions($image_src_1);
        if ($resultType) {
            $imgName = $image_src_1['name'];
            if (file_exists($savePath . $imgName)) {
                $new_img_name = explode('.', $imgName);
                $imgName = $new_img_name[0] . '_' . date('YmdHis') . '.' . $new_img_name[1];
            }

            $save_destination = $savePath . $imgName;
            $resultUpload = move_uploaded_file($image_src_1['tmp_name'], $save_destination);
            $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
            $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . $imgName);
            $width = $imagedata[0];
            $height = $imagedata[1];
            if ($resultUpload) {// success
                $res = preg_match('/^demo_img_.*$/', $old_file);
                if (!empty($old_file)
                    && file_exists(dirname(__FILE__) . './../views/img/' . $old_file)
                    && '1' != $res) {
                    unlink(dirname(__FILE__) . './../views/img/' . $old_file);
                }
                $successUpload = true;
            } else {
                $errorMessage .= $this->displayError($this->l('Image Upload Problem'));
            }
        } else {
            $errorMessage .= $this->displayError($this->l('Please Select Valid Image File.'));
        }
        $returnData['error'] = $errorMessage;
        $returnData['success'] = $successUpload;
        $returnData['name'] = $imgName;
        $returnData['width'] = $width;
        $returnData['height'] = $height;

        return $returnData;
    }

    // Image_conditions
    public function imageConditions($image_src)
    {
        if ('image/jpeg' == $image_src['type']
            || 'image/jpg' == $image_src['type']
            || 'image/png' == $image_src['type']
            || 'image/gif' == $image_src['type']) {
            return true;
        } else {
            return false;
        }
    }
}
