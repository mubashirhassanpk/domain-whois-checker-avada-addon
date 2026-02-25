<?php
/**
 * WHOIS Checker Class
 * 
 * @package Domain_WHOIS_Checker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include WHOIS config if not already loaded
if (!class_exists('DWC_WHOIS_Config')) {
    require_once DWC_PLUGIN_PATH . 'includes/class-whois-config.php';
}

/**
 * WHOIS Checker Class
 */
class DWC_WHOIS_Checker {

    /**
     * Timeout for socket connections
     * @var int
     */
    private $timeout = 30;

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('dwc_settings', array());
        $this->timeout = isset($settings['timeout']) ? (int) $settings['timeout'] : 30;
    }

    /**
     * Check domain availability
     * 
     * @param string $domain Domain name to check
     * @return array|false Result array or false on error
     */
    public function check_domain($domain) {
        // Sanitize domain
        $domain = $this->sanitize_domain($domain);
        
        if (!$domain) {
            return false;
        }

        // Get domain extension
        $extension = DWC_WHOIS_Config::get_domain_extension($domain);
        
        if (!$extension) {
            return array(
                'status' => 'error',
                'message' => __('Unsupported domain extension', 'domain-whois-checker'),
                'domain' => $domain
            );
        }

        // Get WHOIS server configuration
        $server_config = DWC_WHOIS_Config::get_server_for_extension($extension);
        
        if (!$server_config) {
            return array(
                'status' => 'error',
                'message' => __('WHOIS server not found for this domain extension', 'domain-whois-checker'),
                'domain' => $domain,
                'extension' => $extension
            );
        }

        // Check cache first
        $cache_key = 'dwc_whois_' . md5($domain);
        $settings = get_option('dwc_settings', array());
        
        if (!empty($settings['cache_results']) && $settings['cache_results']) {
            $cached_result = get_transient($cache_key);
            if ($cached_result !== false) {
                return $cached_result;
            }
        }

        // Perform WHOIS lookup
        $result = $this->perform_whois_lookup($domain, $server_config);
        
        // Add detailed domain information if not available
        if ($result && $result['status'] === 'success' && !$result['available']) {
            $detailed_info = $this->parse_whois_response($result['response']);
            $result = array_merge($result, $detailed_info);
        }

        // Cache result if caching is enabled
        if (!empty($settings['cache_results']) && $settings['cache_results'] && $result) {
            $cache_duration = isset($settings['cache_duration']) ? (int) $settings['cache_duration'] : 3600;
            set_transient($cache_key, $result, $cache_duration);
        }

        return $result;
    }

    /**
     * Perform WHOIS lookup
     * 
     * @param string $domain Domain name
     * @param array $server_config WHOIS server configuration
     * @return array Result array
     */
    private function perform_whois_lookup($domain, $server_config) {
        $uri = $server_config['uri'];
        $available_text = $server_config['available'];

        if (strpos($uri, 'socket://') === 0) {
            return $this->socket_whois_lookup($domain, $uri, $available_text);
        } elseif (strpos($uri, 'http://') === 0 || strpos($uri, 'https://') === 0) {
            return $this->http_whois_lookup($domain, $uri, $available_text);
        }

        return array(
            'status' => 'error',
            'message' => __('Unsupported WHOIS server type', 'domain-whois-checker'),
            'domain' => $domain
        );
    }

    /**
     * Socket-based WHOIS lookup
     * 
     * @param string $domain Domain name
     * @param string $uri Socket URI
     * @param string $available_text Available text to check for
     * @return array Result array
     */
    private function socket_whois_lookup($domain, $uri, $available_text) {
        // Parse socket URI
        $uri = str_replace('socket://', '', $uri);
        $parts = explode(':', $uri);
        $server = $parts[0];
        $port = isset($parts[1]) ? (int) $parts[1] : 43;

        // Create socket connection
        $socket = @fsockopen($server, $port, $errno, $errstr, $this->timeout);
        
        if (!$socket) {
            return array(
                'status' => 'error',
                'message' => sprintf(__('Connection failed: %s', 'domain-whois-checker'), $errstr),
                'domain' => $domain,
                'server' => $server
            );
        }

        // Send domain query
        fwrite($socket, $domain . "\r\n");

        // Read response
        $response = '';
        while (!feof($socket)) {
            $response .= fgets($socket);
        }
        fclose($socket);

        // Check availability
        $is_available = $this->check_availability($response, $available_text);

        return array(
            'status' => 'success',
            'domain' => $domain,
            'available' => $is_available,
            'response' => $response,
            'server' => $server,
            'checked_at' => current_time('mysql')
        );
    }

    /**
     * HTTP-based WHOIS lookup
     * 
     * @param string $domain Domain name
     * @param string $uri HTTP URI
     * @param string $available_text Available text to check for
     * @return array Result array
     */
    private function http_whois_lookup($domain, $uri, $available_text) {
        $url = $uri . urlencode($domain);

        $response = wp_remote_get($url, array(
            'timeout' => $this->timeout,
            'user-agent' => 'Domain WHOIS Checker WordPress Plugin',
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive'
            )
        ));

        if (is_wp_error($response)) {
            return array(
                'status' => 'error',
                'message' => $response->get_error_message(),
                'domain' => $domain,
                'url' => $url
            );
        }

        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            return array(
                'status' => 'error',
                'message' => __('Empty response from WHOIS server', 'domain-whois-checker'),
                'domain' => $domain,
                'url' => $url
            );
        }

        // Special handling for Pakistani domains
        if ($this->is_pk_domain($domain)) {
            $body = $this->parse_pk_whois_response($body);
        }

        // Check availability
        $is_available = $this->check_availability($body, $available_text);

        return array(
            'status' => 'success',
            'domain' => $domain,
            'available' => $is_available,
            'response' => $body,
            'url' => $url,
            'checked_at' => current_time('mysql')
        );
    }

    /**
     * Check if domain is available based on response text
     * 
     * @param string $response WHOIS response
     * @param string $available_text Text that indicates availability
     * @return bool True if available, false if not
     */
    private function check_availability($response, $available_text) {
        $response = strtolower($response);
        $available_text = strtolower($available_text);

        // Check for availability indicators
        return (strpos($response, $available_text) !== false);
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
     * Get comprehensive domain info (detailed parsing of WHOIS data)
     * 
     * @param string $domain Domain name
     * @return array Domain information
     */
    public function get_domain_info($domain) {
        $result = $this->check_domain($domain);
        
        if (!$result || $result['status'] !== 'success') {
            return $result;
        }

        $info = array(
            'domain' => $domain,
            'available' => $result['available'],
            'checked_at' => $result['checked_at'],
            'raw_response' => $result['response']
        );

        // Parse additional information from WHOIS response
        if (!empty($result['response'])) {
            $response = $result['response'];
            $info = array_merge($info, $this->parse_whois_response($response));
        }

        return $info;
    }

    /**
     * Parse WHOIS response for detailed information
     * 
     * @param string $response WHOIS response text
     * @return array Parsed information
     */
    private function parse_whois_response($response) {
        $info = array();
        $lines = explode("\n", $response);
        
        // Registrar information
        $info['registrar'] = $this->extract_field($response, array(
            'registrar:\s*(.+)',
            'registered through:\s*(.+)',
            'sponsoring registrar:\s*(.+)',
            'registrar name:\s*(.+)'
        ));
        
        // Registration dates
        $info['created'] = $this->extract_field($response, array(
            'creation date:\s*(.+)',
            'registered on:\s*(.+)',
            'created:\s*(.+)',
            'domain registration date:\s*(.+)',
            'registered date:\s*(.+)'
        ));
        
        $info['expires'] = $this->extract_field($response, array(
            'expir(?:y|ation) date:\s*(.+)',
            'expires on:\s*(.+)',
            'expires:\s*(.+)',
            'domain expiration date:\s*(.+)',
            'expiry date:\s*(.+)'
        ));
        
        $info['updated'] = $this->extract_field($response, array(
            'updated date:\s*(.+)',
            'last updated:\s*(.+)',
            'modified:\s*(.+)',
            'changed:\s*(.+)'
        ));
        
        // Nameservers
        $nameservers = array();
        $ns_patterns = array(
            '/name server:\s*(.+)/i',
            '/nserver:\s*(.+)/i',
            '/nameserver:\s*(.+)/i',
            '/ns\d+:\s*(.+)/i'
        );
        
        foreach ($ns_patterns as $pattern) {
            if (preg_match_all($pattern, $response, $matches)) {
                foreach ($matches[1] as $ns) {
                    $ns = trim(strtolower($ns));
                    if (!empty($ns) && !in_array($ns, $nameservers)) {
                        $nameservers[] = $ns;
                    }
                }
            }
        }
        $info['nameservers'] = $nameservers;
        
        // Registrant information
        $info['registrant_name'] = $this->extract_field($response, array(
            'registrant name:\s*(.+)',
            'registrant:\s*(.+)',
            'owner-name:\s*(.+)',
            'holder-name:\s*(.+)'
        ));
        
        $info['registrant_organization'] = $this->extract_field($response, array(
            'registrant organization:\s*(.+)',
            'registrant org:\s*(.+)',
            'owner-organization:\s*(.+)',
            'holder-organization:\s*(.+)'
        ));
        
        $info['registrant_country'] = $this->extract_field($response, array(
            'registrant country:\s*(.+)',
            'owner-country:\s*(.+)',
            'holder-country:\s*(.+)'
        ));
        
        // Administrative contact
        $info['admin_name'] = $this->extract_field($response, array(
            'admin name:\s*(.+)',
            'administrative contact name:\s*(.+)',
            'admin-name:\s*(.+)'
        ));
        
        $info['admin_email'] = $this->extract_field($response, array(
            'admin email:\s*(.+)',
            'administrative contact email:\s*(.+)',
            'admin-email:\s*(.+)'
        ));
        
        // Technical contact
        $info['tech_name'] = $this->extract_field($response, array(
            'tech name:\s*(.+)',
            'technical contact name:\s*(.+)',
            'tech-name:\s*(.+)'
        ));
        
        $info['tech_email'] = $this->extract_field($response, array(
            'tech email:\s*(.+)',
            'technical contact email:\s*(.+)',
            'tech-email:\s*(.+)'
        ));
        
        // Domain status
        $statuses = array();
        if (preg_match_all('/(?:domain )?status:\s*(.+)/i', $response, $matches)) {
            foreach ($matches[1] as $status) {
                $status = trim($status);
                if (!empty($status) && !in_array($status, $statuses)) {
                    $statuses[] = $status;
                }
            }
        }
        $info['status'] = $statuses;
        
        // Clean up empty values
        return array_filter($info, function($value) {
            return !empty($value) || $value === 0;
        });
    }
    
    /**
     * Extract field value using multiple patterns
     * 
     * @param string $response WHOIS response
     * @param array $patterns Array of regex patterns to try
     * @return string|null Extracted value or null if not found
     */
    private function extract_field($response, $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $response, $matches)) {
                $value = trim($matches[1]);
                if (!empty($value)) {
                    return $value;
                }
            }
        }
        return null;
    }
    
    /**
     * Get domain suggestions for unavailable domains
     * 
     * @param string $domain Domain name
     * @return array Array of suggested domains
     */
    public function get_domain_suggestions($domain) {
        $suggestions = array();
        $domain_parts = explode('.', $domain);
        
        if (count($domain_parts) < 2) {
            return $suggestions;
        }
        
        $name = $domain_parts[0];
        $current_ext = implode('.', array_slice($domain_parts, 1));
        
        // Pakistani domain suggestions
        if ($this->is_pk_domain($domain)) {
            $pk_extensions = array('pk', 'com.pk', 'net.pk', 'org.pk', 'web.pk', 'biz.pk', 'info.pk', 'online.pk', 'store.pk', 'tech.pk');
            
            foreach ($pk_extensions as $ext) {
                if ($ext !== $current_ext) {
                    $suggestions[] = $name . '.' . $ext;
                }
            }
            
            // Add international alternatives
            $intl_extensions = array('com', 'net', 'org', 'info', 'biz');
            foreach ($intl_extensions as $ext) {
                $suggestions[] = $name . '.' . $ext;
            }
        } else {
            // Standard international suggestions
            $extensions = array('com', 'net', 'org', 'info', 'biz', 'us', 'co', 'me', 'io');
            
            foreach ($extensions as $ext) {
                if ($ext !== $domain_parts[1]) {
                    $suggestions[] = $name . '.' . $ext;
                }
            }
        }
        
        // Try variations of the domain name
        $variations = array(
            $name . 'online',
            $name . 'pro',
            $name . 'shop',
            'get' . $name,
            'my' . $name,
            $name . 'hq',
            $name . 'pk' // Always suggest .pk variant
        );
        
        foreach ($variations as $variation) {
            if ($this->is_pk_domain($domain)) {
                $suggestions[] = $variation . '.pk';
                $suggestions[] = $variation . '.com.pk';
            } else {
                $suggestions[] = $variation . '.com';
            }
        }
        
        return array_unique(array_slice($suggestions, 0, 15)); // Limit to 15 unique suggestions
    }
    
    /**
     * Check if domain is a Pakistani (.pk) domain
     * 
     * @param string $domain Domain name
     * @return bool True if Pakistani domain
     */
    private function is_pk_domain($domain) {
        $pk_extensions = array('.pk', '.com.pk', '.net.pk', '.org.pk', '.edu.pk', '.web.pk', '.biz.pk', '.fam.pk', 
                              '.gok.pk', '.gob.pk', '.gov.pk', '.info.pk', '.tv.pk', '.online.pk', '.store.pk', '.tech.pk', '.pro.pk');
        
        foreach ($pk_extensions as $ext) {
            if (substr($domain, -strlen($ext)) === $ext) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse Pakistani WHOIS response (PKnic format)
     * 
     * @param string $html_response HTML response from PKnic
     * @return string Parsed plain text response
     */
    private function parse_pk_whois_response($html_response) {
        // Remove HTML tags and extract relevant information
        $text = strip_tags($html_response);
        
        // Clean up the response
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // If domain not found, return standardized message
        if (stripos($text, 'domain not found') !== false || 
            stripos($text, 'no match') !== false ||
            stripos($text, 'not found') !== false) {
            return 'Domain not found';
        }
        
        return $text;
    }
}