// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

// WordPress.com specific global configuration
Cypress.on('uncaught:exception', (err, runnable) => {
  // WordPress.com may have some expected console errors
  // Return false to prevent failing the test
  if (err.message.includes('ResizeObserver loop limit exceeded')) {
    return false
  }
  if (err.message.includes('Non-Error promise rejection captured')) {
    return false
  }
  return true
})

// WordPress.com API rate limiting
beforeEach(() => {
  // Add a small delay between tests to respect WordPress.com rate limits
  cy.wait(100)
})

// WordPress.com session management
before(() => {
  // Clear any existing WordPress.com sessions
  cy.clearCookies()
  cy.clearLocalStorage()
})