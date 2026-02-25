/**
 * Admin JavaScript for Domain WHOIS Checker
 * Enhanced UX with live previews, validation, and connection testing
 */
(function($) {
    'use strict';

    /**
     * Admin JavaScript functionality
     */
    var DWC_Admin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initializeToggles();
            this.initializePreviews();
            this.checkDependencies();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Settings form changes
            $('#dwc_settings_form').on('change', 'input, select, textarea', this.handleSettingChange);
            
            // Enhanced real-time validation
            $('#dwc_settings_form').on('input', 'input[type="text"], input[type="url"], input[type="number"], textarea', this.validateFieldRealtime);
            $('#dwc_settings_form').on('blur', 'input[type="text"], input[type="url"], input[type="number"], textarea', this.validateFieldOnBlur);
            
            // Cache settings dependency
            $('#cache_results').on('change', this.toggleCacheDuration);
            
            // Purchase settings dependency
            $('#enable_purchase').on('change', this.togglePurchaseSettings);
            
            // Live preview updates with debouncing
            $('#purchase_button_text').on('input', this.debounce(this.updateButtonPreview, 300));
            $('#purchase_button_text').on('input', this.updateCharacterCount);
            $('#success_message').on('input', this.debounce(this.updateMessagePreview, 300));
            
            // Button suggestions
            $(document).on('click', '.dwc-suggestion-btn', this.applySuggestion);
            
            // WHMCS connection test
            $('#test-whmcs-connection').on('click', this.testWHMCSConnection);
            
            // Enhanced form validation
            $('#whmcs_url').on('blur', this.validateURL);
            $('#timeout').on('input', this.validateTimeout);
            
            // Auto-save indication
            $('form').on('submit', this.showSaveIndicator);
            
            // Form completion tracking
            this.trackFormCompletion();
        },

        /**
         * Debounce function for performance optimization
         */
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Enhanced real-time field validation
         */
        validateFieldRealtime: function() {
            var $field = $(this);
            var fieldId = $field.attr('id');
            var value = $field.val().trim();
            
            // Clear previous validation states
            $field.removeClass('valid invalid');
            DWC_Admin.hideFieldError($field);
            
            if (!value) return;
            
            // Specific validation rules
            switch(fieldId) {
                case 'whmcs_url':
                    if (DWC_Admin.isValidURL(value)) {
                        $field.addClass('valid');
                        DWC_Admin.showFieldSuccess($field, 'URL format is valid');
                    } else {
                        $field.addClass('invalid');
                        DWC_Admin.showFieldError($field, 'Please enter a valid URL');
                    }
                    break;
                    
                case 'timeout':
                    var timeout = parseInt(value);
                    if (!isNaN(timeout) && timeout >= 5 && timeout <= 120) {
                        $field.addClass('valid');
                        var tip = timeout <= 15 ? 'Fast response' : 
                                 timeout <= 60 ? 'Balanced' : 'Maximum reliability';
                        DWC_Admin.showFieldSuccess($field, tip);
                    } else {
                        $field.addClass('invalid');
                        DWC_Admin.showFieldError($field, 'Timeout must be between 5-120 seconds');
                    }
                    break;
                    
                case 'cache_duration':
                    var duration = parseInt(value);
                    if (!isNaN(duration) && duration >= 1 && duration <= 1440) {
                        $field.addClass('valid');
                        var hours = Math.round(duration / 60 * 10) / 10;
                        DWC_Admin.showFieldSuccess($field, 'Cache for ' + hours + ' hours');
                    } else {
                        $field.addClass('invalid');
                        DWC_Admin.showFieldError($field, 'Duration must be between 1-1440 minutes');
                    }
                    break;
                    
                case 'purchase_button_text':
                    if (value.length > 30) {
                        $field.addClass('invalid');
                        DWC_Admin.showFieldError($field, 'Text too long for button');
                    } else {
                        $field.addClass('valid');
                    }
                    break;
            }
        },

        /**
         * Validate field on blur
         */
        validateFieldOnBlur: function() {
            DWC_Admin.validateFieldRealtime.call(this);
        },

        /**
         * Check if URL is valid
         */
        isValidURL: function(url) {
            var urlPattern = /^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$/;
            return urlPattern.test(url);
        },

        /**
         * Track form completion for better UX
         */
        trackFormCompletion: function() {
            var $form = $('#dwc_settings_form');
            var requiredFields = $form.find('input[required], select[required]');
            var totalFields = requiredFields.length;
            
            if (totalFields === 0) return;
            
            var updateProgress = function() {
                var completedFields = requiredFields.filter(function() {
                    return $(this).val().trim() !== '';
                }).length;
                
                var percentage = Math.round((completedFields / totalFields) * 100);
                var $progress = $('.dwc-form-progress');
                
                if (!$progress.length) {
                    $progress = $('<div class="dwc-form-progress"><div class="dwc-progress-bar"></div><span class="dwc-progress-text"></span></div>');
                    $form.prepend($progress);
                }
                
                $progress.find('.dwc-progress-bar').css('width', percentage + '%');
                $progress.find('.dwc-progress-text').text('Form completion: ' + percentage + '%');
                
                if (percentage === 100) {
                    $progress.addClass('complete');
                } else {
                    $progress.removeClass('complete');
                }
            };
            
            requiredFields.on('input change', updateProgress);
            updateProgress(); // Initial check
        },

        /**
         * Initialize toggle switches
         */
        initializeToggles: function() {
            // Add smooth animations to toggle switches
            $('.dwc-toggle-label').each(function() {
                var $label = $(this);
                var $input = $label.find('input[type="checkbox"]');
                var $slider = $label.find('.dwc-toggle-slider');
                
                // Add ripple effect
                $slider.on('click', function(e) {
                    var $ripple = $('<span class="dwc-ripple"></span>');
                    var size = Math.max($slider.width(), $slider.height());
                    var x = e.pageX - $slider.offset().left - size / 2;
                    var y = e.pageY - $slider.offset().top - size / 2;
                    
                    $ripple.css({
                        width: size,
                        height: size,
                        left: x,
                        top: y
                    }).appendTo($slider);
                    
                    setTimeout(function() {
                        $ripple.remove();
                    }, 600);
                });
            });
        },

        /**
         * Initialize live previews
         */
        initializePreviews: function() {
            this.updateButtonPreview();
            this.updateMessagePreview();
            this.updateCharacterCount();
        },

        /**
         * Check setting dependencies
         */
        checkDependencies: function() {
            this.toggleCacheDuration();
            this.togglePurchaseSettings();
        },

        /**
         * Toggle cache duration field
         */
        toggleCacheDuration: function() {
            var $cacheEnabled = $('#cache_results');
            var $cacheDuration = $('#cache_duration').closest('.dwc-setting-field');
            
            if ($cacheEnabled.is(':checked')) {
                $cacheDuration.slideDown(300);
            } else {
                $cacheDuration.slideUp(300);
            }
        },

        /**
         * Toggle purchase settings
         */
        togglePurchaseSettings: function() {
            var $purchaseEnabled = $('#enable_purchase');
            var $purchaseSettings = $('#whmcs_url, #purchase_button_text').closest('.dwc-setting-field');
            
            if ($purchaseEnabled.is(':checked')) {
                $purchaseSettings.slideDown(300);
            } else {
                $purchaseSettings.slideUp(300);
            }
        },

        /**
         * Update button preview
         */
        updateButtonPreview: function() {
            var buttonText = $('#purchase_button_text').val() || 'Purchase Domain';
            $('.dwc-purchase-button-preview, #live-button-preview').text(buttonText);
        },

        /**
         * Update message preview
         */
        updateMessagePreview: function() {
            var messageText = $('#success_message').val() || 'Congratulations! {domain} is available!';
            var previewText = messageText.replace('{domain}', '<strong>example.com</strong>');
            $('.dwc-success-message-preview').html(previewText);
        },

        /**
         * Update character count
         */
        updateCharacterCount: function() {
            var $input = $('#purchase_button_text');
            var $counter = $('#button-text-count');
            var $counterContainer = $('.dwc-character-count');
            var length = $input.val().length;
            var maxLength = 30;
            
            $counter.text(length);
            
            // Update counter styling based on length
            $counterContainer.removeClass('warning error');
            if (length > maxLength * 0.8) {
                $counterContainer.addClass('warning');
            }
            if (length > maxLength) {
                $counterContainer.addClass('error');
            }
        },

        /**
         * Apply suggestion to input field
         */
        applySuggestion: function(e) {
            e.preventDefault();
            var suggestionText = $(this).data('text');
            $('#purchase_button_text').val(suggestionText).trigger('input');
        },

        /**
         * Test WHMCS connection
         */
        testWHMCSConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#whmcs-test-result');
            var $validation = $('#whmcs-url-validation');
            var whmcsUrl = $('#whmcs_url').val();
            
            if (!whmcsUrl) {
                DWC_Admin.showValidationMessage($validation, 'error', dwc_admin_vars.messages.invalid_url);
                return;
            }
            
            // Show loading state
            $button.addClass('loading').prop('disabled', true);
            $result.removeClass('success error').text('');
            $validation.hide();
            
            $.ajax({
                url: dwc_admin_vars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dwc_test_whmcs_connection',
                    whmcs_url: whmcsUrl,
                    nonce: dwc_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var messageType = response.data.type === 'whmcs_detected' ? 'success' : 'warning';
                        DWC_Admin.showTestResult($result, 'success', response.data.message);
                        DWC_Admin.showValidationMessage($validation, messageType, response.data.message);
                    } else {
                        DWC_Admin.showTestResult($result, 'error', response.data.message);
                        DWC_Admin.showValidationMessage($validation, 'error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = dwc_admin_vars.messages.connection_failed;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    DWC_Admin.showTestResult($result, 'error', errorMsg);
                    DWC_Admin.showValidationMessage($validation, 'error', errorMsg);
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Show test result
         */
        showTestResult: function($element, type, message) {
            var icon = type === 'success' ? '✓' : '✗';
            var iconColor = type === 'success' ? '#0f5132' : '#721c24';
            
            $element.removeClass('success error').addClass(type).html(
                '<span style="display: inline-flex; align-items: center; gap: 5px;">' +
                '<span style="color: ' + iconColor + ';">' + icon + '</span>' +
                message +
                '</span>'
            ).hide().fadeIn(300);
        },

        /**
         * Show validation message
         */
        showValidationMessage: function($element, type, message) {
            $element.removeClass('success error warning').addClass(type);
            $element.html('<p>' + message + '</p>').slideDown(300);
            
            // Auto hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    $element.slideUp(300);
                }, 5000);
            }
        },

        /**
         * Validate URL field
         */
        validateURL: function() {
            var $input = $(this);
            var $validation = $('#whmcs-url-validation');
            var url = $input.val().trim();
            
            if (!url) {
                $input.removeClass('invalid valid');
                $validation.hide();
                return;
            }
            
            var urlPattern = /^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$/;
            
            if (!urlPattern.test(url)) {
                $input.removeClass('valid').addClass('invalid');
                DWC_Admin.showValidationMessage($validation, 'error', dwc_admin_vars.messages.invalid_url);
            } else {
                $input.removeClass('invalid').addClass('valid');
                DWC_Admin.showValidationMessage($validation, 'success', 'URL format is valid. Click "Test Connection" to verify.');
            }
        },

        /**
         * Validate timeout field
         */
        validateTimeout: function() {
            var $input = $(this);
            var timeout = parseInt($input.val());
            
            $input.removeClass('invalid valid');
            
            if (isNaN(timeout) || timeout < 5 || timeout > 120) {
                $input.addClass('invalid');
                DWC_Admin.showFieldError($input, dwc_admin_vars.messages.timeout_range);
            } else {
                $input.addClass('valid');
                DWC_Admin.hideFieldError($input);
                
                // Show helpful tip based on timeout value
                var tip = '';
                if (timeout <= 15) {
                    tip = 'Fast response, but may timeout with slow servers.';
                } else if (timeout <= 60) {
                    tip = 'Good balance of speed and reliability.';
                } else {
                    tip = 'Maximum reliability, but slower response.';
                }
                DWC_Admin.showFieldSuccess($input, tip);
            }
        },

        /**
         * Show field error
         */
        showFieldError: function($input, message) {
            var $error = $input.siblings('.dwc-field-error');
            if (!$error.length) {
                $error = $('<div class="dwc-field-error"></div>').insertAfter($input);
            }
            $error.text(message).fadeIn(200);
        },

        /**
         * Hide field error
         */
        hideFieldError: function($input) {
            $input.siblings('.dwc-field-error').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Show field success message
         */
        showFieldSuccess: function($input, message) {
            DWC_Admin.hideFieldError($input);
            var $success = $input.siblings('.dwc-field-success');
            if (!$success.length) {
                $success = $('<div class="dwc-field-success"></div>').insertAfter($input);
            }
            $success.text(message).fadeIn(200);
            
            // Auto hide after 3 seconds
            setTimeout(function() {
                $success.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Show save indicator
         */
        showSaveIndicator: function() {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var originalText = $submitButton.val();
            
            $submitButton.addClass('loading').val(dwc_admin_vars.messages.saving).prop('disabled', true);
            
            // Remove unsaved changes indicator
            $form.removeClass('has-changes');
            $('.dwc-save-reminder').slideUp(200, function() {
                $(this).remove();
            });
            
            // Show success feedback after form submission
            setTimeout(function() {
                $submitButton.removeClass('loading').val(dwc_admin_vars.messages.saved).css('background', '#46b450');
                
                // Reset button after 2 seconds
                setTimeout(function() {
                    $submitButton.val(originalText).css('background', '').prop('disabled', false);
                }, 2000);
            }, 500);
        },

        /**
         * Handle setting change
         */
        handleSettingChange: function() {
            var $form = $(this).closest('form');
            
            // Add visual feedback for unsaved changes
            $form.addClass('has-changes');
            
            // Show save reminder if not already shown
            if (!$form.find('.dwc-save-reminder').length) {
                var $reminder = $('<div class="dwc-save-reminder">' + 
                    '<span class="dashicons dashicons-info"></span> ' +
                    dwc_admin_vars.messages.unsaved_changes + 
                    '</div>');
                
                $reminder.prependTo($form).hide().slideDown(200);
            }
            
            // Add beforeunload warning
            $(window).on('beforeunload.dwc-changes', function() {
                return dwc_admin_vars.messages.unsaved_changes;
            });
        }
    };

    // Add CSS for new features
    $('<style>').text(`
        .dwc-field-error {
            color: #dc3232;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .dwc-save-reminder {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .has-changes .dwc-form-actions {
            background: rgba(255, 243, 205, 0.3);
            border-color: #ffeaa7;
        }
        
        .dwc-ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .dwc-setting-field input.valid {
            border-color: #46b450;
        }
        
        .dwc-setting-field input.invalid {
            border-color: #dc3232;
        }
    `).appendTo('head');

    // Initialize when document is ready
    $(document).ready(function() {
        DWC_Admin.init();
    });

})(jQuery);