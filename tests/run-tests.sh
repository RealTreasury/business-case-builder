#!/bin/bash

echo "Running Real Treasury Business Case Builder Tests..."
echo "================================================"

# OPENAI_API_KEY is required for tests that make live API calls.
if [ -z "$OPENAI_API_KEY" ]; then
    echo "Warning: OPENAI_API_KEY not set. OpenAI-dependent tests will be skipped."
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

# OpenAI error handling test
echo "10. Running OpenAI error handling test..."
php tests/openai-error-handling.test.php

# AJAX error handling test (PHPUnit)
echo "11. Running AJAX error handling test..."
phpunit tests/RTBCB_AjaxGenerateComprehensiveCaseErrorTest.php

# Admin AJAX report generation tests
echo "12. Running admin AJAX report generation tests..."
phpunit tests/RTBCB_AdminAjaxReportTest.php

# JavaScript tests
echo "13. Running JavaScript tests..."
node tests/handle-submit-error.test.js
node tests/render-results-no-narrative.test.js
node tests/handle-submit-success.test.js
node tests/handle-server-error-display.test.js
node tests/temperature-model.test.js

# WordPress coding standards (if installed)
if command -v phpcs &> /dev/null; then
    echo "14. Running WordPress coding standards check..."
    phpcs --standard=WordPress --ignore=vendor .
else
    echo "14. Skipping WordPress coding standards (phpcs not installed)"
fi

echo "================================================"
echo "Tests complete!"
