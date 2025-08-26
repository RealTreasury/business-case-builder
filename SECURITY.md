# Security Best Practices for Real Treasury Business Case Builder

This document outlines security best practices and guidelines for developers working on the Real Treasury Business Case Builder plugin.

## Overview

Security is paramount when handling financial data and treasury operations. This plugin processes sensitive business information and must adhere to strict security standards.

## Current Security Measures

### 1. Input Sanitization and Validation
- All user input is sanitized using WordPress sanitization functions
- Data validation is performed using the `RTBCB_Validator` class
- AJAX endpoints use nonce verification for CSRF protection

```php
// Example: Proper input handling
if ( ! check_ajax_referer( 'rtbcb_nonce', 'nonce', false ) ) {
    rtbcb_send_json_error( 'security_check_failed', __( 'Security check failed.', 'rtbcb' ), 403 );
}

$company_name = sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) );
```

### 2. Output Escaping
- All output is escaped using appropriate WordPress functions (`esc_html`, `esc_attr`, `esc_url`)
- User-generated content is sanitized before display

### 3. Capability Checks
- Administrative functions require `manage_options` capability
- User permissions are verified for all sensitive operations

```php
if ( ! current_user_can( 'manage_options' ) ) {
    rtbcb_send_json_error( 'insufficient_permissions', __( 'Insufficient permissions.', 'rtbcb' ), 403 );
}
```

### 4. File Access Protection
- `.htaccess` rules prevent direct access to sensitive files
- Security headers are configured to prevent common attacks
- Backup and configuration files are protected

### 5. API Security
- OpenAI API keys are stored securely in WordPress options
- API requests include proper error handling and logging
- Rate limiting considerations for API calls

## Development Guidelines

### Required Security Practices

1. **Always sanitize input data:**
   ```php
   $user_input = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
   ```

2. **Always escape output data:**
   ```php
   echo esc_html( $user_data );
   ```

3. **Use nonces for form submissions:**
   ```php
   wp_nonce_field( 'rtbcb_action', 'rtbcb_nonce' );
   ```

4. **Verify user capabilities:**
   ```php
   if ( ! current_user_can( 'required_capability' ) ) {
       wp_die( __( 'Access denied.', 'rtbcb' ) );
   }
   ```

5. **Validate and sanitize all data:**
   ```php
   $validated_data = RTBCB_Validator::validate_business_data( $input_data );
   ```

### API Key Management

- Store API keys using WordPress options with secure storage
- Never log or expose API keys in debug output
- Use environment variables for sensitive configuration when possible

### Error Handling

- Log security-related errors for administrative review
- Provide generic error messages to users (avoid exposing system details)
- Implement proper error boundaries for exception handling

### Database Security

- Use WordPress prepared statements for all database queries
- Sanitize all data before database insertion
- Follow WordPress database API best practices

```php
global $wpdb;
$result = $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}rtbcb_table WHERE field = %s",
    $sanitized_value
);
```

## Security Testing

### Required Tests

1. **Input Validation Tests:** Verify all inputs are properly sanitized
2. **Authorization Tests:** Confirm capability checks work correctly
3. **CSRF Protection Tests:** Validate nonce verification
4. **SQL Injection Tests:** Test database query safety
5. **XSS Prevention Tests:** Verify output escaping

### Security Checklist

Before releasing any update:

- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] Nonces are used for form submissions
- [ ] User capabilities are checked
- [ ] Database queries use prepared statements
- [ ] API keys are stored securely
- [ ] Error messages don't expose sensitive information
- [ ] File upload restrictions are in place
- [ ] Security headers are configured

## Reporting Security Issues

If you discover a security vulnerability:

1. **Do not create a public issue**
2. Email security concerns to the maintainers
3. Provide detailed reproduction steps
4. Allow time for responsible disclosure

## Security Resources

- [WordPress Security Documentation](https://developer.wordpress.org/plugins/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [OWASP Web Application Security Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)

## Regular Security Reviews

Security practices should be reviewed regularly:

- Code review all security-related changes
- Update dependencies regularly
- Monitor for security advisories
- Conduct periodic security audits