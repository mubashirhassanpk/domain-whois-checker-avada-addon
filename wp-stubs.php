<?php
/**
 * WordPress Stubs for IDE Support
 * This file provides function signatures for IDE autocompletion and error reduction.
 * DO NOT include this file in your actual plugin - it's only for development.
 */

if (false) { // Never actually execute this code
    /**
     * Retrieve an option value based on an option name.
     */
    function get_option($option, $default = false) {}
    
    /**
     * Translate string with gettext context.
     */
    function __($text, $domain = 'default') {}
    
    /**
     * Get transient value.
     */
    function get_transient($transient) {}
    
    /**
     * Set transient value.
     */
    function set_transient($transient, $value, $expiration = 0) {}
    
    /**
     * Get current time.
     */
    function current_time($type, $gmt = 0) {}
    
    /**
     * Perform HTTP GET request.
     */
    function wp_remote_get($url, $args = array()) {}
    
    /**
     * Check if variable is a WordPress Error.
     */
    function is_wp_error($thing) {}
    
    /**
     * Retrieve body from HTTP response.
     */
    function wp_remote_retrieve_body($response) {}
    
    /**
     * Add action hook.
     */
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
    
    /**
     * Add shortcode.
     */
    function add_shortcode($tag, $func) {}
    
    /**
     * Process shortcode attributes.
     */
    function shortcode_atts($pairs, $atts, $shortcode = '') {}
    
    /**
     * Escape HTML attributes.
     */
    function esc_attr($text) {}
    
    /**
     * Escape HTML content.
     */
    function esc_html($text) {}
    
    /**
     * Echo translated text.
     */
    function _e($text, $domain = 'default') {}
}