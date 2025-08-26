# Contributing to Real Treasury Business Case Builder

Thank you for your interest in contributing to the Real Treasury Business Case Builder! This document provides guidelines for contributing to ensure high-quality, secure, and maintainable code.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Development Setup](#development-setup)
3. [Contributing Workflow](#contributing-workflow)
4. [Code Standards](#code-standards)
5. [Testing Requirements](#testing-requirements)
6. [Documentation](#documentation)
7. [Security Guidelines](#security-guidelines)
8. [Performance Considerations](#performance-considerations)
9. [Review Process](#review-process)
10. [Release Process](#release-process)

## Getting Started

### Prerequisites
- Familiarity with WordPress plugin development
- Understanding of PHP, JavaScript, and WordPress coding standards
- Experience with Git and GitHub workflow
- Knowledge of security best practices for financial applications

### Code of Conduct
- Be respectful and professional in all interactions
- Focus on constructive feedback and collaboration
- Prioritize security and data protection in all contributions
- Follow the principle of least privilege in access and permissions

## Development Setup

### Initial Setup
```bash
# Fork the repository on GitHub
# Clone your fork
git clone https://github.com/YOUR_USERNAME/business-case-builder.git
cd business-case-builder

# Add upstream remote
git remote add upstream https://github.com/RealTreasury/business-case-builder.git

# Install dependencies
npm run setup

# Start development environment
npm run wp-env:start
```

### Environment Configuration
Ensure your development environment has:
- PHP 7.4+ (8.0+ recommended)
- WordPress 5.8+ (latest version recommended)
- Node.js 16+ and npm 8+
- Composer 2.0+

## Contributing Workflow

### 1. Issue-Based Development
- Check existing issues before starting work
- Comment on issues you plan to work on
- Create new issues for bugs or feature requests
- Use clear, descriptive titles and detailed descriptions

### 2. Branch Strategy
```bash
# Create feature branch from main
git checkout main
git pull upstream main
git checkout -b feature/descriptive-name

# For bug fixes
git checkout -b fix/issue-description

# For documentation
git checkout -b docs/documentation-update
```

### 3. Commit Guidelines
Use conventional commit messages:
```
feat: add LLM response caching mechanism
fix: resolve validation error in company size input
docs: update API documentation for AJAX endpoints
test: add integration tests for performance monitoring
refactor: consolidate error handling across modules
perf: optimize database queries in ROI calculator
security: enhance input sanitization in AJAX handlers
```

### 4. Pull Request Process
1. **Create Pull Request** with clear title and description
2. **Link related issues** using "Fixes #123" or "Addresses #123"
3. **Provide testing instructions** for reviewers
4. **Update documentation** if necessary
5. **Ensure all checks pass** (tests, linting, security)

## Code Standards

### PHP Standards
Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) and the project's AGENTS.md guidelines:

```php
// ✅ Good
class RTBCB_New_Feature {
    public function process_data( $input ) {
        $sanitized = sanitize_text_field( wp_unslash( $input ) );
        return esc_html( $sanitized );
    }
}

// ❌ Bad
class NewFeature {
    public function processData($input) {
        return $input; // No sanitization/escaping
    }
}
```

### Required Practices
- **Prefix all functions** with `rtbcb_`
- **Prefix all classes** with `RTBCB_`
- **Sanitize all inputs** using appropriate WordPress functions
- **Escape all outputs** using `esc_*` functions
- **Use nonces** for all forms and AJAX requests
- **Check user capabilities** for protected operations
- **Wrap user-visible strings** in translation functions

### JavaScript Standards
```javascript
// ✅ Good
(function($) {
    'use strict';
    
    const rtbcb = {
        ajax: {
            url: rtbcbAjax.ajax_url,
            nonce: rtbcbAjax.nonce
        },
        
        generateCase: function(data) {
            return $.ajax({
                url: this.ajax.url,
                type: 'POST',
                data: {
                    action: 'rtbcb_generate_case',
                    nonce: this.ajax.nonce,
                    ...data
                }
            });
        }
    };
    
})(jQuery);
```

## Testing Requirements

### Required Tests for New Features
1. **Unit Tests** for individual functions/methods
2. **Integration Tests** for complete workflows
3. **Security Tests** for input validation and sanitization
4. **Performance Tests** for expensive operations

### Test Structure
```php
class RTBCB_Your_Feature_Test extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        // Test setup
    }
    
    /**
     * @covers RTBCB_Your_Class::your_method
     */
    public function test_your_method_with_valid_input() {
        $input = $this->get_valid_test_data();
        $result = rtbcb_your_function( $input );
        
        $this->assertNotWPError( $result );
        $this->assertArrayHasKey( 'expected_key', $result );
    }
    
    /**
     * @covers RTBCB_Your_Class::your_method
     */
    public function test_your_method_with_invalid_input() {
        $invalid_input = '';
        $result = rtbcb_your_function( $invalid_input );
        
        $this->assertWPError( $result );
        $this->assertEquals( 'expected_error_code', $result->get_error_code() );
    }
    
    public function tearDown(): void {
        // Test cleanup
        parent::tearDown();
    }
}
```

### Running Tests
```bash
# Run all tests
npm run test

# Run specific test types
npm run test:php:unit
npm run test:php:integration
npm run test:js

# Run linting
npm run lint
npm run lint:fix
```

## Documentation

### Required Documentation Updates
- **API.md**: Update for new AJAX endpoints
- **DEVELOPER.md**: Add new development patterns or tools
- **README.md**: Update if user-facing features change
- **Inline documentation**: PHPDoc comments for all functions/classes

### Documentation Standards
```php
/**
 * Brief description of what the function does.
 *
 * Longer description explaining the purpose, behavior, and any
 * important considerations for using this function.
 *
 * @since 2.1.0
 * @param string $company_name Company name to analyze.
 * @param array  $options {
 *     Optional configuration options.
 *     @type string $industry    Industry sector.
 *     @type string $size        Company size (small|medium|large|enterprise).
 *     @type bool   $use_cache   Whether to use cached results.
 * }
 * @return array|WP_Error Analysis results or WP_Error on failure.
 */
function rtbcb_analyze_company( $company_name, $options = [] ) {
    // Implementation
}
```

## Security Guidelines

### Critical Security Requirements
All contributions must follow these security practices:

1. **Input Validation and Sanitization**
```php
// Always sanitize user input
$user_input = sanitize_text_field( wp_unslash( $_POST['field'] ) );
$email = sanitize_email( wp_unslash( $_POST['email'] ) );
$url = esc_url_raw( wp_unslash( $_POST['url'] ) );
```

2. **Output Escaping**
```php
// Always escape output
echo esc_html( $user_data );
echo esc_attr( $attribute_value );
echo esc_url( $url );
```

3. **Nonce Verification**
```php
// AJAX handlers must verify nonces
if ( ! check_ajax_referer( 'rtbcb_action', 'nonce', false ) ) {
    wp_die( __( 'Security check failed.', 'rtbcb' ) );
}
```

4. **Capability Checks**
```php
// Check user permissions
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions.', 'rtbcb' ) );
}
```

### Sensitive Data Handling
- **Never log API keys** or sensitive user data
- **Use WordPress options API** for secure storage
- **Implement proper access controls** for admin functions
- **Follow GDPR/privacy principles** for data handling

## Performance Considerations

### Performance Requirements
- **Monitor expensive operations** with performance tracking
- **Implement caching** for repetitive API calls
- **Optimize database queries** using prepared statements
- **Minimize memory usage** and clean up resources

### Performance Monitoring
```php
// Monitor performance of new features
RTBCB_Performance_Monitor::start_timer( 'your_operation' );
$result = your_expensive_operation();
RTBCB_Performance_Monitor::end_timer( 'your_operation', $context );
```

### Caching Best Practices
```php
// Check cache before expensive operations
$cache_key = 'rtbcb_' . md5( serialize( $params ) );
$cached_result = get_transient( $cache_key );

if ( false === $cached_result ) {
    $result = expensive_operation( $params );
    set_transient( $cache_key, $result, HOUR_IN_SECONDS );
} else {
    $result = $cached_result;
}
```

## Review Process

### Pull Request Review Criteria
1. **Functionality**: Does it work as expected?
2. **Security**: Are all inputs sanitized and outputs escaped?
3. **Performance**: Is it efficient and well-optimized?
4. **Code Quality**: Follows standards and best practices?
5. **Testing**: Adequate test coverage?
6. **Documentation**: Properly documented?
7. **Compatibility**: Works with target WordPress/PHP versions?

### Review Checklist
- [ ] Code follows WordPress coding standards
- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] Proper nonce verification for forms/AJAX
- [ ] User capability checks where needed
- [ ] Functions are properly documented
- [ ] Tests are included and passing
- [ ] Performance impact is acceptable
- [ ] Security implications are addressed
- [ ] Documentation is updated

### Reviewer Guidelines
- Provide constructive feedback
- Test the functionality locally when possible
- Check for security vulnerabilities
- Verify performance impact
- Ensure documentation is clear and complete

## Release Process

### Version Management
- Use [Semantic Versioning](https://semver.org/)
- Update version in main plugin file
- Update changelog with new features and fixes
- Tag releases appropriately

### Pre-Release Checklist
- [ ] All tests passing
- [ ] Documentation updated
- [ ] Translation template (.pot) updated
- [ ] Changelog updated
- [ ] Version bumped
- [ ] Security review completed
- [ ] Performance testing completed

## Common Contribution Types

### Bug Fixes
1. **Reproduce the bug** in a test environment
2. **Write a test** that fails due to the bug
3. **Fix the bug** with minimal changes
4. **Verify the test** now passes
5. **Check for edge cases** and additional test scenarios

### New Features
1. **Discuss the feature** in an issue first
2. **Design the API** and get feedback
3. **Implement with tests** and documentation
4. **Consider performance** and security implications
5. **Update relevant documentation**

### Security Fixes
1. **Report security issues privately** (not in public issues)
2. **Follow responsible disclosure** practices
3. **Coordinate with maintainers** on timing
4. **Test thoroughly** in isolated environments
5. **Document security implications**

## Resources

### Documentation
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Security](https://developer.wordpress.org/plugins/security/)
- [Project API Documentation](./API.md)
- [Project Developer Guide](./DEVELOPER.md)

### Tools
- [WordPress Coding Standards for PHPCodeSniffer](https://github.com/WordPress/WordPress-Coding-Standards)
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [PHPUnit](https://phpunit.de/)
- [WordPress Environment (@wordpress/env)](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)

## Getting Help

### Communication Channels
1. **GitHub Issues**: Bug reports and feature requests
2. **Pull Request Comments**: Code review discussions
3. **Documentation**: Check existing docs first

### Asking for Help
- Provide clear, detailed descriptions
- Include steps to reproduce issues
- Share relevant code snippets
- Mention your environment details

## Recognition

Contributors are recognized in:
- Git commit history
- Release notes and changelogs
- Project README (for significant contributions)
- GitHub contributor stats

Thank you for contributing to Real Treasury Business Case Builder! Your efforts help create better treasury technology solutions for businesses worldwide.