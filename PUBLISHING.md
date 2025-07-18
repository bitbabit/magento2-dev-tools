# Publishing VelocityDev DeveloperTools to GitHub & Packagist

This guide walks you through publishing your Magento 2 module to GitHub and making it available via Composer Packagist.

## Prerequisites

Before you begin, ensure you have:
- Git installed on your system
- A GitHub account
- A Packagist account (packagist.org)
- Composer installed globally
- Your module code ready and tested

## Step 1: Prepare Your Repository Structure

### 1.1 Create the Repository Structure

Your repository should have this structure:
```
magento2-dev-tools/
├── Api/
├── Console/
├── Controller/
├── etc/
├── Helper/
├── Model/
├── Observer/
├── Plugin/
├── Service/
├── Test/
├── view/
├── composer.json
├── registration.php
├── README.md
├── LICENSE
└── .gitignore
```

### 1.2 Create Essential Files

Create `.gitignore`:
```gitignore
# IDE files
.idea/
.vscode/
*.swp
*.swo
*~

# OS generated files
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# Composer
vendor/
composer.lock

# PHPUnit
.phpunit.cache/
Test/Coverage/

# Magento specific
var/
pub/static/
pub/media/
generated/

# Node modules (if any)
node_modules/
npm-debug.log*
```

Create `LICENSE`:
```text
MIT License

Copyright (c) 2024 VelocityDev

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Step 2: Optimize composer.json for Packagist

Update your `composer.json` with complete metadata:

```json
{
    "name": "velocitydev/magento2-dev-tools",
    "description": "Advanced developer tools for Magento 2 with dynamic control and visualization",
    "type": "magento2-module",
    "version": "1.2.0-beta1",
    "license": "MIT",
    "homepage": "https://github.com/velocitydev/magento2-dev-tools",
    "support": {
        "issues": "https://github.com/velocitydev/magento2-dev-tools/issues",
        "source": "https://github.com/velocitydev/magento2-dev-tools",
        "docs": "https://github.com/velocitydev/magento2-dev-tools/blob/main/README.md"
    },
    "authors": [
        {
            "name": "Babit Kumar",
            "email": "babitkumar6@gmail.com",
            "homepage": "https://github.com/velocitydev",
            "role": "Developer"
        }
    ],
    "keywords": [
        "magento2",
        "magento",
        "developer",
        "tools",
        "debugging",
        "performance",
        "profiler",
        "database",
        "query",
        "monitoring",
        "debug",
        "developer-tools",
        "debug-bar",
        "profiling"
    ],
    "require": {
        "php": "^8.1|^8.2|^8.3",
        "magento/framework": "^103.0",
        "magento/module-backend": "^102.0",
        "magento/module-config": "^101.0",
        "magento/module-store": "^101.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0|^10.0",
        "magento/magento-coding-standard": "^33.0",
        "phpstan/phpstan": "^1.0",
        "squizlabs/php_codesniffer": "^3.6"
    },
    "suggest": {
        "magento/module-developer": "For enhanced developer mode features"
    },
    "autoload": {
        "files": [
            "registration.php"
        ],
        "psr-4": {
            "VelocityDev\\DeveloperTools\\": ""
        }
    },
    "autoload-dev": {
        "psr-4": {
            "VelocityDev\\DeveloperTools\\Test\\": "Test/"
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "magento/magento-composer-installer": true,
            "magento/inventory-composer-installer": true
        }
    },
    "extra": {
        "magento-force": "override"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

## Step 3: Create GitHub Repository

### 3.1 Create Repository on GitHub

1. Go to [GitHub.com](https://github.com) and sign in
2. Click the "+" icon → "New repository"
3. Repository details:
   - **Repository name**: `magento2-dev-tools`
   - **Description**: "Advanced developer tools for Magento 2 with dynamic control and visualization"
   - **Visibility**: Public (required for free Packagist)
   - **Initialize**: Don't initialize (we'll push existing code)

### 3.2 Initialize Local Git Repository

```bash
# Navigate to your module directory
cd /path/to/your/DeveloperTools

# Initialize git repository
git init

# Add remote origin
git remote add origin https://github.com/YOUR-USERNAME/magento2-dev-tools.git

# Create .gitignore if not exists
# (Use the content provided above)

# Add all files
git add .

# Make initial commit
git commit -m "Initial commit: VelocityDev Developer Tools v1.2.0-beta1

- Complete developer tools suite for Magento 2
- Database query profiling with real-time monitoring
- Performance tracking and memory analysis
- API key authentication system
- Interactive web-based toolbar widget
- Console commands for management
- Comprehensive test coverage
- Browser extension support"

# Push to GitHub
git push -u origin main
```

## Step 4: Create Release Tags

```bash
# Create and push version tag
git tag -a v1.2.0-beta1 -m "Version 1.2.0-beta1

Features:
- Database query profiling
- Performance monitoring
- API key authentication
- Interactive toolbar widget
- Console command integration
- Comprehensive debugging tools"

git push origin v1.2.0-beta1
```

## Step 5: Enhance GitHub Repository

### 5.1 Create GitHub Issues Templates

Create `.github/ISSUE_TEMPLATE/bug_report.md`:
```markdown
---
name: Bug report
about: Create a report to help us improve
title: ''
labels: bug
assignees: ''
---

**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Environment:**
- Magento version: [e.g. 2.4.6]
- PHP version: [e.g. 8.2]
- Module version: [e.g. 1.2.0-beta1]
- Browser: [e.g. chrome, safari]

**Additional context**
Add any other context about the problem here.
```

### 5.2 Create Pull Request Template

Create `.github/pull_request_template.md`:
```markdown
## Description
Brief description of changes

## Type of change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Manual testing completed
- [ ] No new warnings or errors

## Checklist
- [ ] My code follows the style guidelines
- [ ] I have performed a self-review
- [ ] I have commented my code
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
```

## Step 6: Register with Packagist

### 6.1 Create Packagist Account

1. Go to [Packagist.org](https://packagist.org)
2. Click "Sign Up" or "Login" if you have an account
3. Connect your GitHub account

### 6.2 Submit Your Package

1. Click "Submit" in the top navigation
2. Enter your repository URL: `https://github.com/YOUR-USERNAME/magento2-dev-tools`
3. Click "Check" to validate
4. Review the package information
5. Click "Submit" to publish

### 6.3 Set Up Auto-Update

1. Go to your package page on Packagist
2. Click "Settings" tab
3. Copy the webhook URL
4. Go to your GitHub repository → Settings → Webhooks
5. Click "Add webhook"
6. Paste the Packagist webhook URL
7. Select "Just the push event"
8. Set Active and save

## Step 7: Verify Installation

Test your package installation:

```bash
# Test in a fresh Magento 2 installation
composer require velocitydev/magento2-dev-tools

# Enable module
bin/magento module:enable VelocityDev_DeveloperTools
bin/magento setup:upgrade
```

## Step 8: Create Documentation Website (Optional)

### 8.1 Enable GitHub Pages

1. Go to repository Settings → Pages
2. Select source: "Deploy from a branch"
3. Branch: main, folder: / (root)
4. Your documentation will be available at: `https://YOUR-USERNAME.github.io/magento2-dev-tools`

### 8.2 Create docs/index.md

```markdown
# VelocityDev Developer Tools Documentation

[View on GitHub](https://github.com/YOUR-USERNAME/magento2-dev-tools)

## Quick Start

```bash
composer require velocitydev/magento2-dev-tools
```

[Full Documentation](README.md)
```

## Step 9: Promote Your Package

### 9.1 Add Badges to README

```markdown
[![Latest Stable Version](https://poser.pugx.org/velocitydev/magento2-dev-tools/v/stable)](https://packagist.org/packages/velocitydev/magento2-dev-tools)
[![Total Downloads](https://poser.pugx.org/velocitydev/magento2-dev-tools/downloads)](https://packagist.org/packages/velocitydev/magento2-dev-tools)
[![License](https://poser.pugx.org/velocitydev/magento2-dev-tools/license)](https://packagist.org/packages/velocitydev/magento2-dev-tools)
[![PHP Version Require](https://poser.pugx.org/velocitydev/magento2-dev-tools/require/php)](https://packagist.org/packages/velocitydev/magento2-dev-tools)
```

### 9.2 Share on Community Platforms

- Magento Community Forums
- Reddit (r/Magento)
- Magento Stack Exchange
- Twitter/LinkedIn
- Dev.to articles

## Step 10: Maintenance & Updates

### 10.1 Version Management

```bash
# For patch releases
git tag -a v1.2.1 -m "Bug fixes and improvements"
git push origin v1.2.1

# For minor releases  
git tag -a v1.3.0 -m "New features and enhancements"
git push origin v1.3.0

# For major releases
git tag -a v2.0.0 -m "Major version with breaking changes"
git push origin v2.0.0
```

### 10.2 Keep Dependencies Updated

```bash
# Update composer.json requirements
composer update --dry-run

# Test with different Magento versions
# Update compatibility matrix in README
```

## Troubleshooting

### Common Issues

**Package not found on Packagist:**
- Ensure repository is public
- Check composer.json syntax
- Verify package name is unique

**Auto-update not working:**
- Verify webhook is configured correctly
- Check webhook delivery in GitHub settings
- Ensure Packagist has access to repository

**Installation fails:**
- Check Magento version compatibility
- Verify PHP version requirements
- Review dependency conflicts

## Best Practices

1. **Semantic Versioning**: Follow [SemVer](https://semver.org/) strictly
2. **Changelog**: Maintain a CHANGELOG.md file
3. **Testing**: Ensure comprehensive test coverage
4. **Documentation**: Keep README and docs updated
5. **Security**: Regular security audits and updates
6. **Community**: Respond to issues and PRs promptly
7. **Compatibility**: Test with multiple Magento versions

## Success Metrics

Monitor your package success:
- Downloads on Packagist
- GitHub stars and forks
- Issues and community engagement
- Dependent packages
- Community feedback

---

After following this guide, your VelocityDev Developer Tools module will be:
- ✅ Published on GitHub with proper documentation
- ✅ Available via Composer/Packagist
- ✅ Auto-updating on new releases
- ✅ Discoverable by the Magento community
- ✅ Ready for community contributions 