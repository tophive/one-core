# OneCore Plugin - SiteGround Captcha Handling

## Overview

This document explains how the OneCore plugin handles SiteGround's captcha system that can interfere with license activation and API calls.

## The Problem

SiteGround hosting providers implement a security system called `sgcaptcha` that can intercept external API calls, including license activation requests to `app.tophivetheme.com`. When this happens, users see redirects to:

```
/.well-known/sgcaptcha/?r=%2Fapi%2Flicense%2Factivate&y=ipc:65.60.4.2:1756146828.636
```

## Solutions Implemented

### 1. Enhanced Error Handling

The plugin now detects captcha redirects and provides user-friendly error messages instead of generic failures.

### 2. Admin Notices

When captcha is detected, the plugin displays admin notices with:
- Clear explanation of the issue
- Direct link to complete the captcha
- Instructions for next steps

### 3. JavaScript Enhancement

The frontend JavaScript now handles captcha responses and shows appropriate messages to users.

## How to Resolve Captcha Issues

### Immediate Solution

1. **Complete the Captcha**: Click the "Complete Captcha Verification" button in the admin notice
2. **Follow the Redirect**: Complete the captcha verification on SiteGround's page
3. **Retry Activation**: Return to your WordPress admin and try activating the license again

### Alternative Solutions

1. **Contact SiteGround Support**: Ask them to whitelist `app.tophivetheme.com` for your domain
2. **Use Different Hosting**: Consider hosting providers that don't implement aggressive captcha systems
3. **VPN/Proxy**: Use a different IP address that might not trigger the captcha

## Technical Details

### API Call Enhancements

- Added `redirection: 5` to allow up to 5 redirects
- Added `sslverify: false` to handle SSL issues
- Enhanced error detection for captcha responses

### Error Response Format

When captcha is detected, the API returns:

```json
{
  "error": "captcha_required",
  "message": "SiteGround captcha detected. Please complete the captcha verification first.",
  "captcha_url": "https://yourdomain.com/.well-known/sgcaptcha/..."
}
```

### Admin Notice System

- Uses WordPress transients to store captcha errors
- Displays notices only to users with `manage_options` capability
- Automatically clears after 5 minutes

## Troubleshooting

### Common Issues

1. **Captcha Not Appearing**: Check if your hosting provider is SiteGround
2. **License Still Fails After Captcha**: Wait a few minutes and try again
3. **Admin Notices Not Showing**: Ensure you have administrator privileges

### Debug Information

Enable WordPress debug logging to see detailed error information:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check the debug log for entries containing "sgcaptcha" or "captcha".

## Support

If you continue to experience issues after implementing these solutions:

1. Check the WordPress debug log for specific error messages
2. Contact your hosting provider about API restrictions
3. Reach out to OneCore support with specific error details

## Version History

- **v1.0**: Initial captcha handling implementation
- Enhanced error detection and user messaging
- Added admin notice system
- Improved JavaScript error handling
