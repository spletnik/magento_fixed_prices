<?php

class Spletnisistemi_AdvConfigurable_Model_Wishlist_Item extends Mage_Wishlist_Model_Item {
    public function getProduct() {
        $product = parent::getProduct();
        $helper = Mage::helper('advconfigurable');


        if ($product->getTypeId() == 'configurable') {


            $custom = $product->getCustomOptions();

            // Simple produkt se ni izbran
            if (!isset($custom['simple_product'])) {
                return $product;
                exit();
            }

            $simple = Mage::getModel('catalog/product')->load($custom['simple_product']->getProductId());

            $price = $helper->getPrice($simple, $product);

            $product->setPrice($price[0]);

            $product->setCustomPrice($price[0]);

            $product->setOriginalCustomPrice($price[0]);

            $product->setCost($price[0]);
        }

        return $product;
    }
}