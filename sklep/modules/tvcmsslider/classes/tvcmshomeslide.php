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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2025 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
class TvcmsHomeSlide extends ObjectModel
{
    public $title;

    public $description;

    public $url;

    public $legend;

    public $btn_caption;

    public $video_width;

    public $video_height;

    public $class_name;

    public $ivr_value;

    public $image;

    public $active;

    public $position;

    public $width;

    public $height;

    public $id_shop;

    public static $definition = [
        'table' => 'tvcmsslider_slides',
        'primary' => 'id_tvcmsslider_slides',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],

            // Lang fields
            'description' => ['type' => self::TYPE_HTML,
    'lang' => true, 'validate' => 'isAnything', 'size' => 65535, ],
            'title' => ['type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
            'legend' => ['type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
            'class_name' => ['type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
            'ivr_value' => ['type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
            'btn_caption' => ['type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
            'video_width' => ['type' => self::TYPE_INT,
                'lang' => true, 'validate' => 'isunsignedInt', ],
            'video_height' => ['type' => self::TYPE_INT,
                'lang' => true, 'validate' => 'isunsignedInt', ],
            'url' => ['type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isUrl', 'size' => 255, ],
            'image' => ['type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
            'width' => ['type' => self::TYPE_INT,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
            'height' => ['type' => self::TYPE_INT,
                'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255, ],
        ],
    ];

    public function __construct($id_slide = null, $id_lang = null, $id_shop = null, Context $context = null)
    {
        parent::__construct($id_slide, $id_lang, $id_shop);
    }

    public function add($autodate = true, $null_values = false)
    {
        $context = Context::getContext();
        $id_shop = $context->shop->id;

        $res = parent::add($autodate, $null_values);
        $res &= Db::getInstance()->execute('
            INSERT INTO `' . _DB_PREFIX_ . 'tvcmsslider` (`id_shop`, `id_tvcmsslider_slides`)
            VALUES(' . (int) $id_shop . ', ' . (int) $this->id . ')');

        return $res;
    }

    public function delete()
    {
        $res = true;

        $images = $this->image;
        foreach ($images as $image) {
            if (0 === preg_match('/sample/', $image)) {
                if ($image && file_exists(dirname(__FILE__) . '/views/img/' . $image)) {
                    $res &= @unlink(dirname(__FILE__) . '/views/img/' . $image);
                }
            }
        }

        $res &= $this->reOrderPositions();

        $res &= Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'tvcmsslider`
            WHERE `id_tvcmsslider_slides` = ' . (int) $this->id);

        $res &= parent::delete();

        return $res;
    }

    public function reOrderPositions()
    {
        $id_slide = $this->id;
        $context = Context::getContext();
        $id_shop = $context->shop->id;

        $max = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT MAX(hss.`position`) as position
            FROM `' . _DB_PREFIX_ . 'tvcmsslider_slides` hss, `' . _DB_PREFIX_ . 'tvcmsslider` hs
            WHERE hss.`id_tvcmsslider_slides` = hs.`id_tvcmsslider_slides` AND hs.`id_shop` = ' . (int) $id_shop);

        if ((int) $max == (int) $id_slide) {
            return true;
        }

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT hss.`position` as position, hss.`id_tvcmsslider_slides` as id_slide
            FROM `' . _DB_PREFIX_ . 'tvcmsslider_slides` hss
            LEFT JOIN `' . _DB_PREFIX_ . 'tvcmsslider` hs ON (hss.`id_tvcmsslider_slides` = hs.`id_tvcmsslider_slides`)
            WHERE hs.`id_shop` = ' . (int) $id_shop . ' AND hss.`position` > ' . (int) $this->position);

        foreach ($rows as $row) {
            $current_slide = new TvcmsHomeSlide($row['id_slide']);
            $current_slide->position;
            $current_slide->update();
        }

        return true;
    }

    public static function getAssociatedIdsShop($id_slide)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT hs.`id_shop`
            FROM `' . _DB_PREFIX_ . 'tvcmsslider` hs
            WHERE hs.`id_tvcmsslider_slides` = ' . (int) $id_slide);

        if (!is_array($result)) {
            return false;
        }

        $return = [];

        foreach ($result as $id_shop) {
            $return[] = (int) $id_shop['id_shop'];
        }

        return $return;
    }
}
