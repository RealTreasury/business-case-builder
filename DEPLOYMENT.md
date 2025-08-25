# WordPress.com Deployment Guide

This guide provides step-by-step instructions for deploying the Real Treasury Business Case Builder to WordPress.com, including both staging and production environments.

## Prerequisites

### Development Environment Setup
1. **Node.js 16+** and **npm 8+** installed
2. **PHP 7.4+** with required extensions
3. **Composer** for dependency management
4. **Git** for version control

### WordPress.com Account Requirements
- WordPress.com Business plan or higher (required for plugin uploads)
- Admin access to your WordPress.com site
- FTP/SFTP access (optional, for debugging)

## Pre-Deployment Checklist

### Code Quality Verification
```bash
# Run complete test suite
npm run test

# Check WordPress Coding Standards
npm run lint:php

# Verify PHP compatibility
npm run compat:check

# Check JavaScript quality
npm run lint:js
```

### WordPress.com Compatibility Check
```bash
# Build production package
npm run build

# Verify no incompatible functions
grep -r "exec\|shell_exec\|system\|passthru" --include="*.php" ./inc ./admin ./public
```

## Staging Environment Deployment

### Step 1: Prepare Staging Build
```bash
# Create staging build
npm run build:production

# Verify build contents
ls -la build/
```

### Step 2: Upload to WordPress.com Staging
1. Log in to your WordPress.com staging site admin
2. Navigate to **Plugins → Add New**
3. Click **Upload Plugin**
4. Select the generated ZIP file from `build/` directory
5. Click **Install Now**
6. **Activate** the plugin

### Step 3: Staging Configuration
1. Navigate to **Real Treasury → Settings**
2. Enter your OpenAI API key (use development key for staging)
3. Configure any additional settings
4. Test core functionality

### Step 4: Staging Testing
```bash
# Run E2E tests against staging
CYPRESS_BASE_URL=https://your-staging-site.wordpress.com npm run test:e2e
```

## Production Deployment

### Step 1: Final Pre-Production Checks
```bash
# Ensure all tests pass
npm run test

# Verify production build
npm run build

# Check build size (must be < 50MB for WordPress.com)
ls -lh build/*.zip
```

### Step 2: Production Upload
1. **Backup your live site** before deployment
2. Log in to your WordPress.com production site admin
3. Navigate to **Plugins → Add New**
4. Click **Upload Plugin**
5. Select the production ZIP file
6. Click **Install Now**

### Step 3: Plugin Activation
1. **Deactivate the old version** (if updating)
2. **Activate the new version**
3. Check for any activation errors
4. Verify plugin appears in **Real Treasury** menu

### Step 4: Production Configuration
1. Navigate to **Real Treasury → Settings**
2. Enter your **production OpenAI API key**
3. Configure WordPress.com specific settings:
   - Cache timeout settings
   - Memory limit awareness
   - Rate limiting configurations

### Step 5: Post-Deployment Verification
1. Test business case generation
2. Verify admin dashboard functionality
3. Check frontend components
4. Monitor error logs for 24 hours

## WordPress.com Specific Considerations

### Plugin Limitations on WordPress.com
- **No direct file system access** outside plugin directory
- **Limited external HTTP requests** (use `wp_remote_*` functions)
- **Shared hosting constraints** (memory, execution time)
- **Automatic updates** may overwrite customizations

### Performance Optimization for WordPress.com
- Use **WordPress.com CDN** for static assets
- Implement **efficient caching strategies**
- Minimize **database queries**
- Optimize **API request patterns**

### Security Considerations
- **Always escape output** with appropriate `esc_*` functions
- **Validate and sanitize input** using WordPress functions
- **Use nonces** for form submissions
- **Follow WordPress security best practices**

## Troubleshooting Common Issues

### Plugin Upload Errors
**Issue**: "Plugin is too large"
```bash
# Check ZIP file size
ls -lh build/*.zip

# If too large, check for included development files
unzip -l build/*.zip | grep -E "(node_modules|vendor|tests)"
```

**Solution**: Ensure build script excludes development files

### Memory Limit Issues
**Issue**: "Fatal error: Allowed memory size exhausted"

**Solution**: Optimize memory usage in code:
```php
// Use in memory-intensive operations
rtbcb_increase_memory_limit();
rtbcb_log_memory_usage( 'before_operation' );
```

### API Rate Limiting
**Issue**: OpenAI API requests failing

**Solution**: Implement rate limiting:
```php
// Check rate limits before API calls
$rate_limit_status = rtbcb_check_api_rate_limit();
if ( ! $rate_limit_status['can_proceed'] ) {
    // Handle rate limiting
}
```

### WordPress.com Cache Issues
**Issue**: Changes not appearing immediately

**Solution**: 
1. Clear WordPress.com cache via admin
2. Implement cache-busting for critical updates
3. Use appropriate cache headers

## Rollback Procedure

### Emergency Rollback
If critical issues occur after deployment:

1. **Immediately deactivate** the plugin via WordPress admin
2. **Upload and activate** the previous working version
3. **Restore any database changes** if necessary
4. **Clear all caches**

### Database Rollback
```sql
-- Only if database migrations were included
-- Contact WordPress.com support for database restoration
```

## Monitoring and Maintenance

### Post-Deployment Monitoring
- Monitor **error logs** for 48 hours post-deployment
- Check **performance metrics** (page load times)
- Verify **API usage patterns**
- Monitor **user feedback** and support requests

### Regular Maintenance
- **Weekly**: Check error logs
- **Monthly**: Review performance metrics
- **Quarterly**: Update dependencies and test
- **Annually**: Full security audit

## WordPress.com Support Resources

### Documentation
- [WordPress.com Plugin Guidelines](https://wordpress.com/support/plugins/)
- [WordPress.com Business Plan Features](https://wordpress.com/support/plan-features/)

### Support Channels
- WordPress.com support ticket system
- WordPress.com community forums
- Real Treasury support (for plugin-specific issues)

## Emergency Contacts

- **WordPress.com Support**: Available 24/7 for Business plan users
- **Real Treasury Support**: [Contact information]
- **Development Team**: [Contact information]

---

## Deployment Automation (Advanced)

For frequent deployments, consider setting up automated deployment:

```yaml
# .github/workflows/deploy-production.yml
name: Deploy to WordPress.com Production
on:
  release:
    types: [published]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build and deploy
        run: |
          npm ci
          npm run build
          # Custom deployment script for WordPress.com
```

This automation requires careful setup and testing with WordPress.com's infrastructure.