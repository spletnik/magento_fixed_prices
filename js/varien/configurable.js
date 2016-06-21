/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 * @category    Varien
 * @package     js
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (typeof Product == 'undefined') {
    var Product = {};
}

/**************************** CONFIGURABLE PRODUCT **************************/
Product.Config = Class.create();
Product.Config.prototype = {
    initialize: function(config){
        this.config     = config;
        this.taxConfig  = this.config.taxConfig;
        if (config.containerId) {
            this.settings   = $$('#' + config.containerId + ' ' + '.super-attribute-select');
        } else {
            this.settings   = $$('.super-attribute-select');
        }
        this.state      = new Hash();
        this.priceTemplate = new Template(this.config.template);
        this.prices     = config.prices;
        
        // Set default values from config
        if (config.defaultValues) {
            this.values = config.defaultValues;
        }
        
        // Overwrite defaults by url
        var separatorIndex = window.location.href.indexOf('#');
        if (separatorIndex != -1) {
            var paramsStr = window.location.href.substr(separatorIndex+1);
            var urlValues = paramsStr.toQueryParams();
            if (!this.values) {
                this.values = {};
            }
            for (var i in urlValues) {
                this.values[i] = urlValues[i];
            }
        }
        
        // Overwrite defaults by inputs values if needed
        if (config.inputsInitialized) {
            this.values = {};
            this.settings.each(function(element) {
                if (element.value) {
                    var attributeId = element.id.replace(/[a-z]*/, '');
                    this.values[attributeId] = element.value;
                }
            }.bind(this));
        }
            
        // Put events to check select reloads 
        this.settings.each(function(element){
            Event.observe(element, 'change', this.configure.bind(this))
        }.bind(this));

        // fill state
        this.settings.each(function(element){
            var attributeId = element.id.replace(/[a-z]*/, '');
            if(attributeId && this.config.attributes[attributeId]) {
                element.config = this.config.attributes[attributeId];
                element.attributeId = attributeId;
                this.state[attributeId] = false;
            }
        }.bind(this))

        // Init settings dropdown
        var childSettings = [];
        for(var i=this.settings.length-1;i>=0;i--){
            var prevSetting = this.settings[i-1] ? this.settings[i-1] : false;
            var nextSetting = this.settings[i+1] ? this.settings[i+1] : false;
            if (i == 0){
                this.fillSelect(this.settings[i])
            } else {
                this.settings[i].disabled = true;
            }
            $(this.settings[i]).childSettings = childSettings.clone();
            $(this.settings[i]).prevSetting   = prevSetting;
            $(this.settings[i]).nextSetting   = nextSetting;
            childSettings.push(this.settings[i]);
        }

	optionsPrice.configurablePrices = {
		productPrice: optionsPrice.productPrice,
		productOldPrice: optionsPrice.productOldPrice == optionsPrice.productPrice?null:optionsPrice.productOldPrice,
	};

        // Set values to inputs
        this.configureForValues();
        document.observe("dom:loaded", this.configureForValues.bind(this));
    },
    
    configureForValues: function () {
        if (this.values) {
            this.settings.each(function(element){
                var attributeId = element.attributeId;
                element.value = (typeof(this.values[attributeId]) == 'undefined')? '' : this.values[attributeId];
                this.configureElement(element);
            }.bind(this));
        }
    },

    configure: function(event){
        var element = Event.element(event);
        this.configureElement(element);
    },

    configureElement : function(element) {
        this.reloadOptionLabels(element);
        if(element.value){
            this.state[element.config.id] = element.value;
            if(element.nextSetting){
                element.nextSetting.disabled = false;
                this.fillSelect(element.nextSetting);
                this.resetChildren(element.nextSetting);
            }
        }
        else {
            this.resetChildren(element);
        }
        this.reloadPrice();
    },

    reloadOptionLabels: function(element){
        var selectedPrice;
        if(element.options[element.selectedIndex].config && !this.config.stablePrices){
            selectedPrice = parseFloat(element.options[element.selectedIndex].config.price)
        }
        else{
            selectedPrice = 0;
        }
        for(var i=0;i<element.options.length;i++){
            if(element.options[i].config){
                element.options[i].text = this.getOptionLabel(element.options[i].config, element.options[i].config.price-selectedPrice);
            }
        }
    },

    resetChildren : function(element){
        if(element.childSettings) {
            for(var i=0;i<element.childSettings.length;i++){
                element.childSettings[i].selectedIndex = 0;
                element.childSettings[i].disabled = true;
                if(element.config){
                    this.state[element.config.id] = false;
                }
            }
        }
    },

    fillSelect: function(element){
        var attributeId = element.id.replace(/[a-z]*/, '');
        var options = this.getAttributeOptions(attributeId);
        this.clearSelect(element);
        element.options[0] = new Option(this.config.chooseText, '');

        var prevConfig = false;
        if(element.prevSetting){
            prevConfig = element.prevSetting.options[element.prevSetting.selectedIndex];
        }

        if(options) {
            var index = 1;
            for(var i=0;i<options.length;i++){
                var allowedProducts = [];
                if(prevConfig) {
                    for(var j=0;j<options[i].products.length;j++){
                        if(prevConfig.config.allowedProducts
                            && prevConfig.config.allowedProducts.indexOf(options[i].products[j])>-1){
                            allowedProducts.push(options[i].products[j]);
                        }
                    }
                } else {
                    allowedProducts = options[i].products.clone();
                }

                if(allowedProducts.size()>0){
                    options[i].allowedProducts = allowedProducts;
                    element.options[index] = new Option(this.getOptionLabel(options[i], options[i].price), options[i].id);
                    if (typeof options[i].price != 'undefined') {
                        element.options[index].setAttribute('price', options[i].price);
                    }
                    element.options[index].config = options[i];
                    index++;
                }
            }
        }
    },

    getOptionLabel: function(option, price){
        var price = parseFloat(price);
        if (this.taxConfig.includeTax) {
            var tax = price / (100 + this.taxConfig.defaultTax) * this.taxConfig.defaultTax;
            var excl = price - tax;
            var incl = excl*(1+(this.taxConfig.currentTax/100));
        } else {
            var tax = price * (this.taxConfig.currentTax / 100);
            var excl = price;
            var incl = excl + tax;
        }

        if (this.taxConfig.showIncludeTax || this.taxConfig.showBothPrices) {
            price = incl;
        } else {
            price = excl;
        }

        var str = option.label;
        if(price){
            if (this.taxConfig.showBothPrices) {
                str+= ' ' + this.formatPrice(excl, true) + ' (' + this.formatPrice(price, true) + ' ' + this.taxConfig.inclTaxTitle + ')';
            } else {
                str+= ' ' + this.formatPrice(price, true);
            }
        }
        return str;
    },

    formatPrice: function(price, showSign){
        var str = '';
        price = parseFloat(price);
        if(showSign){
            if(price<0){
                str+= '-';
                price = -price;
            }
            else{
                str+= '+';
            }
        }

        var roundedPrice = (Math.round(price*100)/100).toString();

        if (this.prices && this.prices[roundedPrice]) {
            str+= this.prices[roundedPrice];
        }
        else {
            str+= this.priceTemplate.evaluate({price:price.toFixed(2)});
        }
        return str;
    },

    clearSelect: function(element){
        for(var i=element.options.length-1;i>=0;i--){
            element.remove(i);
        }
    },

    getAttributeOptions: function(attributeId){
        if(this.config.attributes[attributeId]){
            return this.config.attributes[attributeId].options;
        }
    },

	getSimplePid: function() {
		if(!SSAdvConfigurablePrices) return null;

		var optionHash = "";
		var options = document.getElementsByClassName('SSAdvConfigurable');
		for(var i=0; i<options.length; i++) {
			var val = options[i].value;
			if(!val) {
				optionHash = null;
				break;
			}
			optionHash += options[i].getAttribute('rel')+val;
		}
		var pid = SSAdvConfigurablePrices.options[optionHash];
		return pid?pid:null;
	},

        // Helper: Get price with/without tax
        calcTax: function(price) { // Price from admin panel
	    var tax = 1+optionsPrice.currentTax/100;
	    var tprice;
	    if(optionsPrice.includeTax == "true") {
		tprice = price;
		price = tprice/tax;
	    } else {
		tprice = price*tax;
	    }
	    return {
		price: price,
		tprice: tprice
	    }
	},
	setTierPrice: function(pid) {
	    var qty = parseInt(document.getElementById('qty').value, 10);
	    var tiers = SSAdvConfigurablePrices.tiers[pid];
	    if(!tiers) return false;
	    var price = false;
	    for(var i in tiers) {
		if(qty >= i)
		    price = tiers[i];
	    }
	    if(price !== false)
		return this.calcTax(price);
	    return false;
	},

	showTiers: function(pid) {
		var tiers = SSAdvConfigurablePrices.tiers[pid];
		if(tiers == undefined) return false;

		$$('#product_addtocart_form .price-box')[0].insert({
			after: '<ul class="tier-prices product-pricing"></ul>'
		});
		var tierbox = $$('#product_addtocart_form .tier-prices')[0];

		// Show tier prices
		var html = '';
		var i = 0;
		for(var q in tiers) {
		    // Label
		    var label = SSAdvConfigurableLabel['tiers'];

		    // Get price
		    var price = SSAdvConfigurablePrices.prices[pid];
		    price = this.calcTax(price[1]?price[1]:price[0]);
		    // ... and tier price
		    var tier = this.calcTax(parseFloat(tiers[q]));

		    var percent = ((1-tier.price/price.price) * 100).toFixed(2);

		    var first = ((optionsPrice.showIncludeTax && !optionsPrice.showBothPrice)?tier.tprice:tier.price).toFixed(2);
		    var second = tier.tprice.toFixed(2);
		    label = label.replace('INDEX', i);
		    label = label.replace('QTY', parseInt(q));
		    label = label.replace('PRICE', optionsPrice.formatPrice(first));
		    label = label.replace('PERCENT', percent);
		    if(optionsPrice.showBothPrices)
			label = label.replace('PRICE', optionsPrice.formatPrice(second));
		    html += '<li>'+label+'</li>';
		}
		tierbox.innerHTML = html;
	},
    
    // html
    showSpecialHtml: function() {
	// Already exist
	if(document.querySelector('.product-shop .price-box .special-price')) return;

	var special = SSAdvConfigurableLabel['special'];
	var pricebox = $$('.product-essential .price-box');
	if(pricebox.length)
	    pricebox[0].innerHTML = special.replace('_CLONE_', '').replace('_YIELD_', pricebox[0].innerHTML);
	if(pricebox.length >= 2)
	    pricebox[1].innerHTML = special.replace('CLONE_', 'clone').replace('_YIELD_', pricebox[1].innerHTML);
    },

    hideSpecialHtml: function() {
	// Already hidden
	if(!document.querySelector('.product-shop .price-box .special-price')) return;

	var c = 0;
	$$('.product-essential .price-box').each(function(e) {
	    if(optionsPrice.showBothPrices) {  
		var exc = e.querySelector('.price-excluding-tax');
		var inc = e.querySelector('.price-including-tax');
		e.innerHTML = (exc?exc.outerHTML:'')+(inc?inc.outerHTML:'');
	    } else {
		var template = '<span class="regular-price" id="product-price-'+optionsPrice.productId+(c?'_clone':'')+'">' +
		    '<span class="price"></span>' +
		    '</span>';
		e.innerHTML = template;
		c++;
	    }
	});
    },
    reloadPrice: function() {
	if(typeof SSAdvConfigurablePrices === 'undefined') return;
	var tierbox = $$('#product_addtocart_form .tier-prices');
	if(tierbox.length) tierbox[0].remove();

	var pid = this.getSimplePid();
	var price = null;
	var special = null;
	if(pid) {
	    // Simple price
	    var p;
	    var pr = SSAdvConfigurablePrices.prices[pid];
	    price = pr[0];
	    if(p = this.setTierPrice(pid)) {
		special = (optionsPrice.includeTax == "true")?p.tprice:p.price;
		if(pr[1] !== null && pr[1] < special)
		    special = pr[1];
	    } else {
		special = pr[1];
	    }
	    this.showTiers(pid);
	}else{
	    // Configurable price
	    var original = optionsPrice.configurablePrices;
	    var tax = 1+(optionsPrice.currentTax/100);
	    if(original.productOldPrice === null) {
		price = original.productPrice;
	    } else {
		special = original.productPrice;
		price = original.productOldPrice;
	    }
	}


	// 2-decimal rounding
	price = parseFloat(price).toFixed(2);

	// Set prices
	// console.log(price + " => " + special); (doesn't works in IE8)

	if(special) {
	    this.showSpecialHtml();
	    optionsPrice.productOldPrice = price;
	    optionsPrice.productPrice = special;
	} else {
	    this.hideSpecialHtml();
	    optionsPrice.productPrice = price;
	}
	optionsPrice.reload();
    }
};

document.observe('dom:loaded', function() {
	// Remove tier box
	if(typeof SSAdvConfigurablePrices !== 'undefined') {
	    var tierbox = $$('#product_addtocart_form .tier-prices');
	    if(tierbox.length) tierbox[0].remove();

	    // Qty change event
	    document.getElementById('qty').observe('keyup', function() {
		spConfig.reloadPrice();
	    });
	}
});
