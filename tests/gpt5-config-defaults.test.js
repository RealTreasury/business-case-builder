const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const { execSync } = require('child_process');

async function runTests() {
    const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
    vm.runInThisContext(code);

    const phpMin = parseInt(execSync("php -r \"function get_option(\\$n, \\$d=false){return \\$d;} define('ABSPATH', __DIR__); include 'inc/config.php'; echo rtbcb_get_gpt5_config()['min_output_tokens'];\"").toString(), 10);

    assert.strictEqual(
        RTBCB_GPT5_MIN_TOKENS,
        phpMin,
        'JS default min tokens should match PHP default'
    );
}

runTests().then(() => console.log('GPT-5 config defaults test passed.'));
