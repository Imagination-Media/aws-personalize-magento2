/**
 * Amazon AWS Personalize integration (https://docs.aws.amazon.com/personalize/index.html)
 *
 * Use AWS Personalize to generate recommendations
 *
 * @package     ImaginationMedia\AwsPersonalize
 * @author      Igor Ludgero Miura <igor@imaginationmedia.com>
 * @copyright   Copyright (c) 2019 - 2020 Imagination Media (https://www.imaginationmedia.com/)
 * @license     https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

define([
    'uiComponent',
    'jquery',
    'ko',
    'underscore',
    'mageUtils',
    'Magento_Ui/js/lib/collapsible',
    'mage/translate'
], function (Component, $, ko, _, utils, Collapsible) {
    'use strict';
    return Collapsible.extend({
        initialize: function () {
            this._super();

            if (this.productId == 0) {
                var postData = {};
            } else {
                var postData = {
                    'itemId': this.productId
                };
            }

            var divClassType = this.classType;

            $.ajax({
                type: "POST",
                url: this.controllerUrl,
                async: true,
                data: postData,
                success: function (response) {
                    if (response.hasOwnProperty('error')) {
                        console.log(response.error);
                    } else {
                        var responseValues = Object.values(response);

                        $('.block-content.content.aws-personalize-'+divClassType).html();

                        if (responseValues.length > 0) {
                            $('.aws-personalize-'+divClassType).show();

                            for (var index in responseValues) {
                                var product = responseValues[index];
                                var productTemplate =  `<li class="item product product-item">
                                                            <div class="product-item-info">
                                                                <a href="${product.url}" class="product photo product-item-photo">
                                                                    <img src="${product.image}" alt="${product.name}" />
                                                                </a>
                                                                <div class="product details product-item-details">
                                                                    <strong class="product name product-item-name">
                                                                        <a class="product-item-link" title="${product.name}" href="${product.url}">${product.name}</a>
                                                                    </strong>
                                                                </div>
                                                                <div class="price-box price-final_price" data-role="priceBox" data-product-id="${product.id}" data-price-box="product-id-${product.id}">
                                                                    <span class="price-container price-final_price tax weee">
                                                                            <span id="product-price-${product.id}" data-price-amount="${product.price}" data-price-type="finalPrice" class="price-wrapper ">
                                                                                <span class="price">${product.price_with_currency}</span>
                                                                            </span>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </li>`;
                                $('.block-content.content.aws-personalize-'+divClassType+' .product-items').append(productTemplate);
                            }
                        } else {
                            $('.aws-personalize.'+divClassType).hide();
                        }
                    }
                },
                dataType: 'json'
            });
        }
    });
});
