<?php

class Spletnisistemi_AdvConfigurable_Helper_Data extends Mage_Core_Helper_Abstract {
    public function getPrice($product, $parent) {
        $price = null;


        // Catalog rules
        $this->s($price, $this->catalogRulePrice($parent, $product->getPrice()));

        // Tier price
        $tiers = $product->getTierPrice();
        $qty = $parent->getQty();
        $this->s($price, $this->findTierPrice($product, $tiers, $qty));

        // Special price
        $from = strtotime($product->getSpecialFromDate());
        $to = strtotime($product->getSpecialToDate());
        if ((!$from || $from < time()) && (!$to || $to > time()))
            $this->s($price, $product->getSpecialPrice());

        // Price + (group price)
        $this->s($price, $product->getFinalPrice());

        $orig = $product->getPrice();
        if ($orig == $price)
            $price = null; // Special price
        return array($orig, $price);
    }

    private function s(&$price, $p) {
        if ($p && ($price === null || $p < $price))
            $price = $p;
    }

    public function catalogRulePrice($product, $price) {
        $customer_group_id = Mage::getSingleton('customer/session')->getCustomer()->getGroupId();
        $storeId = $product->getStoreId();
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
        $dateTs = Mage::app()->getLocale()->storeTimeStamp($storeId);

        $resource = Mage::getResourceModel('catalogrule/rule');
        $pid = $product->getProductId();
        if (!$pid) $pid = $product->getEntityId();
        $rules = $resource->getRulesFromProduct($dateTs, $websiteId, $customer_group_id, $pid);

        $cr = Mage::helper('catalogrule');

        $applied = false;
        foreach ($rules as $rule) {
            $price = $cr->calcPriceRule(
                $rule['action_operator'],
                $rule['action_amount'],
                $price);
            $applied = true;
            if ($rule['action_stop']) break;
        }
        if ($applied)
            return $price;
        return null;
    }

    public function findTierPrice($product, $tiers, $qty) {
        $group_id = $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $storeId = $product->getStoreId();
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        $max_qty = 0;
        $price = null;
        foreach ($tiers as $tier) {
            if (($tier['all_groups'] || $tier['cust_group'] == $group_id) && ($tier['website_id'] == 0 || $tier['website_id'] == $websiteId)) {
                if ($tier['price_qty'] >= $max_qty && $qty >= $tier['price_qty']) {
                    $max_qty = $tier['price_qty'];
                    $price = $tier['website_price'];
                }
            }
        }
        return $price;
    }

    public function getTierPrices($product) {
        $tiers = $product->getTierPrice();
        $group_id = $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $storeId = $product->getStoreId();
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        $ret = array();
        foreach ($tiers as $tier)
            if (($tier['all_groups'] || $tier['cust_group'] == $group_id) && ($tier['website_id'] == 0 || $tier['website_id'] == $websiteId))
                $ret[$tier['price_qty']] = $tier['price'];
        return $ret;
    }

    public function toCurrency($price) {
        $helper = Mage::helper('directory');
        $base_currency = Mage::app()->getStore()->getBaseCurrencyCode();
        $currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        return $helper->currencyConvert($price, $base_currency, $currency);
    }
}

