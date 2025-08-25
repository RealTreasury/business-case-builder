#!/usr/bin/env node

/**
 * Simple build script for creating WordPress.com compatible plugin ZIP
 * This version uses native Node.js modules only, no external dependencies
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const pluginName = 'real-treasury-business-case-builder';
const packageJson = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'package.json'), 'utf8'));
const version = packageJson.version;
const buildDir = path.join(__dirname, '..', 'build');
const zipName = `${pluginName}-${version}.zip`;

// Files and directories to exclude from the build
const excludePatterns = [
    'node_modules',
    'vendor',
    '.git',
    '.github',
    'tests',
    'cypress',
    'coverage',
    'bin',
    'scripts',
    'tmp',
    'build', // Important: exclude build directory itself
    '.wp-env.json',
    '.wp-env.override.json',
    'package.json',
    'package-lock.json',
    'composer.json',
    'composer.lock',
    'phpunit.xml.dist',
    'cypress.config.js',
    '.eslintrc.json',
    '.editorconfig',
    '.gitignore',
    'phpcs.xml.dist',
    'DEPLOYMENT.md'
];

function shouldExclude(file) {
    return excludePatterns.some(pattern => {
        return file.includes(pattern);
    });
}

function createBuildDirectory() {
    if (!fs.existsSync(buildDir)) {
        fs.mkdirSync(buildDir, { recursive: true });
        console.log(`Created build directory: ${buildDir}`);
    }
}

function validateWordPressComCompatibility() {
    console.log('Validating WordPress.com compatibility...');
    
    // Check main plugin file
    const mainFile = path.join(__dirname, '..', 'real-treasury-business-case-builder.php');
    if (!fs.existsSync(mainFile)) {
        throw new Error('Main plugin file not found');
    }
    
    const mainFileContent = fs.readFileSync(mainFile, 'utf8');
    
    // Check for required WordPress headers
    const requiredHeaders = [
        'Plugin Name:',
        'Description:',
        'Version:',
        'Author:'
    ];
    
    for (const header of requiredHeaders) {
        if (!mainFileContent.includes(header)) {
            throw new Error(`Missing required header: ${header}`);
        }
    }
    
    console.log('✅ WordPress.com compatibility check completed');
}

function createSimpleZip() {
    console.log(`Creating ZIP file: ${zipName}`);
    
    const zipPath = path.join(buildDir, zipName);
    const baseDir = path.join(__dirname, '..');
    
    // Create temporary directory for clean plugin structure
    const tempDir = path.join(buildDir, 'temp');
    const pluginDir = path.join(tempDir, pluginName);
    
    if (fs.existsSync(tempDir)) {
        execSync(`rm -rf "${tempDir}"`);
    }
    fs.mkdirSync(pluginDir, { recursive: true });
    
    // Copy files
    function copyFiles(sourceDir, targetDir) {
        const items = fs.readdirSync(sourceDir);
        
        for (const item of items) {
            const sourcePath = path.join(sourceDir, item);
            const relativePath = path.relative(baseDir, sourcePath);
            
            if (shouldExclude(relativePath)) {
                continue;
            }
            
            const targetPath = path.join(targetDir, item);
            const stat = fs.statSync(sourcePath);
            
            if (stat.isDirectory()) {
                fs.mkdirSync(targetPath, { recursive: true });
                copyFiles(sourcePath, targetPath);
            } else {
                fs.copyFileSync(sourcePath, targetPath);
                console.log(`Added: ${relativePath}`);
            }
        }
    }
    
    copyFiles(baseDir, pluginDir);
    
    // Create ZIP using system zip command
    process.chdir(tempDir);
    execSync(`zip -r "${zipPath}" "${pluginName}"`);
    
    // Cleanup
    execSync(`rm -rf "${tempDir}"`);
    
    const stats = fs.statSync(zipPath);
    const sizeInMB = (stats.size / 1024 / 1024).toFixed(2);
    console.log(`ZIP file created successfully: ${zipName} (${sizeInMB} MB)`);
    
    // WordPress.com size check
    if (stats.size > 50 * 1024 * 1024) { // 50MB limit
        console.warn('Warning: ZIP file is larger than 50MB. WordPress.com may reject large files.');
    }
    
    return zipPath;
}

async function build() {
    try {
        console.log(`Building ${pluginName} v${version} for WordPress.com...`);
        console.log('==========================================');
        
        validateWordPressComCompatibility();
        createBuildDirectory();
        const zipPath = createSimpleZip();
        
        console.log('==========================================');
        console.log('Build completed successfully!');
        console.log(`ZIP file ready for WordPress.com upload: ${zipPath}`);
        console.log('');
        console.log('WordPress.com Deployment Instructions:');
        console.log('1. Log in to your WordPress.com admin dashboard');
        console.log('2. Go to Plugins → Add New');
        console.log('3. Click "Upload Plugin"');
        console.log(`4. Upload ${zipName}`);
        console.log('5. Activate the plugin');
        console.log('6. Configure the OpenAI API key in Real Treasury → Settings');
        
    } catch (error) {
        console.error('Build failed:', error.message);
        process.exit(1);
    }
}

if (require.main === module) {
    build();
}

module.exports = { build };