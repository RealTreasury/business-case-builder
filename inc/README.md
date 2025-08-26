# Inc Directory Architecture

This directory contains the core business logic and API integration for the Real Treasury Business Case Builder plugin.

## Fresh Architecture Overview

The inc/ directory follows a clean, modern architecture pattern:

### Core Classes (`inc/classes/`)
- `class-rtbcb-api.php` - Modern OpenAI API integration
- `class-rtbcb-calculator.php` - ROI calculation engine
- `class-rtbcb-validator.php` - Input validation and sanitization
- `class-rtbcb-database.php` - Database operations and migrations
- `class-rtbcb-security.php` - Security and authentication

### API Layer (`inc/api/`)
- `openai-client.php` - OpenAI API client with modern error handling
- `rate-limiter.php` - API rate limiting and quota management
- `response-parser.php` - API response parsing and validation

### Utilities (`inc/utils/`)
- `helpers.php` - General utility functions
- `formatters.php` - Data formatting utilities
- `validators.php` - Validation helper functions

### Legacy Cleanup
All legacy files from the previous architecture have been removed and replaced with:
- Modern class-based structure
- Proper error handling and logging
- Comprehensive input validation
- Security best practices
- WordPress coding standards compliance

## Dependencies
- WordPress 6.0+
- PHP 7.4+
- OpenAI API access (optional)

## Security Features
- Input sanitization on all user data
- Output escaping for all displayed content
- Capability checks for admin functions
- Nonce verification for AJAX requests
- Rate limiting for API calls