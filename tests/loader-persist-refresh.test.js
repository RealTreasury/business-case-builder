const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay"><div class="rtbcb-modal-container"></div><div id="rtbcb-progress-container" style="display:none"></div><form id="rtbcbForm"></form></div>
</body></html>`;
const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

global.rtbcb_ajax = { ajax_url: 'http://example.com', nonce: 'test' };

const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(code);

window.sessionStorage.setItem('rtbcbJobId', 'job-123');

BusinessCaseBuilder.prototype.startProgressiveLoading = function(){};
BusinessCaseBuilder.prototype.pollJob = function(){};

const builder = new BusinessCaseBuilder();

const overlay = document.getElementById('rtbcbModalOverlay');
const progress = document.getElementById('rtbcb-progress-container');

assert.ok(overlay.classList.contains('active'), 'Overlay not active after refresh');
assert.strictEqual(progress.style.display, 'flex', 'Loader not visible after refresh');
assert.strictEqual(builder.activeJobId, 'job-123', 'Active job id not restored');

console.log('Loader restoration test passed.');
