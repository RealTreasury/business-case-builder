#!/bin/bash

echo "Running Real Treasury Business Case Builder Tests..."
echo "================================================"

# Ensure required environment variables for tests
export OPENAI_API_KEY="${OPENAI_API_KEY:-sk-test}"
export RTBCB_TEST_MODEL="${RTBCB_TEST_MODEL:-gpt-5-mini}"

# Install JS dependencies for headless browser tests
npm install --no-save --no-package-lock jsdom >/dev/null 2>&1
export NODE_OPTIONS="--require ./tests/jsdom-setup.js"

# Determine commands for phpunit and phpcs
PHPUNIT="phpunit"
if [ -f vendor/bin/phpunit ]; then
    PHPUNIT="vendor/bin/phpunit"
elif ! command -v phpunit >/dev/null 2>&1; then
    PHPUNIT=""
fi

PHPCS="phpcs"
if [ -f vendor/bin/phpcs ]; then
    PHPCS="vendor/bin/phpcs"
elif ! command -v phpcs >/dev/null 2>&1; then
    PHPCS=""
fi

# PHP Lint
echo "1. Running PHP syntax check..."
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l

# JSON output lint
echo "2. Running JSON output lint..."
php tests/json-output-lint.php

# Cosine similarity search test
echo "3. Running cosine similarity search test..."
php tests/cosine-similarity-search.test.php

# Filter override test
echo "4. Running filter override test..."
php tests/filters-override.test.php

# Scenario selection test
echo "5. Running scenario selection test..."
php tests/scenario-selection.test.php

# Parse comprehensive response test
echo "6. Running parse comprehensive response test..."
php tests/parse-comprehensive-response.test.php

# Mini model dynamic test
echo "7. Running mini model dynamic test..."
php tests/mini-model-dynamic.test.php

# API tester GPT-5 mini test
echo "8. Running API tester GPT-5 mini test..."
php tests/api-tester-gpt5-mini.test.php

# Reasoning-first output parsing test
echo "9. Running reasoning-first output test..."
php tests/reasoning-first-output.test.php

# OpenAI API key validation test
echo "10. Running OpenAI API key validation test..."
php tests/openai-api-key-validation.test.php

# Lead storage test
echo "11. Running lead storage test..."
php tests/lead-storage.test.php

echo "12. Rendering comprehensive report template..."
php tests/render-comprehensive-template.test.php

echo "13. Running report memory usage test..."
php tests/report-memory-usage.test.php

echo "14. Running report interactivity test..."
node tests/report-interactivity.test.js

echo "12b. Running extended report interactivity test..."
node tests/report-interactivity-extended.test.js

# Email and PDF test
echo "13b. Running email and PDF test..."
php tests/email-and-pdf.test.php

# AJAX error handling test (PHPUnit)
echo "15. Running AJAX error handling tests..."
if [ -n "$PHPUNIT" ]; then
    $PHPUNIT tests/RTBCB_AjaxGenerateComprehensiveCaseErrorTest.php
    $PHPUNIT tests/RTBCB_AjaxGenerateComprehensiveCaseFatalErrorTest.php
    $PHPUNIT tests/RTBCB_GenerateBusinessAnalysisTimeoutTest.php
    $PHPUNIT tests/report-error-handling.test.php
else
    echo "Skipping AJAX error handling tests (phpunit not installed)"
fi

# Background job test
echo "14. Running background job tests..."
php tests/background-job.test.php

# Job status test
echo "14b. Running job status tests..."
php tests/job-status.test.php

# Business analysis generation test
echo "14c. Running business analysis generation test..."
if [ -n "$PHPUNIT" ]; then
    $PHPUNIT tests/generate-business-analysis.test.php
else
    echo "Skipping business analysis generation test (phpunit not installed)"
fi

# JavaScript tests
echo "16. Running JavaScript tests..."
node tests/handle-submit-error.test.js
node tests/handle-submit-no-ajax-url.test.js
node tests/render-results-no-narrative.test.js
node tests/handle-submit-success.test.js
node tests/handle-server-error-display.test.js
node tests/handle-invalid-server-response.test.js
node tests/handle-string-error-response.test.js
node tests/temperature-model.test.js
node tests/min-output-tokens.test.js
node tests/gpt5-config-defaults.test.js
node tests/export-to-pdf-button.test.js
node tests/wizard-report-flow.test.js
npx --yes jest tests/poll-job-completed.test.js --config '{"testEnvironment":"node"}'
npx --yes jest tests/poll-job-show-results.test.js --config '{"testEnvironment":"node"}'
npx --yes jest tests/poll-job-progress-text.test.js --config '{"testEnvironment":"node"}'
npx --yes jest tests/poll-job-partial-fields.test.js --config '{"testEnvironment":"node"}'

# WordPress coding standards (if installed)
if [ -n "$PHPCS" ]; then
    echo "17. Running WordPress coding standards check..."
    $PHPCS --config-set installed_paths vendor/wp-coding-standards/wpcs >/dev/null 2>&1
    $PHPCS --standard=WordPress --ignore=vendor .
else
    echo "17. Skipping WordPress coding standards (phpcs not installed)"
fi

echo "18. Running project growth path test..."
php tests/project-growth-path.test.php

echo "18b. Running company research cache test..."
php tests/company-research-cache.test.php

echo "19. Running validator tests..."
if [ -n "$PHPUNIT" ]; then
    $PHPUNIT -c phpunit.xml
else
    echo "Skipping validator tests (phpunit not installed)"
fi

echo "================================================"
echo "Tests complete!"
