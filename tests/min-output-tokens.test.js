const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

require('./jsdom-setup');

async function runTests() {
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);

    global.FormData = class { constructor() { this.store = {}; } append(k, v) { this.store[k] = v; } };
    global.document = { getElementById: () => null };
    global.DOMPurify = { sanitize: (html) => html };

    let capturedBody;
    global.rtbcbReport = {
        report_model: 'gpt-5-mini',
        model_capabilities: {},
        ajax_url: 'https://example.com',
        template_url: 'template.html',
        min_output_tokens: 4000,
        max_output_tokens: 8000
    };

    global.fetch = (url, options) => {
        if (!options) {
            return Promise.resolve({ ok: true, text: () => Promise.resolve('<html></html>') });
        }
        capturedBody = JSON.parse(options.body.store.body);
        return Promise.resolve({
            ok: true,
            body: { getReader: () => ({ read: () => Promise.resolve({ done: true }) }) }
        });
    };

    await generateProfessionalReport('context');
    assert.strictEqual(capturedBody.max_output_tokens, 4000, 'Should apply min_output_tokens');

    global.rtbcbReport = {
        report_model: 'gpt-5-mini',
        model_capabilities: {},
        ajax_url: 'https://example.com',
        template_url: 'template.html',
        min_output_tokens: 5000,
        max_output_tokens: 2500
    };

    await generateProfessionalReport('context');
    assert.strictEqual(capturedBody.max_output_tokens, 2500, 'Should not exceed max_output_tokens');

    global.rtbcbReport = {
        report_model: 'gpt-5-mini',
        model_capabilities: {},
        ajax_url: 'https://example.com',
        template_url: 'template.html',
        min_output_tokens: 1,
        max_output_tokens: 8000
    };

    await generateProfessionalReport('context');
    assert.strictEqual(capturedBody.max_output_tokens, 3000, 'Should include buffer in token estimate');
}

runTests().then(() => console.log('Min output tokens test passed.'));
