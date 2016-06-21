<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog super product configurable part block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Spletnisistemi_AdvConfigurable_Block_Configurable extends Mage_Catalog_Block_Product_View_Type_Configurable {

    /*
     *
     * Get prices of simples.
     * */
    public function getJsonFixedConfig() {
        $prices = array();
        $tiers = array();
        $map = array();

        $h = Mage::helper('advconfigurable');
        $p = $this->getProduct();
        $attributes = $this->getAllowAttributes();
        $has_cr = $h->catalogRulePrice($p, 10);
        $tax_helper = Mage::helper('tax');

        foreach ($this->getAllowProducts() as $product) {
            $pid = $product->getId();
            $product = Mage::getModel('catalog/product')->load($pid);
            list($price, $special) = $h->getPrice($product, $p);

            // attrs -> pid
            $attr_values = '';
            foreach ($attributes as $attribute) {
                $attr = $attribute->getProductAttribute();
                $attr_id = $attr->getId();
                $attr_val = $product->getData($attr->getAttributeCode());
                $attr_values .= "{$attr_id}{$attr_val}";
            }

            $map[$attr_values] = $pid;
            $prices[$pid] = array(
                $h->toCurrency($price),
                $special ? $h->toCurrency($special) : null,
            );

            if (($has_cr === null) && ($product_tiers = $h->getTierPrices($product))) {
                foreach ($product_tiers as $qty => &$tier_price)
                    $tier_price = $h->toCurrency($tier_price);
                $tiers[$pid] = $product_tiers;
            }
        }

        return array(
            'prices'  => $prices,
            'tiers'   => $tiers,
            'options' => $map,
        );
    }
}