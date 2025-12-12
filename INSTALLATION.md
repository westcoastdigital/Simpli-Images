# Simpli Images - Installation Guide

## Quick Installation

1. Upload the entire `simpli-images` folder to `/wp-content/plugins/`
2. Activate through WordPress admin > Plugins
3. Go to Settings > Simpli Images
4. Configure your settings

## File Structure

Your `/wp-content/plugins/` directory should look like this:

```
wp-content/
└── plugins/
    └── simpli-images/
        ├── simpli-images.php          (Main plugin file)
        ├── README.md                  (Documentation)
        ├── inc/
        │   ├── Settings.php           (Settings page)
        │   └── helpers.php            (Helper functions)
        └── assets/
            ├── admin.css              (Admin styles)
            └── admin.js               (Admin scripts)
```

## Required Permissions

The plugin needs write access to:
- `/wp-content/uploads/simpli-cache/` (created automatically)

## Configuration

### For Most Sites (Blog/Portfolio):
```
Uploads Tab:
- Max Dimension: 1200px
- Max File Size: 1.2MB
- JPEG Quality: 82%

Image Sizes Tab:
- Remove All Sizes: ON
```

### For E-commerce Sites (WooCommerce):
```
Uploads Tab:
- Max Dimension: 1500px
- Max File Size: 1.5MB
- JPEG Quality: 85%

Image Sizes Tab:
- Remove All Sizes: OFF
- Disable: Theme sizes (keep WooCommerce + Core)
- Regenerate on Deactivation: ON
```

## First Steps After Installation

1. **Configure Settings**
   - Go to Settings > Simpli Images
   - Set max dimensions and file size
   - Choose image size strategy

2. **Test Upload**
   - Upload a test image
   - Check file size and dimensions
   - Verify only original is created

3. **Test Dynamic Generation**
   - Add this to your theme:
   ```php
   <?php simplimg(get_post_thumbnail_id(), 300, 300, 'crop'); ?>
   ```
   - Check if image displays correctly
   - View page source to see cached URL

4. **Monitor Cache**
   - Check cache stats in Uploads tab
   - Images will generate on first view
   - Subsequent views use cached versions

## Updating Existing Sites

If you have an existing site with many images:

1. **Backup First**
   - Backup your media library
   - Backup your database

2. **Install Plugin**
   - Upload and activate as normal
   - Don't change settings yet

3. **Choose Strategy**
   - Option A: Keep existing thumbnails (Remove All Sizes: OFF)
   - Option B: Remove thumbnails (Remove All Sizes: ON)

4. **Update Theme**
   - Replace `the_post_thumbnail()` with `simplimg()`
   - Update image calls in templates
   - Test thoroughly

5. **Clean Up** (Optional)
   - Use Media Cleaner plugin to remove unused sizes
   - Or manually delete old thumbnails

## Theme Integration

Replace existing image calls:

**Before:**
```php
the_post_thumbnail('medium');
the_post_thumbnail('thumbnail');
```

**After:**
```php
<img src="<?php simplimg(get_post_thumbnail_id(), 300, 'auto'); ?>" alt="">
<img src="<?php simplimg(get_post_thumbnail_id(), 150, 150, 'crop'); ?>" alt="">
```

## Troubleshooting Installation

### Plugin doesn't activate
- Check PHP version (requires 7.0+)
- Check WordPress version (requires 5.0+)
- Look for fatal errors in debug.log

### Settings page blank
- Check file permissions on inc/ folder
- Verify all files uploaded correctly
- Check for PHP errors

### Images not optimizing
- Verify GD or Imagick is installed: `phpinfo()`
- Check PHP memory_limit (256M recommended)
- Test with smaller images first

### Cache directory not created
- Check write permissions on /wp-content/uploads/
- Manually create /simpli-cache/ and set to 755

## Server Requirements

- PHP 7.0 or higher
- WordPress 5.0 or higher
- GD Library or Imagick extension
- memory_limit: 256M+ recommended
- max_execution_time: 30s+

## Verification

To verify successful installation:

1. Check WordPress admin > Settings > Simpli Images exists
2. Upload a test image and check it's optimized
3. Use `simplimg()` in a template and verify it works
4. Check that `/wp-content/uploads/simpli-cache/` is created

## Getting Help

If you encounter issues:

1. Check the README.md for detailed documentation
2. Enable WordPress debug mode to see errors
3. Check server error logs
4. Contact: https://simpliweb.com.au

## Uninstallation

To cleanly remove the plugin:

1. Settings > Image Sizes > Enable "Regenerate on Deactivation"
2. Settings > Uploads > Clear All Cache
3. Deactivate plugin (thumbnails auto-regenerate)
4. Delete plugin files

Or to remove without regenerating:

1. Deactivate plugin
2. Delete plugin files
3. Manually delete `/wp-content/uploads/simpli-cache/` if desired
