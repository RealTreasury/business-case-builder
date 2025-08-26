# Business Case Builder Testing Framework

This directory contains a comprehensive testing framework for the Real Treasury Business Case Builder plugin.

## Test Categories

### 1. API Testing (`api/`)
- OpenAI API integration tests
- Authentication and rate limiting
- Error handling and fallbacks
- Model capability testing

### 2. Unit Tests (`unit/`)
- Core business logic validation
- Calculator engine testing
- Data validation and sanitization
- Class method testing

### 3. Integration Tests (`integration/`)
- WordPress integration
- AJAX endpoint testing
- Database operations
- Admin interface testing

### 4. End-to-End Tests (`e2e/`)
- Complete user workflows
- Form submission and processing
- Report generation
- Multi-step wizard testing

## Running Tests

### Prerequisites
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (for JavaScript tests)
npm install
```

### Execute Test Suite
```bash
# Run all tests
./tests/run-tests.sh

# Run specific test categories
./tests/run-tests.sh --api-only
./tests/run-tests.sh --unit-only
./tests/run-tests.sh --integration-only
./tests/run-tests.sh --e2e-only
```

### Environment Variables
- `OPENAI_API_KEY`: Required for API integration tests
- `WP_TEST_DB_NAME`: Database name for WordPress integration tests
- `WP_TEST_DB_USER`: Database user for testing
- `WP_TEST_DB_PASSWORD`: Database password for testing

## Test Standards

All tests follow WordPress coding standards and include:
- Proper error handling and reporting
- Comprehensive assertions
- Clear test descriptions
- Cleanup procedures
- Performance benchmarks where applicable

## Continuous Integration

Tests are automatically run in GitHub Actions for:
- PHP 7.4, 8.0, 8.1, 8.2, 8.3
- WordPress 6.0, 6.1, 6.2, latest
- Multiple database configurations