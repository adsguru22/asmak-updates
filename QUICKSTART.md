# 🚀 Quick Start Guide

Get your ASMAK Command Center auto-update system running in 5 minutes!

## 📋 Prerequisites

- GitHub account (free)
- WordPress plugin ready to deploy
- 5 minutes of your time

## ⚡ Quick Setup (3 Steps)

### Step 1: Enable GitHub Pages (2 minutes)

1. Go to **Settings** > **Pages** in this repository
2. Set Source to **"Deploy from a branch"**
3. Select branch: **`gh-pages`**
4. Click **Save**

> **Note:** The `gh-pages` branch will be created automatically when you publish your first release or manually trigger the workflow.

### Step 2: Integrate Updater into Your Plugin (2 minutes)

Copy `plugin-integration/updater.php` to your plugin:

```bash
cp plugin-integration/updater.php /path/to/your-plugin/includes/
```

Add to your plugin's main file:

```php
// Load plugin updater
require_once plugin_dir_path(__FILE__) . 'includes/updater.php';

// Initialize auto-updater (admin only)
if (is_admin()) {
    $updater = new ASMAK_Plugin_Updater(__FILE__);
    $updater->set_update_url('https://adsguru22.github.io/asmak-updates/info.json');
    $updater->initialize();
}
```

### Step 3: Create Your First Release (1 minute)

1. Package your plugin:
   ```bash
   zip -r asmak-command-center.zip your-plugin/
   ```

2. Create GitHub Release:
   - Go to **Releases** > **Create a new release**
   - Tag: `v1.2.0`
   - Upload: `asmak-command-center.zip`
   - Click **Publish**

3. Done! 🎉

## ✅ Verify It's Working

After 2-3 minutes, check:

- ✅ Landing page: https://adsguru22.github.io/asmak-updates/
- ✅ Update info: https://adsguru22.github.io/asmak-updates/info.json

## 🔄 How to Update Your Plugin

1. Update version number in your plugin
2. Create new ZIP file
3. Create new GitHub Release (e.g., `v1.3.0`)
4. Upload the ZIP
5. GitHub Actions automatically updates everything!

## 📚 Need More Details?

- **Complete Setup Guide**: [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **GitHub Pages Help**: [GITHUB_PAGES_SETUP.md](GITHUB_PAGES_SETUP.md)
- **Verification**: Run `./verify.sh` to check everything is ready

## 🐛 Something Not Working?

Run the verification script:

```bash
./verify.sh
```

This will check all files and configurations.

## 💡 Pro Tips

1. **Always test locally** before creating a release
2. **Use semantic versioning** (v1.2.3)
3. **Write good changelog** in release notes
4. **Check Actions tab** if workflow fails

## 🎯 What You Get

✅ **Free hosting** via GitHub Pages  
✅ **Automatic updates** for WordPress users  
✅ **Professional landing page**  
✅ **Zero maintenance**  
✅ **Version control** built-in  

## 🆘 Help

Create an [issue](https://github.com/adsguru22/asmak-updates/issues) if you need help!

---

**That's it! You're ready to roll! 🚀**
