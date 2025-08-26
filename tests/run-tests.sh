#!/bin/bash

# Business Case Builder Test Runner
# Modern testing framework for comprehensive validation

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
TEST_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$TEST_DIR")"
FAILED_TESTS=0
TOTAL_TESTS=0

# Parse command line arguments
API_ONLY=false
UNIT_ONLY=false
INTEGRATION_ONLY=false
E2E_ONLY=false
VERBOSE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --api-only)
            API_ONLY=true
            shift
            ;;
        --unit-only)
            UNIT_ONLY=true
            shift
            ;;
        --integration-only)
            INTEGRATION_ONLY=true
            shift
            ;;
        --e2e-only)
            E2E_ONLY=true
            shift
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [options]"
            echo "Options:"
            echo "  --api-only         Run only API tests"
            echo "  --unit-only        Run only unit tests"
            echo "  --integration-only Run only integration tests"
            echo "  --e2e-only         Run only end-to-end tests"
            echo "  --verbose, -v      Verbose output"
            echo "  --help, -h         Show this help"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Utility functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[PASS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[FAIL]${NC} $1"
}

run_test() {
    local test_file="$1"
    local test_name="$(basename "$test_file" .test.php)"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if [[ "$VERBOSE" == true ]]; then
        log_info "Running: $test_name"
    fi
    
    if php "$test_file" > /dev/null 2>&1; then
        log_success "$test_name"
    else
        log_error "$test_name"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        if [[ "$VERBOSE" == true ]]; then
            php "$test_file"
        fi
    fi
}

# Header
echo "================================================================="
echo "Real Treasury Business Case Builder - Test Suite"
echo "================================================================="
echo ""

# Check prerequisites
log_info "Checking prerequisites..."

# Check PHP
if ! command -v php &> /dev/null; then
    log_error "PHP is not installed"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if [[ $(echo "$PHP_VERSION" | cut -d. -f1) -lt 7 ]] || [[ $(echo "$PHP_VERSION" | cut -d. -f1) -eq 7 && $(echo "$PHP_VERSION" | cut -d. -f2) -lt 4 ]]; then
    log_error "PHP 7.4 or higher is required (found: $PHP_VERSION)"
    exit 1
fi

log_success "PHP $PHP_VERSION"

# Check Composer dependencies
if [[ ! -d "$PLUGIN_DIR/vendor" ]]; then
    log_warning "Composer dependencies not found. Installing..."
    cd "$PLUGIN_DIR"
    composer install --no-dev
fi

# Check OpenAI API key for API tests
if [[ -z "$OPENAI_API_KEY" ]]; then
    log_warning "OPENAI_API_KEY not set. API tests will be skipped."
fi

echo ""

# Run PHP syntax check
log_info "Running PHP syntax validation..."
if find "$PLUGIN_DIR" -name "*.php" -not -path "*/vendor/*" -print0 | xargs -0 -n1 php -l > /dev/null 2>&1; then
    log_success "PHP syntax validation"
else
    log_error "PHP syntax validation failed"
    exit 1
fi

echo ""

# Run test suites
if [[ "$API_ONLY" == false && "$UNIT_ONLY" == false && "$INTEGRATION_ONLY" == false && "$E2E_ONLY" == false ]]; then
    # Run all tests
    API_ONLY=true
    UNIT_ONLY=true
    INTEGRATION_ONLY=true
    E2E_ONLY=true
fi

# API Tests
if [[ "$API_ONLY" == true ]]; then
    log_info "Running API tests..."
    if [[ -z "$OPENAI_API_KEY" ]]; then
        log_warning "Skipping API tests (no OPENAI_API_KEY)"
    else
        for test_file in "$TEST_DIR"/api/*.test.php; do
            if [[ -f "$test_file" ]]; then
                run_test "$test_file"
            fi
        done
    fi
    echo ""
fi

# Unit Tests
if [[ "$UNIT_ONLY" == true ]]; then
    log_info "Running unit tests..."
    for test_file in "$TEST_DIR"/unit/*.test.php; do
        if [[ -f "$test_file" ]]; then
            run_test "$test_file"
        fi
    done
    echo ""
fi

# Integration Tests
if [[ "$INTEGRATION_ONLY" == true ]]; then
    log_info "Running integration tests..."
    for test_file in "$TEST_DIR"/integration/*.test.php; do
        if [[ -f "$test_file" ]]; then
            run_test "$test_file"
        fi
    done
    echo ""
fi

# End-to-End Tests
if [[ "$E2E_ONLY" == true ]]; then
    log_info "Running end-to-end tests..."
    for test_file in "$TEST_DIR"/e2e/*.test.php; do
        if [[ -f "$test_file" ]]; then
            run_test "$test_file"
        fi
    done
    echo ""
fi

# Summary
echo "================================================================="
echo "Test Results"
echo "================================================================="
echo "Total tests: $TOTAL_TESTS"
echo "Passed: $((TOTAL_TESTS - FAILED_TESTS))"
echo "Failed: $FAILED_TESTS"

if [[ $FAILED_TESTS -gt 0 ]]; then
    log_error "Some tests failed!"
    exit 1
else
    log_success "All tests passed!"
    exit 0
fi