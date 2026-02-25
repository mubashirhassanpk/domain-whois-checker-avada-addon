<?php
/**
 * Avada Integration Class
 * 
 * @package Domain_WHOIS_Checker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Avada Integration Class
 */
class DWC_Avada_Integration {

    /**
     * Constructor
     */
    public function __construct() {
        // Add element to Fusion Builder
        add_action('init', array($this, 'register_fusion_element'));
        
        // Add element to Avada Live Editor
        add_action('fusion_builder_before_init', array($this, 'add_fusion_element'));
    }

    /**
     * Register Fusion Element
     */
    public function register_fusion_element() {
        if (!class_exists('FusionBuilder')) {
            return;
        }

        // Register the element
        fusion_builder_map(
            array(
                'name' => esc_html__('Domain WHOIS Checker', 'domain-whois-checker'),
                'shortcode' => 'fusion_domain_whois_checker',
                'icon' => 'fusiona-search',
                'preview' => DWC_PLUGIN_PATH . 'includes/avada-preview.php',
                'preview_id' => 'fusion-builder-block-module-domain-whois-checker-preview-template',
                'allow_generator' => true,
                'params' => array(
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Placeholder Text', 'domain-whois-checker'),
                        'description' => esc_html__('Enter placeholder text for the domain input field.', 'domain-whois-checker'),
                        'param_name' => 'placeholder',
                        'value' => esc_html__('Enter domain name...', 'domain-whois-checker'),
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Button Text', 'domain-whois-checker'),
                        'description' => esc_html__('Enter text for the check button.', 'domain-whois-checker'),
                        'param_name' => 'button_text',
                        'value' => esc_html__('Check Domain', 'domain-whois-checker'),
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_html__('Show Detailed Information', 'domain-whois-checker'),
                        'description' => esc_html__('Choose whether to show detailed WHOIS information for registered domains.', 'domain-whois-checker'),
                        'param_name' => 'show_details',
                        'value' => array(
                            'no' => esc_html__('No', 'domain-whois-checker'),
                            'yes' => esc_html__('Yes', 'domain-whois-checker'),
                        ),
                        'default' => 'no',
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_html__('Show Purchase Button', 'domain-whois-checker'),
                        'description' => esc_html__('Choose whether to show purchase button for available domains.', 'domain-whois-checker'),
                        'param_name' => 'show_purchase',
                        'value' => array(
                            'no' => esc_html__('No', 'domain-whois-checker'),
                            'yes' => esc_html__('Yes', 'domain-whois-checker'),
                        ),
                        'default' => 'yes',
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_html__('Show Domain Suggestions', 'domain-whois-checker'),
                        'description' => esc_html__('Choose whether to show alternative domain suggestions for unavailable domains.', 'domain-whois-checker'),
                        'param_name' => 'show_suggestions',
                        'value' => array(
                            'no' => esc_html__('No', 'domain-whois-checker'),
                            'yes' => esc_html__('Yes', 'domain-whois-checker'),
                        ),
                        'default' => 'yes',
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_html__('Style', 'domain-whois-checker'),
                        'description' => esc_html__('Select the style for the domain checker.', 'domain-whois-checker'),
                        'param_name' => 'style',
                        'value' => array(
                            'default' => esc_html__('Default', 'domain-whois-checker'),
                            'modern' => esc_html__('Modern', 'domain-whois-checker'),
                            'minimal' => esc_html__('Minimal', 'domain-whois-checker'),
                            'rounded' => esc_html__('Rounded', 'domain-whois-checker'),
                        ),
                        'default' => 'default',
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_html__('Button Background Color', 'domain-whois-checker'),
                        'description' => esc_html__('Select the background color for the check button.', 'domain-whois-checker'),
                        'param_name' => 'button_bg_color',
                        'value' => '#007cba',
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_html__('Button Text Color', 'domain-whois-checker'),
                        'description' => esc_html__('Select the text color for the check button.', 'domain-whois-checker'),
                        'param_name' => 'button_text_color',
                        'value' => '#ffffff',
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_html__('Available Status Color', 'domain-whois-checker'),
                        'description' => esc_html__('Select the color for available domain status.', 'domain-whois-checker'),
                        'param_name' => 'available_color',
                        'value' => '#46b450',
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_html__('Not Available Status Color', 'domain-whois-checker'),
                        'description' => esc_html__('Select the color for not available domain status.', 'domain-whois-checker'),
                        'param_name' => 'not_available_color',
                        'value' => '#dc3232',
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_html__('Purchase Button Color', 'domain-whois-checker'),
                        'description' => esc_html__('Select the color for the purchase button.', 'domain-whois-checker'),
                        'param_name' => 'purchase_button_color',
                        'value' => '#46b450',
                    ),
                    array(
                        'type' => 'dimension',
                        'heading' => esc_html__('Input Border Radius', 'domain-whois-checker'),
                        'description' => esc_html__('Set the border radius for input fields.', 'domain-whois-checker'),
                        'param_name' => 'input_border_radius',
                        'value' => '4px',
                    ),
                    array(
                        'type' => 'dimension',
                        'heading' => esc_html__('Button Border Radius', 'domain-whois-checker'),
                        'description' => esc_html__('Set the border radius for the button.', 'domain-whois-checker'),
                        'param_name' => 'button_border_radius',
                        'value' => '4px',
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('CSS Class', 'domain-whois-checker'),
                        'description' => esc_html__('Add a custom CSS class for additional styling.', 'domain-whois-checker'),
                        'param_name' => 'class',
                        'value' => '',
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('CSS ID', 'domain-whois-checker'),
                        'description' => esc_html__('Add a custom CSS ID.', 'domain-whois-checker'),
                        'param_name' => 'id',
                        'value' => '',
                    ),
                ),
            )
        );
    }

    /**
     * Add Fusion Element
     */
    public function add_fusion_element() {
        add_shortcode('fusion_domain_whois_checker', array($this, 'render_fusion_element'));
    }

    /**
     * Render Fusion Element
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_fusion_element($atts) {
        $atts = shortcode_atts(
            array(
                'placeholder' => esc_html__('Enter domain name...', 'domain-whois-checker'),
                'button_text' => esc_html__('Check Domain', 'domain-whois-checker'),
                'show_details' => 'no',
                'show_purchase' => 'yes',
                'show_suggestions' => 'yes',
                'style' => 'default',
                'button_bg_color' => '#007cba',
                'button_text_color' => '#ffffff',
                'available_color' => '#46b450',
                'not_available_color' => '#dc3232',
                'purchase_button_color' => '#46b450',
                'input_border_radius' => '4px',
                'button_border_radius' => '4px',
                'class' => '',
                'id' => '',
            ),
            $atts,
            'fusion_domain_whois_checker'
        );

        // Generate unique ID
        $unique_id = 'dwc-fusion-' . uniqid();
        if (!empty($atts['id'])) {
            $unique_id = esc_attr($atts['id']);
        }

        // Convert attributes to boolean
        $show_details = ($atts['show_details'] === 'yes');
        $show_purchase = ($atts['show_purchase'] === 'yes');
        $show_suggestions = ($atts['show_suggestions'] === 'yes');

        // Generate custom CSS
        $custom_css = $this->generate_custom_css($unique_id, $atts);

        ob_start();
        ?>
        <style type="text/css">
            <?php echo $custom_css; ?>
        </style>

        <div class="dwc-fusion-container dwc-style-<?php echo esc_attr($atts['style']); ?> <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($unique_id); ?>">
            <div class="dwc-form-container">
                <div class="dwc-input-group">
                    <input 
                        type="text" 
                        class="dwc-domain-input" 
                        placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                        id="<?php echo esc_attr($unique_id); ?>-input"
                    />
                    <button 
                        type="button" 
                        class="dwc-check-button"
                        data-instance="<?php echo esc_attr($unique_id); ?>"
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
     * Generate custom CSS for Fusion element
     * 
     * @param string $id Element ID
     * @param array $atts Element attributes
     * @return string CSS code
     */
    private function generate_custom_css($id, $atts) {
        $css = '';

        // Button colors
        $css .= '#' . $id . ' .dwc-check-button {';
        $css .= 'background-color: ' . esc_attr($atts['button_bg_color']) . ' !important;';
        $css .= 'color: ' . esc_attr($atts['button_text_color']) . ' !important;';
        $css .= 'border-radius: ' . esc_attr($atts['button_border_radius']) . ' !important;';
        $css .= '}';

        // Input border radius
        $css .= '#' . $id . ' .dwc-domain-input {';
        $css .= 'border-radius: ' . esc_attr($atts['input_border_radius']) . ' !important;';
        $css .= '}';

        // Status colors
        $css .= '#' . $id . ' .dwc-available {';
        $css .= 'color: ' . esc_attr($atts['available_color']) . ' !important;';
        $css .= '}';

        $css .= '#' . $id . ' .dwc-not-available {';
        $css .= 'color: ' . esc_attr($atts['not_available_color']) . ' !important;';
        $css .= '}';
        
        // Purchase button color
        $css .= '#' . $id . ' .dwc-purchase-button {';
        $css .= 'background: linear-gradient(135deg, ' . esc_attr($atts['purchase_button_color']) . ' 0%, ' . $this->darken_color($atts['purchase_button_color'], 10) . ' 100%) !important;';
        $css .= '}';
        
        $css .= '#' . $id . ' .dwc-purchase-button:hover {';
        $css .= 'background: linear-gradient(135deg, ' . $this->darken_color($atts['purchase_button_color'], 10) . ' 0%, ' . $this->darken_color($atts['purchase_button_color'], 20) . ' 100%) !important;';
        $css .= '}';

        // Button hover effect
        $css .= '#' . $id . ' .dwc-check-button:hover {';
        $css .= 'background-color: ' . $this->darken_color($atts['button_bg_color'], 20) . ' !important;';
        $css .= '}';

        return $css;
    }

    /**
     * Darken a color by a percentage
     * 
     * @param string $color Hex color code
     * @param int $percent Percentage to darken
     * @return string Darkened color
     */
    private function darken_color($color, $percent) {
        $color = str_replace('#', '', $color);
        
        if (strlen($color) == 6) {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
        } else {
            return $color;
        }

        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
               str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
               str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}

// Initialize Avada integration if Avada is active
if (class_exists('Avada') || function_exists('avada_theme_setup')) {
    new DWC_Avada_Integration();
}