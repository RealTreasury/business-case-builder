#!/bin/bash

echo "Running Real Treasury Business Case Builder Tests..."
echo "================================================"

# Ensure required environment variables for tests
export OPENAI_API_KEY="${OPENAI_API_KEY:-sk-test}"
export RTBCB_TEST_MODEL="${RTBCB_TEST_MODEL:-gpt-5-mini}"

# Ensure PHPUnit is installed
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "Run \`composer install\` to install PHPUnit"
    exit 1
fi

# Install JS dependencies for headless browser tests
npm install --no-save --no-package-lock jsdom >/dev/null 2>&1
export NODE_OPTIONS="--require ./tests/jsdom-setup.js"

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

echo "12b. Rendering financial benchmarks in template..."
php tests/financial-benchmarks-template.test.php

echo "12c. Rendering comprehensive template with missing fields..."
php tests/render-comprehensive-template-missing-fields.test.php

echo "12d. Rendering comprehensive template preview placeholders..."
php tests/render-comprehensive-template-preview.test.php

echo "13. Running report interactivity test..."
node tests/report-interactivity.test.js

echo "13b. Running extended report interactivity test..."
node tests/report-interactivity-extended.test.js

# Email and PDF test
echo "14. Running email and PDF test..."
php tests/email-notification.test.php

# AJAX error handling test (PHPUnit)
echo "15. Running AJAX error handling tests..."
vendor/bin/phpunit tests/RTBCB_AjaxGenerateComprehensiveCaseErrorTest.php
vendor/bin/phpunit tests/RTBCB_AjaxGenerateComprehensiveCaseFatalErrorTest.php
vendor/bin/phpunit tests/RTBCB_GenerateBusinessAnalysisTimeoutTest.php
vendor/bin/phpunit tests/RTBCB_ReportErrorHandlingTest.php
echo "15b. Running debug and nonce handler tests..."
vendor/bin/phpunit tests/RTBCB_EmergencyDebugHandlerTest.php
vendor/bin/phpunit tests/RTBCB_SimpleTestHandlerTest.php
vendor/bin/phpunit tests/RTBCB_NonceRegenerationEndpointTest.php

# Background job test
echo "14. Running background job tests..."
php tests/background-job.test.php

# Job status test
echo "14b. Running job status tests..."
php tests/job-status.test.php

# Business analysis generation test
echo "14c. Running business analysis generation test..."
vendor/bin/phpunit tests/RTBCB_GenerateBusinessAnalysisTest.php
echo "14d. Running Jetpack compatibility test..."
php tests/jetpack-compatibility.test.php
echo "14e. Running reports bulk delete test..."
php tests/reports-bulk-delete.test.php
echo "14f. Running report type test..."
vendor/bin/phpunit tests/RTBCB_ReportTypeTest.php

# JavaScript tests
echo "16. Running JavaScript tests..."
node tests/handle-submit-error.test.js
node tests/handle-submit-no-ajax-url.test.js
node tests/render-results-no-narrative.test.js
node tests/handle-submit-success.test.js
node tests/handle-server-error-display.test.js
node tests/handle-invalid-server-response.test.js
node tests/handle-string-error-response.test.js
node tests/handle-submit-invalid-ajax-url.test.js
node tests/rtbcb-handle-submit-invalid-ajax-url.test.js
node tests/handle-submit-nonce-retry.test.js
node tests/is-valid-url.test.js
node tests/temperature-model.test.js
node tests/min-output-tokens.test.js
node tests/gpt5-config-defaults.test.js
node tests/api-logs-page.test.js
node tests/wizard-report-flow.test.js
node tests/wizard-basic-flow.test.js
node tests/operational-insights-render.test.js
node tests/progress-cancel-button.test.js
node tests/wizard-missing-progress-step.test.js
node tests/wizard-no-form-no-interval.test.js
npx --yes jest tests/poll-job-completed.test.js --config '{"testEnvironment":"node"}'
npx --yes jest tests/poll-job-show-results.test.js --config '{"testEnvironment":"node"}'
npx --yes jest tests/poll-job-progress-text.test.js --config '{"testEnvironment":"node"}'
npx --yes jest tests/poll-job-partial-fields.test.js --config '{"testEnvironment":"node"}'

# WordPress coding standards (if installed)
if command -v phpcs &> /dev/null; then
    echo "17. Running WordPress coding standards check..."
    phpcs --standard=WordPress --ignore=vendor .
else
    echo "17. Skipping WordPress coding standards (phpcs not installed)"
fi

echo "18. Running project growth path test..."
php tests/project-growth-path.test.php

echo "18b. Running company research cache test..."
php tests/company-research-cache.test.php

echo "18c. Running Jetpack cron test..."
php tests/jetpack-cron.test.php

echo "18d. Running WordPress.com streaming fallback test..."
php tests/wpcom-sse-fallback.test.php

echo "18e. Running WP.com streaming response parser test..."
php tests/wpcom-streaming-response.test.php

echo "18f. Running non-RTBCB AJAX isolation test..."
php tests/non-rtbcb-ajax.test.php

echo "19. Running validator tests..."
vendor/bin/phpunit -c phpunit.xml

echo "================================================"
echo "Tests complete!"
