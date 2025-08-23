const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const { execSync } = require('child_process');

(async () => {
    // Server-side test for call_openai
    const serverOutput = execSync('php tests/helpers/capture-call-openai-body.php', { encoding: 'utf8' });
    const serverBody = JSON.parse(serverOutput.trim());
    assert.ok(!('temperature' in serverBody), 'Server request body should not include temperature');

    // Client-side test for generateProfessionalReport
    let capturedBody;
    global.rtbcbReport = { report_model: 'gpt-5-test', api_key: 'test' };
    global.fetch = async (url, options) => {
        capturedBody = JSON.parse(options.body);
        return { ok: true, json: async () => ({ choices: [{ message: { content: '<html></html>' } }] }) };
    };
    global.document = { getElementById: () => null };
    global.DOMPurify = { sanitize: (html) => html };
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);
    await generateProfessionalReport('context');
    assert.ok(!('temperature' in capturedBody), 'Client request body should not include temperature');

    console.log('GPT-5 temperature test passed.');
})();
