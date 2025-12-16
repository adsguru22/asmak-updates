# GitHub Pages Setup Instructions

## ⚠️ IMPORTANT: Setup GitHub Pages First

Before the auto-update system can work, you **MUST** enable GitHub Pages for this repository.

## 📝 Step-by-Step Setup

### Step 1: Enable GitHub Pages

1. Go to your repository on GitHub: https://github.com/adsguru22/asmak-updates
2. Click on **Settings** (top menu)
3. In the left sidebar, click **Pages**
4. Under **Source**, you'll see a dropdown that says "None"
5. Change it to **"Deploy from a branch"**
6. Select branch: **`gh-pages`**
7. Keep the folder as **`/ (root)`**
8. Click **Save**

### Step 2: Initial Deployment

Since the `gh-pages` branch doesn't exist yet, we need to create it with our initial content:

```bash
# Create and switch to gh-pages branch
git checkout --orphan gh-pages

# Add all files
git add index.html info.json assets/

# Commit
git commit -m "Initial GitHub Pages deployment"

# Push to GitHub
git push origin gh-pages

# Switch back to main
git checkout main
```

**OR** you can trigger the workflow manually after merging this PR:

1. Go to **Actions** tab
2. Select **"Update Plugin Info on Release"** workflow
3. Click **"Run workflow"**
4. This will create the `gh-pages` branch automatically

### Step 3: Verify Deployment

1. Wait 1-2 minutes for GitHub to build and deploy your site
2. Go back to **Settings** > **Pages**
3. You should see a message: "Your site is published at https://adsguru22.github.io/asmak-updates/"
4. Click the URL to verify it's working

### Step 4: Test the Update System

Visit these URLs to confirm everything is working:

- ✅ Landing Page: https://adsguru22.github.io/asmak-updates/
- ✅ Update Info: https://adsguru22.github.io/asmak-updates/info.json

Both should load without errors.

## 🔄 How to Create Your First Release

### Prepare Your Plugin ZIP

1. Package your WordPress plugin:
   ```bash
   cd /path/to/asmak-command-center
   zip -r asmak-command-center.zip . \
     -x "*.git*" "node_modules/*" ".DS_Store" "*.zip"
   ```

2. Verify the ZIP contains your plugin files

### Create the Release

1. Go to your repository on GitHub
2. Click **Releases** (right sidebar)
3. Click **"Create a new release"** or **"Draft a new release"**
4. Fill in the form:
   - **Tag version**: `v1.2.0` (must match version in info.json)
   - **Release title**: `Version 1.2.0`
   - **Description**: Add changelog/release notes
5. Upload your `asmak-command-center.zip` file in the assets section
6. Click **"Publish release"**

### What Happens Next

1. ✅ GitHub Actions workflow automatically triggers
2. ✅ `info.json` gets updated with new version
3. ✅ `index.html` gets updated with new version
4. ✅ Changes are committed to main branch
5. ✅ New version is deployed to GitHub Pages
6. ✅ WordPress sites start detecting the update (within 12 hours)

## 🐛 Troubleshooting

### Problem: "404 Not Found" when visiting GitHub Pages URL

**Solution:**
- Make sure GitHub Pages is enabled (Settings > Pages)
- Verify the `gh-pages` branch exists
- Wait 2-5 minutes for deployment to complete
- Check Actions tab for deployment errors

### Problem: Workflow doesn't run on release

**Solution:**
- Check that `.github/workflows/update-info.yml` exists
- Verify GitHub Actions is enabled (Settings > Actions)
- Check the Actions tab for error messages
- Ensure you have the necessary permissions

### Problem: info.json shows 404

**Solution:**
- Ensure `info.json` is in the root directory
- Check that it's deployed to `gh-pages` branch
- Clear your browser cache
- Try accessing with `?v=timestamp` parameter

### Problem: WordPress doesn't show update notification

**Solution:**
- Clear WordPress transients:
  ```php
  delete_transient('asmak_plugin_update_' . md5($url));
  ```
- Check that `info.json` URL is correct in plugin code
- Verify version in `info.json` is higher than installed version
- Enable `WP_DEBUG` to see error messages

## ✅ Verification Checklist

Before you consider the setup complete, verify:

- [ ] GitHub Pages is enabled and working
- [ ] https://adsguru22.github.io/asmak-updates/ shows the landing page
- [ ] https://adsguru22.github.io/asmak-updates/info.json returns valid JSON
- [ ] GitHub Actions workflow runs successfully
- [ ] You can create a test release
- [ ] Plugin updater is integrated in WordPress plugin
- [ ] WordPress can fetch update info from GitHub Pages

## 📞 Need Help?

If you're stuck:
1. Check the [SETUP_GUIDE.md](SETUP_GUIDE.md) for detailed documentation
2. Review GitHub Actions logs in the Actions tab
3. Create an issue in this repository with:
   - What you're trying to do
   - What error you're seeing
   - Screenshots if applicable

## 🎉 Success!

Once everything is working, you'll have:
- ✅ Free, automated plugin update system
- ✅ Professional landing page for your plugin
- ✅ Automatic updates on every release
- ✅ Zero ongoing maintenance

Enjoy your auto-update system! 🚀
