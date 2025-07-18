# Publishing Checklist for VelocityDev Developer Tools

Use this checklist to ensure you complete all steps for publishing your module to GitHub and Packagist.

## Pre-Publishing Checklist

### ✅ Code Quality
- [ ] All unit tests pass (`vendor/bin/phpunit`)
- [ ] Code follows Magento 2 coding standards
- [ ] No syntax errors or warnings
- [ ] All features documented in README.md
- [ ] Console commands work correctly
- [ ] Admin configuration accessible
- [ ] Frontend toolbar displays properly

### ✅ Documentation
- [ ] README.md is complete and accurate
- [ ] CHANGELOG.md updated with current version
- [ ] LICENSE file exists
- [ ] composer.json has all required metadata
- [ ] Code is well-commented

### ✅ Files Ready
- [ ] .gitignore file created
- [ ] GitHub issue templates created
- [ ] Pull request template created
- [ ] All test files included
- [ ] No sensitive data in codebase

## GitHub Publishing Checklist

### ✅ Repository Setup
- [ ] GitHub repository created (public)
- [ ] Repository name: `magento2-dev-tools`
- [ ] Repository description added
- [ ] Topics/tags added for discoverability

### ✅ Git Operations
- [ ] Local git repository initialized
- [ ] Remote origin added
- [ ] All files committed
- [ ] Version tag created (`v1.2.0-beta1`)
- [ ] Code pushed to GitHub
- [ ] Tags pushed to GitHub

### ✅ Repository Enhancement
- [ ] README.md displays correctly on GitHub
- [ ] Issue templates working
- [ ] Pull request template working
- [ ] GitHub Pages enabled (optional)
- [ ] Repository settings configured

## Packagist Publishing Checklist

### ✅ Packagist Account
- [ ] Packagist.org account created
- [ ] GitHub account connected to Packagist

### ✅ Package Submission
- [ ] Package submitted to Packagist
- [ ] Repository URL validated
- [ ] Package information verified
- [ ] Package published successfully

### ✅ Auto-Update Setup
- [ ] Webhook URL copied from Packagist
- [ ] Webhook added to GitHub repository
- [ ] Webhook configured for push events
- [ ] Webhook tested and working

## Post-Publishing Checklist

### ✅ Verification
- [ ] Package visible on Packagist
- [ ] Package installable via Composer
- [ ] Installation instructions tested
- [ ] Module functions correctly after Composer install

### ✅ Promotion
- [ ] Badges added to README.md
- [ ] Package shared on relevant platforms
- [ ] Documentation website created (optional)
- [ ] Community informed about release

### ✅ Maintenance Setup
- [ ] Issue tracking enabled
- [ ] Notification settings configured
- [ ] Contribution guidelines documented
- [ ] Release process documented

## Quick Commands Reference

```bash
# Test installation
composer require velocitydev/magento2-dev-tools

# Enable module
bin/magento module:enable VelocityDev_DeveloperTools
bin/magento setup:upgrade

# Generate API key
bin/magento velocity:devtools:generate-api-key

# Run tests
vendor/bin/phpunit

# Create new release
git tag -a v1.3.0 -m "Version 1.3.0"
git push origin v1.3.0
```

## Troubleshooting Quick Fixes

**Package not found:**
- Check repository is public
- Verify composer.json syntax
- Ensure package name is unique

**Installation fails:**
- Check Magento version compatibility
- Verify PHP version requirements
- Review dependency conflicts

**Auto-update not working:**
- Verify webhook configuration
- Check webhook delivery logs
- Ensure Packagist has repository access

## Success Metrics to Track

- [ ] Downloads on Packagist
- [ ] GitHub stars and forks
- [ ] Issues and community engagement
- [ ] Dependent packages
- [ ] Community feedback and reviews

---

## Final Notes

- Follow semantic versioning for all releases
- Respond promptly to issues and pull requests
- Keep documentation updated with each release
- Test thoroughly before each release
- Maintain backward compatibility when possible

**Good luck with your open source project! 🚀** 