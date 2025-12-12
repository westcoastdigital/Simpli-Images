# Simpli Images

A WordPress plugin for media library optimization that prevents intermediate image sizes, automatically resizes/compresses uploaded images, and provides dynamic image generation with caching.

## Version 1.1.0 - What's New

- **Refactored Architecture**: Cleaner code structure with separate files
- **Tabbed Settings Interface**: Organized settings across multiple tabs
- **Selective Size Control**: Choose which image sizes to disable, grouped by plugin/theme
- **Thumbnail Regeneration**: Built-in tool to regenerate thumbnails when needed
- **Cache Management**: Clear cached images directly from settings page
- **Deactivation Options**: Optionally regenerate thumbnails on plugin deactivation

## Features

- **Upload Optimization**: Automatically resize and compress images on upload
- **Remove Image Sizes**: Globally disable all intermediate sizes or selectively disable specific ones
- **Dynamic Image Generation**: Generate images at any size on-demand with `simplimg()` function
- **Smart Caching**: Generated images are cached for fast subsequent loads
- **Flexible Cropping**: Multiple crop positions (center, top, bottom, corners)
- **Aspect Ratio Support**: Easy 16:9, 4:3, 1:1, etc. with automatic cropping
- **WooCommerce Safe**: Keep WooCommerce sizes while removing others
- **Theme Compatible**: Identify and selectively disable theme-specific image sizes

## Installation

1. Upload the `simpli-images` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings at Settings > Simpli Images

## Plugin Structure

```
simpli-images/
├── simpli-images.php      # Main plugin file
├── inc/
│   ├── Settings.php       # Settings page and admin UI
│   └── helpers.php        # Dynamic image generation functions
└── assets/
    ├── admin.css          # Admin styles
    └── admin.js           # Admin JavaScript
```

## Settings

### Uploads Tab

Configure how images are processed when uploaded:

**Max Image Dimension**
- Default: 1200px
- Resizes images on their longest edge
- Set to 0 to disable resizing

**Max Image File Size**
- Default: 1.2MB
- Compresses images to stay under this limit
- Set to 0 to disable compression

**JPEG Compression Quality**
- Default: 82%
- Starting quality for JPEG compression (1-100)
- Higher = better quality but larger files

**Cache Management**
- View cached image statistics (count and size)
- Clear all cached images with one click
- Cache location: `/wp-content/uploads/simpli-cache/`

### Image Sizes Tab

Control which WordPress image sizes are generated:

**Remove All Image Sizes**
- Global toggle to disable all intermediate sizes
- When enabled, only optimized originals are stored
- Use `simplimg()` for dynamic sizing

**Selective Size Control** (when not removing all)
- Sizes grouped by source: WordPress Core, Theme, Plugins
- Checkbox interface to disable specific sizes
- Example: Keep WooCommerce sizes, disable theme sizes

**Thumbnail Regeneration**
- Regenerate all thumbnails manually
- Optional: Auto-regenerate on plugin deactivation
- Useful when switching themes or re-enabling sizes

## Dynamic Image Generation

### Basic Usage

```php
// Square thumbnail (150x150px, cropped center)
<img src="<?php simplimg($image_id, 150, 150, 'crop'); ?>" alt="">

// Scale to width, auto height
<img src="<?php simplimg($image_id, 300, 'auto'); ?>" alt="">

// 16:9 aspect ratio (cropped center)
<img src="<?php simplimg($image_id, 16, 9, 'crop'); ?>" alt="">

// 16:9 aspect ratio (cropped from top)
<img src="<?php simplimg($image_id, 16, 9, 'top'); ?>" alt="">
```

### Crop Positions

- `'crop'` or `'center'` - Center crop (default)
- `'top'` - Top center
- `'bottom'` - Bottom center
- `'left'` - Left center
- `'right'` - Right center
- `'top-left'`, `'top-right'`, `'bottom-left'`, `'bottom-right'` - Corner crops
- `false` - Scale without cropping

### Common Aspect Ratios

```php
// 16:9 (Widescreen)
simplimg($id, 16, 9, 'crop');

// 4:3 (Traditional)
simplimg($id, 4, 3, 'crop');

// 1:1 (Square)
simplimg($id, 1, 1, 'crop');

// 21:9 (Ultrawide)
simplimg($id, 21, 9, 'crop');

// 3:2 (Classic 35mm)
simplimg($id, 3, 2, 'crop');
```

### Real-World Examples

**Product Grid:**
```php
<div class="products">
    <?php foreach ($products as $product): 
        $image_id = get_post_thumbnail_id($product->ID);
    ?>
        <div class="product">
            <img src="<?php simplimg($image_id, 300, 300, 'crop'); ?>" alt="">
        </div>
    <?php endforeach; ?>
</div>
```

**Hero Banner (16:9, top-cropped):**
```php
<div class="hero">
    <img src="<?php simplimg($image_id, 16, 9, 'top'); ?>" alt="">
</div>
```

**Responsive Images:**
```php
<picture>
    <source media="(max-width: 768px)" 
            srcset="<?php simplimg($id, 1, 1, 'crop', false); ?>">
    <source media="(max-width: 1024px)" 
            srcset="<?php simplimg($id, 4, 3, 'crop', false); ?>">
    <img src="<?php simplimg($id, 16, 9, 'crop', false); ?>" alt="">
</picture>
```

## Image Size Management

### Understanding Image Size Groups

The plugin intelligently groups image sizes by their source:

**WordPress Core**
- thumbnail, medium, medium_large, large

**WooCommerce** (if active)
- shop_catalog, shop_single, shop_thumbnail, etc.

**Your Theme**
- Detected automatically based on size name patterns

**Other Plugins**
- Elementor, Yoast SEO, Divi, etc.

### Selective Disabling Strategy

**Scenario 1: E-commerce Site**
```
Remove All: OFF
Disabled: All theme sizes, WordPress medium_large
Enabled: WooCommerce sizes, thumbnail, medium, large
```

**Scenario 2: Blog Site**
```
Remove All: ON
Use simplimg() for all image rendering
```

**Scenario 3: Mixed Content**
```
Remove All: OFF
Disabled: Unused plugin sizes (Elementor if not using)
Enabled: Core sizes + active plugins
```

## Cache Management

### How Caching Works

1. **First Request**: Image generated and saved to `/simpli-cache/`
2. **Subsequent Requests**: Served directly from cache (0ms PHP processing)
3. **Automatic Cleanup**: Cache cleared when attachment is deleted

### Cache Format

```
{image-id}-{width}x{height}-{crop}.{ext}
Example: 123-300x300-crop.jpg
```

### Manual Cache Clearing

**Via Settings Page:**
- Go to Settings > Simpli Images > Uploads tab
- Click "Clear All Cache" button
- View statistics before clearing

**Programmatically:**
```php
// Clear cache for specific image
simplimg_clear_cache($image_id);

// Clear all cached images
$deleted_count = simplimg_clear_all_cache();
```

## Thumbnail Regeneration

### When to Regenerate

- After changing image size settings
- After switching themes
- After plugin deactivation (if enabled)
- When restoring WordPress defaults

### How to Regenerate

**Via Settings Page:**
1. Go to Settings > Simpli Images > Image Sizes tab
2. Click "Regenerate All Thumbnails Now"
3. Confirm the action (may take several minutes)

**On Deactivation:**
1. Enable "Regenerate on Deactivation" checkbox
2. Save settings
3. Thumbnails auto-regenerate when plugin is deactivated

### Performance Note

Regeneration processes all images in your media library. For sites with thousands of images, this may take 5-10 minutes or longer.

## Use Cases

### Photography Website
```
Max Dimension: 2400px (high-quality display)
Max File Size: 2MB
JPEG Quality: 85%
Remove All Sizes: ON
```

### Blog/Content Site
```
Max Dimension: 1200px (standard width)
Max File Size: 1MB
JPEG Quality: 82%
Remove All Sizes: ON
```

### eCommerce Site (WooCommerce)
```
Max Dimension: 1500px (product detail)
Max File Size: 1.5MB
JPEG Quality: 85%
Remove All Sizes: OFF
Disabled: Theme sizes only
Enabled: WooCommerce + Core sizes
```

### High-Volume Site
```
Max Dimension: 1000px (minimize storage)
Max File Size: 800KB
JPEG Quality: 75%
Remove All Sizes: ON
```

## Technical Details

### Storage Comparison

**Before Plugin** (typical WordPress):
```
original.jpg (3000x2000, 2.5MB)
original-1536x1024.jpg
original-2048x1365.jpg
original-scaled.jpg
original-medium.jpg
original-thumbnail.jpg
Total: 6+ files per upload
```

**After Plugin**:
```
original.jpg (1200x800, <1.2MB)
Total: 1 file per upload
+ Dynamic sizes cached on demand
```

### File Type Support

- **JPEG/JPG**: Full optimization with quality control
- **PNG**: Dimension resizing only
- **GIF**: Dimension resizing only
- **WebP**: Basic support

### Performance

**Upload Processing:** <1 second per image  
**First Dynamic Request:** 100-200ms (generation + cache)  
**Cached Requests:** 0ms (direct file access)

### Compatibility

- WordPress 5.0+
- PHP 7.0+
- Works with: Media Library, Featured Images, ACF Image fields
- Compatible with: Most page builders, WooCommerce, Elementor

### Server Requirements

- GD or Imagick PHP extension
- PHP memory_limit: 256M recommended for large images
- Write permissions to `/wp-content/uploads/`

## Troubleshooting

### Images aren't being resized on upload
- Check that Max Dimension > 0
- Verify uploaded image is larger than max dimension
- Check PHP memory_limit (256M recommended)

### Dynamic images not generating
- Verify image ID is valid
- Check /simpli-cache/ directory permissions
- Clear cache and try again

### WooCommerce images broken
- Ensure "Remove All Sizes" is OFF
- Keep WooCommerce sizes enabled in Image Sizes tab
- Regenerate thumbnails after changing settings

### Thumbnails missing after activation
- This is expected if "Remove All Sizes" is enabled
- Use `simplimg()` function to render images
- Or disable "Remove All" and regenerate thumbnails

## Migration & Deactivation

### Migrating Away

1. Go to Image Sizes tab
2. Enable "Regenerate on Deactivation"
3. Save settings
4. Deactivate plugin
5. Thumbnails will regenerate automatically

### Clean Uninstall

1. Clear all cache (Uploads tab)
2. Regenerate thumbnails (Image Sizes tab)
3. Deactivate plugin
4. Delete plugin files

## Support

For support or custom development:
- Website: https://simpliweb.com.au
- Plugin URI: https://simpliweb.com.au

## Changelog

### 1.1.0
- NEW: Tabbed settings interface
- NEW: Selective image size control grouped by plugin/theme
- NEW: Thumbnail regeneration tool
- NEW: Cache management in settings
- NEW: Auto-regenerate on deactivation option
- IMPROVED: Refactored code structure
- IMPROVED: Better WooCommerce compatibility

### 1.0.0
- Initial release
- Remove intermediate image sizes
- Max dimension resizing
- File size compression
- JPEG quality control
- Dynamic image generation with `simplimg()`
- Automatic caching

## License

GPL v2 or later
