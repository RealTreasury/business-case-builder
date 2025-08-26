# Real Treasury Business Case Builder - API Documentation

This document provides comprehensive documentation for all AJAX endpoints and APIs in the Real Treasury Business Case Builder plugin.

## Table of Contents

1. [Authentication & Security](#authentication--security)
2. [Core Business Case API](#core-business-case-api)
3. [Testing & Diagnostics API](#testing--diagnostics-api)
4. [LLM Integration API](#llm-integration-api)
5. [RAG (Retrieval-Augmented Generation) API](#rag-retrieval-augmented-generation-api)
6. [Export & Data Management API](#export--data-management-api)
7. [Error Handling](#error-handling)
8. [Rate Limiting](#rate-limiting)
9. [Response Formats](#response-formats)

## Authentication & Security

All AJAX endpoints require:
- **Nonce verification** for CSRF protection
- **User capability checks** (typically `manage_options` for admin endpoints)
- **Input sanitization** using WordPress sanitization functions
- **Output escaping** for security

### Common Request Headers
```javascript
{
    'Content-Type': 'application/x-www-form-urlencoded',
    'X-Requested-With': 'XMLHttpRequest'
}
```

### Common Request Parameters
- `action`: The WordPress AJAX action name
- `nonce`: Security nonce for the specific action
- Additional parameters vary by endpoint

## Core Business Case API

### Generate Comprehensive Business Case

**Endpoint:** `rtbcb_generate_case`  
**Method:** POST  
**Capability Required:** None (public endpoint)  
**Nonce Action:** `rtbcb_nonce`

Generates a complete business case analysis including financial modeling, risk assessment, and implementation planning.

#### Request Parameters
```javascript
{
    action: 'rtbcb_generate_case',
    nonce: '{nonce_value}',
    company_name: 'string',
    company_size: 'small|medium|large|enterprise',
    complexity: 'basic|standard|advanced',
    focus_areas: ['area1', 'area2', ...],
    roi_inputs: {
        annual_volume: 'number',
        avg_transaction_size: 'number',
        manual_processing_time: 'number',
        error_rate: 'number',
        // ... additional ROI parameters
    },
    scenario: 'comprehensive|quick|detailed'
}
```

#### Response Format
```json
{
    "success": true,
    "data": {
        "executive_summary": {
            "strategic_positioning": "string",
            "business_case_strength": "Strong|Moderate|Weak",
            "key_value_drivers": ["string", ...],
            "executive_recommendation": "string"
        },
        "financial_analysis": {
            "investment_breakdown": { ... },
            "payback_analysis": { ... },
            "roi_projections": { ... }
        },
        "risk_assessment": { ... },
        "implementation_roadmap": { ... },
        "vendor_evaluation": { ... },
        "performance_metrics": {
            "generation_time": "float",
            "api_calls_made": "number",
            "cache_hits": "number"
        }
    }
}
```

### Simple Business Case Generation

**Endpoint:** `rtbcb_simple_test`  
**Method:** POST  
**Capability Required:** None (public endpoint)  
**Nonce Action:** `rtbcb_simple_nonce`

Generates a simplified business case for quick analysis.

#### Request Parameters
```javascript
{
    action: 'rtbcb_simple_test',
    nonce: '{nonce_value}',
    company_name: 'string',
    industry: 'string',
    size: 'small|medium|large|enterprise'
}
```

## Testing & Diagnostics API

### API Health Check

**Endpoint:** `rtbcb_api_health_ping`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_health_ping`

Tests connectivity and health of the OpenAI API integration.

#### Request Parameters
```javascript
{
    action: 'rtbcb_api_health_ping',
    nonce: '{nonce_value}'
}
```

#### Response Format
```json
{
    "success": true,
    "data": {
        "api_status": "healthy|degraded|down",
        "response_time": "float",
        "api_key_valid": "boolean",
        "model_availability": {
            "gpt-5-mini": "available|unavailable",
            "gpt-4": "available|unavailable"
        },
        "last_error": "string|null",
        "timestamp": "unix_timestamp"
    }
}
```

### Run Comprehensive API Health Tests

**Endpoint:** `rtbcb_run_api_health_tests`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_api_health`

Runs a comprehensive suite of API health and functionality tests.

#### Request Parameters
```javascript
{
    action: 'rtbcb_run_api_health_tests',
    nonce: '{nonce_value}',
    test_types: ['connectivity', 'models', 'functionality'],
    verbose: 'boolean'
}
```

### Debug API Key

**Endpoint:** `rtbcb_debug_api_key`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_debug_api_key`

Validates and tests the configured OpenAI API key.

#### Request Parameters
```javascript
{
    action: 'rtbcb_debug_api_key',
    nonce: '{nonce_value}'
}
```

## LLM Integration API

### Test LLM Model

**Endpoint:** `rtbcb_test_llm_model`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_llm_testing`

Tests specific LLM models with custom prompts and analyzes performance.

#### Request Parameters
```javascript
{
    action: 'rtbcb_test_llm_model',
    nonce: '{nonce_value}',
    modelIds: ['gpt-5-mini', 'gpt-4'],
    testPrompts: ['prompt1', 'prompt2'],
    maxOutputTokens: 'number',
    temperature: 'float'
}
```

#### Response Format
```json
{
    "success": true,
    "data": {
        "test_results": [
            {
                "model": "gpt-5-mini",
                "prompt": "string",
                "response": "string",
                "performance": {
                    "response_time": "float",
                    "token_count": "number",
                    "quality_score": "number",
                    "cached": "boolean"
                },
                "error": "string|null"
            }
        ],
        "summary": {
            "total_tests": "number",
            "successful_tests": "number",
            "average_response_time": "float",
            "cache_hit_rate": "float"
        }
    }
}
```

### Enhanced Company Overview Test

**Endpoint:** `rtbcb_test_company_overview_enhanced`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_unified_test_dashboard`

Tests company overview generation with enhanced analytics.

#### Request Parameters
```javascript
{
    action: 'rtbcb_test_company_overview_enhanced',
    nonce: '{nonce_value}',
    company_name: 'string',
    industry: 'string',
    company_size: 'string',
    use_rag: 'boolean'
}
```

### Run LLM Performance Test

**Endpoint:** `rtbcb_run_llm_test`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_llm_testing`

Runs comprehensive LLM performance and functionality tests.

#### Request Parameters
```javascript
{
    action: 'rtbcb_run_llm_test',
    nonce: '{nonce_value}',
    modelIds: ['model1', 'model2'],
    testPrompts: ['prompt1', 'prompt2'],
    iterations: 'number',
    include_performance_metrics: 'boolean'
}
```

## RAG (Retrieval-Augmented Generation) API

### Test RAG System

**Endpoint:** `rtbcb_run_rag_test`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_rag_testing`

Tests the RAG (Retrieval-Augmented Generation) system for context-aware responses.

#### Request Parameters
```javascript
{
    action: 'rtbcb_run_rag_test',
    nonce: '{nonce_value}',
    queries: ['query1', 'query2'],
    topK: 'number',
    includeMetadata: 'boolean',
    testReranking: 'boolean'
}
```

#### Response Format
```json
{
    "success": true,
    "data": {
        "rag_results": [
            {
                "query": "string",
                "retrieved_documents": [
                    {
                        "content": "string",
                        "score": "float",
                        "metadata": { ... }
                    }
                ],
                "response": "string",
                "performance": {
                    "retrieval_time": "float",
                    "generation_time": "float",
                    "total_time": "float"
                }
            }
        ],
        "index_stats": {
            "total_documents": "number",
            "index_size": "string",
            "last_updated": "unix_timestamp"
        }
    }
}
```

## Export & Data Management API

### Export Results

**Endpoint:** `rtbcb_export_results`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_export_results`

Exports generated business case results in various formats.

#### Request Parameters
```javascript
{
    action: 'rtbcb_export_results',
    nonce: '{nonce_value}',
    export_format: 'pdf|docx|json|csv',
    include_charts: 'boolean',
    include_raw_data: 'boolean',
    session_id: 'string'
}
```

#### Response Format
```json
{
    "success": true,
    "data": {
        "download_url": "string",
        "file_size": "number",
        "expires_at": "unix_timestamp",
        "format": "string"
    }
}
```

### Calculate ROI Test

**Endpoint:** `rtbcb_calculate_roi_test`  
**Method:** POST  
**Capability Required:** `manage_options`  
**Nonce Action:** `rtbcb_roi_calculator_test`

Tests ROI calculation functionality with provided inputs.

#### Request Parameters
```javascript
{
    action: 'rtbcb_calculate_roi_test',
    nonce: '{nonce_value}',
    roi_data: {
        annual_volume: 'number',
        avg_transaction_size: 'number',
        manual_processing_time: 'number',
        error_rate: 'number',
        staff_hourly_rate: 'number',
        // ... additional ROI parameters
    },
    scenario: 'conservative|moderate|aggressive'
}
```

## Error Handling

All endpoints follow consistent error handling patterns:

### Error Response Format
```json
{
    "success": false,
    "data": {
        "error_code": "string",
        "error_message": "string",
        "error_details": { ... },
        "timestamp": "unix_timestamp",
        "request_id": "string"
    }
}
```

### Common Error Codes
- `security_check_failed`: Nonce verification failed
- `insufficient_permissions`: User lacks required capabilities
- `invalid_input`: Request parameters are invalid
- `api_error`: External API (OpenAI) error
- `rate_limit_exceeded`: Too many requests
- `server_error`: Internal server error

### Error Logging
All errors are logged using the centralized error handling system with context:
```php
RTBCB_Error_Handler::log_error($message, $level, $context, $source);
```

## Rate Limiting

API endpoints implement rate limiting to prevent abuse:

- **Standard endpoints**: 100 requests per hour per user
- **LLM testing endpoints**: 20 requests per hour per user
- **Export endpoints**: 10 requests per hour per user

Rate limiting headers in responses:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1609459200
```

## Response Formats

### Success Response
```json
{
    "success": true,
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "data": {
        "error_code": "string",
        "error_message": "string"
    }
}
```

### Performance Metadata
Many responses include performance metadata:
```json
{
    "performance": {
        "execution_time": "float",
        "memory_usage": "number",
        "api_calls": "number",
        "cache_hits": "number"
    }
}
```

## Usage Examples

### JavaScript (Frontend)
```javascript
// Generate business case
jQuery.ajax({
    url: rtbcbAjax.ajax_url,
    type: 'POST',
    data: {
        action: 'rtbcb_generate_case',
        nonce: rtbcbAjax.nonce,
        company_name: 'Example Corp',
        company_size: 'medium',
        complexity: 'advanced',
        focus_areas: ['cash_management', 'risk_management']
    },
    success: function(response) {
        if (response.success) {
            console.log('Business case generated:', response.data);
        } else {
            console.error('Error:', response.data.error_message);
        }
    }
});
```

### PHP (Backend)
```php
// Health check
$response = wp_remote_post(admin_url('admin-ajax.php'), [
    'body' => [
        'action' => 'rtbcb_api_health_ping',
        'nonce' => wp_create_nonce('rtbcb_health_ping')
    ]
]);

$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);
```

## Caching

Many endpoints support response caching:
- **LLM responses**: Cached for 1 hour by default
- **ROI calculations**: Cached for 30 minutes
- **RAG results**: Cached for 2 hours

Cache behavior can be controlled via options:
- `rtbcb_llm_caching_enabled`: Enable/disable LLM caching
- `rtbcb_llm_cache_duration`: Cache duration in seconds

## Security Considerations

1. **Always verify nonces** before processing requests
2. **Check user capabilities** for sensitive operations
3. **Sanitize all inputs** using WordPress functions
4. **Escape all outputs** to prevent XSS
5. **Use HTTPS** for all API communications
6. **Log security events** for audit purposes

## Performance Monitoring

All API calls are automatically monitored for performance:
- Response times are logged
- Memory usage is tracked
- Cache hit rates are measured
- Error rates are monitored

Access performance data via:
```php
$summary = RTBCB_Performance_Monitor::get_performance_summary();
```