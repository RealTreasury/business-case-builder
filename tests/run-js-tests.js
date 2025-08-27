#!/usr/bin/env node

/**
 * JavaScript test runner for Real Treasury Business Case Builder
 * 
 * This script runs all existing JavaScript tests in a Node.js environment.
 */

const fs = require('fs');
const path = require('path');

// Test files to run
const testFiles = [
    'handle-submit-error.test.js',
    'render-results-no-narrative.test.js',
    'handle-submit-success.test.js',
    'handle-server-error-display.test.js',
    'temperature-model.test.js',
    'dashboard-api-key-button.test.js',
    'jquery-noconflict.test.js'
];

const testsDir = path.join(__dirname, '..', 'tests');

let passedTests = 0;
let failedTests = 0;
const results = [];

console.log('Running JavaScript Tests...');
console.log('==========================');

for (const testFile of testFiles) {
    const testPath = path.join(testsDir, testFile);
    
    if (!fs.existsSync(testPath)) {
        console.log(`❌ ${testFile} - File not found`);
        failedTests++;
        results.push({ file: testFile, status: 'failed', error: 'File not found' });
        continue;
    }
    
    try {
        console.log(`Running ${testFile}...`);
        
        // Run the test file
        require(testPath);
        
        console.log(`✅ ${testFile} - Passed`);
        passedTests++;
        results.push({ file: testFile, status: 'passed' });
        
    } catch (error) {
        console.log(`❌ ${testFile} - Failed: ${error.message}`);
        failedTests++;
        results.push({ file: testFile, status: 'failed', error: error.message });
    }
}

console.log('\n==========================');
console.log('JavaScript Test Results:');
console.log(`Passed: ${passedTests}`);
console.log(`Failed: ${failedTests}`);
console.log(`Total: ${passedTests + failedTests}`);

if (failedTests > 0) {
    console.log('\nFailed tests:');
    results.filter(r => r.status === 'failed').forEach(result => {
        console.log(`- ${result.file}: ${result.error}`);
    });
    process.exit(1);
} else {
    console.log('\n🎉 All JavaScript tests passed!');
    process.exit(0);
}