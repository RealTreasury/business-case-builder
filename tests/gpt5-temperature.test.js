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
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);
    await generateProfessionalReport('context');
    assert.strictEqual(capturedBody.temperature, 0.7, 'Client request body should include temperature 0.7');

    console.log('GPT-5 temperature test passed.');
})();
