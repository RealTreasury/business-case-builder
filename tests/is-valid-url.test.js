const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

let code = fs.readFileSync('public/js/rtbcb.js', 'utf8');
code = code.replace('export async function rtbcbStreamAnalysis', 'async function rtbcbStreamAnalysis');
vm.runInThisContext(code);

assert.strictEqual(isValidUrl('https://example.com'), true);
assert.strictEqual(isValidUrl('http://example.com'), true);
assert.strictEqual(isValidUrl('ftp://example.com'), false);
assert.strictEqual(isValidUrl(''), false);
assert.strictEqual(isValidUrl('/wp-admin/admin-ajax.php'), true);

console.log('isValidUrl tests passed.');
