<?php
/**
 * Shortcode Class
 * 
 * @package Domain_WHOIS_Checker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode Class
 */
class DWC_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('domain_whois_checker', array($this, 'render_shortcode'));
    }

    /**
     * Render shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'placeholder' => __('Enter domain name...', 'domain-whois-checker'),
            'button_text' => __('Check Domain', 'domain-whois-checker'),
            'show_details' => false,
            'show_purchase' => true,
            'show_suggestions' => true,
            'style' => 'default'
        ), $atts, 'domain_whois_checker');

        // Generate unique ID for this instance
        $instance_id = 'dwc-' . uniqid();

        // Convert string booleans to actual booleans
        $show_details = filter_var($atts['show_details'], FILTER_VALIDATE_BOOLEAN);
        $show_purchase = filter_var($atts['show_purchase'], FILTER_VALIDATE_BOOLEAN);
        $show_suggestions = filter_var($atts['show_suggestions'], FILTER_VALIDATE_BOOLEAN);

        ob_start();
        ?>
        <div class="dwc-checker-container dwc-style-<?php echo esc_attr($atts['style']); ?>" id="<?php echo esc_attr($instance_id); ?>">
            <div class="dwc-form-container">
                <div class="dwc-input-group">
                    <input 
                        type="text" 
                        class="dwc-domain-input" 
                        placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                        id="<?php echo esc_attr($instance_id); ?>-input"
                    />
                    <button 
                        type="button" 
                        class="dwc-check-button"
                        data-instance="<?php echo esc_attr($instance_id); ?>"
                        data-show-details="<?php echo $show_details ? 'true' : 'false'; ?>"
                        data-show-purchase="<?php echo $show_purchase ? 'true' : 'false'; ?>"
                        data-show-suggestions="<?php echo $show_suggestions ? 'true' : 'false'; ?>"
                    >
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                
                <div class="dwc-loading" style="display: none;">
                    <span class="dwc-spinner"></span>
                    <span class="dwc-loading-text"><?php _e('Checking domain availability...', 'domain-whois-checker'); ?></span>
                </div>
            </div>

            <div class="dwc-results-container" style="display: none;">
                <div class="dwc-results-content"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate result HTML
     * 
     * @param array $result Domain check result
     * @param bool $show_details Whether to show detailed information
     * @param bool $show_purchase Whether to show purchase button
     * @param bool $show_suggestions Whether to show domain suggestions
     * @return string HTML output
     */
    public static function generate_result_html($result, $show_details = false, $show_purchase = true, $show_suggestions = true) {
        if (!$result || (isset($result['status']) && $result['status'] === 'error')) {
            $message = isset($result['message']) ? $result['message'] : __('Unable to check domain', 'domain-whois-checker');
            return '<div class="dwc-result dwc-error"><div class="dwc-message">' . esc_html($message) . '</div></div>';
        }

        $html = '<div class="dwc-result">';
        
        // Domain name
        $html .= '<div class="dwc-domain-name">' . esc_html($result['domain']) . '</div>';
        
        // Availability status
        if (isset($result['available'])) {
            $status_class = $result['available'] ? 'dwc-available' : 'dwc-not-available';
            $status_text = $result['available'] 
                ? __('Available', 'domain-whois-checker') 
                : __('Not Available', 'domain-whois-checker');
            
            $html .= '<div class="dwc-status ' . $status_class . '">';
            $html .= '<span class="dwc-status-icon"></span>';
            $html .= '<span class="dwc-status-text">' . esc_html($status_text) . '</span>';
            $html .= '</div>';
            
            // Show purchase button for available domains
            if ($result['available'] && $show_purchase) {
                $whmcs = new DWC_WHMCS_Integration();
                if ($whmcs->is_enabled()) {
                    $html .= $whmcs->generate_purchase_button($result['domain'], $result);
                }
            }
        }

        // Show detailed information if requested and domain is not available
        if ($show_details && isset($result['available']) && !$result['available']) {
            $html .= self::generate_whois_details_html($result);
        }
        
        // Show domain suggestions for unavailable domains
        if (isset($result['available']) && !$result['available'] && $show_suggestions && !empty($result['suggestions'])) {
            $whmcs = new DWC_WHMCS_Integration();
            if ($whmcs->is_enabled()) {
                $html .= $whmcs->generate_suggestions_html($result['domain'], $result['suggestions']);
            }
        }

        // Checked at timestamp
        if (!empty($result['checked_at'])) {
            $html .= '<div class="dwc-checked-at">';
            $html .= __('Checked at:', 'domain-whois-checker') . ' ';
            $html .= esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result['checked_at'])));
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
    
    /**
     * Generate WHOIS details HTML
     * 
     * @param array $result Domain check result
     * @return string HTML for WHOIS details
     */
    public static function generate_whois_details_html($result) {
        $html = '<div class="dwc-whois-details">';
        $html .= '<h4>' . __('WHOIS Information', 'domain-whois-checker') . '</h4>';
        $html .= '<div class="dwc-details-grid">';
        
        // Basic domain info
        $fields = array(
            'registrar' => __('Registrar', 'domain-whois-checker'),
            'created' => __('Created', 'domain-whois-checker'),
            'expires' => __('Expires', 'domain-whois-checker'),
            'updated' => __('Updated', 'domain-whois-checker')
        );
        
        foreach ($fields as $key => $label) {
            if (!empty($result[$key])) {
                $html .= '<div class="dwc-detail-item">';
                $html .= '<strong>' . esc_html($label) . ':</strong> ';
                $html .= '<span>' . esc_html($result[$key]) . '</span>';
                $html .= '</div>';
            }
        }
        
        // Contact information
        $contact_fields = array(
            'registrant_name' => __('Registrant Name', 'domain-whois-checker'),
            'registrant_organization' => __('Organization', 'domain-whois-checker'),
            'registrant_country' => __('Country', 'domain-whois-checker'),
            'admin_name' => __('Admin Contact', 'domain-whois-checker'),
            'tech_name' => __('Tech Contact', 'domain-whois-checker')
        );
        
        $has_contact_info = false;
        foreach ($contact_fields as $key => $label) {
            if (!empty($result[$key])) {
                if (!$has_contact_info) {
                    $html .= '<div class="dwc-detail-section">';
                    $html .= '<h5>' . __('Contact Information', 'domain-whois-checker') . '</h5>';
                    $has_contact_info = true;
                }
                $html .= '<div class="dwc-detail-item">';
                $html .= '<strong>' . esc_html($label) . ':</strong> ';
                $html .= '<span>' . esc_html($result[$key]) . '</span>';
                $html .= '</div>';
            }
        }
        if ($has_contact_info) {
            $html .= '</div>';
        }
        
        // Nameservers
        if (!empty($result['nameservers']) && is_array($result['nameservers'])) {
            $html .= '<div class="dwc-detail-section">';
            $html .= '<h5>' . __('Name Servers', 'domain-whois-checker') . '</h5>';
            $html .= '<div class="dwc-nameservers-list">';
            foreach ($result['nameservers'] as $ns) {
                $html .= '<div class="dwc-nameserver">' . esc_html($ns) . '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Domain status
        if (!empty($result['status']) && is_array($result['status'])) {
            $html .= '<div class="dwc-detail-section">';
            $html .= '<h5>' . __('Domain Status', 'domain-whois-checker') . '</h5>';
            $html .= '<div class="dwc-status-list">';
            foreach ($result['status'] as $status) {
                $html .= '<div class="dwc-status-item">' . esc_html($status) . '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close details-grid
        $html .= '</div>'; // Close whois-details
        
        return $html;
    }

    /**
     * Widget-style domain checker
     * 
     * @param array $args Widget arguments
     * @return string HTML output
     */
    public static function render_widget($args = array()) {
        $defaults = array(
            'title' => __('Domain Checker', 'domain-whois-checker'),
            'placeholder' => __('Enter domain...', 'domain-whois-checker'),
            'button_text' => __('Check', 'domain-whois-checker'),
            'show_details' => false,
            'compact' => true
        );

        $args = wp_parse_args($args, $defaults);
        $instance_id = 'dwc-widget-' . uniqid();

        ob_start();
        ?>
        <div class="dwc-widget" id="<?php echo esc_attr($instance_id); ?>">
            <?php if ($args['title']): ?>
                <h3 class="dwc-widget-title"><?php echo esc_html($args['title']); ?></h3>
            <?php endif; ?>
            
            <div class="dwc-widget-form">
                <div class="dwc-input-group <?php echo $args['compact'] ? 'dwc-compact' : ''; ?>">
                    <input 
                        type="text" 
                        class="dwc-domain-input" 
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                        id="<?php echo esc_attr($instance_id); ?>-input"
                    />
                    <button 
                        type="button" 
                        class="dwc-check-button"
                        data-instance="<?php echo esc_attr($instance_id); ?>"
                        data-show-details="<?php echo $args['show_details'] ? 'true' : 'false'; ?>"
                    >
                        <?php echo esc_html($args['button_text']); ?>
                    </button>
                </div>
                
                <div class="dwc-loading" style="display: none;">
                    <span class="dwc-spinner"></span>
                    <span class="dwc-loading-text"><?php _e('Checking...', 'domain-whois-checker'); ?></span>
                </div>
            </div>

            <div class="dwc-results-container" style="display: none;">
                <div class="dwc-results-content"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize shortcode class
new DWC_Shortcode();