const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<a id="rtbcb-open-btn" href="/rtbcb/?rtbcb_wizard=1"></a>
<div id="rtbcbModalOverlay"><form id="rtbcbForm"></form></div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost/business-case-builder/', runScripts: 'outside-only' });

global.window = dom.window;
global.document = dom.window.document;
global.FormData = dom.window.FormData;
global.navigator = dom.window.navigator;

const React = require('react');
const ReactDOM = require('react-dom');

global.wp = window.wp = {
element: { ...React, render: ReactDOM.render },
};

const code = fs.readFileSync('public/js/rtbcb-wizard-component.js', 'utf8');
vm.runInThisContext(code);

(async () => {
document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
await new Promise((r) => setTimeout(r, 0));
await new Promise((r) => setTimeout(r, 0));
const openBtn = document.getElementById('rtbcb-open-btn');
openBtn.click();
await new Promise((r) => setTimeout(r, 0));
const overlay = document.getElementById('rtbcbModalOverlay');
assert.ok(overlay.classList.contains('active'), 'Overlay should be active after clicking open button');
console.log('Wizard open button test passed.');
process.exit(0);
})().catch((err) => {
console.error(err);
process.exit(1);
});
