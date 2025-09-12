const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay"><div class="rtbcb-modal-container"><div class="rtbcb-modal"><button id="rtbcb-close-btn"></button><form id="rtbcbForm"><div class="rtbcb-wizard-step active" data-step="1"></div><div class="rtbcb-wizard-step" data-step="2"></div></form></div></div></div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost/rtbcb', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

const React = require('react');
const ReactDOM = require('react-dom');

global.wp = window.wp = {
element: { ...React, render: ReactDOM.render },
};

const componentCode = fs.readFileSync('public/js/rtbcb-wizard-component.js', 'utf8');
vm.runInThisContext(componentCode);

const wizardCode = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
vm.runInThisContext(wizardCode);

(async () => {
document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
await new Promise((r) => setTimeout(r, 0));
await new Promise((r) => setTimeout(r, 0));

const overlay = document.getElementById('rtbcbModalOverlay');
assert.ok(overlay.classList.contains('active'), 'Overlay should be active on dedicated page');
assert.strictEqual(window.businessCaseBuilder.currentStep, 1, 'Wizard should start at step 1');

overlay.dispatchEvent(new dom.window.MouseEvent('click', { bubbles: true }));
await new Promise((r) => setTimeout(r, 0));
assert.ok(!overlay.classList.contains('active'), 'Overlay should close when clicking overlay on step 1');

overlay.classList.add('active');
window.businessCaseBuilder.currentStep = 2;
overlay.dispatchEvent(new dom.window.MouseEvent('click', { bubbles: true }));
await new Promise((r) => setTimeout(r, 0));
assert.ok(overlay.classList.contains('active'), 'Overlay should remain open when not on step 1');

console.log('Overlay click close test passed.');
process.exit(0);
})().catch((err) => {
console.error(err);
process.exit(1);
});
