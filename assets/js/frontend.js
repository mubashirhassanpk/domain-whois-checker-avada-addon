/**
 * Frontend JavaScript for Domain WHOIS Checker
 */
(function($) {
    'use strict';

    // Domain WHOIS Checker Class
    window.DomainWhoisChecker = {
        
        /**
         * Initialize the checker
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Check button click
            $(document).on('click', '.dwc-check-button', this.handleCheckDomain);
            
            // Enter key press on input
            $(document).on('keypress', '.dwc-domain-input', function(e) {
                if (e.which === 13) { // Enter key
                    $(this).closest('.dwc-form-container').find('.dwc-check-button').click();
                }
            });

            // Enhanced real-time domain validation
            $(document).on('input', '.dwc-domain-input', this.handleInputChange);
            $(document).on('focus', '.dwc-domain-input', this.handleInputFocus);
            $(document).on('blur', '.dwc-domain-input', this.handleInputBlur);

            // Clear results when input changes significantly
            $(document).on('input', '.dwc-domain-input', function() {
                var container = $(this).closest('.dwc-checker-container, .dwc-fusion-container, .dwc-widget');
                container.find('.dwc-results-container').hide();
            });
        },

        /**
         * Handle input change with real-time validation
         */
        handleInputChange: function() {
            var $input = $(this);
            var domain = $input.val().trim();
            var $container = $input.closest('.dwc-checker-container, .dwc-fusion-container, .dwc-widget');
            var $button = $container.find('.dwc-check-button');
            var $feedback = $input.siblings('.dwc-input-feedback');
            
            // Remove existing feedback if input is empty
            if (!domain) {
                $input.removeClass('valid invalid');
                $feedback.removeClass('show');
                $button.prop('disabled', false);
                return;
            }
            
            // Real-time validation (feedback disabled)
            if (DomainWhoisChecker.isValidDomain(domain)) {
                $input.removeClass('invalid');
                // Don't show feedback - just enable button
                $button.prop('disabled', false);
            } else {
                $input.removeClass('valid');
                // Don't show feedback - just disable button
                $button.prop('disabled', false); // Keep button enabled
            }
        },

        /**
         * Handle input focus
         */
        handleInputFocus: function() {
            var $input = $(this);
            var $container = $input.closest('.dwc-input-group');
            $container.addClass('focused');
        },

        /**
         * Handle input blur
         */
        handleInputBlur: function() {
            var $input = $(this);
            var $container = $input.closest('.dwc-input-group');
            $container.removeClass('focused');
            
            // Validate on blur if there's content
            if ($input.val().trim()) {
                DomainWhoisChecker.handleInputChange.call(this);
            }
        },

        /**
         * Show input feedback (DISABLED)
         */
        showInputFeedback: function($input, type, message) {
            // Feedback completely disabled - do nothing
            return;
        },

        /**
         * Handle domain check
         */
        handleCheckDomain: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var instance = $button.data('instance');
            var showDetails = $button.data('show-details') === true || $button.data('show-details') === 'true';
            var showPurchase = $button.data('show-purchase') === true || $button.data('show-purchase') === 'true';
            var showSuggestions = $button.data('show-suggestions') === true || $button.data('show-suggestions') === 'true';
            
            var $container = $('#' + instance);
            var $input = $container.find('.dwc-domain-input');
            var $loading = $container.find('.dwc-loading');
            var $results = $container.find('.dwc-results-container');
            
            var domain = $input.val().trim();
            
            if (!domain) {
                DomainWhoisChecker.showError($container, dwc_ajax.error_text || 'Please enter a domain name');
                return;
            }

            // Validate domain format
            if (!DomainWhoisChecker.isValidDomain(domain)) {
                DomainWhoisChecker.showError($container, 'Please enter a valid domain name');
                return;
            }

            // Show enhanced loading state
            $button.prop('disabled', true).addClass('loading');
            
            // Update button text with typewriter effect
            var loadingTexts = [
                dwc_ajax.checking_text || 'Checking...',
                'Validating domain...',
                'Fetching WHOIS data...'
            ];
            var currentTextIndex = 0;
            
            var updateLoadingText = function() {
                if ($button.hasClass('loading')) {
                    $button.attr('data-loading-text', loadingTexts[currentTextIndex]);
                    currentTextIndex = (currentTextIndex + 1) % loadingTexts.length;
                    setTimeout(updateLoadingText, 1500);
                }
            };
            updateLoadingText();
            
            $loading.show().addClass('animated');
            $results.hide();

            // Make AJAX request
            $.ajax({
                url: dwc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dwc_check_domain',
                    domain: domain,
                    show_details: showDetails,
                    nonce: dwc_ajax.nonce
                },
                success: function(response) {
                    DomainWhoisChecker.handleResponse($container, response, showDetails, showPurchase, showSuggestions);
                },
                error: function(xhr, status, error) {
                    DomainWhoisChecker.showError($container, dwc_ajax.error_text || 'An error occurred while checking the domain');
                },
                complete: function() {
                    // Enhanced reset with smooth transition
                    $button.removeClass('loading').prop('disabled', false);
                    $button.text($button.data('original-text') || 'Check Domain');
                    $loading.removeClass('animated').hide();
                    
                    // Add completion pulse effect
                    $button.addClass('completed');
                    setTimeout(function() {
                        $button.removeClass('completed');
                    }, 600);
                }
            });

            // Store original button text if not already stored
            if (!$button.data('original-text')) {
                $button.data('original-text', $button.text());
            }
        },

        /**
         * Handle AJAX response
         */
        handleResponse: function($container, response, showDetails, showPurchase, showSuggestions) {
            var $results = $container.find('.dwc-results-container');
            var $content = $container.find('.dwc-results-content');
            
            if (response.success) {
                var html = this.generateResultHTML(response.data, showDetails, showPurchase, showSuggestions);
                $content.html(html);
                $results.slideDown();
                
                // Bind purchase button events
                this.bindPurchaseEvents($content);
            } else {
                this.showError($container, response.data.message || 'Unable to check domain');
            }
        },

        /**
         * Generate result HTML
         */
        generateResultHTML: function(result, showDetails, showPurchase, showSuggestions) {
            var html = '<div class="dwc-result">';
            
            // Domain name
            html += '<div class="dwc-domain-name">' + this.escapeHtml(result.domain) + '</div>';
            
            // Availability status
            if (typeof result.available !== 'undefined') {
                var statusClass = result.available ? 'dwc-available' : 'dwc-not-available';
                var statusText = result.available ? 'Available' : 'Not Available';
                
                html += '<div class="dwc-status ' + statusClass + '">';
                html += '<span class="dwc-status-icon"></span>';
                html += '<span class="dwc-status-text">' + statusText + '</span>';
                html += '</div>';
                
                // Show purchase section for available domains
                if (result.available && showPurchase && typeof dwc_ajax.whmcs_enabled !== 'undefined' && dwc_ajax.whmcs_enabled) {
                    html += this.generatePurchaseSection(result);
                }
            }

            // Show detailed WHOIS information if requested and domain is not available
            if (showDetails && !result.available) {
                html += this.generateWhoisDetails(result);
            }
            
            // Show domain suggestions for unavailable domains
            if (!result.available && showSuggestions && result.suggestions && result.suggestions.length > 0) {
                html += this.generateSuggestions(result.domain, result.suggestions);
            }

            // Checked at timestamp
            if (result.checked_at) {
                html += '<div class="dwc-checked-at">';
                html += 'Checked at: ' + this.escapeHtml(result.checked_at);
                html += '</div>';
            }

            html += '</div>';
            return html;
        },

        /**
         * Show error message
         */
        showError: function($container, message) {
            var $results = $container.find('.dwc-results-container');
            var $content = $container.find('.dwc-results-content');
            
            var html = '<div class="dwc-result dwc-error">';
            html += '<div class="dwc-message">' + this.escapeHtml(message) + '</div>';
            html += '</div>';
            
            $content.html(html);
            $results.slideDown();
        },

        /**
         * Validate domain format
         */
        isValidDomain: function(domain) {
            // Remove protocol if present
            domain = domain.replace(/^https?:\/\//, '');
            
            // Remove www if present
            domain = domain.replace(/^www\./, '');
            
            // Remove trailing slash and path
            domain = domain.split('/')[0];
            
            // Remove port
            domain = domain.split(':')[0];
            
            // Basic domain validation
            var domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/;
            return domainRegex.test(domain);
        },

        /**
         * Escape HTML characters
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },
        
        /**
         * Generate purchase section HTML
         */
        generatePurchaseSection: function(result) {
            var html = '<div class="dwc-purchase-section">';
            
            // Success message
            var successMessage = (typeof dwc_ajax.success_message !== 'undefined' && dwc_ajax.success_message) 
                ? dwc_ajax.success_message.replace('{domain}', '<strong>' + this.escapeHtml(result.domain) + '</strong>')
                : 'Congratulations! <strong>' + this.escapeHtml(result.domain) + '</strong> is available!';
            
            html += '<div class="dwc-success-message">' + successMessage + '</div>';
            
            // Purchase button
            var buttonText = (typeof dwc_ajax.purchase_button_text !== 'undefined' && dwc_ajax.purchase_button_text) 
                ? dwc_ajax.purchase_button_text 
                : 'Get This Domain';
            
            html += '<div class="dwc-purchase-actions">';
            html += '<button type="button" class="dwc-purchase-button" data-domain="' + this.escapeHtml(result.domain) + '">';
            html += this.escapeHtml(buttonText);
            html += '</button>';
            html += '<div class="dwc-purchase-info">';
            html += '<small>Opens secure checkout in new tab</small>';
            html += '</div>';
            html += '</div>';
            
            html += '</div>';
            return html;
        },
        
        /**
         * Generate WHOIS details HTML
         */
        generateWhoisDetails: function(result) {
            var html = '<div class="dwc-whois-details">';
            html += '<h4>WHOIS Information</h4>';
            html += '<div class="dwc-details-grid">';
            
            // Basic domain info
            var fields = {
                'registrar': 'Registrar',
                'created': 'Created',
                'expires': 'Expires',
                'updated': 'Updated'
            };
            
            for (var key in fields) {
                if (result[key]) {
                    html += '<div class="dwc-detail-item">';
                    html += '<strong>' + fields[key] + ':</strong> ';
                    html += '<span>' + this.escapeHtml(result[key]) + '</span>';
                    html += '</div>';
                }
            }
            
            // Contact information
            var contactFields = {
                'registrant_name': 'Registrant Name',
                'registrant_organization': 'Organization',
                'registrant_country': 'Country',
                'admin_name': 'Admin Contact',
                'tech_name': 'Tech Contact'
            };
            
            var hasContactInfo = false;
            for (var key in contactFields) {
                if (result[key]) {
                    if (!hasContactInfo) {
                        html += '<div class="dwc-detail-section">';
                        html += '<h5>Contact Information</h5>';
                        hasContactInfo = true;
                    }
                    html += '<div class="dwc-detail-item">';
                    html += '<strong>' + contactFields[key] + ':</strong> ';
                    html += '<span>' + this.escapeHtml(result[key]) + '</span>';
                    html += '</div>';
                }
            }
            if (hasContactInfo) {
                html += '</div>';
            }
            
            // Nameservers
            if (result.nameservers && Array.isArray(result.nameservers) && result.nameservers.length > 0) {
                html += '<div class="dwc-detail-section">';
                html += '<h5>Name Servers</h5>';
                html += '<div class="dwc-nameservers-list">';
                for (var i = 0; i < result.nameservers.length; i++) {
                    html += '<div class="dwc-nameserver">' + this.escapeHtml(result.nameservers[i]) + '</div>';
                }
                html += '</div>';
                html += '</div>';
            }
            
            // Domain status
            if (result.status && Array.isArray(result.status) && result.status.length > 0) {
                html += '<div class="dwc-detail-section">';
                html += '<h5>Domain Status</h5>';
                html += '<div class="dwc-status-list">';
                for (var i = 0; i < result.status.length; i++) {
                    html += '<div class="dwc-status-item">' + this.escapeHtml(result.status[i]) + '</div>';
                }
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div>'; // Close details-grid
            html += '</div>'; // Close whois-details
            
            return html;
        },
        
        /**
         * Generate domain suggestions HTML
         */
        generateSuggestions: function(originalDomain, suggestions) {
            if (!suggestions || suggestions.length === 0) {
                return '';
            }
            
            var html = '<div class="dwc-suggestions-section">';
            html += '<h4>Alternative Suggestions:</h4>';
            html += '<div class="dwc-suggestions-list">';
            
            for (var i = 0; i < Math.min(suggestions.length, 6); i++) {
                html += '<div class="dwc-suggestion-item">';
                html += '<span class="dwc-suggestion-domain">' + this.escapeHtml(suggestions[i]) + '</span>';
                html += '<button type="button" class="dwc-suggestion-buy" data-domain="' + this.escapeHtml(suggestions[i]) + '">Buy</button>';
                html += '</div>';
            }
            
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        /**
         * Bind purchase button events
         */
        bindPurchaseEvents: function($container) {
            var self = this;
            
            // Purchase button click
            $container.find('.dwc-purchase-button, .dwc-suggestion-buy').off('click').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var domain = $button.data('domain');
                
                if (!domain) {
                    return;
                }
                
                // Add loading state
                $button.addClass('loading').prop('disabled', true);
                
                // Generate purchase URL and redirect
                if (typeof dwc_ajax.whmcs_url !== 'undefined' && dwc_ajax.whmcs_url) {
                    var purchaseUrl = dwc_ajax.whmcs_url + 'cart.php?a=add&domain=register&query=' + encodeURIComponent(domain);
                    
                    // Show user feedback
                    var originalText = $button.text();
                    $button.text('Opening checkout...');
                    
                    // Delay to show the feedback, then open
                    setTimeout(function() {
                        window.open(purchaseUrl, '_blank', 'noopener,noreferrer');
                        
                        // Reset button after short delay
                        setTimeout(function() {
                            $button.removeClass('loading').prop('disabled', false).text(originalText);
                        }, 1000);
                    }, 500);
                } else {
                    alert('Purchase functionality is not configured.');
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        DomainWhoisChecker.init();
        
        // Auto-focus on first input if container is in viewport
        var $firstInput = $('.dwc-domain-input').first();
        if ($firstInput.length && DomainWhoisChecker.isInViewport($firstInput[0])) {
            setTimeout(function() {
                $firstInput.focus();
            }, 500);
        }
    });

    /**
     * Check if element is in viewport
     */
    DomainWhoisChecker.isInViewport = function(element) {
        var rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    };

})(jQuery);