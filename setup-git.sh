#!/bin/bash

# VelocityDev Developer Tools - Git Setup Script
# This script helps automate the initial Git setup for publishing to GitHub

echo "🚀 VelocityDev Developer Tools - Git Setup"
echo "=========================================="

# Check if we're already in a git repository
if [ -d .git ]; then
    echo "⚠️  Git repository already exists. Skipping git init."
else
    echo "📁 Initializing Git repository..."
    git init
fi

# Check if origin remote exists
if git remote get-url origin >/dev/null 2>&1; then
    echo "🔗 Remote 'origin' already exists:"
    git remote get-url origin
    read -p "Do you want to update the remote URL? (y/N): " update_remote
    if [[ $update_remote =~ ^[Yy]$ ]]; then
        read -p "Enter your GitHub repository URL: " repo_url
        git remote set-url origin "$repo_url"
        echo "✅ Remote URL updated to: $repo_url"
    fi
else
    read -p "Enter your GitHub repository URL (e.g., https://github.com/babit-choudhary/magento2-dev-tools.git): " repo_url
    if [ -n "$repo_url" ]; then
        git remote add origin "$repo_url"
        echo "✅ Remote 'origin' added: $repo_url"
    else
        echo "⚠️  No remote URL provided. You can add it later with:"
        echo "   git remote add origin <your-repo-url>"
    fi
fi

# Check current branch and switch to main if needed
current_branch=$(git branch --show-current 2>/dev/null || echo "")
if [ "$current_branch" != "main" ]; then
    echo "🔄 Switching to 'main' branch..."
    git checkout -b main 2>/dev/null || git checkout main 2>/dev/null || echo "Could not switch to main branch"
fi

# Add all files
echo "📝 Adding files to Git..."
git add .

# Check if there are changes to commit
if git diff --staged --quiet; then
    echo "ℹ️  No changes to commit."
else
    # Create initial commit
    echo "💾 Creating initial commit..."
    git commit -m "Initial commit: VelocityDev Developer Tools v1.2.0-beta1

- Complete developer tools suite for Magento 2
- Database query profiling with real-time monitoring
- Performance tracking and memory analysis
- API key authentication system
- Interactive web-based toolbar widget
- Console commands for management
- Comprehensive test coverage
- Browser extension support"

    echo "✅ Initial commit created successfully!"
fi

# Create and push version tag
echo "🏷️  Creating version tag..."
if git tag -l | grep -q "v1.2.0-beta1"; then
    echo "⚠️  Tag v1.2.0-beta1 already exists."
else
    git tag -a v1.2.0-beta1 -m "Version 1.2.0-beta1

Features:
- Database query profiling
- Performance monitoring  
- API key authentication
- Interactive toolbar widget
- Console command integration
- Comprehensive debugging tools"
    echo "✅ Tag v1.2.0-beta1 created!"
fi

# Push to GitHub
read -p "Do you want to push to GitHub now? (Y/n): " push_now
if [[ ! $push_now =~ ^[Nn]$ ]]; then
    echo "⬆️  Pushing to GitHub..."
    
    # Push main branch
    if git push -u origin main; then
        echo "✅ Main branch pushed successfully!"
    else
        echo "❌ Failed to push main branch. Please check your remote URL and credentials."
    fi
    
    # Push tags
    if git push origin --tags; then
        echo "✅ Tags pushed successfully!"
    else
        echo "❌ Failed to push tags."
    fi
else
    echo "ℹ️  Skipping push. You can push later with:"
    echo "   git push -u origin main"
    echo "   git push origin --tags"
fi

echo ""
echo "🎉 Git setup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Ensure your GitHub repository exists and is public"
echo "2. Register your package on Packagist.org"
echo "3. Set up webhook for auto-updates"
echo "4. Add badges to your README"
echo ""
echo "📚 For detailed instructions, see PUBLISHING.md"
echo ""
echo "🔗 Useful commands:"
echo "   git status                 # Check repository status"
echo "   git remote -v             # View remote URLs"
echo "   git log --oneline         # View commit history"
echo "   git tag -l                # List all tags"
echo ""
echo "Happy coding! 🚀" 