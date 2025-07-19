/**
 * MagoArab_EasYorder JavaScript
 *
 * @category    MagoArab
 * @package     MagoArab_EasYorder
 * @author      MagoArab Development Team
 * @copyright   Copyright (c) 2025 MagoArab
 * @license     https://opensource.org/licenses/MIT MIT License
 */

define([
    'jquery',
    'mage/url',
    'mage/translate'
], function ($, url, $t) {
    'use strict';

    return {
        /**
         * Initialize EasyOrder functionality
         * @param {Object} config - Configuration object
         */
        init: function(config) {
            this.config = config;
            this.bindEvents();
            this.initializeForm();
        },

        /**
         * Bind form events
         */
        bindEvents: function() {
            var self = this;
            
            // Quantity controls
            $(document).on('click', '.qty-btn', function() {
                self.handleQuantityChange($(this));
            });
            
            // Country change
            $(document).on('change', '#country_id', function() {
                self.loadShippingMethods();
            });
            
            // Address field changes
            $(document).on('change', '#city, #address, #region, #postcode, #qty', function() {
                if ($('#country_id').val()) {
                    self.loadShippingMethods();
                }
            });
            
            // Shipping method change
            $(document).on('change', 'input[name="shipping_method"]', function() {
                self.validateForm();
                self.updateCalculation();
            });
            
            // Payment method change
            $(document).on('change', 'input[name="payment_method"]', function() {
                self.validateForm();
            });
            
            // Form field validation
            $(document).on('change blur', '#easyorder-form input[required], #easyorder-form select[required], #easyorder-form textarea[required]', function() {
                self.validateForm();
            });
            
            // Form submission
            $(document).on('submit', '#easyorder-form', function(e) {
                e.preventDefault();
                self.submitOrder();
            });
        },

        /**
         * Initialize form state
         */
        initializeForm: function() {
            this.validateForm();
            
            // Auto-load shipping methods if country is pre-selected
            if ($('#country_id').val()) {
                this.loadShippingMethods();
            }
        },

        /**
         * Handle quantity button clicks
         * @param {jQuery} button - The clicked button
         */
        handleQuantityChange: function(button) {
            var action = button.data('action');
            var qtyInput = $('#qty');
            var currentQty = parseInt(qtyInput.val()) || 1;
            
            if (action === 'plus') {
                qtyInput.val(currentQty + 1);
            } else if (action === 'minus' && currentQty > 1) {
                qtyInput.val(currentQty - 1);
            }
            
            this.updateCalculation();
        },

        /**
         * Load available shipping methods
         */
        loadShippingMethods: function() {
            var self = this;
            var countryId = $('#country_id').val();
            var region = $('#region').val();
            var postcode = $('#postcode').val();
            
            if (!countryId) {
                return;
            }
            
            $('#shipping-section').show();
            $('#shipping-methods-container').html('<div class="loading-message">' + $t('Loading shipping methods...') + '</div>');
            
            $.post(this.config.urls.shipping, {
                product_id: this.config.productId,
                country_id: countryId,
                region: region,
                postcode: postcode
            })
            .done(function(response) {
                self.handleShippingMethodsResponse(response);
            })
            .fail(function() {
                $('#shipping-methods-container').html('<div class="error-message">' + $t('Error loading shipping methods') + '</div>');
            });
        },

        /**
         * Handle shipping methods response
         * @param {Object} response - AJAX response
         */
        handleShippingMethodsResponse: function(response) {
            if (response.success && response.shipping_methods && response.shipping_methods.length > 0) {
                var html = '<div class="shipping-methods">';
                
                $.each(response.shipping_methods, function(index, method) {
                    html += '<label class="shipping-method">';
                    html += '<input type="radio" name="shipping_method" value="' + method.code + '" class="shipping-radio">';
                    html += '<span class="shipping-label">' + method.carrier_title + ' - ' + method.title + '</span>';
                    html += '<span class="shipping-price">' + this.formatPrice(method.price) + '</span>';
                    html += '</label>';
                }.bind(this));
                
                html += '</div>';
                
                $('#shipping-methods-container').html(html);
                
                // Auto-select first method
                $('#shipping-methods-container input[name="shipping_method"]:first').prop('checked', true);
                
                this.validateForm();
                this.updateCalculation();
            } else {
                $('#shipping-methods-container').html('<div class="error-message">' + (response.message || $t('No shipping methods available')) + '</div>');
            }
        },

        /**
         * Update order calculation
         */
        updateCalculation: function() {
            var self = this;
            var shippingMethod = $('input[name="shipping_method"]:checked').val();
            var qty = parseInt($('#qty').val()) || 1;
            var countryId = $('#country_id').val();
            var region = $('#region').val();
            var postcode = $('#postcode').val();
            
            if (!shippingMethod || !countryId) {
                return;
            }
            
            $.post(this.config.urls.calculate, {
                product_id: this.config.productId,
                qty: qty,
                shipping_method: shippingMethod,
                country_id: countryId,
                region: region,
                postcode: postcode
            })
            .done(function(response) {
                if (response.success) {
                    self.updateOrderSummary(response.calculation);
                }
            })
            .fail(function() {
                console.log('Failed to calculate total');
            });
        },

        /**
         * Update order summary display
         * @param {Object} calculation - Calculation data
         */
        updateOrderSummary: function(calculation) {
            $('#product-subtotal').text(calculation.formatted.subtotal);
            $('#shipping-cost').text(calculation.formatted.shipping_cost);
            $('#order-total').text(calculation.formatted.total);
            $('#order-summary-section').show();
        },

        /**
         * Validate form and enable/disable submit button
         */
        validateForm: function() {
            var isValid = true;
            var form = $('#easyorder-form');
            
            // Check required fields
            form.find('input[required], select[required], textarea[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    return false;
                }
            });
            
            // Check shipping method selection
            if (!$('input[name="shipping_method"]:checked').length) {
                isValid = false;
            }
            
            // Check payment method selection
            if (!$('input[name="payment_method"]:checked').length) {
                isValid = false;
            }
            
            $('#easyorder-submit-btn').prop('disabled', !isValid);
        },

        /**
         * Submit order
         */
        submitOrder: function() {
            var self = this;
            var submitBtn = $('#easyorder-submit-btn');
            var loadingOverlay = $('#loading-overlay');
            var form = $('#easyorder-form');
            
            if (submitBtn.prop('disabled')) {
                return;
            }
            
            submitBtn.prop('disabled', true);
            loadingOverlay.show();
            this.hideMessages();
            
            $.post(this.config.urls.submit, form.serialize())
                .done(function(response) {
                    if (response.success) {
                        self.showSuccessMessage(response.message, response.increment_id);
                        form.hide();
                    } else {
                        self.showErrorMessage(response.message || $t('Error creating order'));
                        submitBtn.prop('disabled', false);
                    }
                })
                .fail(function() {
                    self.showErrorMessage($t('Connection error. Please try again.'));
                    submitBtn.prop('disabled', false);
                })
                .always(function() {
                    loadingOverlay.hide();
                });
        },

        /**
         * Show success message
         * @param {string} message - Success message
         * @param {string} orderNumber - Order increment ID
         */
        showSuccessMessage: function(message, orderNumber) {
            $('#success-text').text(message);
            $('#order-number').text(orderNumber);
            $('#success-message').show();
            this.scrollToElement($('#success-message'));
        },

        /**
         * Show error message
         * @param {string} message - Error message
         */
        showErrorMessage: function(message) {
            $('#error-text').text(message);
            $('#error-message').show();
            this.scrollToElement($('#error-message'));
        },

        /**
         * Hide all messages
         */
        hideMessages: function() {
            $('#success-message, #error-message').hide();
        },

        /**
         * Scroll to element
         * @param {jQuery} element - Element to scroll to
         */
        scrollToElement: function(element) {
            $('html, body').animate({
                scrollTop: element.offset().top - 50
            }, 500);
        },

        /**
         * Format price
         * @param {number} price - Price to format
         * @return {string} Formatted price
         */
        formatPrice: function(price) {
            try {
                return new Intl.NumberFormat('ar-EG', {
                    style: 'currency',
                    currency: 'EGP'
                }).format(price);
            } catch (e) {
                return price + ' ' + $t('EGP');
            }
        }
    };
});