# GitHub Authentication Setup Guide

This guide helps you resolve the GitHub authentication error and set up secure access for your repository.

## Error: Password Authentication Removed

If you see this error:
```
remote: Support for password authentication was removed on August 13, 2021.
remote: Please see https://docs.github.com/get-started/getting-started-with-git/about-remote-repositories#cloning-with-https-urls for information on currently recommended modes of authentication.
```

GitHub no longer accepts passwords for Git operations. You need to use either:
1. **Personal Access Token (PAT)** - Recommended for HTTPS
2. **SSH Keys** - Recommended for SSH

## Solution 1: Personal Access Token (PAT) - Recommended

### Step 1: Create Personal Access Token

1. Go to [GitHub.com](https://github.com) and sign in
2. Click your profile picture → **Settings**
3. In the left sidebar, click **Developer settings**
4. Click **Personal access tokens** → **Tokens (classic)**
5. Click **Generate new token** → **Generate new token (classic)**
6. Fill out the form:
   - **Note**: "VelocityDev Developer Tools - Git Operations"
   - **Expiration**: Choose your preferred duration (90 days recommended)
   - **Scopes**: Select the following:
     - ✅ `repo` (Full control of private repositories)
     - ✅ `workflow` (Update GitHub Action workflows)
     - ✅ `write:packages` (Upload packages to GitHub Package Registry)
7. Click **Generate token**
8. **IMPORTANT**: Copy the token immediately (you won't see it again!)

### Step 2: Configure Git with Token

#### Option A: Store Token in Git Credential Manager (Recommended)
```bash
# Configure Git to use credential manager
git config --global credential.helper manager-core

# Try pushing again - Git will prompt for credentials
git push -u origin main
# Username: your-github-username
# Password: paste-your-personal-access-token-here
```

#### Option B: Update Remote URL with Token
```bash
# Replace the remote URL with token
git remote set-url origin https://YOUR-USERNAME:YOUR-TOKEN@github.com/YOUR-USERNAME/magento2-dev-tools.git

# Example:
# git remote set-url origin https://johnsmith:ghp_xxxxxxxxxxxxxxxxxxxx@github.com/johnsmith/magento2-dev-tools.git
```

#### Option C: Use Git Credential Store (Less Secure)
```bash
# Store credentials in plain text (not recommended for shared machines)
git config --global credential.helper store

# Push and enter credentials when prompted
git push -u origin main
```

### Step 3: Test the Setup
```bash
# Verify remote URL
git remote -v

# Test pushing
git push -u origin main
git push origin --tags
```

## Solution 2: SSH Keys (Alternative)

### Step 1: Generate SSH Key
```bash
# Generate new SSH key
ssh-keygen -t ed25519 -C "your-email@example.com"

# Press Enter to accept default file location
# Enter a secure passphrase (recommended)
```

### Step 2: Add SSH Key to SSH Agent
```bash
# Start SSH agent
eval "$(ssh-agent -s)"

# Add SSH key to agent
ssh-add ~/.ssh/id_ed25519
```

### Step 3: Add SSH Key to GitHub
```bash
# Copy SSH public key to clipboard (Linux/macOS)
cat ~/.ssh/id_ed25519.pub

# Copy SSH public key to clipboard (Windows)
clip < ~/.ssh/id_ed25519.pub
```

1. Go to GitHub.com → Settings → SSH and GPG keys
2. Click **New SSH key**
3. Title: "VelocityDev Development Machine"
4. Paste your public key
5. Click **Add SSH key**

### Step 4: Update Remote URL to SSH
```bash
# Change remote URL to SSH
git remote set-url origin git@github.com:YOUR-USERNAME/magento2-dev-tools.git

# Test SSH connection
ssh -T git@github.com

# Push to GitHub
git push -u origin main
git push origin --tags
```

## PowerShell Script for Windows Users

Create `setup-github-auth.ps1`:

```powershell
# GitHub Authentication Setup for Windows
Write-Host "🔐 GitHub Authentication Setup" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green

# Check if Git is installed
if (!(Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Git is not installed. Please install Git first." -ForegroundColor Red
    exit 1
}

Write-Host "📋 Choose authentication method:" -ForegroundColor Yellow
Write-Host "1. Personal Access Token (PAT) - Recommended"
Write-Host "2. SSH Keys"
$choice = Read-Host "Enter your choice (1 or 2)"

switch ($choice) {
    "1" {
        Write-Host "🔑 Setting up Personal Access Token..." -ForegroundColor Cyan
        Write-Host "1. Go to: https://github.com/settings/tokens"
        Write-Host "2. Generate new token with 'repo' scope"
        Write-Host "3. Copy the token"
        
        $username = Read-Host "Enter your GitHub username"
        $token = Read-Host "Enter your Personal Access Token" -AsSecureString
        $tokenPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($token))
        
        $remoteUrl = "https://$username:$tokenPlain@github.com/$username/magento2-dev-tools.git"
        git remote set-url origin $remoteUrl
        
        Write-Host "✅ Remote URL updated with token authentication" -ForegroundColor Green
    }
    "2" {
        Write-Host "🔐 Setting up SSH Keys..." -ForegroundColor Cyan
        
        $email = Read-Host "Enter your GitHub email"
        ssh-keygen -t ed25519 -C $email
        
        Write-Host "📋 Copy this public key to GitHub:" -ForegroundColor Yellow
        Get-Content "$env:USERPROFILE\.ssh\id_ed25519.pub"
        
        Write-Host "1. Go to: https://github.com/settings/ssh/new"
        Write-Host "2. Paste the public key above"
        Write-Host "3. Press Enter when done..."
        Read-Host
        
        $username = Read-Host "Enter your GitHub username"
        git remote set-url origin "git@github.com:$username/magento2-dev-tools.git"
        
        Write-Host "✅ Remote URL updated for SSH authentication" -ForegroundColor Green
    }
    default {
        Write-Host "❌ Invalid choice" -ForegroundColor Red
        exit 1
    }
}

# Test the connection
Write-Host "🧪 Testing connection..." -ForegroundColor Cyan
git remote -v
git push -u origin main
git push origin --tags

Write-Host "🎉 Setup complete!" -ForegroundColor Green
```

## Quick Commands for Your Situation

**Option 1: Use Personal Access Token (Easiest)**
```bash
# 1. Create PAT at: https://github.com/settings/tokens
# 2. Configure Git credential manager
git config --global credential.helper manager-core

# 3. Try pushing again (will prompt for username/token)
git push -u origin main
```

**Option 2: Update Remote URL with Token**
```bash
# Replace YOUR-USERNAME and YOUR-TOKEN
git remote set-url origin https://YOUR-USERNAME:YOUR-TOKEN@github.com/YOUR-USERNAME/magento2-dev-tools.git
git push -u origin main
git push origin --tags
```

## Troubleshooting

### Common Issues

**Token not working:**
- Ensure token has `repo` scope
- Check token hasn't expired
- Verify username is correct

**SSH key issues:**
- Ensure public key is added to GitHub
- Test SSH connection: `ssh -T git@github.com`
- Check SSH agent is running

**Credential manager issues:**
```bash
# Reset credential manager
git config --global --unset credential.helper
git config --global credential.helper manager-core
```

### Security Best Practices

1. **Use Fine-grained PATs** when available
2. **Set appropriate expiration dates** for tokens
3. **Use SSH keys** for better security
4. **Don't commit tokens** to your repository
5. **Rotate tokens regularly**

## Next Steps After Authentication Setup

1. ✅ Push your code to GitHub
2. ✅ Register package on Packagist
3. ✅ Set up webhooks for auto-updates
4. ✅ Share your awesome module with the community!

---

For more information, see:
- [GitHub Authentication Documentation](https://docs.github.com/en/authentication)
- [Managing Personal Access Tokens](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens)
- [Connecting to GitHub with SSH](https://docs.github.com/en/authentication/connecting-to-github-with-ssh) 