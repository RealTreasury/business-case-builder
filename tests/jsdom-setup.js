const { JSDOM } = require('jsdom');

const dom = new JSDOM('<!doctype html><html><body></body></html>', { url: 'https://example.org/' });

const currentWindow = dom.window;
const currentDocument = dom.window.document;

Object.defineProperty(global, 'window', {
    configurable: true,
    get() {
        return currentWindow;
    },
    set(value) {
        if (value && typeof value === 'object') {
            Object.entries(value).forEach(([key, val]) => {
                try {
                    currentWindow[key] = val;
                } catch (e) {
                    // ignore read-only properties
                }
            });
        }
    }
});

Object.defineProperty(global, 'document', {
    configurable: true,
    get() {
        return currentDocument;
    },
    set(value) {
        if (value && typeof value === 'object') {
            Object.entries(value).forEach(([key, val]) => {
                try {
                    currentDocument[key] = val;
                } catch (e) {
                    // ignore read-only properties like readyState
                }
            });
        }
    }
});

global.HTMLElement = currentWindow.HTMLElement;
global.Node = currentWindow.Node;
global.navigator = currentWindow.navigator;

Object.getOwnPropertyNames(currentWindow).forEach(prop => {
    if (typeof global[prop] === 'undefined') {
        global[prop] = currentWindow[prop];
    }
});
