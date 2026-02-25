# Domain WHOIS Checker - Avada Addon

A powerful WordPress plugin that provides domain WHOIS lookup functionality with seamless integration for the Avada theme builder. Check domain availability and view detailed WHOIS information directly from your WordPress site.

## Features

- **Comprehensive WHOIS Support**: Supports 200+ domain extensions including popular TLDs and country-specific domains
- **Multiple Query Methods**: Supports both socket-based and HTTP-based WHOIS lookups
- **Avada Theme Integration**: Native support for Avada Fusion Builder with custom element
- **Shortcode Support**: Easy-to-use shortcodes for any theme or page builder
- **Caching System**: Built-in caching to improve performance and reduce server load
- **Responsive Design**: Mobile-friendly interface that works on all devices
- **Admin Interface**: Comprehensive settings panel with testing tools
- **Customizable Styling**: Multiple style options and customization settings
- **Real-time Validation**: Client-side domain validation for better user experience

## Supported Domain Extensions

The plugin supports a wide range of domain extensions including:

- **Popular TLDs**: .com, .net, .org, .info, .biz, .name, .mobi
- **New gTLDs**: .app, .blog, .shop, .tech, .online, .site, .store, .cloud
- **Country TLDs**: .uk, .de, .fr, .ca, .au, .br, .in, .cn, .jp, and many more
- **Specialized TLDs**: .edu, .gov, .mil, .museum, .aero, .coop

## Installation

### Manual Installation

1. Download the plugin files and upload them to your `/wp-content/plugins/domain-whois-checker/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > WHOIS Checker to configure the plugin

### Via WordPress Admin

1. Go to Plugins > Add New
2. Upload the plugin zip file
3. Activate the plugin
4. Configure settings under Settings > WHOIS Checker

## Configuration

After installation, configure the plugin:

1. **Connection Timeout**: Set the maximum wait time for WHOIS server responses (5-120 seconds)
2. **Cache Results**: Enable/disable result caching for better performance
3. **Cache Duration**: How long to cache results (300-86400 seconds)

## Usage

### Shortcode Usage

Use the `[domain_whois_checker]` shortcode to display a domain checker form:

```php
[domain_whois_checker]
```

#### Shortcode Parameters

- `placeholder`: Placeholder text for input field (default: "Enter domain name...")
- `button_text`: Text for check button (default: "Check Domain")
- `show_details`: Show detailed WHOIS information (default: false)
- `style`: Visual style - default, modern, minimal, rounded

#### Examples

```php
// Basic usage
[domain_whois_checker]

// With custom text
[domain_whois_checker placeholder="Check your domain" button_text="Search"]

// With detailed information
[domain_whois_checker show_details="true"]

// Modern style with details
[domain_whois_checker style="modern" show_details="true" button_text="Check Availability"]
```

### Avada Integration

If you're using the Avada theme, the plugin adds a custom Fusion Builder element:

1. Edit your page with Fusion Builder
2. Click "Add Element"
3. Search for "Domain WHOIS Checker"
4. Configure the element options:
   - Placeholder text
   - Button text and colors
   - Show detailed information
   - Style options
   - Border radius settings
   - Custom CSS classes and IDs

### PHP Integration

For developers, you can use the plugin's classes directly:

```php
// Check a domain
$checker = new DWC_WHOIS_Checker();
$result = $checker->check_domain('example.com');

// Get detailed domain info
$info = $checker->get_domain_info('example.com');

// Widget-style output
echo DWC_Shortcode::render_widget(array(
    'title' => 'Check Domain',
    'placeholder' => 'Enter domain...',
    'show_details' => true
));
```

## Styling and Customization

### CSS Classes

The plugin uses the following CSS classes for styling:

- `.dwc-checker-container`: Main container
- `.dwc-domain-input`: Domain input field
- `.dwc-check-button`: Check button
- `.dwc-result`: Result container
- `.dwc-available`: Available domain styling
- `.dwc-not-available`: Unavailable domain styling
- `.dwc-loading`: Loading state

### Style Variations

Four built-in styles are available:

1. **Default**: Standard form styling
2. **Modern**: Rounded container with shadow
3. **Minimal**: Clean, borderless design
4. **Rounded**: Fully rounded elements

### Custom CSS

Add custom styles to your theme's CSS:

```css
/* Custom button colors */
.dwc-check-button {
    background-color: #your-color !important;
}

/* Custom input styling */
.dwc-domain-input {
    border-color: #your-color;
}

/* Custom result colors */
.dwc-available {
    color: #your-green-color;
}

.dwc-not-available {
    color: #your-red-color;
}
```

## WHOIS Server Configuration

The plugin includes configuration for 200+ WHOIS servers. The system automatically:

1. Detects the domain extension
2. Finds the appropriate WHOIS server
3. Performs the lookup using socket or HTTP connection
4. Parses the response for availability status

### Supported Query Types

- **Socket connections**: Direct TCP connections to WHOIS servers (port 43)
- **HTTP queries**: Web-based WHOIS lookups for registries that provide web interfaces

## Caching System

The plugin includes an intelligent caching system:

- **Transient-based**: Uses WordPress transients for storage
- **Configurable duration**: Set cache lifetime from 5 minutes to 24 hours
- **Automatic cleanup**: WordPress handles cache expiration
- **Per-domain caching**: Each domain is cached separately

## Performance Optimization

- **Efficient lookups**: Direct socket connections when possible
- **Connection pooling**: Reuses connections for multiple queries
- **Timeout handling**: Prevents hanging requests
- **Error handling**: Graceful failure with informative messages

## Troubleshooting

### Common Issues

1. **Connection timeouts**: Increase timeout setting or check server firewall
2. **Socket errors**: Some servers may require HTTP queries instead
3. **Cache issues**: Clear WordPress cache or disable caching temporarily
4. **Extension not supported**: Check if the domain extension is in the configuration

### Debug Mode

To enable debug mode, add this to your `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for error messages.

## Security

- **Input sanitization**: All inputs are properly sanitized
- **Nonce verification**: AJAX requests use WordPress nonces
- **Capability checks**: Admin functions require proper permissions
- **SQL injection prevention**: Uses WordPress database methods

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 16+
- Internet Explorer 11+

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- cURL extension (for HTTP queries)
- Socket extension (for socket queries)
- Avada theme (optional, for Fusion Builder integration)

## Changelog

### Version 1.0.0
- Initial release
- Support for 200+ domain extensions
- Avada Fusion Builder integration
- Shortcode functionality
- Admin interface
- Caching system
- Multiple styling options

## Support

For support and feature requests:

1. Check the documentation first
2. Test with the admin testing tool
3. Check WordPress debug logs
4. Contact plugin support with specific error messages

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- WHOIS server data compiled from various registry sources
- Icons and design inspired by WordPress admin interface
- Built with WordPress best practices and standards