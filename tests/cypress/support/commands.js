// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

// WordPress login command optimized for WordPress.com
Cypress.Commands.add('wpLogin', (username, password) => {
  cy.session([username, password], () => {
    cy.visit('/wp-admin')
    cy.get('input[name="log"]').type(username)
    cy.get('input[name="pwd"]').type(password)
    cy.get('input[name="wp-submit"]').click()
    cy.url().should('include', '/wp-admin')
  })
})

// WordPress logout command
Cypress.Commands.add('wpLogout', () => {
  cy.visit('/wp-admin/profile.php')
  cy.get('a').contains('Log Out').click()
})

// Plugin activation command for WordPress.com
Cypress.Commands.add('activatePlugin', (pluginSlug) => {
  cy.visit('/wp-admin/plugins.php')
  cy.get(`tr[data-slug="${pluginSlug}"]`).within(() => {
    cy.get('.activate a').click()
  })
  cy.get('.notice-success').should('be.visible')
})

// Plugin deactivation command for WordPress.com
Cypress.Commands.add('deactivatePlugin', (pluginSlug) => {
  cy.visit('/wp-admin/plugins.php')
  cy.get(`tr[data-slug="${pluginSlug}"]`).within(() => {
    cy.get('.deactivate a').click()
  })
  cy.get('.notice-success').should('be.visible')
})

// WordPress.com specific: Check plugin compatibility
Cypress.Commands.add('checkWordPressComCompatibility', () => {
  cy.visit('/wp-admin/plugins.php')
  cy.get('tr[data-slug="real-treasury-business-case-builder"]').should('not.have.class', 'plugin-update-tr')
  cy.get('.notice-error').should('not.exist')
})

// WordPress admin navigation
Cypress.Commands.add('navigateToPlugin', (menuText) => {
  cy.visit('/wp-admin')
  cy.get('#adminmenu').contains(menuText).click()
})

// WordPress.com API testing command
Cypress.Commands.add('testWordPressComAPI', (endpoint, options = {}) => {
  const apiUrl = Cypress.env('wpcomApiUrl')
  cy.request({
    url: `${apiUrl}${endpoint}`,
    ...options
  }).then((response) => {
    expect(response.status).to.equal(200)
    return response
  })
})

// WordPress.com CDN testing
Cypress.Commands.add('checkCDNAssets', () => {
  cy.visit('/')
  cy.get('script, link[rel="stylesheet"], img').each(($el) => {
    const src = $el.attr('src') || $el.attr('href')
    if (src) {
      let url;
      try {
        url = new URL(src, window.location.origin);
      } catch (e) {
        // Skip invalid URLs
        return;
      }
      // Check if the hostname is exactly 'wp.com' or ends with '.wp.com'
      if (url.hostname === 'wp.com' || url.hostname.endsWith('.wp.com')) {
        cy.request(src).its('status').should('equal', 200)
      }
    }
  })
})

// Real Treasury plugin specific commands
Cypress.Commands.add('accessRealTreasuryDashboard', () => {
  cy.navigateToPlugin('Real Treasury')
  cy.url().should('include', 'admin.php?page=rtbcb-dashboard')
})

Cypress.Commands.add('configureOpenAIAPI', (apiKey) => {
  cy.accessRealTreasuryDashboard()
  cy.get('input[name="rtbcb_openai_api_key"]').clear().type(apiKey)
  cy.get('input[type="submit"]').click()
  cy.get('.notice-success').should('be.visible')
})

Cypress.Commands.add('runBusinessCaseBuilder', (companyData) => {
  cy.visit('/rtbcb-wizard')
  cy.get('input[name="company_name"]').type(companyData.name)
  cy.get('input[name="company_size"]').type(companyData.size)
  cy.get('textarea[name="pain_points"]').type(companyData.painPoints)
  cy.get('button[type="submit"]').click()
  cy.get('.results-container').should('be.visible')
})

// WordPress.com hosting specific checks
Cypress.Commands.add('checkWordPressComLimitations', () => {
  // Check that external HTTP requests are handled properly
  cy.window().then((win) => {
    expect(win.fetch).to.exist
  })
  
  // Check for WordPress.com specific headers
  cy.request('/').then((response) => {
    expect(response.headers).to.have.property('server')
  })
})

// Performance testing for WordPress.com
Cypress.Commands.add('checkPageLoadPerformance', (maxLoadTime = 3000) => {
  cy.visit('/', {
    onBeforeLoad: (win) => {
      win.performance.mark('start')
    },
    onLoad: (win) => {
      win.performance.mark('end')
      win.performance.measure('pageLoad', 'start', 'end')
      const measure = win.performance.getEntriesByName('pageLoad')[0]
      expect(measure.duration).to.be.lessThan(maxLoadTime)
    }
  })
})