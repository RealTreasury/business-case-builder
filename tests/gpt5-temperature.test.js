const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { execSync } = require('child_process');

// Run server-side check
execSync('php tests/gpt5-temperature-server.php', { stdio: 'inherit' });

// Prepare client-side environment
let capturedBody;
global.rtbcbReport = { api_key: 'test', report_model: 'gpt-5' };
global.document = { getElementById: () => null };
global.fetch = async (url, options) => {
    capturedBody = JSON.parse(options.body);
    return { ok: true, json: async () => ({ choices: [{ message: { content: '<p>ok</p>' } }] }) };
};

const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
vm.runInThisContext(code);

(async () => {
    await generateProfessionalReport({});
    assert.ok(!('temperature' in capturedBody));
    console.log('Client-side temperature test passed.');
})();
