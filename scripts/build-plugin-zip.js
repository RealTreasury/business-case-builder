#!/usr/bin/env node

/**
 * Build script for creating WordPress.com compatible plugin ZIP
 * 
 * This script creates a production-ready ZIP file optimized for WordPress.com upload
 * by excluding development files and including only necessary plugin files.
 */

const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const pluginName = 'real-treasury-business-case-builder';
const version = require('../package.json').version;
const buildDir = path.join(__dirname, '..', 'build');
const zipName = `${pluginName}-${version}.zip`;
const zipPath = path.join(buildDir, zipName);

// Files and directories to exclude from the build
const excludePatterns = [
    'node_modules/**',
    'vendor/**',
    '.git/**',
    '.github/**',
    'tests/**',
    'cypress/**',
    'coverage/**',
    'bin/**',
    'scripts/**',
    'tmp/**',
    '.wp-env.json',
    '.wp-env.override.json',
    'package.json',
    'package-lock.json',
    'composer.json',
    'composer.lock',
    'phpunit.xml.dist',
    'cypress.config.js',
    '.eslintrc.js',
    '.editorconfig',
    '.gitignore',
    '*.log',
    '*.cache',
    '.DS_Store',
    'Thumbs.db',
    '*.zip'
];

// Files that must be included for WordPress.com
const includeFiles = [
    'real-treasury-business-case-builder.php',
    'readme.txt',
    'inc/**/*.php',
    'admin/**/*.php',
    'admin/**/*.css',
    'admin/**/*.js',
    'public/**/*.php',
    'public/**/*.css',
    'public/**/*.js',
    'templates/**/*.php',
    'languages/**/*',
    '.htaccess'
];

function shouldExclude(file) {
    return excludePatterns.some(pattern => {
        const regex = new RegExp(pattern.replace(/\*\*/g, '.*').replace(/\*/g, '[^/]*'));
        return regex.test(file);
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
    
    // Check for WordPress.com incompatible functions
    const incompatibleFunctions = [
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'file_get_contents', // Should use wp_remote_get instead
        'curl_init' // Should use wp_remote_* functions
    ];
    
    // This is a basic check - in a real implementation you'd want more sophisticated analysis
    for (const func of incompatibleFunctions) {
        if (mainFileContent.includes(`${func}(`)) {
            console.warn(`Warning: Found potentially incompatible function: ${func}`);
        }
    }
    
    console.log('WordPress.com compatibility check completed');
}

function optimizeForWordPressCom() {
    console.log('Optimizing for WordPress.com...');
    
    // Check that external HTTP requests use WordPress HTTP API
    const incDir = path.join(__dirname, '..', 'inc');
    if (fs.existsSync(incDir)) {
        const phpFiles = fs.readdirSync(incDir).filter(file => file.endsWith('.php'));
        
        for (const file of phpFiles) {
            const content = fs.readFileSync(path.join(incDir, file), 'utf8');
            
            // Verify wp_remote_* functions are used instead of direct HTTP calls
            if (content.includes('curl_init') || content.includes('file_get_contents(\'http')) {
                console.warn(`Warning: ${file} may contain direct HTTP calls. Use wp_remote_* functions for WordPress.com compatibility.`);
            }
        }
    }
    
    console.log('WordPress.com optimization completed');
}

function createZip() {
    return new Promise((resolve, reject) => {
        console.log(`Creating ZIP file: ${zipPath}`);
        
        const output = fs.createWriteStream(zipPath);
        const archive = archiver('zip', {
            zlib: { level: 9 } // Maximum compression for WordPress.com upload
        });
        
        output.on('close', () => {
            const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
            console.log(`ZIP file created successfully: ${zipName} (${sizeInMB} MB)`);
            
            // WordPress.com has file size limits
            if (archive.pointer() > 50 * 1024 * 1024) { // 50MB limit
                console.warn('Warning: ZIP file is larger than 50MB. WordPress.com may reject large files.');
            }
            
            resolve();
        });
        
        archive.on('error', (err) => {
            reject(err);
        });
        
        archive.pipe(output);
        
        // Add files to archive
        const baseDir = path.join(__dirname, '..');
        
        function addDirectory(dir, archivePath = '') {
            const items = fs.readdirSync(dir);
            
            for (const item of items) {
                const fullPath = path.join(dir, item);
                const relativePath = path.relative(baseDir, fullPath);
                const archiveItemPath = path.join(pluginName, archivePath, item).replace(/\\/g, '/');
                
                if (shouldExclude(relativePath)) {
                    continue;
                }
                
                const stat = fs.statSync(fullPath);
                
                if (stat.isDirectory()) {
                    addDirectory(fullPath, path.join(archivePath, item));
                } else {
                    archive.file(fullPath, { name: archiveItemPath });
                    console.log(`Added: ${archiveItemPath}`);
                }
            }
        }
        
        addDirectory(baseDir);
        archive.finalize();
    });
}

async function build() {
    try {
        console.log(`Building ${pluginName} v${version} for WordPress.com...`);
        console.log('==========================================');
        
        validateWordPressComCompatibility();
        optimizeForWordPressCom();
        createBuildDirectory();
        await createZip();
        
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