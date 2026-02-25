<?php
/**
 * WHMCS Integration Class
 * 
 * @package Domain_WHOIS_Checker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WHMCS Integration Class
 */
class DWC_WHMCS_Integration {

    /**
     * WHMCS base URL
     * @var string
     */
    private $whmcs_url;

    /**
     * Settings
     * @var array
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('dwc_settings', array());
        $this->whmcs_url = isset($this->settings['whmcs_url']) ? trailingslashit($this->settings['whmcs_url']) : '';
        
        // Add hooks
        add_action('wp_ajax_dwc_get_purchase_url', array($this, 'ajax_get_purchase_url'));
        add_action('wp_ajax_nopriv_dwc_get_purchase_url', array($this, 'ajax_get_purchase_url'));
    }

    /**
     * Check if WHMCS integration is enabled
     * 
     * @return bool True if enabled
     */
    public function is_enabled() {
        return !empty($this->settings['enable_purchase']) && !empty($this->whmcs_url);
    }

    /**
     * Get domain purchase URL
     * 
     * @param string $domain Domain name
     * @return string Purchase URL
     */
    public function get_purchase_url($domain) {
        if (!$this->is_enabled()) {
            return '';
        }

        $domain = $this->sanitize_domain($domain);
        
        if (!$domain) {
            return '';
        }

        // Build WHMCS cart URL
        $cart_url = $this->whmcs_url . 'cart.php';
        
        $params = array(
            'a' => 'add',
            'domain' => 'register',
            'query' => $domain
        );

        return add_query_arg($params, $cart_url);
    }

    /**
     * Get domain pricing information (if available via WHMCS API)
     * 
     * @param string $domain Domain name
     * @return array|false Pricing information or false
     */
    public function get_domain_pricing($domain) {
        if (!$this->is_enabled()) {
            return false;
        }

        // Extract TLD from domain
        $domain_parts = explode('.', $domain);
        if (count($domain_parts) < 2) {
            return false;
        }

        $tld = end($domain_parts);
        
        // Default pricing structure (can be customized via settings)
        $default_pricing = array(
            // Standard domains
            'com' => array('1year' => '$12.99', '2year' => '$25.98'),
            'net' => array('1year' => '$14.99', '2year' => '$29.98'),
            'org' => array('1year' => '$13.99', '2year' => '$27.98'),
            'info' => array('1year' => '$11.99', '2year' => '$23.98'),
            'biz' => array('1year' => '$15.99', '2year' => '$31.98'),
            'us' => array('1year' => '$9.99', '2year' => '$19.98'),
            'co' => array('1year' => '$29.99', '2year' => '$59.98'),
            'me' => array('1year' => '$19.99', '2year' => '$39.98'),
            'io' => array('1year' => '$49.99', '2year' => '$99.98'),
            
            // Pakistani domains - Competitive pricing
            'pk' => array('1year' => 'PKR 1,500', '2year' => 'PKR 3,000', '3year' => 'PKR 4,500'),
            'com.pk' => array('1year' => 'PKR 1,200', '2year' => 'PKR 2,400', '3year' => 'PKR 3,600'),
            'net.pk' => array('1year' => 'PKR 1,200', '2year' => 'PKR 2,400', '3year' => 'PKR 3,600'),
            'org.pk' => array('1year' => 'PKR 1,200', '2year' => 'PKR 2,400', '3year' => 'PKR 3,600'),
            'edu.pk' => array('1year' => 'PKR 1,000', '2year' => 'PKR 2,000', '3year' => 'PKR 3,000'),
            'web.pk' => array('1year' => 'PKR 1,300', '2year' => 'PKR 2,600', '3year' => 'PKR 3,900'),
            'biz.pk' => array('1year' => 'PKR 1,400', '2year' => 'PKR 2,800', '3year' => 'PKR 4,200'),
            'fam.pk' => array('1year' => 'PKR 1,100', '2year' => 'PKR 2,200', '3year' => 'PKR 3,300'),
            'gok.pk' => array('1year' => 'PKR 2,000', '2year' => 'PKR 4,000', '3year' => 'PKR 6,000'),
            'gob.pk' => array('1year' => 'PKR 2,000', '2year' => 'PKR 4,000', '3year' => 'PKR 6,000'),
            'gov.pk' => array('1year' => 'PKR 2,000', '2year' => 'PKR 4,000', '3year' => 'PKR 6,000'),
            'info.pk' => array('1year' => 'PKR 1,300', '2year' => 'PKR 2,600', '3year' => 'PKR 3,900'),
            'tv.pk' => array('1year' => 'PKR 1,800', '2year' => 'PKR 3,600', '3year' => 'PKR 5,400'),
            'online.pk' => array('1year' => 'PKR 1,600', '2year' => 'PKR 3,200', '3year' => 'PKR 4,800'),
            'store.pk' => array('1year' => 'PKR 1,700', '2year' => 'PKR 3,400', '3year' => 'PKR 5,100'),
            'tech.pk' => array('1year' => 'PKR 1,900', '2year' => 'PKR 3,800', '3year' => 'PKR 5,700'),
            'pro.pk' => array('1year' => 'PKR 1,600', '2year' => 'PKR 3,200', '3year' => 'PKR 4,800')
        );

        // Check if custom pricing is set in options
        $custom_pricing = get_option('dwc_domain_pricing', array());
        
        if (!empty($custom_pricing[$tld])) {
            return $custom_pricing[$tld];
        }

        return isset($default_pricing[$tld]) ? $default_pricing[$tld] : false;
    }

    /**
     * Generate purchase button HTML
     * 
     * @param string $domain Domain name
     * @param array $result Domain check result
     * @return string HTML for purchase button
     */
    public function generate_purchase_button($domain, $result = array()) {
        if (!$this->is_enabled() || empty($result['available']) || !$result['available']) {
            return '';
        }

        $purchase_url = $this->get_purchase_url($domain);
        
        if (empty($purchase_url)) {
            return '';
        }

        $button_text = isset($this->settings['purchase_button_text']) 
            ? $this->settings['purchase_button_text'] 
            : __('Get This Domain', 'domain-whois-checker');

        $pricing = $this->get_domain_pricing($domain);
        $pricing_text = '';
        
        if ($pricing && isset($pricing['1year'])) {
            $pricing_text = ' - ' . $pricing['1year'] . '/year';
        }

        ob_start();
        ?>
        <div class="dwc-purchase-section">
            <div class="dwc-success-message">
                <?php echo $this->get_success_message($domain); ?>
            </div>
            
            <?php if ($pricing): ?>
                <div class="dwc-pricing-info">
                    <div class="dwc-pricing-options">
                        <?php foreach ($pricing as $period => $price): ?>
                            <span class="dwc-price-option">
                                <strong><?php echo esc_html($period); ?>:</strong> <?php echo esc_html($price); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="dwc-purchase-actions">
                <a href="<?php echo esc_url($purchase_url); ?>" 
                   class="dwc-purchase-button" 
                   target="_blank" 
                   rel="noopener noreferrer">
                    <?php echo esc_html($button_text); ?><?php echo esc_html($pricing_text); ?>
                </a>
                
                <div class="dwc-purchase-info">
                    <small><?php _e('Opens secure checkout in new tab', 'domain-whois-checker'); ?></small>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get success message for available domain
     * 
     * @param string $domain Domain name
     * @return string Success message
     */
    public function get_success_message($domain) {
        $template = isset($this->settings['success_message']) 
            ? $this->settings['success_message'] 
            : __('Congratulations! {domain} is available!', 'domain-whois-checker');

        return str_replace('{domain}', '<strong>' . esc_html($domain) . '</strong>', $template);
    }

    /**
     * AJAX handler for getting purchase URL
     */
    public function ajax_get_purchase_url() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dwc_nonce')) {
            wp_die('Security check failed');
        }

        $domain = sanitize_text_field($_POST['domain']);
        
        if (empty($domain)) {
            wp_send_json_error(array('message' => __('Domain name is required', 'domain-whois-checker')));
        }

        $purchase_url = $this->get_purchase_url($domain);
        
        if ($purchase_url) {
            wp_send_json_success(array(
                'purchase_url' => $purchase_url,
                'domain' => $domain
            ));
        } else {
            wp_send_json_error(array('message' => __('Unable to generate purchase URL', 'domain-whois-checker')));
        }
    }

    /**
     * Sanitize domain name
     * 
     * @param string $domain Domain name
     * @return string|false Sanitized domain or false on error
     */
    private function sanitize_domain($domain) {
        // Remove whitespace
        $domain = trim($domain);
        
        // Convert to lowercase
        $domain = strtolower($domain);
        
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove www
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Remove trailing slash and path
        $domain = explode('/', $domain)[0];
        
        // Remove port
        $domain = explode(':', $domain)[0];
        
        // Validate domain format
        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
            return false;
        }
        
        return $domain;
    }

    /**
     * Generate domain suggestions HTML
     * 
     * @param string $domain Original domain
     * @param array $suggestions Array of suggested domains
     * @return string HTML for domain suggestions
     */
    public function generate_suggestions_html($domain, $suggestions) {
        if (empty($suggestions) || !$this->is_enabled()) {
            return '';
        }

        ob_start();
        ?>
        <div class="dwc-suggestions-section">
            <h4><?php _e('Alternative Suggestions:', 'domain-whois-checker'); ?></h4>
            <div class="dwc-suggestions-list">
                <?php foreach (array_slice($suggestions, 0, 6) as $suggestion): ?>
                    <div class="dwc-suggestion-item">
                        <span class="dwc-suggestion-domain"><?php echo esc_html($suggestion); ?></span>
                        <a href="<?php echo esc_url($this->get_purchase_url($suggestion)); ?>" 
                           class="dwc-suggestion-buy" 
                           target="_blank" 
                           rel="noopener noreferrer">
                            <?php _e('Buy', 'domain-whois-checker'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get WHMCS system URL for admin testing
     * 
     * @return string WHMCS URL or empty string
     */
    public function get_whmcs_url() {
        return $this->whmcs_url;
    }

    /**
     * Test WHMCS connection
     * 
     * @return array Test result
     */
    public function test_connection() {
        if (!$this->is_enabled()) {
            return array(
                'success' => false,
                'message' => __('WHMCS integration is not enabled', 'domain-whois-checker')
            );
        }

        $test_url = $this->whmcs_url . 'cart.php';
        $response = wp_remote_get($test_url, array(
            'timeout' => 10,
            'user-agent' => 'Domain WHOIS Checker WordPress Plugin'
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(__('Connection failed: %s', 'domain-whois-checker'), $response->get_error_message())
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => sprintf(__('HTTP Error: %d', 'domain-whois-checker'), $response_code)
            );
        }

        return array(
            'success' => true,
            'message' => __('WHMCS connection successful', 'domain-whois-checker')
        );
    }
}