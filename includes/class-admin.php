<?php
/**
 * Admin Interface Class
 * 
 * @package Domain_WHOIS_Checker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Interface Class
 */
class DWC_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_ajax_dwc_test_domain', array($this, 'ajax_test_domain'));
        add_action('wp_ajax_dwc_test_whmcs_connection', array($this, 'ajax_test_whmcs_connection'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin page
        if ($hook !== 'settings_page_domain-whois-checker') {
            return;
        }

        wp_enqueue_script(
            'dwc-admin-js',
            DWC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            DWC_VERSION,
            true
        );

        // Enqueue clean admin CSS without animations
        wp_enqueue_style(
            'dwc-admin-css',
            DWC_PLUGIN_URL . 'assets/css/admin-clean.css',
            array(),
            DWC_VERSION . '.4.0'
        );

        // Localize script with data
        wp_localize_script('dwc-admin-js', 'dwc_admin_vars', array(
            'nonce' => wp_create_nonce('dwc_admin_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'messages' => array(
                'testing_connection' => __('Testing connection...', 'domain-whois-checker'),
                'connection_success' => __('Connection successful!', 'domain-whois-checker'),
                'connection_failed' => __('Connection failed. Please check the URL and try again.', 'domain-whois-checker'),
                'invalid_url' => __('Please enter a valid URL.', 'domain-whois-checker'),
                'timeout_range' => __('Timeout must be between 5 and 120 seconds.', 'domain-whois-checker'),
                'unsaved_changes' => __('You have unsaved changes.', 'domain-whois-checker'),
                'saving' => __('Saving...', 'domain-whois-checker'),
                'saved' => __('Settings saved successfully!', 'domain-whois-checker')
            )
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Domain WHOIS Checker', 'domain-whois-checker'),
            __('WHOIS Checker', 'domain-whois-checker'),
            'manage_options',
            'domain-whois-checker',
            array($this, 'admin_page')
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('dwc_settings_group', 'dwc_settings', array($this, 'sanitize_settings'));

        // WHOIS Checker Configuration Section
        add_settings_section(
            'dwc_whois_section',
            __('WHOIS Checker Configuration', 'domain-whois-checker'),
            array($this, 'whois_section_callback'),
            'domain-whois-checker'
        );

        add_settings_field(
            'timeout',
            __('Connection Timeout', 'domain-whois-checker'),
            array($this, 'timeout_callback'),
            'domain-whois-checker',
            'dwc_whois_section'
        );

        add_settings_field(
            'cache_results',
            __('Enable Caching', 'domain-whois-checker'),
            array($this, 'cache_results_callback'),
            'domain-whois-checker',
            'dwc_whois_section'
        );

        add_settings_field(
            'cache_duration',
            __('Cache Duration', 'domain-whois-checker'),
            array($this, 'cache_duration_callback'),
            'domain-whois-checker',
            'dwc_whois_section'
        );

        // Domain Purchase Integration Section
        add_settings_section(
            'dwc_purchase_section',
            __('Domain Purchase Settings', 'domain-whois-checker'),
            array($this, 'purchase_section_callback'),
            'domain-whois-checker'
        );

        add_settings_field(
            'enable_purchase',
            __('Enable Purchase Feature', 'domain-whois-checker'),
            array($this, 'enable_purchase_callback'),
            'domain-whois-checker',
            'dwc_purchase_section'
        );

        add_settings_field(
            'whmcs_url',
            __('WHMCS/Billing System URL', 'domain-whois-checker'),
            array($this, 'whmcs_url_callback'),
            'domain-whois-checker',
            'dwc_purchase_section'
        );

        add_settings_field(
            'purchase_button_text',
            __('Purchase Button Text', 'domain-whois-checker'),
            array($this, 'purchase_button_text_callback'),
            'domain-whois-checker',
            'dwc_purchase_section'
        );

        add_settings_field(
            'success_message',
            __('Available Domain Message', 'domain-whois-checker'),
            array($this, 'success_message_callback'),
            'domain-whois-checker',
            'dwc_purchase_section'
        );
    }

    /**
     * WHOIS section callback
     */
    public function whois_section_callback() {
        echo '<p>' . __('Configure how the WHOIS domain checker operates, including timeout settings and caching options for better performance.', 'domain-whois-checker') . '</p>';
    }

    /**
     * Purchase section callback
     */
    public function purchase_section_callback() {
        echo '<p>' . __('Configure domain purchase functionality. Connect to your WHMCS or billing system to allow customers to purchase available domains directly.', 'domain-whois-checker') . '</p>';
        echo '<div class="dwc-notice dwc-info"><p><strong>' . __('Note:', 'domain-whois-checker') . '</strong> ' . __('You need a WHMCS installation or compatible billing system to enable purchase functionality.', 'domain-whois-checker') . '</p></div>';
    }

    /**
     * Timeout field callback
     */
    public function timeout_callback() {
        $settings = get_option('dwc_settings', array());
        $timeout = isset($settings['timeout']) ? $settings['timeout'] : 30;
        ?>
        <div class="dwc-setting-field">
            <div class="dwc-field-group">
                <input type="number" id="timeout" name="dwc_settings[timeout]" value="<?php echo esc_attr($timeout); ?>" min="5" max="120" class="small-text dwc-required" required />
                <span class="dwc-unit"><?php _e('seconds', 'domain-whois-checker'); ?></span>
                <p class="description">
                    <strong><?php _e('Recommended:', 'domain-whois-checker'); ?></strong> <?php _e('30-60 seconds for reliable results.', 'domain-whois-checker'); ?><br>
                    <?php _e('Higher values increase reliability but slow down queries. Lower values may cause timeouts with slow WHOIS servers.', 'domain-whois-checker'); ?>
                </p>
                <div class="dwc-field-help">
                    <details>
                        <summary><?php _e('Advanced Settings', 'domain-whois-checker'); ?></summary>
                        <p><strong><?php _e('Low (5-15s):', 'domain-whois-checker'); ?></strong> <?php _e('Fast response, risk of timeouts', 'domain-whois-checker'); ?></p>
                        <p><strong><?php _e('Medium (30-60s):', 'domain-whois-checker'); ?></strong> <?php _e('Balanced performance and reliability', 'domain-whois-checker'); ?></p>
                        <p><strong><?php _e('High (90-120s):', 'domain-whois-checker'); ?></strong> <?php _e('Maximum reliability, slower response', 'domain-whois-checker'); ?></p>
                    </details>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Cache results field callback
     */
    public function cache_results_callback() {
        $settings = get_option('dwc_settings', array());
        $cache_results = isset($settings['cache_results']) ? $settings['cache_results'] : true;
        ?>
        <div class="dwc-setting-field">
            <div class="dwc-field-group">
                <label class="dwc-toggle-label">
                    <input type="checkbox" id="cache_results" name="dwc_settings[cache_results]" value="1" <?php checked($cache_results, 1); ?> />
                    <span class="dwc-toggle-slider"></span>
                    <span class="dwc-toggle-text"><?php _e('Enable WHOIS result caching', 'domain-whois-checker'); ?></span>
                </label>
                <p class="description">
                    <strong><?php _e('Highly Recommended:', 'domain-whois-checker'); ?></strong> 
                    <?php _e('Caching dramatically improves performance and reduces server load by storing recent lookup results.', 'domain-whois-checker'); ?>
                </p>
                <div class="dwc-cache-benefits">
                    <h4><?php _e('Benefits of Caching:', 'domain-whois-checker'); ?></h4>
                    <ul>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Faster response times for repeat queries', 'domain-whois-checker'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Reduces load on WHOIS servers', 'domain-whois-checker'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Improves user experience', 'domain-whois-checker'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Prevents rate limiting issues', 'domain-whois-checker'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Cache duration field callback
     */
    public function cache_duration_callback() {
        $settings = get_option('dwc_settings', array());
        $cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 3600;
        
        $duration_options = array(
            300 => __('5 minutes', 'domain-whois-checker'),
            900 => __('15 minutes', 'domain-whois-checker'), 
            1800 => __('30 minutes', 'domain-whois-checker'),
            3600 => __('1 hour', 'domain-whois-checker'),
            7200 => __('2 hours', 'domain-whois-checker'),
            21600 => __('6 hours', 'domain-whois-checker'),
            43200 => __('12 hours', 'domain-whois-checker'),
            86400 => __('24 hours', 'domain-whois-checker')
        );
        ?>
        <div class="dwc-setting-field">
            <select id="cache_duration" name="dwc_settings[cache_duration]">
                <?php foreach ($duration_options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($cache_duration, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php _e('How long to store cached results. Longer durations reduce server load but may show outdated information.', 'domain-whois-checker'); ?></p>
        </div>
        <?php
    }

    /**
     * WHMCS URL field callback
     */
    public function whmcs_url_callback() {
        $settings = get_option('dwc_settings', array());
        $whmcs_url = isset($settings['whmcs_url']) ? $settings['whmcs_url'] : '';
        ?>
        <div class="dwc-setting-field">
            <div class="dwc-field-group">
                <input type="url" id="whmcs_url" name="dwc_settings[whmcs_url]" value="<?php echo esc_attr($whmcs_url); ?>" class="regular-text dwc-url-field" placeholder="https://billing.yoursite.com" />
                <p class="description">
                    <?php _e('Enter the full URL to your WHMCS installation or billing system.', 'domain-whois-checker'); ?>
                </p>
                <div class="dwc-url-examples">
                    <h4><?php _e('Valid URL Examples:', 'domain-whois-checker'); ?></h4>
                    <div class="dwc-examples-grid">
                        <div class="dwc-example">
                            <code>https://billing.yoursite.com</code>
                            <span class="dwc-example-type"><?php _e('Subdomain', 'domain-whois-checker'); ?></span>
                        </div>
                        <div class="dwc-example">
                            <code>https://yoursite.com/whmcs</code>
                            <span class="dwc-example-type"><?php _e('Subdirectory', 'domain-whois-checker'); ?></span>
                        </div>
                        <div class="dwc-example">
                            <code>https://my.yoursite.com</code>
                            <span class="dwc-example-type"><?php _e('Customer Portal', 'domain-whois-checker'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="dwc-url-test" style="margin-top: 15px;">
                    <button type="button" id="test-whmcs-connection" class="button button-secondary">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Test Connection', 'domain-whois-checker'); ?>
                    </button>
                    <span id="whmcs-test-result"></span>
                </div>
                <div class="dwc-field-validation" id="whmcs-url-validation" style="display: none;">
                    <!-- Validation feedback will be inserted here -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enable purchase field callback
     */
    public function enable_purchase_callback() {
        $settings = get_option('dwc_settings', array());
        $enable_purchase = isset($settings['enable_purchase']) ? $settings['enable_purchase'] : false;
        ?>
        <div class="dwc-setting-field">
            <label class="dwc-toggle-label">
                <input type="checkbox" id="enable_purchase" name="dwc_settings[enable_purchase]" value="1" <?php checked($enable_purchase, 1); ?> />
                <span class="dwc-toggle-slider"></span>
                <?php _e('Show purchase button for available domains', 'domain-whois-checker'); ?>
            </label>
            <p class="description"><?php _e('When enabled, visitors can click a purchase button to buy available domains through your billing system.', 'domain-whois-checker'); ?></p>
        </div>
        <?php
    }

    /**
     * Purchase button text field callback
     */
    public function purchase_button_text_callback() {
        $settings = get_option('dwc_settings', array());
        $purchase_button_text = isset($settings['purchase_button_text']) ? $settings['purchase_button_text'] : __('Get This Domain', 'domain-whois-checker');
        ?>
        <div class="dwc-setting-field">
            <div class="dwc-field-group">
                <input type="text" id="purchase_button_text" name="dwc_settings[purchase_button_text]" value="<?php echo esc_attr($purchase_button_text); ?>" class="regular-text" placeholder="<?php _e('Buy Now', 'domain-whois-checker'); ?>" maxlength="30" />
                <p class="description">
                    <?php _e('Customize the text displayed on the purchase button. Keep it short and action-oriented (max 30 characters).', 'domain-whois-checker'); ?>
                </p>
                <div class="dwc-character-count">
                    <span id="button-text-count">0</span>/30 <?php _e('characters', 'domain-whois-checker'); ?>
                </div>
                <div class="dwc-button-suggestions">
                    <h4><?php _e('Popular Button Texts:', 'domain-whois-checker'); ?></h4>
                    <div class="dwc-suggestion-buttons">
                        <button type="button" class="dwc-suggestion-btn" data-text="<?php _e('Buy Now', 'domain-whois-checker'); ?>"><?php _e('Buy Now', 'domain-whois-checker'); ?></button>
                        <button type="button" class="dwc-suggestion-btn" data-text="<?php _e('Purchase Domain', 'domain-whois-checker'); ?>"><?php _e('Purchase Domain', 'domain-whois-checker'); ?></button>
                        <button type="button" class="dwc-suggestion-btn" data-text="<?php _e('Register Now', 'domain-whois-checker'); ?>"><?php _e('Register Now', 'domain-whois-checker'); ?></button>
                        <button type="button" class="dwc-suggestion-btn" data-text="<?php _e('Get This Domain', 'domain-whois-checker'); ?>"><?php _e('Get This Domain', 'domain-whois-checker'); ?></button>
                        <button type="button" class="dwc-suggestion-btn" data-text="<?php _e('Order Domain', 'domain-whois-checker'); ?>"><?php _e('Order Domain', 'domain-whois-checker'); ?></button>
                    </div>
                </div>
                <div class="dwc-button-preview">
                    <strong><?php _e('Live Preview:', 'domain-whois-checker'); ?></strong>
                    <div class="dwc-preview-container">
                        <span class="dwc-purchase-button-preview" id="live-button-preview"><?php echo esc_html($purchase_button_text); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Success message field callback
     */
    public function success_message_callback() {
        $settings = get_option('dwc_settings', array());
        $success_message = isset($settings['success_message']) ? $settings['success_message'] : __('Congratulations! {domain} is available!', 'domain-whois-checker');
        ?>
        <div class="dwc-setting-field">
            <textarea id="success_message" name="dwc_settings[success_message]" rows="3" class="large-text" placeholder="<?php _e('Great news! {domain} is ready to register!', 'domain-whois-checker'); ?>"><?php echo esc_textarea($success_message); ?></textarea>
            <p class="description">
                <?php _e('Message shown when a domain is available for registration.', 'domain-whois-checker'); ?><br>
                <?php _e('Use <code>{domain}</code> as a placeholder for the domain name.', 'domain-whois-checker'); ?>
            </p>
            <div class="dwc-message-preview">
                <strong><?php _e('Preview:', 'domain-whois-checker'); ?></strong>
                <div class="dwc-success-message-preview">
                    <?php echo str_replace('{domain}', '<strong>example.com</strong>', esc_html($success_message)); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['timeout'])) {
            $sanitized['timeout'] = max(5, min(120, intval($input['timeout'])));
        }

        if (isset($input['cache_results'])) {
            $sanitized['cache_results'] = (bool) $input['cache_results'];
        }

        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = max(300, min(86400, intval($input['cache_duration'])));
        }

        if (isset($input['whmcs_url'])) {
            $sanitized['whmcs_url'] = esc_url_raw($input['whmcs_url']);
        }

        if (isset($input['enable_purchase'])) {
            $sanitized['enable_purchase'] = (bool) $input['enable_purchase'];
        }

        if (isset($input['purchase_button_text'])) {
            $sanitized['purchase_button_text'] = sanitize_text_field($input['purchase_button_text']);
        }

        if (isset($input['success_message'])) {
            $sanitized['success_message'] = sanitize_textarea_field($input['success_message']);
        }

        return $sanitized;
    }

    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1 class="dwc-main-title">
                <span class="dashicons dashicons-search"></span>
                <?php _e('Domain WHOIS Checker', 'domain-whois-checker'); ?>
            </h1>
            <p class="dwc-subtitle"><?php _e('Configure domain checking and purchase integration settings', 'domain-whois-checker'); ?></p>

            <?php settings_errors(); ?>

            <div class="dwc-admin-wrap">
                <!-- Main Settings Panel -->
                <div class="dwc-admin-main">
                    <div class="dwc-settings-panel">
                        <div class="dwc-panel-header">
                            <h2><?php _e('Settings', 'domain-whois-checker'); ?></h2>
                        </div>
                        <div class="dwc-panel-content">
                            <form method="post" action="options.php">
                                <?php
                                settings_fields('dwc_settings_group');
                                do_settings_sections('domain-whois-checker');
                                ?>
                                <div class="dwc-form-actions">
                                    <?php submit_button(__('Save Settings', 'domain-whois-checker'), 'primary', 'submit', false); ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="dwc-admin-sidebar">
                    <!-- Domain Test Tool -->
                    <div class="dwc-sidebar-panel">
                        <div class="dwc-panel-header">
                            <h3><?php _e('Test Domain Checker', 'domain-whois-checker'); ?></h3>
                        </div>
                        <div class="dwc-panel-content">
                            <p class="description"><?php _e('Test the domain availability checker with any domain name.', 'domain-whois-checker'); ?></p>
                            
                            <div class="dwc-test-form">
                                <input type="text" id="test-domain" class="regular-text" placeholder="example.com" />
                                <button type="button" id="test-domain-btn" class="button button-secondary">
                                    <?php _e('Check Domain', 'domain-whois-checker'); ?>
                                </button>
                                <div id="test-loading" style="display: none;">
                                    <span class="spinner is-active"></span> 
                                    <span><?php _e('Checking...', 'domain-whois-checker'); ?></span>
                                </div>
                            </div>

                            <div id="test-results" class="dwc-test-results" style="display: none;">
                                <div id="test-results-content"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Help -->
                    <div class="dwc-sidebar-panel">
                        <div class="dwc-panel-header">
                            <h3><?php _e('Quick Help', 'domain-whois-checker'); ?></h3>
                        </div>
                        <div class="dwc-panel-content">
                            <div class="dwc-help-section">
                                <h4><?php _e('Basic Usage', 'domain-whois-checker'); ?></h4>
                                <p><?php _e('Add domain checker to any page or post:', 'domain-whois-checker'); ?></p>
                                <code class="dwc-shortcode">[domain_whois_checker]</code>
                            </div>

                            <div class="dwc-help-section">
                                <h4><?php _e('With Custom Options', 'domain-whois-checker'); ?></h4>
                                <code class="dwc-shortcode">[domain_whois_checker placeholder="Enter domain" button_text="Search"]</code>
                            </div>

                            <?php if (class_exists('Avada') || function_exists('avada_theme_setup')): ?>
                            <div class="dwc-help-section">
                                <h4><?php _e('Avada Integration', 'domain-whois-checker'); ?></h4>
                                <p><?php _e('Available in Fusion Builder elements as "Domain WHOIS Checker".', 'domain-whois-checker'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status Overview -->
                    <div class="dwc-sidebar-panel">
                        <div class="dwc-panel-header">
                            <h3><?php _e('Status Overview', 'domain-whois-checker'); ?></h3>
                        </div>
                        <div class="dwc-panel-content">
                            <?php
                            $settings = get_option('dwc_settings', array());
                            $cache_enabled = isset($settings['cache_results']) ? $settings['cache_results'] : true;
                            $purchase_enabled = isset($settings['enable_purchase']) ? $settings['enable_purchase'] : false;
                            $whmcs_url = isset($settings['whmcs_url']) ? $settings['whmcs_url'] : '';
                            ?>
                            <div class="dwc-status-item">
                                <span class="dwc-status-label"><?php _e('Caching:', 'domain-whois-checker'); ?></span>
                                <span class="dwc-status-value <?php echo $cache_enabled ? 'enabled' : 'disabled'; ?>">
                                    <?php echo $cache_enabled ? __('Enabled', 'domain-whois-checker') : __('Disabled', 'domain-whois-checker'); ?>
                                </span>
                            </div>
                            <div class="dwc-status-item">
                                <span class="dwc-status-label"><?php _e('Purchase Feature:', 'domain-whois-checker'); ?></span>
                                <span class="dwc-status-value <?php echo $purchase_enabled ? 'enabled' : 'disabled'; ?>">
                                    <?php echo $purchase_enabled ? __('Enabled', 'domain-whois-checker') : __('Disabled', 'domain-whois-checker'); ?>
                                </span>
                            </div>
                            <div class="dwc-status-item">
                                <span class="dwc-status-label"><?php _e('WHMCS URL:', 'domain-whois-checker'); ?></span>
                                <span class="dwc-status-value <?php echo !empty($whmcs_url) ? 'configured' : 'not-configured'; ?>">
                                    <?php echo !empty($whmcs_url) ? __('Configured', 'domain-whois-checker') : __('Not Set', 'domain-whois-checker'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supported Extensions Panel -->
            <div class="dwc-extensions-panel">
                <h2><?php _e('Supported Domain Extensions', 'domain-whois-checker'); ?></h2>
                <p class="description"><?php _e('The plugin supports WHOIS lookups for the following domain extensions:', 'domain-whois-checker'); ?></p>
                
                <div class="dwc-extensions-container">
                    <?php
                    $whois_servers = DWC_WHOIS_Config::get_whois_servers();
                    $all_extensions = array();
                    
                    foreach ($whois_servers as $server) {
                        $extensions = explode(',', $server['extensions']);
                        foreach ($extensions as $ext) {
                            $all_extensions[] = trim($ext);
                        }
                    }
                    
                    $all_extensions = array_unique($all_extensions);
                    sort($all_extensions);
                    
                    foreach ($all_extensions as $ext) {
                        echo '<span class="dwc-extension-tag">' . esc_html($ext) . '</span>';
                    }
                    ?>
                </div>
                <p class="dwc-extensions-count">
                    <?php printf(__('Total: %d extensions supported', 'domain-whois-checker'), count($all_extensions)); ?>
                </p>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#test-domain-btn').on('click', function() {
                var domain = $('#test-domain').val().trim();
                
                if (!domain) {
                    alert('<?php _e("Please enter a domain name", "domain-whois-checker"); ?>');
                    return;
                }

                $('#test-loading').show();
                $('#test-results').hide();
                $(this).prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dwc_test_domain',
                        domain: domain,
                        nonce: '<?php echo wp_create_nonce("dwc_admin_nonce"); ?>'
                    },
                    success: function(response) {
                        $('#test-loading').hide();
                        $('#test-domain-btn').prop('disabled', false);
                        
                        if (response.success) {
                            var result = response.data;
                            var html = '<div class="dwc-test-result">';
                            html += '<p><strong><?php _e("Domain:", "domain-whois-checker"); ?></strong> ' + result.domain + '</p>';
                            html += '<p><strong><?php _e("Status:", "domain-whois-checker"); ?></strong> ';
                            
                            if (result.available) {
                                html += '<span style="color: green;"><?php _e("Available", "domain-whois-checker"); ?></span>';
                            } else {
                                html += '<span style="color: red;"><?php _e("Not Available", "domain-whois-checker"); ?></span>';
                            }
                            
                            html += '</p>';
                            
                            if (result.registrar) {
                                html += '<p><strong><?php _e("Registrar:", "domain-whois-checker"); ?></strong> ' + result.registrar + '</p>';
                            }
                            
                            if (result.created) {
                                html += '<p><strong><?php _e("Created:", "domain-whois-checker"); ?></strong> ' + result.created + '</p>';
                            }
                            
                            if (result.expires) {
                                html += '<p><strong><?php _e("Expires:", "domain-whois-checker"); ?></strong> ' + result.expires + '</p>';
                            }
                            
                            html += '<p><strong><?php _e("Checked at:", "domain-whois-checker"); ?></strong> ' + result.checked_at + '</p>';
                            html += '</div>';
                            
                            $('#test-results-content').html(html);
                        } else {
                            $('#test-results-content').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                        
                        $('#test-results').show();
                    },
                    error: function() {
                        $('#test-loading').hide();
                        $('#test-domain-btn').prop('disabled', false);
                        alert('<?php _e("An error occurred while checking the domain", "domain-whois-checker"); ?>');
                    }
                });
            });

            $('#test-domain').on('keypress', function(e) {
                if (e.which == 13) {
                    $('#test-domain-btn').click();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for testing domains in admin
     */
    public function ajax_test_domain() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dwc_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $domain = sanitize_text_field($_POST['domain']);
        
        if (empty($domain)) {
            wp_send_json_error(array('message' => __('Domain name is required', 'domain-whois-checker')));
        }

        // Initialize WHOIS checker
        require_once DWC_PLUGIN_PATH . 'includes/class-whois-checker.php';
        $whois_checker = new DWC_WHOIS_Checker();
        $result = $whois_checker->get_domain_info($domain);

        if ($result && (!isset($result['status']) || $result['status'] !== 'error')) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => isset($result['message']) ? $result['message'] : __('Unable to check domain', 'domain-whois-checker')));
        }
    }

    /**
     * AJAX handler for testing WHMCS connection
     */
    public function ajax_test_whmcs_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dwc_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'domain-whois-checker')));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'domain-whois-checker')));
        }

        $whmcs_url = sanitize_url($_POST['whmcs_url']);
        
        if (empty($whmcs_url)) {
            wp_send_json_error(array('message' => __('WHMCS URL is required', 'domain-whois-checker')));
        }

        // Validate URL format
        if (!filter_var($whmcs_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(array('message' => __('Please enter a valid URL', 'domain-whois-checker')));
        }

        // Test connection with timeout
        $response = wp_remote_get($whmcs_url, array(
            'timeout' => 10,
            'sslverify' => false,
            'headers' => array(
                'User-Agent' => 'Domain WHOIS Checker Plugin - Connection Test'
            )
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Connection failed: %s', 'domain-whois-checker'),
                    $response->get_error_message()
                )
            ));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code >= 200 && $response_code < 400) {
            // Check if it looks like a WHMCS installation
            $is_whmcs = false;
            
            if (stripos($response_body, 'whmcs') !== false || 
                stripos($response_body, 'client area') !== false ||
                stripos($response_body, 'billing') !== false) {
                $is_whmcs = true;
            }

            if ($is_whmcs) {
                wp_send_json_success(array(
                    'message' => __('Connection successful! WHMCS installation detected.', 'domain-whois-checker'),
                    'type' => 'whmcs_detected'
                ));
            } else {
                wp_send_json_success(array(
                    'message' => __('Connection successful, but WHMCS not detected. Please verify the URL.', 'domain-whois-checker'),
                    'type' => 'no_whmcs_detected'
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Server responded with error code: %d', 'domain-whois-checker'),
                    $response_code
                )
            ));
        }
    }
}
