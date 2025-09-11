const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const { JSDOM } = require('jsdom');

require('./jsdom-setup');

const html = `<!DOCTYPE html><html><body>
<button id="rtbcb-open-btn"></button>
<div id="rtbcbModalOverlay"><div class="rtbcb-modal-container"><div class="rtbcb-modal"><button id="rtbcb-close-btn"></button><form id="rtbcbForm"><div class="rtbcb-wizard-step active" data-step="1"></div><div class="rtbcb-wizard-step" data-step="2"></div></form></div></div></div>
</body></html>`;

const dom = new JSDOM(html, { url: 'http://localhost', runScripts: 'outside-only' });

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
  const openBtn = document.getElementById('rtbcb-open-btn');

  openBtn.click();
  await new Promise((r) => setTimeout(r, 0));
  assert.ok(overlay.classList.contains('active'), 'Overlay should be active after opening');

  overlay.dispatchEvent(new dom.window.MouseEvent('click', { bubbles: true }));
  await new Promise((r) => setTimeout(r, 0));
  assert.ok(!overlay.classList.contains('active'), 'Overlay should close when clicking overlay on step 1');

  openBtn.click();
  await new Promise((r) => setTimeout(r, 0));
  assert.ok(overlay.classList.contains('active'), 'Overlay should reopen');

  window.businessCaseBuilder.currentStep = 2;
  overlay.dispatchEvent(new dom.window.MouseEvent('click', { bubbles: true }));
  await new Promise((r) => setTimeout(r, 0));
  assert.ok(overlay.classList.contains('active'), 'Overlay should remain open when not on step 1');

  console.log('Overlay click close test passed.');
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
