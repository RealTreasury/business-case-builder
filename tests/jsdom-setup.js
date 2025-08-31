const { JSDOM } = require('jsdom');

const dom = new JSDOM('<!doctype html><html><body></body></html>');
let doc = dom.window.document;

Object.defineProperty(global, 'document', {
  configurable: true,
  get() {
    return doc;
  },
  set(v) {
    doc = Object.setPrototypeOf(v, dom.window.document);
  }
});

global.window = dom.window;
global.HTMLElement = dom.window.HTMLElement;
global.Node = dom.window.Node;
global.Event = dom.window.Event;
global.navigator = dom.window.navigator;
