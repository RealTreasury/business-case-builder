const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const { execSync } = require('child_process');

(async () => {
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);

    const unsupportedModels = ['gpt-4.1', 'gpt-4.1-mini', 'gpt-5'];
    const supportedModels = ['gpt-4o', 'gpt-4.1-preview'];

    for (const model of [...unsupportedModels, ...supportedModels]) {
        let capturedBody;
        global.rtbcbReport = { report_model: model, api_key: 'test' };
        global.fetch = async (url, options) => {
            capturedBody = JSON.parse(options.body);
            return { ok: true, json: async () => ({ output_text: '<html></html>' }) };
        };
        global.document = { getElementById: () => null };
        global.DOMPurify = { sanitize: (html) => html };

        await generateProfessionalReport('context');

        const shouldInclude = supportedModels.includes(model);

        if (shouldInclude) {
            assert.strictEqual(capturedBody.temperature, 0.7, `Client request body for ${model} should include temperature 0.7`);
        } else {
            assert.ok(!('temperature' in capturedBody), `Client request body for ${model} should not include temperature`);
        }

        const serverBody = JSON.parse(execSync('php tests/helpers/capture-call-openai-body.php 2>/dev/null', {
            encoding: 'utf8',
            env: { ...process.env, RTBCB_TEST_MODEL: model }
        }));

        if (shouldInclude) {
            assert.strictEqual(serverBody.temperature, 0.7, `Server request body for ${model} should include temperature 0.7`);
        } else {
            assert.ok(!('temperature' in serverBody), `Server request body for ${model} should not include temperature`);
        }
    }

    console.log('Model temperature test passed.');
})();
