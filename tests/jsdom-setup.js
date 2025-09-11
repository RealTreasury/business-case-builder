const { JSDOM } = require('jsdom');

const dom = new JSDOM('<!doctype html><html><body></body></html>');

global.window = dom.window;
global.document = dom.window.document;
global.HTMLElement = dom.window.HTMLElement;
global.Node = dom.window.Node;
global.Event = dom.window.Event;
global.navigator = dom.window.navigator;
global.HTMLElement.prototype.scrollIntoView = function() {};

global.TEST_ENV = true;
global.alert = () => {};

// Stub modal close handler used by wizard scripts
global.window.closeBusinessCaseModal = () => {};

// Expose common DOM helpers on the global object for tests
global.createElement = (...args) => global.document.createElement(...args);
global.appendChild = (...args) => global.document.body.appendChild(...args);
global.getElementById = (...args) => global.document.getElementById(...args);
global.querySelector = (...args) => global.document.querySelector(...args);
global.querySelectorAll = (...args) => global.document.querySelectorAll(...args);
