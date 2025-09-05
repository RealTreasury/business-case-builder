const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

document.readyState = 'complete';

let intervalCalls = 0;
const originalSetInterval = global.setInterval;
const originalClearInterval = global.clearInterval;

global.setInterval = () => {
    intervalCalls++;
    return 1;
};

global.clearInterval = () => {};

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

global.setInterval = originalSetInterval;
global.clearInterval = originalClearInterval;

assert.strictEqual(intervalCalls, 0, 'setInterval should not be called when TEST_ENV and no form');
console.log('No interval with missing form test passed.');
