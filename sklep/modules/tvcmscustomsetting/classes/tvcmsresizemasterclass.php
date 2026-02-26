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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
class TvcmsResizeMasterClass
{
    // *** Class variables
    private $image;

    private $width;

    private $height;

    private $imgResized;

    public function __construct($fileName)
    {
        // *** Open up the file
        $this->image = $this->openImage($fileName);

        // *** FIX: Check if image is loaded correctly before getting dimensions
        // This prevents "TypeError: imagesx(): Argument #1 ($image) must be of type GdImage, bool given"
        if ($this->image === false) {
            $this->width = 0;
            $this->height = 0;
            return;
        }

        // *** Get width and height
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    // # --------------------------------------------------------

    private function openImage($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        // *** Get extension
        $extension = Tools::strtolower(strrchr($file, '.'));

        switch ($extension) {
            case '.jpg':
            case '.jpeg':
                $img = @imagecreatefromjpeg($file);
                break;
            case '.gif':
                $img = @imagecreatefromgif($file);
                break;
            case '.png':
                $img = @imagecreatefrompng($file);
                break;
            case '.webp': // *** FIX: Added WebP support
                if (function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($file);
                } else {
                    $img = false;
                }
                break;
            default:
                $img = false;
                break;
        }

        return $img;
    }

    // # --------------------------------------------------------

    public function resizeImage($newWd, $newHt, $type = 0)
    {
        // *** FIX: If image failed to load, do not proceed
        if (!$this->image) {
            return;
        }

        $option = '';
        switch ($type) {
            case 1:
                $option = 'exact';
                break;
            case 2:
                $option = 'portrait';
                break;
            case 3:
                $option = 'landscape';
                break;
            case 4:
                $option = 'auto';
                break;
            case 5:
                $option = 'crop';
                break;
            default:
                $option = 'exact';
                break;
        }

        // *** Get optimal width and height - based on $option
        $optionArray = $this->getDimensions($newWd, $newHt, $option);
        $oplWd = $optionArray['oplWd'];
        $optHt = $optionArray['optHt'];

        // *** Resample - create image canvas of x, y size
        $this->imgResized = imagecreatetruecolor($oplWd, $optHt);

        // Make the background transparent
        imagealphablending($this->imgResized, false);
        imagesavealpha($this->imgResized, true);
        
        imagecopyresampled($this->imgResized, $this->image, 0, 0, 0, 0, $oplWd, $optHt, $this->width, $this->height);

        // *** if option is 'crop', then crop too
        if ('crop' == $option) {
            $this->crop($oplWd, $optHt, $newWd, $newHt);
        }
    }

    // # --------------------------------------------------------

    private function getDimensions($newWd, $newHt, $option)
    {
        // Safety check for division by zero
        if ($this->width == 0 || $this->height == 0) {
            return ['oplWd' => $newWd, 'optHt' => $newHt];
        }

        switch ($option) {
            case 'exact':
                $oplWd = $newWd;
                $optHt = $newHt;
                break;
            case 'portrait':
                $oplWd = $this->getSizeByFixedHeight($newHt);
                $optHt = $newHt;
                break;
            case 'landscape':
                $oplWd = $newWd;
                $optHt = $this->getSizeByFixedWidth($newWd);
                break;
            case 'auto':
                $optionArray = $this->getSizeByAuto($newWd, $newHt);
                $oplWd = $optionArray['oplWd'];
                $optHt = $optionArray['optHt'];
                break;
            case 'crop':
                $optionArray = $this->getOptimalCrop($newWd, $newHt);
                $oplWd = $optionArray['oplWd'];
                $optHt = $optionArray['optHt'];
                break;
        }

        return ['oplWd' => $oplWd, 'optHt' => $optHt];
    }

    // # --------------------------------------------------------

    private function getSizeByFixedHeight($newHt)
    {
        $ratio = $this->width / $this->height;
        $newWd = $newHt * $ratio;

        return $newWd;
    }

    private function getSizeByFixedWidth($newWd)
    {
        $ratio = $this->height / $this->width;
        $newHt = $newWd * $ratio;

        return $newHt;
    }

    private function getSizeByAuto($newWd, $newHt)
    {
        if ($this->height < $this->width) {
            // *** Image to be resized is wider (landscape)
            $oplWd = $newWd;
            $optHt = $this->getSizeByFixedWidth($newWd);
        } elseif ($this->height > $this->width) {
            // *** Image to be resized is taller (portrait)
            $oplWd = $this->getSizeByFixedHeight($newHt);
            $optHt = $newHt;
        } else {
            // *** Image to be resizerd is a square
            if ($newHt < $newWd) {
                $oplWd = $newWd;
                $optHt = $this->getSizeByFixedWidth($newWd);
            } elseif ($newHt > $newWd) {
                $oplWd = $this->getSizeByFixedHeight($newHt);
                $optHt = $newHt;
            } else {
                // *** Sqaure being resized to a square
                $oplWd = $newWd;
                $optHt = $newHt;
            }
        }

        return ['oplWd' => $oplWd, 'optHt' => $optHt];
    }

    private function getOptimalCrop($newWd, $newHt)
    {
        $heightRatio = $this->height / $newHt;
        $widthRatio = $this->width / $newWd;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $optHt = $this->height / $optimalRatio;
        $oplWd = $this->width / $optimalRatio;

        return ['oplWd' => $oplWd, 'optHt' => $optHt];
    }

    // # --------------------------------------------------------

    private function crop($oplWd, $optHt, $newWd, $newHt)
    {
        // *** Find center - this will be used for the crop
        $cropStX = ($oplWd / 2) - ($newWd / 2);
        $cropStY = ($optHt / 2) - ($newHt / 2);

        $crop = $this->imgResized;
        // imagedestroy($this->imgResized);

        // *** Now crop from center to exact requested size
        $this->imgResized = imagecreatetruecolor($newWd, $newHt);
        imagecopyresampled($this->imgResized, $crop, 0, 0, $cropStX, $cropStY, $newWd, $newHt, $newWd, $newHt);
    }

    // # --------------------------------------------------------

    public function saveImage($savePath, $showImage = false, $imageQuality = '100')
    {
        // *** FIX: If image failed to load/resize, prevent saving crash
        if (!$this->imgResized) {
            return;
        }

        // *** Get extension
        $ext = explode('.', $savePath);
        $extension = end($ext);
        $extension = Tools::strtolower('.' . $extension);
        
        if (!$showImage) {
            switch ($extension) {
                case '.jpg':
                case '.jpeg':
                    if (imagetypes() & IMG_JPG) {
                        @imagejpeg($this->imgResized, $savePath, $imageQuality);
                    }
                    break;

                case '.gif':
                    if (imagetypes() & IMG_GIF) {
                        @imagegif($this->imgResized, $savePath);
                    }
                    break;

                case '.png':
                    // *** Scale quality from 0-100 to 0-9
                    $scaleQuality = round(($imageQuality / 100) * 9);
                    // *** Invert quality setting as 0 is best, not 9
                    $invertScaleQuality = 9 - $scaleQuality;

                    if (imagetypes() & IMG_PNG) {
                        @imagepng($this->imgResized, $savePath, $invertScaleQuality);
                    }
                    break;

                case '.webp': // *** FIX: Added WebP save support
                    if (imagetypes() & IMG_WEBP) {
                        @imagewebp($this->imgResized, $savePath, $imageQuality);
                    }
                    break;

                default:
                    // *** No extension - No save.
                    break;
            }
        } else {// for show image
            switch ($extension) {
                case '.jpg':
                case '.jpeg':
                    Tools::redirect('Content-Type: image/jpg');
                    @imagejpeg($this->imgResized);
                    break;

                case '.gif':
                    Tools::redirect('Content-Type: image/gif');
                    @imagegif($this->imgResized);
                    break;

                case '.png':
                    Tools::redirect('Content-Type: image/png');
                    @imagepng($this->imgResized);
                    break;

                case '.webp': // *** FIX: Added WebP show support
                    Tools::redirect('Content-Type: image/webp');
                    @imagewebp($this->imgResized);
                    break;
            }
        }

        if ($this->imgResized) {
            imagedestroy($this->imgResized);
        }
    }
}