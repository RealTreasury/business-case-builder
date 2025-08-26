# Copilot Coding Instructions

## Repository Overview

**Real Treasury Business Case Builder** is a WordPress plugin that generates ROI-driven business cases for treasury technology investments. It features a multi-step wizard, OpenAI-powered narrative generation, and comprehensive PDF reports.

- **Type**: WordPress Plugin (PHP/JavaScript)
- **Size**: ~85 files, ~15,000 lines of code
- **Languages**: PHP 7.4+ (WordPress), JavaScript (ES6), CSS
- **Framework**: WordPress 6.0+ with OpenAI API integration
- **Runtime**: LAMP/LEMP stack, requires MySQL/MariaDB

## Build and Validation Commands

### Prerequisites
Always run these commands **in order** before any development work:
```bash
# 1. Install PHP dependencies (REQUIRED)
composer install

# 2. Verify PHP environment 
php --version  # Must be 7.4+
```

### Core Validation Steps
```bash
# 1. PHP Syntax Check (ALWAYS run first)
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l

# 2. Run all tests (takes ~2-3 minutes)
bash tests/run-tests.sh

# 3. Specific test categories
php tests/json-output-lint.php                    # JSON validation
php tests/cosine-similarity-search.test.php       # RAG functionality  
php tests/scenario-selection.test.php             # ROI calculations
node tests/handle-submit-error.test.js            # Frontend error handling
```

### WordPress Integration Testing
```bash
# Test WordPress coding standards (if phpcs available)
phpcs --standard=WordPress --ignore=vendor .

# Test plugin activation/deactivation
wp plugin activate real-treasury-business-case-builder
wp plugin deactivate real-treasury-business-case-builder
```

### CI/CD Pipeline Validation
The `.github/workflows/ci.yml` runs comprehensive checks:
- PHP 7.4-8.3 compatibility testing
- WordPress 6.2-latest compatibility  
- PHPUnit tests with MySQL
- JavaScript linting and testing
- End-to-end Cypress tests
- WordPress.com compatibility validation

**Time Requirements:**
- PHP syntax check: ~10 seconds
- Full test suite: ~2-3 minutes
- CI pipeline: ~8-12 minutes

## Project Architecture

### Directory Structure
```
real-treasury-business-case-builder/
├── real-treasury-business-case-builder.php  # Main plugin file & bootstrap
├── inc/                                      # Core PHP classes
│   ├── class-rtbcb-calculator.php           # ROI calculation engine
│   ├── class-rtbcb-llm.php                  # OpenAI API integration  
│   ├── class-rtbcb-router.php               # Request orchestration
│   ├── class-rtbcb-rag.php                  # Vector search/similarity
│   ├── enhanced-ajax-handlers.php           # AJAX endpoints
│   └── helpers.php                          # Utility functions
├── admin/                                    # WordPress admin interface
│   ├── class-rtbcb-admin.php               # Admin menu/pages
│   ├── unified-test-dashboard-page.php     # Testing interface
│   └── leads-page-enhanced.php             # Lead management
├── public/                                   # Frontend assets
│   ├── css/rtbcb.css                       # Main styles
│   ├── js/rtbcb-wizard.js                  # Multi-step form
│   └── js/rtbcb-report.js                  # Report rendering
├── templates/                               # PHP templates  
│   ├── business-case-form.php              # User-facing wizard
│   └── comprehensive-report-template.php   # Report output
└── tests/                                   # Test suite
    ├── run-tests.sh                        # Master test runner
    ├── *.test.php                          # PHP unit tests
    └── *.test.js                           # JavaScript tests
```

### Key Configuration Files
- **composer.json**: PHP dependencies (minimal - no heavy frameworks)
- **.github/workflows/ci.yml**: Complete CI/CD pipeline with matrix testing
- **AGENTS.md**: Root-level coding standards (WordPress PHP standards)  
- **Directory-specific AGENTS.md**: Context-specific coding rules

### Data Flow & Architecture
1. **User Interaction**: Shortcode `[rt_business_case_builder]` renders wizard
2. **AJAX Processing**: `rtbcb_generate_case` action → `enhanced-ajax-handlers.php`
3. **Orchestration**: `RTBCB_Router` coordinates validation, calculation, RAG, LLM
4. **External APIs**: OpenAI GPT-4/GPT-4o for narrative generation
5. **Output**: JSON response → HTML report → optional PDF generation

### Database Tables
- `wp_rtbcb_leads`: Captured lead data and ROI results
- `wp_rtbcb_rag_index`: Vector embeddings for contextual search

## Coding Standards & Guidelines

### WordPress-Specific Rules
- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use 4-space indentation (not tabs)
- Prefix functions with `rtbcb_`, classes with `RTBCB_`
- Sanitize ALL input with `sanitize_*()`, escape ALL output with `esc_*()`
- Wrap user-visible strings: `__( 'text', 'rtbcb' )`

### File Organization Rules
- **inc/**: Core classes named `class-rtbcb-{feature}.php`
- **admin/**: Dashboard functionality, pages end with `-page.php`
- **public/**: Frontend assets and hooks
- **templates/**: Pure markup with minimal logic
- **vendor/**: Third-party code - **NEVER MODIFY**

### Security Requirements
- WordPress nonce verification for all forms/AJAX
- Capability checks: `current_user_can( 'manage_options' )`
- Input sanitization before database operations
- Output escaping in templates

### Testing Strategy
- **Unit Tests**: PHP classes in `/tests/*.test.php`
- **Integration Tests**: AJAX handlers with mock WordPress environment
- **JavaScript Tests**: Node.js-based testing for frontend components
- **E2E Tests**: Cypress tests for complete user workflows

### Common Pitfalls to Avoid
1. **Never modify vendor/ directory** - contains Composer dependencies
2. **Always escape output** - use appropriate `esc_*()` functions  
3. **Check API keys exist** before OpenAI calls - graceful fallbacks required
4. **Memory management** - LLM operations can consume 128MB+
5. **WordPress.com compatibility** - no exec(), shell_exec(), file system writes

### Validation Checklist
Before committing any changes:
- [ ] Run PHP syntax check on all modified files
- [ ] Execute relevant tests from `tests/run-tests.sh`
- [ ] Verify WordPress coding standards compliance
- [ ] Test with WordPress 6.0+ and PHP 7.4+
- [ ] Confirm OpenAI API integration works with/without API key
- [ ] Validate frontend wizard functionality in browser

### Performance Considerations
- Set `memory_limit` to 256MB for LLM operations
- Use `set_time_limit(300)` for API-heavy requests
- Implement graceful fallbacks when OpenAI API unavailable
- Cache embeddings in `wp_rtbcb_rag_index` table
- Optimize for WordPress.com shared hosting constraints

**Trust these instructions** - they represent validated patterns for this codebase. Only search beyond these instructions if specific implementation details are missing or you encounter errors following these guidelines.