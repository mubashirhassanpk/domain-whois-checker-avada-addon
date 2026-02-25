<?php
/**
 * Plugin Name: Domain WHOIS Checker - Avada Addon
 * Plugin URI: https://www.mubashirhassan.com.com/domain-whois-checker
 * Description: A WordPress plugin to check domain WHOIS information with Avada theme builder integration.
 * Version: 1.0.0
 * Author: Mubashir Hassan
 * Copyright: © 2025 Mubashir Hassan
 * Author URI: https://www.mubashirhassan.com
 * License: GPL v2 or later
 * Text Domain: domain-whois-checker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DWC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DWC_VERSION', '1.0.0');

/**
 * Main Domain WHOIS Checker Class
 */
class DomainWhoisChecker {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('domain-whois-checker', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Include required files
        $this->includes();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once DWC_PLUGIN_PATH . 'includes/class-whois-config.php';
        require_once DWC_PLUGIN_PATH . 'includes/class-whois-checker.php';
        require_once DWC_PLUGIN_PATH . 'includes/class-whmcs-integration.php';
        require_once DWC_PLUGIN_PATH . 'includes/class-admin.php';
        require_once DWC_PLUGIN_PATH . 'includes/class-shortcode.php';
        
        // Load Avada integration if Avada theme is active
        if ($this->is_avada_active()) {
            require_once DWC_PLUGIN_PATH . 'includes/class-avada-integration.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // AJAX hooks
        add_action('wp_ajax_dwc_check_domain', array($this, 'ajax_check_domain'));
        add_action('wp_ajax_nopriv_dwc_check_domain', array($this, 'ajax_check_domain'));
        
        // Initialize classes
        new DWC_Admin();
        new DWC_WHMCS_Integration();
    }

    /**
     * Check if Avada theme is active
     */
    private function is_avada_active() {
        return (class_exists('Avada') || function_exists('avada_theme_setup'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue clean frontend CSS without animations
        wp_enqueue_style(
            'dwc-frontend-style',
            DWC_PLUGIN_URL . 'assets/css/frontend-clean.css',
            array(),
            DWC_VERSION . '.5.0'
        );

        wp_enqueue_script(
            'dwc-frontend-script',
            DWC_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            DWC_VERSION . '.5.0',
            true
        );

        // Localize script for AJAX
        $settings = get_option('dwc_settings', array());
        wp_localize_script('dwc-frontend-script', 'dwc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dwc_nonce'),
            'checking_text' => __('Checking...', 'domain-whois-checker'),
            'error_text' => __('Error occurred while checking domain', 'domain-whois-checker'),
            'whmcs_enabled' => !empty($settings['enable_purchase']) && !empty($settings['whmcs_url']),
            'whmcs_url' => !empty($settings['whmcs_url']) ? trailingslashit($settings['whmcs_url']) : '',
            'purchase_button_text' => !empty($settings['purchase_button_text']) ? $settings['purchase_button_text'] : __('Get This Domain', 'domain-whois-checker'),
            'success_message' => !empty($settings['success_message']) ? $settings['success_message'] : __('Congratulations! {domain} is available!', 'domain-whois-checker')
        ));
        
        // Add inline CSS to force hide validation messages
        wp_add_inline_style('dwc-frontend-style', '
            .dwc-input-feedback,
            .dwc-tooltip,
            .tooltip,
            [data-tooltip],
            .dwc-validation-message,
            .dwc-input-group::before,
            .dwc-input-group::after,
            .dwc-domain-input + *:not(.dwc-check-button),
            .dwc-domain-input ~ .dwc-input-feedback {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
                z-index: -9999 !important;
                pointer-events: none !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
            }
        ');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin page
        if (strpos($hook, 'domain-whois-checker') === false) {
            return;
        }

        wp_enqueue_style(
            'dwc-admin-style',
            DWC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DWC_VERSION
        );

        wp_enqueue_script(
            'dwc-admin-script',
            DWC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            DWC_VERSION,
            true
        );
    }

    /**
     * AJAX handler for domain checking
     */
    public function ajax_check_domain() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dwc_nonce')) {
            wp_die('Security check failed');
        }

        $domain = sanitize_text_field($_POST['domain']);
        $show_details = isset($_POST['show_details']) ? filter_var($_POST['show_details'], FILTER_VALIDATE_BOOLEAN) : false;
        
        if (empty($domain)) {
            wp_send_json_error(array('message' => __('Domain name is required', 'domain-whois-checker')));
        }

        // Initialize WHOIS checker
        $whois_checker = new DWC_WHOIS_Checker();
        
        if ($show_details) {
            $result = $whois_checker->get_domain_info($domain);
        } else {
            $result = $whois_checker->check_domain($domain);
        }

        if ($result) {
            // Add suggestions for unavailable domains
            if (!empty($result['available']) && !$result['available']) {
                $result['suggestions'] = $whois_checker->get_domain_suggestions($domain);
            }
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => __('Unable to check domain', 'domain-whois-checker')));
        }
    }
}

/**
 * Initialize the plugin
 */
function dwc_init() {
    return new DomainWhoisChecker();
}

// Start the plugin
add_action('plugins_loaded', 'dwc_init');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'dwc_activate');
function dwc_activate() {
    // Create database tables or set default options if needed
    if (!get_option('dwc_settings')) {
        add_option('dwc_settings', array(
            'timeout' => 10,
            'cache_results' => true,
            'cache_duration' => 3600,
            'purchase_button_text' => 'Get This Domain'
        ));
    }
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'dwc_deactivate');
function dwc_deactivate() {
    // Clean up if needed
}