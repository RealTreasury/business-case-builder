# Developer Guide - Real Treasury Business Case Builder

Welcome to the Real Treasury Business Case Builder developer documentation. This guide provides comprehensive information for developers contributing to or extending the plugin.

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Architecture Overview](#architecture-overview)
3. [Core Components](#core-components)
4. [Development Workflow](#development-workflow)
5. [Coding Standards](#coding-standards)
6. [Testing](#testing)
7. [Performance Optimization](#performance-optimization)
8. [Security Best Practices](#security-best-practices)
9. [Debugging](#debugging)
10. [Contributing](#contributing)

## Development Environment Setup

### Prerequisites
- **PHP 7.4+** (8.0+ recommended)
- **WordPress 5.8+** (6.0+ recommended)
- **Node.js 16+** and **npm 8+**
- **Composer 2.0+**
- **Git**

### Quick Setup
```bash
# Clone repository
git clone https://github.com/RealTreasury/business-case-builder.git
cd business-case-builder

# Install dependencies
npm run setup

# Start development environment
npm run wp-env:start

# Access local site
open http://localhost:8888
# Admin: admin / password
```

### Manual Setup
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (may have network issues in CI)
npm install

# Setup WordPress environment
npm run wp-env:start

# Install WordPress test suite
npm run setup:tests
```

### Environment Configuration
```bash
# WordPress environment (.wp-env.json)
{
  "core": "WordPress/WordPress#master",
  "phpVersion": "8.0",
  "plugins": ["."],
  "themes": ["https://downloads.wordpress.org/theme/twentytwentythree.zip"]
}
```

## Architecture Overview

### Plugin Structure
```
real-treasury-business-case-builder/
├── real-treasury-business-case-builder.php  # Main plugin file
├── inc/                                     # Core classes
│   ├── class-rtbcb-llm.php                 # LLM integration
│   ├── class-rtbcb-calculator.php          # ROI calculations
│   ├── class-rtbcb-performance-monitor.php # Performance tracking
│   ├── class-rtbcb-error-handler.php       # Error management
│   └── ...
├── admin/                                   # Admin interface
├── public/                                  # Frontend assets
├── templates/                               # PHP templates
├── tests/                                   # Test suite
├── languages/                               # Translations
└── vendor/                                  # Composer dependencies
```

### Data Flow
1. **User Input** → WordPress AJAX → **Validation** → **Processing**
2. **LLM Integration** → **Cache Check** → **API Call** → **Response Processing**
3. **Performance Monitoring** → **Error Handling** → **Response**

### Key Design Patterns
- **Singleton Pattern**: Main plugin class
- **Factory Pattern**: LLM model selection
- **Observer Pattern**: Error and performance monitoring
- **Strategy Pattern**: Different business case scenarios

## Core Components

### 1. LLM Integration (`RTBCB_LLM`)
Handles OpenAI API integration with caching and retry logic.

```php
class RTBCB_LLM {
    // Cached API calls
    private function call_openai_with_retry($model, $prompt, $max_retries = null);
    
    // Performance monitoring
    private function log_performance($model, $prompt, $duration, $cached);
    
    // Error handling
    private function handle_api_error($response, $model, $prompt);
}
```

### 2. Performance Monitor (`RTBCB_Performance_Monitor`)
Tracks performance metrics across the application.

```php
class RTBCB_Performance_Monitor {
    public static function start_timer($operation_name);
    public static function end_timer($operation_name, $context = []);
    public static function get_performance_summary();
}
```

### 3. Error Handler (`RTBCB_Error_Handler`)
Centralized error logging and management.

```php
class RTBCB_Error_Handler {
    public static function log_error($message, $level, $context, $source);
    public static function handle_llm_error($error_message, $model, $prompt, $response_code);
    public static function get_error_stats($hours = 24);
}
```

### 4. Calculator (`RTBCB_Calculator`)
Financial calculations and ROI modeling.

```php
class RTBCB_Calculator {
    public static function calculate_roi($inputs);
    public static function generate_financial_projections($roi_data);
    public static function calculate_payback_period($investment, $annual_benefit);
}
```

## Development Workflow

### 1. Feature Development
```bash
# Create feature branch
git checkout -b feature/your-feature-name

# Make changes following coding standards
# Add tests for new functionality
# Update documentation

# Run tests
npm run test

# Lint code
npm run lint

# Commit changes
git commit -m "feat: add your feature description"

# Push and create PR
git push origin feature/your-feature-name
```

### 2. Testing Workflow
```bash
# Run all tests
npm run test

# Run specific test suites
npm run test:php           # PHP unit tests
npm run test:js            # JavaScript tests
npm run test:php:unit      # PHP unit tests only
npm run test:php:integration # PHP integration tests only

# Run linting
npm run lint               # All linting
npm run lint:php           # PHP linting
npm run lint:js            # JavaScript linting
```

### 3. Code Quality
```bash
# Fix code style issues
npm run lint:fix
npm run lint:php:fix
npm run lint:js:fix

# Check PHP compatibility
npm run compat:check

# Manual syntax check
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
```

## Coding Standards

### PHP Standards
Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

#### Key Rules
```php
// Class naming: RTBCB_ prefix
class RTBCB_Your_Class {

// Function naming: rtbcb_ prefix for global functions
function rtbcb_your_function() {

// Constants: RTBCB_ prefix, uppercase
define( 'RTBCB_YOUR_CONSTANT', 'value' );

// Sanitization: Always sanitize input
$input = sanitize_text_field( wp_unslash( $_POST['field'] ) );

// Escaping: Always escape output
echo esc_html( $data );

// Nonces: Use for all forms and AJAX
wp_nonce_field( 'action_name', 'nonce_name' );
check_ajax_referer( 'action_name', 'nonce_name' );

// Translations: Use text domain 'rtbcb'
__( 'Your string', 'rtbcb' );
esc_html__( 'Your string', 'rtbcb' );
```

#### Documentation Standards
```php
/**
 * Brief description of the function.
 *
 * Longer description if needed. Explain what the function does,
 * any important behaviors, side effects, etc.
 *
 * @since 2.1.0
 * @param string $param1 Description of parameter.
 * @param array  $param2 {
 *     Optional. Description of parameter.
 *     @type string $key1 Description.
 *     @type bool   $key2 Description.
 * }
 * @return array|WP_Error Result array or WP_Error on failure.
 */
function rtbcb_your_function( $param1, $param2 = [] ) {
    // Implementation
}
```

### JavaScript Standards
Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/).

```javascript
// Use jQuery safely
(function($) {
    'use strict';
    
    // Your code here
    
})(jQuery);

// AJAX calls
$.ajax({
    url: rtbcbAjax.ajax_url,
    type: 'POST',
    data: {
        action: 'your_action',
        nonce: rtbcbAjax.nonce,
        // other data
    },
    success: function(response) {
        if (response.success) {
            // Handle success
        } else {
            // Handle error
        }
    }
});
```

## Testing

### PHP Testing

#### Unit Tests
```php
class RTBCB_Your_Class_Test extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        // Test setup
    }
    
    public function test_your_method() {
        $result = rtbcb_your_function( 'test' );
        $this->assertEquals( 'expected', $result );
    }
    
    public function tearDown(): void {
        // Test cleanup
        parent::tearDown();
    }
}
```

#### Integration Tests
```php
class RTBCB_Integration_Test extends WP_UnitTestCase {
    
    public function test_full_workflow() {
        // Test complete user workflows
        $input = $this->get_test_input();
        $result = rtbcb_generate_business_case( $input );
        
        $this->assertNotWPError( $result );
        $this->assertArrayHasKey( 'executive_summary', $result );
    }
}
```

### Test Data
```php
// Use consistent test data
protected function get_test_company_data() {
    return [
        'name' => 'Test Corporation',
        'size' => 'medium',
        'industry' => 'manufacturing',
        'complexity' => 'standard',
    ];
}
```

### Mocking External APIs
```php
// Mock OpenAI API responses
add_filter( 'pre_http_request', function( $preempt, $args ) {
    if ( strpos( $args['body'], 'api.openai.com' ) !== false ) {
        return [
            'body' => json_encode([
                'output_text' => 'Mock response content'
            ]),
            'response' => [ 'code' => 200 ]
        ];
    }
    return $preempt;
}, 10, 2 );
```

## Performance Optimization

### Caching Strategy
```php
// LLM response caching
$cache_key = rtbcb_generate_cache_key( $model, $prompt );
$cached = get_transient( $cache_key );

if ( false === $cached ) {
    $response = rtbcb_call_api( $model, $prompt );
    set_transient( $cache_key, $response, HOUR_IN_SECONDS );
}
```

### Performance Monitoring
```php
// Monitor performance of operations
RTBCB_Performance_Monitor::start_timer( 'llm_api_call' );
$result = rtbcb_expensive_operation();
RTBCB_Performance_Monitor::end_timer( 'llm_api_call', $context );
```

### Database Optimization
```php
// Use prepared statements
global $wpdb;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}table WHERE field = %s",
        $value
    )
);

// Batch operations
$wpdb->query( 'START TRANSACTION' );
// Multiple operations
$wpdb->query( 'COMMIT' );
```

## Security Best Practices

### Input Validation and Sanitization
```php
// Sanitize different input types
$text = sanitize_text_field( wp_unslash( $_POST['text'] ) );
$email = sanitize_email( wp_unslash( $_POST['email'] ) );
$url = esc_url_raw( wp_unslash( $_POST['url'] ) );
$textarea = sanitize_textarea_field( wp_unslash( $_POST['textarea'] ) );

// Validate complex data
$validated = RTBCB_Validator::validate_business_data( $input );
if ( is_wp_error( $validated ) ) {
    return $validated;
}
```

### Output Escaping
```php
// Escape for different contexts
echo esc_html( $text );
echo esc_attr( $attribute );
echo esc_url( $url );
echo wp_kses_post( $html_content );
```

### Capability Checks
```php
// Check user permissions
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions.', 'rtbcb' ) );
}

// AJAX capability checks
if ( ! check_ajax_referer( 'action_name', 'nonce', false ) ) {
    wp_die( __( 'Security check failed.', 'rtbcb' ) );
}
```

## Debugging

### Debug Mode
```php
// Enable debug mode
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'RTBCB_DEBUG', true );

// Debug logging
if ( defined( 'RTBCB_DEBUG' ) && RTBCB_DEBUG ) {
    error_log( 'RTBCB Debug: ' . $message );
}
```

### Performance Debugging
```php
// Monitor specific operations
RTBCB_Performance_Monitor::start_timer( 'debug_operation' );
your_function();
$duration = RTBCB_Performance_Monitor::end_timer( 'debug_operation' );

// Get performance summary
$summary = RTBCB_Performance_Monitor::get_performance_summary();
error_log( 'Performance Summary: ' . print_r( $summary, true ) );
```

### Error Debugging
```php
// Get recent errors
$errors = RTBCB_Error_Handler::get_recent_errors( 50 );
foreach ( $errors as $error ) {
    error_log( "Error: {$error['message']} ({$error['source']})" );
}

// Check for critical errors
if ( RTBCB_Error_Handler::has_critical_errors( 1 ) ) {
    error_log( 'Critical errors detected in the last hour' );
}
```

## Contributing

### Pull Request Process
1. **Fork** the repository
2. **Create** a feature branch
3. **Write** tests for new functionality
4. **Follow** coding standards
5. **Update** documentation
6. **Submit** pull request with clear description

### Code Review Checklist
- [ ] Code follows WordPress coding standards
- [ ] All inputs are sanitized
- [ ] All outputs are escaped
- [ ] Functions are documented
- [ ] Tests are included
- [ ] Performance impact is considered
- [ ] Security implications are addressed

### Release Process
1. **Version** update in main plugin file
2. **Changelog** update
3. **Translation** template update
4. **Testing** on multiple environments
5. **Tag** release in Git
6. **Build** distribution package

## Common Development Tasks

### Adding a New AJAX Endpoint
```php
// 1. Register the action
add_action( 'wp_ajax_rtbcb_your_action', 'rtbcb_handle_your_action' );
add_action( 'wp_ajax_nopriv_rtbcb_your_action', 'rtbcb_handle_your_action' );

// 2. Implement the handler
function rtbcb_handle_your_action() {
    // Security checks
    if ( ! check_ajax_referer( 'rtbcb_your_nonce', 'nonce', false ) ) {
        wp_die( __( 'Security check failed.', 'rtbcb' ) );
    }
    
    // Input validation
    $input = sanitize_text_field( wp_unslash( $_POST['input'] ) );
    
    // Processing
    $result = rtbcb_process_input( $input );
    
    // Response
    wp_send_json_success( $result );
}
```

### Adding a New Class
```php
// 1. Create file: inc/class-rtbcb-your-class.php
<?php
/**
 * Description of your class.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

class RTBCB_Your_Class {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Initialization
    }
    
    /**
     * Your method.
     *
     * @param string $param Parameter description.
     * @return array Result.
     */
    public function your_method( $param ) {
        // Implementation
    }
}

// 2. Include in main plugin file
require_once RTBCB_PATH . 'inc/class-rtbcb-your-class.php';
```

### Extending Performance Monitoring
```php
// Add custom metrics
RTBCB_Performance_Monitor::record_metric( 
    'custom_operation', 
    $duration, 
    $context 
);

// Get operation stats
$stats = RTBCB_Performance_Monitor::get_operation_stats( 
    'custom_operation', 
    24 
);
```

## Resources

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)

## Support

For development questions or issues:
1. Check existing [GitHub Issues](https://github.com/RealTreasury/business-case-builder/issues)
2. Review this documentation
3. Check the API documentation
4. Create a new issue with detailed information

Remember to follow security best practices and never commit sensitive information like API keys to the repository.