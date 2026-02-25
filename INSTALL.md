# Installation Guide

## Quick Start

1. **Upload Plugin Files**
   - Upload all files to: `/wp-content/plugins/domain-whois-checker/`
   - Or install via WordPress admin by uploading the zip file

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Domain WHOIS Checker - Avada Addon"
   - Click "Activate"

3. **Configure Settings**
   - Go to Settings → WHOIS Checker
   - Adjust timeout and caching settings as needed
   - Test with a domain using the built-in testing tool

4. **Use the Plugin**

   ### Option A: Shortcode (any theme)
   ```
   [domain_whois_checker]

   ---
   [domain_whois_checker show_details="true" show_purchase="true" show_suggestions="true"]
   ```

   ### Option B: Avada Fusion Builder (Avada theme only)
   - Edit page with Fusion Builder
   - Add Element → Search "Domain WHOIS Checker"
   - Configure options and save

## File Structure

```
domain-whois-checker/
├── domain-whois-checker.php (Main plugin file)
├── README.md
├── includes/
│   ├── class-whois-config.php (WHOIS servers configuration)
│   ├── class-whois-checker.php (Main checker class)
│   ├── class-admin.php (Admin interface)
│   ├── class-shortcode.php (Shortcode handler)
│   └── class-avada-integration.php (Avada integration)
└── assets/
    ├── css/
    │   ├── frontend.css (Frontend styles)
    │   └── admin.css (Admin styles)
    └── js/
        ├── frontend.js (Frontend JavaScript)
        └── admin.js (Admin JavaScript)
```

## Requirements

- WordPress 5.0+
- PHP 7.0+
- cURL extension enabled
- Socket extension enabled (for socket-based WHOIS queries)

## Next Steps

1. Test the plugin with different domain extensions
2. Customize styling to match your theme
3. Add shortcodes to your pages/posts
4. Configure Avada elements if using Avada theme

For detailed usage instructions, see README.md