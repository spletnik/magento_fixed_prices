<?php
/**
 * spletnisistemi
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the spletnisistemi EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.spletnisistemi.si/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@spletnisistemi.si so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.spletnisistemi.si/ for more information
 * or send an email to sales@spletnisistemi.si
 *
 * @category   spletnisistemi
 * @package    spletnisistemi_MultiFees
 * @copyright  Copyright (c) 2009 spletnisistemi (http://www.spletnisistemi.si/)
 * @license    http://www.spletnisistemi.si/LICENSE-1.0.html
 */

/**
 * Multi Fees extension
 *
 * @category   spletnisistemi
 * @package    spletnisistemi_MultiFees
 * @author     spletnisistemi Dev Team <dev@spletnisistemi.si>
 */
class Spletnisistemi_Module_ModuleController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        $session = $this->_getSession();
        $helper = Mage::helper('multifees');
        if ($helper->isFeeEnabled()) {
            $resPrice = 0;
            $storeFee = $this->getRequest()->getPost('fee');
            $feeMessage = $this->getRequest()->getPost('message');
            $feeDate = $this->getRequest()->getPost('date');

            $filter = new Zend_Filter();
            $filter->addFilter(new Zend_Filter_StringTrim());
            $filter->addFilter(new Zend_Filter_StripTags());

            $detailsFees = array();

            $productIds = Mage::getSingleton('checkout/cart')->getQuoteProductIds();

            if ($storeFee) {
                $subtotal = $session->getQuote()->getSubtotal();
                $quoteItems = $session->getQuote()->getAllItems();
                if ($quoteItems) {
                    foreach ($quoteItems as $quoteItem) {
                        $productModel = Mage::getModel('catalog/product');
                        $product = $productModel->load($quoteItem->getProduct()->getId());
                        $additionalFees = $product->getAdditionalFees();
                        if ('-2' == $additionalFees) {
                            $subtotal -= $quoteItem->getPrice();
                        }
                    }
                }
                foreach ($storeFee as $feeId => $value) {
                    if (is_array($value) && count($value)) {
                        foreach ($value as $id) {
                            if ($id) {
                                $price = 0;
                                $detailsFees[$feeId]['title'] = $feeId;
                                $detailsFees[$feeId]['message'] = Mage::helper('core/string')->truncate($filter->filter($feeMessage[$feeId]), 1024);
                                $detailsFees[$feeId]['date'] = $filter->filter($feeDate[$feeId]);

                                $option = Mage::getSingleton('multifees/option')->load((int)$id);
                                if (Mage::helper('multifees')->isTypeFixed($option->getPriceType())) {
                                    $price = $option->getPrice();
                                } else {
                                    if ($subtotal > 0) {
                                        $price = ($subtotal * $option->getPrice()) / 100;
                                    }
                                }
                                $resPrice += $price;

                                $detailsFees[$feeId]['options'][$id] = Mage::getModel('multifees/option')->getOptionItem($id)->getOption();
                                $detailsFees[$feeId]['price'][$id] = $price;
                            }
                        }
                    }
                }
                if ($detailsFees) {
                    $session->setDetailsMultifees($detailsFees);
                }
            }
            if ($resPrice < 0 || !count($detailsFees)) {
                return $this->removeAction();
            } else {
                $session->setStoreMultifees($storeFee);
                $session->setMultifees($resPrice);
            }
        }
        $this->_redirect('checkout/cart');
    }
}