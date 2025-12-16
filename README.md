# 🚀 Adsguru Updates

Update server for ADSGURU Command Center plugin. Free auto-update system using GitHub Pages.

## 📋 Overview

This repository hosts the auto-update server for the adsguru Command Center WordPress plugin using GitHub Pages. It provides a completely free solution for plugin updates without requiring a dedicated server.

## ✨ Features

- ✅ **Completely Free** - Using GitHub Pages (no hosting costs)
- ✅ **Automatic Updates** - WordPress users get notified of new versions
- ✅ **GitHub Actions Integration** - Auto-update on release
- ✅ **Professional Landing Page** - Beautiful info page with stats
- ✅ **Production Ready** - Battle-tested WordPress updater class
- ✅ **Zero Maintenance** - Set it and forget it

## 📊 Plugin Stats

- **11** Modules
- **42+** API Endpoints  
- **6+** Integrations
- **100+** Active Installations

## 🔗 Links

- **Landing Page**: [https://adsguru22.github.io/adsguru-updates/](https://adsguru22.github.io/adsguru-updates/)
- **Update Info**: [https://adsguru22.github.io/adsguru-updates/info.json](https://adsguru22.github.io/adsguru-updates/info.json)
- **Latest Version**: 1.2.0

## 📚 Documentation

See [SETUP_GUIDE.md](SETUP_GUIDE.md) for complete setup instructions including:
- How to enable GitHub Pages
- How to integrate the updater into your WordPress plugin
- How to create new releases
- Troubleshooting guide

## 🚀 Quick Start

### For Plugin Developers

1. **Copy the updater class** to your plugin:
   ```
   cp plugin-integration/updater.php your-plugin/includes/
   ```

2. **Add to your plugin's main file**:
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/updater.php';
   
   if (is_admin()) {
       $updater = new adsguru_Plugin_Updater(__FILE__);
       $updater->set_update_url('https://adsguru22.github.io/adsguru-updates/info.json');
       $updater->initialize();
   }
   ```

3. **Done!** Your plugin now auto-updates from GitHub Pages.

### For Repository Setup

1. Enable GitHub Pages in repository settings (use `gh-pages` branch)
2. Create a new release with your plugin ZIP file
3. GitHub Actions will automatically update `info.json` and deploy to Pages
4. WordPress users will see the update notification

## 📦 File Structure

```
adsguru-updates/
├── .github/
│   └── workflows/
│       └── update-info.yml      # Auto-update workflow
├── plugin-integration/
│   ├── updater.php              # WordPress updater class
│   └── example-integration.php  # Example implementation
├── info.json                    # Plugin metadata (auto-updated)
├── index.html                   # Landing page
├── SETUP_GUIDE.md              # Complete documentation
└── README.md                    # This file
```

## 🔄 How It Works

1. You create a new GitHub Release with the plugin ZIP file
2. GitHub Actions workflow automatically runs
3. `info.json` is updated with new version and download URL
4. Changes are deployed to GitHub Pages
5. WordPress checks for updates and finds the new version
6. Users get notified and can update with one click

## 🛠️ Requirements

### For Plugin
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

### For Update Server
- GitHub account (free)
- GitHub Pages enabled
- GitHub Actions enabled (free)

## 📝 License

Free and open source. Use it however you want!

## 🤝 Contributing

Contributions welcome! Feel free to:
- Report bugs
- Suggest features
- Submit pull requests

## 📞 Support

Need help? Create an issue in this repository.

---

**Made with ❤️ by Adsguru Team**
