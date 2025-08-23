const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const { execSync } = require('child_process');

(async () => {
    // Client-side test for generateProfessionalReport
    let capturedBody;
    global.rtbcbReport = { report_model: 'gpt-5-test', api_key: 'test' };
    global.fetch = async (url, options) => {
        capturedBody = JSON.parse(options.body);
        return { ok: true, json: async () => ({ output_text: '<html></html>' }) };
    };
    global.document = { getElementById: () => null };
    global.DOMPurify = { sanitize: (html) => html };
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);
    await generateProfessionalReport('context');

    assert.strictEqual(capturedBody.temperature, 0.7, 'Client request body should include temperature 0.7');
    assert.deepStrictEqual(capturedBody.text, { verbosity: 'medium' }, 'Client request body should include text settings');
    assert.strictEqual(capturedBody.max_tokens, 4000, 'Client request body should include max_tokens 4000');

    // Server-side test for call_openai
    const serverBody = JSON.parse(execSync('php tests/helpers/capture-call-openai-body.php 2>/dev/null', { encoding: 'utf8' }));
    assert.strictEqual(serverBody.temperature, 0.7, 'Server request body should include temperature 0.7');
    assert.deepStrictEqual(serverBody.text, { verbosity: 'medium' }, 'Server request body should include text settings');
    assert.strictEqual(serverBody.max_tokens, 4000, 'Server request body should include max_tokens 4000');

    console.log('GPT-5 temperature test passed.');
})();
