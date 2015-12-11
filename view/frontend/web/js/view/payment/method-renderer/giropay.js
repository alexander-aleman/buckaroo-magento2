/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'TIG_Buckaroo/js/action/place-order',
        'ko'
    ],
    function (
        $,
        Component,
        additionalValidators,
        placeOrderAction,
        ko
    ) {
        'use strict';



        /**
         * Add validation methods
         * */


        $.validator.addMethod(
            'BIC', function (value) {
                var patternBIC = new RegExp('^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$');
                return patternBIC.test(value);
            }, $.mage.__('Enter Valid BIC number'));






        return Component.extend({
            defaults: {
                template: 'TIG_Buckaroo/payment/tig_buckaroo_giropay'
            },

            initObservable: function () {

                /**
                 * Bind this values to the input field.
                 */

                this.bicnumber = ko.observable('');

                this.bicnumber.subscribe( function () {
                    $('.' + this.getCode() + ' [data-validate]').valid();
                }, this);


                /**
                 * Run validation on the inputfield
                 */

                var runValidation = function () {
                    $('.' + this.getCode() + ' [data-validate]').valid();
                };
                this.bicnumber.subscribe(runValidation,this);


                /**
                 * Check if the required fields are filled. If so: enable place order button | if not: disable place order button
                 */

                this.buttoncheck = ko.computed( function () {
                    return this.bicnumber().length > 0;
                }, this);

                return this;
            },

            /**
             * Run function
             */
                
            validate: function () {
                return $('.' + this.getCode() + ' [data-validate]').valid();
            },

            /**
             * Place order.
             *
             * @todo    To override the script used for placeOrderAction, we need to override the placeOrder method
             *          on our parent class (Magento_Checkout/js/view/payment/default) so we can
             *
             *          placeOrderAction has been changed from Magento_Checkout/js/action/place-order to our own
             *          version (TIG_Buckaroo/js/action/place-order) to prevent redirect and handle the response.
             */
            placeOrder: function (data, event) {
                var self = this,
                    placeOrder;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            afterPlaceOrder: function () {
                var response = window.checkoutConfig.payment.buckaroo.response;
                response = $.parseJSON(response);
                if (response.RequiredAction !== undefined && response.RequiredAction.RedirectURL !== undefined) {
                    window.location.replace(response.RequiredAction.RedirectURL);
                }
            },

            getData: function() {
                return {
                    "method": this.item.method,
                    "po_number": null,
                    "additional_data": {
                        "customer_bic": this.bicnumber()
                    }
                };
            }
        });
    }
);
