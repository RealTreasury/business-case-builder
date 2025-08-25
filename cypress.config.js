const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8888',
    supportFile: 'tests/cypress/support/e2e.js',
    specPattern: 'tests/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    videosFolder: 'tests/cypress/videos',
    screenshotsFolder: 'tests/cypress/screenshots',
    fixturesFolder: 'tests/cypress/fixtures',
    downloadsFolder: 'tests/cypress/downloads',
    viewportWidth: 1280,
    viewportHeight: 720,
    video: true,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
    responseTimeout: 10000,
    pageLoadTimeout: 30000,
    experimentalStudio: true,
    env: {
      // WordPress admin credentials for testing
      adminUsername: 'admin',
      adminPassword: 'password',
      // WordPress.com specific testing
      wpcomApiUrl: 'https://public-api.wordpress.com',
    },
    setupNodeEvents(on, config) {
      // implement node event listeners here
      
      // WordPress.com specific task for testing remote sites
      on('task', {
        // Custom task for WordPress.com API testing
        testWordPressComAPI(options) {
          return new Promise((resolve) => {
            // Mock implementation for WordPress.com API testing
            resolve({ success: true, message: 'WordPress.com API test passed' });
          });
        },
        
        // Custom task for plugin activation on WordPress.com
        activatePluginOnWordPressCom(options) {
          return new Promise((resolve) => {
            // Mock implementation for plugin activation
            resolve({ success: true, message: 'Plugin activated on WordPress.com' });
          });
        }
      });

      return config;
    },
  },
  
  component: {
    devServer: {
      framework: 'create-react-app',
      bundler: 'webpack',
    },
    supportFile: 'tests/cypress/support/component.js',
    specPattern: 'tests/cypress/component/**/*.cy.{js,jsx,ts,tsx}',
    indexHtmlFile: 'tests/cypress/support/component-index.html',
  },
  
  // WordPress.com specific configuration
  retries: {
    runMode: 2,
    openMode: 0
  },
  
  // Optimized for WordPress.com testing
  chromeWebSecurity: false,
  
  // WordPress environment detection
  env: {
    coverage: false,
    codeCoverage: {
      exclude: ['cypress/**/*', 'tests/**/*', 'vendor/**/*', 'node_modules/**/*']
    }
  }
})