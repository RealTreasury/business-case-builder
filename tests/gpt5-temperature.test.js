const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const { execSync } = require('child_process');

(async () => {
    // Server-side test for call_openai
    const serverOutput = execSync('php tests/helpers/capture-call-openai-body.php', { encoding: 'utf8' });
    const serverBody = JSON.parse(serverOutput.trim());
    assert.ok('temperature' in serverBody, 'Server request body should include temperature');
    assert.ok('reasoning' in serverBody, 'Server request body should include reasoning');
    assert.ok('max_tokens' in serverBody, 'Server request body should include max_tokens');
    assert.ok(
        serverBody.input &&
            Array.isArray(serverBody.input) &&
            serverBody.input.some(part => part.content && part.content.some(c => 'text' in c)),
        'Server request body should include text content'
    );
    assert.ok(serverBody.reasoning && 'effort' in serverBody.reasoning, 'Server reasoning should include effort');

    // Client-side test for generateProfessionalReport
    let capturedBody;
    global.rtbcbReport = { report_model: 'gpt-5-test', api_key: 'test' };
    global.fetch = async (url, options) => {
        capturedBody = JSON.parse(options.body);
        return { ok: true, json: async () => ({ choices: [{ message: { content: '<html></html>' } }] }) };
    };
    global.document = { getElementById: () => null };
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);
    await generateProfessionalReport('context');
    assert.ok('temperature' in capturedBody, 'Client request body should include temperature');
    assert.ok('reasoning' in capturedBody, 'Client request body should include reasoning');
    assert.ok('max_tokens' in capturedBody, 'Client request body should include max_tokens');
    assert.ok(
        capturedBody.input &&
            Array.isArray(capturedBody.input) &&
            capturedBody.input.some(part => part.content && part.content.some(c => 'text' in c)),
        'Client request body should include text content'
    );
    assert.ok(capturedBody.reasoning && 'effort' in capturedBody.reasoning, 'Client reasoning should include effort');

    console.log('GPT-5 temperature test passed.');
})();
