const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

global.rtbcb_ajax = { ajax_url: 'https://example.com', nonce: 'test-nonce' };

class SimpleFormData {
    constructor(form) {
        this._data = [];
        if (form && form.fields) {
            for (const [key, value] of Object.entries(form.fields)) {
                this._data.push([key, value]);
            }
        }
    }
    append(key, value) {
        this._data.push([key, value]);
    }
    entries() {
        return this._data[Symbol.iterator]();
    }
    [Symbol.iterator]() {
        return this.entries();
    }
}

global.FormData = SimpleFormData;

  let receivedHeaders = null;
  let warnCalled = false;
  const originalWarn = console.warn;
  console.warn = () => { warnCalled = true; };

  global.fetch = function(url, options) {
      receivedHeaders = options.headers;
      const payload = {
          success: true,
          job_id: 'job-123'
      };
      const response = {
          ok: true,
          status: 200,
          json: async () => payload,
          text: async () => JSON.stringify(payload),
          headers: { get: () => 'application/json' },
          clone() { return this; }
      };
      return Promise.resolve(response);
  };

const form = {
    fields: {
        email: 'test@example.com',
        company_name: 'Test Co',
        company_size: '100',
        industry: 'Finance',
        hours_reconciliation: '1',
        hours_cash_positioning: '1',
        num_banks: '1',
        ftes: '1',
        business_objective: 'growth',
        implementation_timeline: '3 months',
        budget_range: '1000',
        'pain_points[]': 'manual'
    },
    querySelector: (selector) => selector === '[name="company_name"]' ? { value: 'Test Co' } : null,
    querySelectorAll: () => [],
    addEventListener: () => {},
    closest: () => ({ style: {} })
};

const formElem = document.createElement('form');
formElem.id = 'rtbcbForm';
Object.assign(formElem, form);
document.body.appendChild(formElem);

document.readyState = 'complete';
const modalOverlay = document.createElement('div');
modalOverlay.id = 'rtbcbModalOverlay';
document.body.appendChild(modalOverlay);
const progressContainer = document.createElement('div');
progressContainer.id = 'rtbcb-progress-container';
document.body.appendChild(progressContainer);

  const code = fs.readFileSync('public/js/rtbcb-wizard.js', 'utf8');
  vm.runInThisContext(code);

  const builder = new BusinessCaseBuilder();
  builder.form = form;
  builder.showProgress = () => {};
  builder.showResults = () => {};
  builder.showEnhancedError = () => {};
  builder.pollJob = () => { builder.handleSuccess({ report_html: '<div>Report</div>' }); };

 (async () => {
    await builder.handleSubmit();
    const injected = document.getElementById('rtbcb-results-enhanced');
    assert.ok(injected);
    assert.strictEqual(injected.innerHTML, '<div>Report</div>');
    assert.strictEqual(receivedHeaders['Accept'], 'application/json, text/html');
    assert.strictEqual(warnCalled, false);
    console.warn = originalWarn;
    console.log('Success path test passed.');
 })().catch(err => {
    console.error(err);
    process.exit(1);
});