const fs = require('fs');
const vm = require('vm');
const assert = require('assert');

require('./jsdom-setup');

const appended = [];
const reportContainer = {
    innerHTML: '',
    textContent: '',
    appendChild: (el) => appended.push(el)
};
const loading = { style: { display: '' } };
const error = { style: { display: '', }, textContent: '' };

global.document = {
    getElementById: (id) => {
        if (id === 'loading') return loading;
        if (id === 'error') return error;
        if (id === 'report-container') return reportContainer;
        return null;
    },
    createElement: (tag) => {
        if (tag === 'button') {
            return { textContent: '', className: '', onclick: null, click() { this.onclick && this.onclick(); } };
        }
        if (tag === 'iframe') {
            return { style: {}, set srcdoc(v) { this._srcdoc = v; } };
        }
    }
};

let printCalled = false;
global.window = {
    open: () => {
        const win = {
            document: { documentElement: { innerHTML: '' } },
            focus: () => {},
            print: () => {
                printCalled = true;
            },
            set onload(fn) {
                fn();
            }
        };
        return win;
    }
};

const code = fs.readFileSync('public/js/rtbcb-report.js', 'utf8');
vm.runInThisContext(code);

global.generateProfessionalReport = async () => '<!DOCTYPE html><html><body>Report</body></html>';

(async () => {
    await generateAndDisplayReport({});
    const btn = appended.find(el => el.textContent === 'Export to PDF');
    assert(btn, 'Export button not found');
    btn.click();
    assert.strictEqual(printCalled, true, 'window.print not called');
    console.log('export-to-pdf-button.test.js passed');
})().catch(err => { console.error(err); process.exit(1); });

