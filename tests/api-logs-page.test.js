const { execSync } = require('child_process');
const assert = require('assert');
const { JSDOM } = require('jsdom');

const php = `<?php
define('ABSPATH', getcwd() . '/');
function current_user_can($cap) { return true; }
function esc_html__($t, $d = null) { return $t; }
function esc_html_e($t, $d = null) { echo $t; }
function esc_html($t) { return $t; }
function esc_attr($t) { return $t; }
function esc_js($t) { return $t; }
function wp_trim_words($text, $num_words = 55, $more = null) { return $text; }
function __($t, $d = null) { return $t; }
$nonce = 'abc';
$logs = [
    [
        'id' => 1,
        'lead_id' => 0,
        'user_email' => 'test@example.com',
        'company_name' => 'Test Co',
        'request_json' => '{}',
        'response_json' => '{}',
        'total_tokens' => 42,
        'prompt_tokens' => 40,
        'completion_tokens' => 2,
        'is_truncated' => 0,
        'corruption_detected' => 0,
        'created_at' => '2024-01-01 00:00:00'
    ]
];
include 'admin/api-logs-page.php';
?>`;

const output = execSync('php', { input: php }).toString();
const dom = new JSDOM(output);
const promptCell = dom.window.document.querySelector('#rtbcb-api-logs-table tbody tr td:nth-child(6)');
const completionCell = dom.window.document.querySelector('#rtbcb-api-logs-table tbody tr td:nth-child(7)');
const totalCell = dom.window.document.querySelector('#rtbcb-api-logs-table tbody tr td:nth-child(8)');
assert.ok(promptCell && completionCell && totalCell);
assert.strictEqual(promptCell.textContent.trim(), '40');
assert.strictEqual(completionCell.textContent.trim(), '2');
assert.strictEqual(totalCell.textContent.trim(), '42');
console.log('api-logs-page.test.js passed');
