const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

require('./jsdom-setup');

// Load Chart.js and the date adapter
const chartJs = fs.readFileSync('public/js/chart.js', 'utf8');
vm.runInThisContext(chartJs);
global.Chart = window.Chart;

const adapterCode = fs.readFileSync('public/js/chartjs-adapter-date-fns.js', 'utf8');
vm.runInThisContext(adapterCode);

const AdapterCtor = global.Chart._adapters && global.Chart._adapters._date;
assert.ok(AdapterCtor, 'Date adapter not registered');
const dateAdapter = new AdapterCtor();
assert.strictEqual(typeof dateAdapter.formats, 'function');
assert.strictEqual(typeof dateAdapter.parse, 'function');
assert.strictEqual(typeof dateAdapter.format, 'function');

// Verify adapter methods do not throw and behave sensibly
assert.doesNotThrow(() => dateAdapter.formats());
const ts = dateAdapter.parse('2020-01-01T00:00:00Z');
assert.strictEqual(typeof ts, 'number');
assert.strictEqual(dateAdapter.format(ts, 'yyyy-MM-dd'), '2020-01-01');

console.log('chartjs-date-adapter test passed.');
