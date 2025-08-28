const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const { execSync } = require('child_process');

function runTests() {
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);

    const capabilities = JSON.parse(execSync("php -r \"echo json_encode(include 'inc/model-capabilities.php');\"").toString());
    const unsupportedModels = [...capabilities.temperature.unsupported, 'gpt-5-mini'];
    const supportedModels = ['gpt-4o', 'gpt-4.1-preview'];

    global.FormData = class {
        constructor() { this.store = {}; }
        append(key, value) { this.store[key] = value; }
    };

    const models = [...unsupportedModels, ...supportedModels];

    models.forEach((model) => {
        let capturedBody;
        global.rtbcbReport = { report_model: model, model_capabilities: capabilities, ajax_url: 'https://example.com' };

        global.XMLHttpRequest = function() {
            this.open = function(method, url, async) {
                this.method = method;
                this.url = url;
                this.async = async;
            };
            this.send = function(formData) {
                capturedBody = JSON.parse(formData.store.body);
                this.status = 200;
                this.responseText = JSON.stringify({ output_text: '<html></html>' });
            };
        };

        global.document = { getElementById: () => null };
        global.DOMPurify = { sanitize: (html) => html };

        generateProfessionalReport('context');
        const shouldInclude = supportedModels.includes(model);

        assert.strictEqual(
            capturedBody.max_output_tokens,
            20000,
            'Client request body should include max_output_tokens 20000'
        );

        if (shouldInclude) {
            assert.strictEqual(
                capturedBody.temperature,
                0.7,
                `Client request body for ${model} should include temperature 0.7`
            );
        } else {
            assert.ok(
                !('temperature' in capturedBody),
                `Client request body for ${model} should not include temperature`
            );
        }

        const serverBody = JSON.parse(execSync('php tests/helpers/capture-call-openai-body.php 2>/dev/null', {
            encoding: 'utf8',
            env: { ...process.env, RTBCB_TEST_MODEL: model }
        }));

        assert.strictEqual(
            serverBody.max_output_tokens,
            256,
            'Server request body should enforce minimum max_output_tokens of 256'
        );

        if (shouldInclude) {
            assert.strictEqual(
                serverBody.temperature,
                0.7,
                `Server request body for ${model} should include temperature 0.7`
            );
        } else {
            assert.ok(
                !('temperature' in serverBody),
                `Server request body for ${model} should not include temperature`
            );
        }
    });
}
runTests();
console.log('Model temperature test passed.');

