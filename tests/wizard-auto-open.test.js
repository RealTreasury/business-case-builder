const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<div id="rtbcbModalOverlay"><form id="rtbcbForm"></form></div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost/rtbcb/?rtbcb_wizard=1', runScripts: 'outside-only' });

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
    const overlay = document.getElementById('rtbcbModalOverlay');
    assert.ok(overlay.classList.contains('active'), 'Overlay should be active on load when rtbcb_wizard parameter is present');
    console.log('Wizard auto-open test passed.');
    process.exit(0);
})().catch((err) => {
    console.error(err);
    process.exit(1);
});
