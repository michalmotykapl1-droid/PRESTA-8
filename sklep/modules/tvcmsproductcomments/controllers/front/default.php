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

// Include Module
include_once dirname(__FILE__) . '/../../tvcmsproductcomments.php';
// Include Models
include_once dirname(__FILE__) . '/../../tvcmsproductcomment.php';
include_once dirname(__FILE__) . '/../../tvcmsproductcommentcriterion.php';

class TvcmsProductCommentsDefaultModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->context = Context::getContext();
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::isSubmit('action')) {
            switch (Tools::getValue('action')) {
                case 'add_comment':
                    $this->ajaxProcessAddComment();

                    break;
                case 'report_abuse':
                    $this->ajaxProcessReportAbuse();

                    break;
                case 'comment_is_usefull':
                    $this->ajaxProcessCommentIsUsefull();

                    break;
            }
        }
    }

    protected function ajaxProcessAddComment()
    {
        $module_instance = new TvcmsProductComments();

        $result = true;
        $id_guest = 0;
        $id_customer = $this->context->customer->id;
        if (!$id_customer) {
            $id_guest = $this->context->cookie->id_guest;
        }

        $errors = [];
        // Validation
        if (!Validate::isInt(Tools::getValue('id_product'))) {
            $errors[] = $module_instance->l('Product ID is incorrect', 'default');
        }
        if (!Tools::getValue('title') || !Validate::isGenericName(Tools::getValue('title'))) {
            $errors[] = $module_instance->l('Title must be required.', 'default');
        }
        if (!Tools::getValue('content') || !Validate::isMessage(Tools::getValue('content'))) {
            $errors[] = $module_instance->l('Comment must be required.', 'default');
        }
        if (!$id_customer
            && (!Tools::isSubmit('customer_name')
                || !Tools::getValue('customer_name')
                || !Validate::isGenericName(Tools::getValue('customer_name')))) {
            $errors[] = $module_instance->l('Your name must be required.', 'default');
        }
        if (!$this->context->customer->id && !Configuration::get('TVCMSPRODUCT_COMMENTS_ALLOW_GUESTS')) {
            $errors[] = $module_instance->l('You must be connected in order to send a comment', 'default');
        }
        if (!count(Tools::getValue('criterion'))) {
            $errors[] = $module_instance->l('You must give a rating', 'default');
        }

        $product = new Product(Tools::getValue('id_product'));
        if (!$product->id) {
            $errors[] = $module_instance->l('Product not found', 'default');
        }

        if (!count($errors)) {
            $customer_comment = TvcmsProductComment::getByCustomer(
                Tools::getValue('id_product'),
                $id_customer,
                true,
                $id_guest
            );
            $tmp = (int) Configuration::get('TVCMSPRODUCT_COMMENTS_MINIMAL_TIME');
            if (!$customer_comment
                || ($customer_comment
                    && (strtotime($customer_comment['date_add']) + $tmp) < time())) {
                $comment = new TvcmsProductComment();
                $comment->content = strip_tags(Tools::getValue('content'));
                $comment->id_product = (int) Tools::getValue('id_product');
                $comment->id_customer = (int) $id_customer;
                $comment->id_guest = $id_guest;
                $comment->customer_name = Tools::getValue('customer_name');
                if (!$comment->customer_name) {
                    $comment->customer_name = pSQL($this->context->customer->firstname
                        . ' ' . $this->context->customer->lastname);
                }
                $comment->title = Tools::getValue('title');
                $comment->grade = 0;
                $comment->validate = 0;
                $comment->save();

                $grade_sum = 0;
                foreach (Tools::getValue('criterion') as $id_tvcmsproduct_comment_criterion => $grade) {
                    $grade_sum += $grade;
                    $t = $id_tvcmsproduct_comment_criterion;
                    $tvcmsproduct_comment_criterion = new TvcmsProductCommentCriterion($t);
                    if ($tvcmsproduct_comment_criterion->id) {
                        $tvcmsproduct_comment_criterion->addGrade($comment->id, $grade);
                    }
                }

                if (count(Tools::getValue('criterion')) >= 1) {
                    $comment->grade = $grade_sum / count(Tools::getValue('criterion'));
                    // Update Grade average of comment
                    $comment->save();
                }
                $result = true;
                Hook::exec('actionSpawnActionTvcmsProductComment', ['post', Tools::getAllValues()]);
                Tools::clearCache(
                    Context::getContext()->smarty,
                    $this->getTemplatePath('tvcmsproductcomments-reviews.tpl')
                );
            } else {
                $result = false;
                $errors[] = $module_instance->l('Please wait before posting another comment', 'default')
                    . ' ' . Configuration::get('TVCMSPRODUCT_COMMENTS_MINIMAL_TIME') . ' '
                    . $module_instance->l('seconds before posting a new comment', 'default');
            }
        } else {
            $result = false;
        }

        exit(json_encode([
            'result' => $result,
            'errors' => $errors,
        ]));
    }

    protected function ajaxProcessReportAbuse()
    {
        if (!Tools::isSubmit('id_tvcmsproduct_comment')) {
            exit('0');
        }

        if (TvcmsProductComment::isAlreadyReport(
            Tools::getValue('id_tvcmsproduct_comment'),
            $this->context->cookie->id_customer
        )) {
            exit('0');
        }

        if (TvcmsProductComment::reportComment(
            (int) Tools::getValue('id_tvcmsproduct_comment'),
            $this->context->cookie->id_customer
        )) {
            Hook::exec('actionSpawnActionTvcmsProductComment', ['post', Tools::getAllValues()]);
            exit('1');
        }

        exit('0');
    }

    protected function ajaxProcessCommentIsUsefull()
    {
        if (!Tools::isSubmit('id_tvcmsproduct_comment') || !Tools::isSubmit('value')) {
            exit('0');
        }

        if (TvcmsProductComment::isAlreadyUsefulness(
            Tools::getValue('id_tvcmsproduct_comment'),
            $this->context->cookie->id_customer
        )) {
            exit('0');
        }

        if (TvcmsProductComment::setCommentUsefulness(
            (int) Tools::getValue('id_tvcmsproduct_comment'),
            (bool) Tools::getValue('value'),
            $this->context->cookie->id_customer
        )) {
            Hook::exec('actionSpawnActionTvcmsProductComment', ['post', Tools::getAllValues()]);
            exit('1');
        }

        exit('0');
    }
}
