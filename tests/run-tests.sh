#!/bin/bash

echo "Running Real Treasury Business Case Builder Tests..."
echo "================================================"

# PHP Lint
echo "1. Running PHP syntax check..."
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l

# JSON output lint
echo "2. Running JSON output lint..."
php tests/json-output-lint.php

# JavaScript test
echo "3. Running JavaScript tests..."
node tests/handle-submit-error.test.js

# WordPress coding standards (if installed)
if command -v phpcs &> /dev/null; then
    echo "4. Running WordPress coding standards check..."
    phpcs --standard=WordPress --ignore=vendor .
else
    echo "4. Skipping WordPress coding standards (phpcs not installed)"
fi

echo "================================================"
echo "Tests complete!"
