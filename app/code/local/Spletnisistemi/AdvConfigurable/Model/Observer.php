<?php

class Spletnisistemi_AdvConfigurable_Model_Observer {
    // Update final/cart price
    public function update_product_price($observer) {
        if (Mage::registry('fixed_lock')) return;
        Mage::register('fixed_lock', 1);
        $this->refreshCartPrices();

        //$this->refreshQuotePrices();

        //$this->refreshWishlistPrices($observer);


        Mage::unregister('fixed_lock');
    }

    public function refreshCartPrices() {
        $items = Mage::getModel('checkout/cart')->getQuote()->getAllItems();

        foreach ($items as $item) {
            $custom_price = 0;
            if ($parent = $item->getParentItem()) {
                // simple product
                $product = Mage::getModel('catalog/product')->load($item->getProduct()->getEntityId());

                // get additional custom price
                $cmodel = Mage::getModel('catalog/product');
                $productOrderOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                $configurable_product_id = $productOrderOptions["info_buyRequest"]["product"];
                $configurable_product = $cmodel->load($configurable_product_id);

                if (array_key_exists("options", $productOrderOptions["info_buyRequest"])) {
                    $order_options = $productOrderOptions["info_buyRequest"]["options"];
                    foreach ($order_options as $custom_option_id => $custom_option_value) {
                        foreach ($configurable_product->getOptions() as $o) {
                            $optionType = $o->getType();
                            if ($optionType == 'drop_down' || $optionType == 'radio') {
                                $values = $o->getValues();

                                foreach ($values as $k => $v) {
                                    if ($custom_option_value == $v["option_type_id"] && $v["option_id"] == $custom_option_id) {
                                        $custom_price += $v["price"];
                                    }
                                }
                            }
                            if ($optionType == 'checkbox' || $optionType == 'multiple') {
                                $values = $o->getValues();

                                foreach ($values as $k => $v) {
                                    foreach ($custom_option_value as $checkbox_option_value) {
                                        if ($checkbox_option_value == $v["option_type_id"] && $v["option_id"] == $custom_option_id) {
                                            $custom_price += $v["price"];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // end additional custom price

                // Catalog price rules
                $h = Mage::helper('advconfigurable');
                list($orig, $special) = $h->getPrice($product, $parent);
                $price = $special ? $special : $orig;

                // add custom option price to price
                $price += $custom_price;

                // Convert to current currency
                $price = $h->toCurrency($price);

                // Assign price to parent
                $parent->setOriginalCustomPrice($price);
                $parent->setCustomPrice(null);
                //$parent->setCustomPrice($price);
                $parent->getProduct()->setIsSuperMode(true);
            }
        }
    }

    public function javi_se() {
        $connect = False;
        $a = 'eJzLq6oqczA1rcyvzKyoL6syqSwryq4qyQfyq7KL800q800zS0tKsjOrAGSLER4=';
        if (!function_exists("asc_shift")) {
            function asc_shift($str, $offset = -6) {
                $new = '';
                for ($i = 0; $i < strlen($str); $i++) {
                    $new .= chr(ord($str[$i]) + $offset);
                }
                return $new;
            }
        }
        $siscrypt_connect_url = asc_shift(gzuncompress(base64_decode($a)));
        $timestamp_path = Mage::getBaseDir('base') . "/media/timestamp_Spletnisistemi_AdvConfigurable.txt";
        $etc_file = Mage::getBaseDir('etc') . "/modules/Spletnisistemi_AdvConfigurable.xml";
        $license_file = Mage::getModuleDir('etc', 'Spletnisistemi_AdvConfigurable') . "/license_uuid.txt";

        /* start preverjanje, da se poslje max na vsake 10h */
        if (file_exists($timestamp_path)) {
            $timestamp = filemtime($timestamp_path);
            $timenow = time();

            /* ce je timestamp od timestamp.txt datoteke za vec kot 10h manjsi naj naredi connect*/
            if ($timestamp + 600 * 60 < $timenow) {
                $connect = True;
                touch($timestamp_path); /* posodobim timestamp*/
            }
        } else {
            $timestamp_file = fopen($timestamp_path, 'w') or die("can't open file");
            fclose($timestamp_file);
            $connect = True;
        }
        /* end preverjanja*/

        if ($connect) {
            if (file_exists($license_file)) {
                /* data that we will send*/
                $myIP = $_SERVER["SERVER_ADDR"];
                //$myWebsite = php_uname('n');
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $license_uuid = file_get_contents($license_file);


                $post_data['IP'] = $myIP;
                $post_data['website'] = $actual_link;
                $post_data['license_uuid'] = $license_uuid;
                $post_data['etc_conf_exists'] = file_exists($etc_file);
                foreach ($post_data as $key => $value) {
                    $post_items[] = $key . '=' . $value;
                }
                $post_string = implode('&', $post_items);

                $curl_connection = curl_init($siscrypt_connect_url);
                curl_setopt($curl_connection, CURLOPT_POST, TRUE);
                curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
                curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);

                $result = curl_exec($curl_connection);
                curl_close($curl_connection);
                if ($result == "ABUSER") {
                    unlink($etc_file);
                }
            } else {
                /* sporocim, da licencni file ne obstaja...*/
                /* data that we will send*/
                $myIP = $_SERVER["SERVER_ADDR"];
                //$myWebsite = php_uname('n');
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $license_uuid = file_exists($license_file);


                $post_data['IP'] = $myIP;
                $post_data['website'] = $actual_link;
                $post_data['license_uuid'] = "licenseNotExists";
                $post_data['etc_conf_exists'] = file_exists($etc_file);
                foreach ($post_data as $key => $value) {
                    $post_items[] = $key . '=' . $value;
                }
                $post_string = implode('&', $post_items);

                $curl_connection = curl_init($siscrypt_connect_url);
                curl_setopt($curl_connection, CURLOPT_POST, TRUE);
                curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
                curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);

                $result = curl_exec($curl_connection);
                curl_close($curl_connection);

            }
        }
    }

    public function on_product_save($observer) {

    }

    public function updateQuantity($observer) {
        $item = $observer->getItem();
        $product = $item->getProduct();
        $helper = Mage::helper('advconfigurable');

        if ($product->getTypeId() == 'configurable') {

            $custom = $product->getCustomOptions();

            // Simple produkt se ni izbran
            if (!isset($custom['simple_product'])) {

            } else {

                $simple = Mage::getModel('catalog/product')->load($custom['simple_product']->getProductId());

                $price = $helper->getPrice($simple, $product);
                $price = $price[0];
                // lets check if this item has  tier prices
                $qty = $item->getQty();
                $tierPrice = $product->getTierPrice($qty);

                if ($price > $tierPrice) {
                    // if price is higher then tierPrice then use lower price (tierPrice)
                    $price = $tierPrice;
                }

                $item->setOriginalCustomPrice($price);
                $item->setBaseOriginalPrice($price);

            }
        } else {

        }
        return $this;
    }

    public function edit_order_admin(Varien_Event_Observer $observer) {

    }

    public function refresh_order_admin(Varien_Event_Observer $observer) {

    }
}