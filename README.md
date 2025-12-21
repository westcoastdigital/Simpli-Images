# Simpli Images

A WordPress plugin for media library optimization that prevents intermediate image sizes, automatically resizes/compresses uploaded images, and provides dynamic image generation with caching.

## Version 1.3.0 - What's New

- **WebP Support**: Generate cached images in WebP format for 25-35% smaller file sizes
- **Improved Error Handling**: Better upload error detection and logging for troubleshooting
- **Flexible Upload Optimization**: Can disable upload processing to prevent errors on resource-constrained servers
- **Automatic Cache Clearing**: WebP setting changes automatically clear and regenerate cache
- **Enhanced Reliability**: More robust error handling and validation throughout the plugin
- **Better Documentation**: Comprehensive troubleshooting guides and recommended settings

## Features

- **Upload Optimization**: Automatically resize and compress images on upload (can be disabled)
- **WebP Support**: Generate cached images in WebP format for better compression
- **Remove Image Sizes**: Globally disable all intermediate sizes or selectively disable specific ones
- **Dynamic Image Generation**: Generate images at any size on-demand with `simplimg()` function
- **WordPress Function Override**: Automatically use optimized images for all WordPress image functions
- **Smart Caching**: Generated images are cached for fast subsequent loads
- **Flexible Cropping**: Multiple crop positions (center, top, bottom, corners)
- **Aspect Ratio Support**: Easy 16:9, 4:3, 1:1, etc. with automatic cropping
- **WooCommerce Safe**: Keep WooCommerce sizes while removing others
- **Theme Compatible**: Identify and selectively disable theme-specific image sizes
- **Error Logging**: Detailed error logging for troubleshooting upload issues

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
- **Note**: If experiencing upload errors, set to 0

**Max Image File Size**
- Default: 1.2MB
- Compresses images to stay under this limit
- Set to 0 to disable compression
- **Note**: If experiencing upload errors, set to 0

**JPEG Compression Quality**
- Default: 82%
- Starting quality for JPEG compression (1-100)
- Higher = better quality but larger files

**WebP Format** ⭐ NEW in 1.3.0
- Default: Disabled
- When enabled, cached images are saved in WebP format
- Typically 25-35% smaller than JPEG with same visual quality
- Only affects dynamically generated cached images, not uploaded originals
- Automatically clears cache when toggled to regenerate in new format
- Requires server WebP support (GD 2.0+ or Imagick)

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

**Override WordPress Image Functions** ⭐ NEW in 1.2.0
- Default: Enabled
- Automatically uses Simpli Images for all WordPress image requests
- Works with `wp_get_attachment_image()`, `the_post_thumbnail()`, `get_the_post_thumbnail()`, etc.
- No theme/plugin code changes needed
- Toggle off to use traditional WordPress image handling

**Selective Size Control** (when not removing all)
- Sizes grouped by source: WordPress Core, Theme, Plugins
- Checkbox interface to disable specific sizes
- Example: Keep WooCommerce sizes, disable theme sizes

**Thumbnail Regeneration**
- Regenerate all thumbnails manually
- Optional: Auto-regenerate on plugin deactivation
- Useful when switching themes or re-enabling sizes

## WordPress Function Override

### How It Works

When enabled, the plugin automatically intercepts all WordPress image function calls and serves dynamically generated, optimized images instead of pre-generated thumbnails.

**Supported Functions:**
- `wp_get_attachment_image()`
- `wp_get_attachment_image_src()`
- `wp_get_attachment_image_url()`
- `get_the_post_thumbnail()`
- `the_post_thumbnail()`
- Any function that uses `image_downsize` internally

### Automatic Size Mapping

The plugin intelligently handles WordPress's registered image sizes:

```php
// Your theme calls this
the_post_thumbnail('medium');

// Plugin automatically generates
// 800x600 optimized image (or whatever 'medium' is configured as)
// First request: generates and caches
// Subsequent requests: serves from cache
```

### Examples

**Before Plugin (Traditional WordPress):**
```php
// Requires pre-generated thumbnail files
the_post_thumbnail('thumbnail');           // Uses thumbnail-150x150.jpg
wp_get_attachment_image($id, 'medium');    // Uses medium-300x300.jpg
```

**After Plugin (Automatic Override):**
```php
// Same code, but uses dynamically generated images
the_post_thumbnail('thumbnail');           // Generates 150x150 on-demand
wp_get_attachment_image($id, 'medium');    // Generates 300x300 on-demand

// Also works with custom sizes
wp_get_attachment_image($id, array(500, 300));  // Generates 500x300
```

**Manual Control:**
```php
// Still have full control with simplimg()
simplimg($id, 16, 9, 'crop');              // 16:9 aspect ratio
simplimg($id, 400, 'auto', false);         // Scale to 400px width
```

### Benefits of Override Mode

1. **Zero Code Changes**: Works with existing themes and plugins
2. **Massive Storage Savings**: No pre-generated thumbnails needed
3. **Perfect for Multisite**: Consistent image handling across all sites
4. **Better Performance**: Only generates sizes actually used
5. **Flexible**: Can disable override and fall back to traditional mode

### When to Disable Override

- Using a caching plugin that pre-generates specific sizes
- Theme requires exact WordPress image size metadata
- Debugging image issues
- Prefer manual `simplimg()` calls for full control

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

**Scenario 1: E-commerce Site (Recommended)**
```
Remove All: OFF
Override WordPress Functions: ON
Disabled: All theme sizes, WordPress medium_large
Enabled: WooCommerce sizes, thumbnail, medium, large
```

**Scenario 2: Blog Site**
```
Remove All: ON
Override WordPress Functions: ON
Use dynamic images for everything
```

**Scenario 3: Mixed Content**
```
Remove All: OFF
Override WordPress Functions: ON
Disabled: Unused plugin sizes (Elementor if not using)
Enabled: Core sizes + active plugins
```

**Scenario 4: Maximum Control**
```
Remove All: ON
Override WordPress Functions: OFF
Use simplimg() manually in theme templates
```

## Cache Management

### How Caching Works

1. **First Request**: Image generated and saved to `/simpli-cache/`
2. **Subsequent Requests**: Served directly from cache (0ms PHP processing)
3. **Automatic Cleanup**: Cache cleared when attachment is deleted

### Cache Format

```
{image-id}-{width}x{height}-{crop}.{ext}
Examples: 
  123-300x300-crop.jpg (WebP disabled)
  123-300x300-crop.webp (WebP enabled)
  456-16x9-crop-top.webp
```

**Note:** File extension changes based on WebP setting. When you toggle WebP on/off, cache is automatically cleared and regenerated in the new format.

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

### Recommended for Most Sites (Safest)
```
Max Dimension: 0 (disabled - no upload errors)
Max File Size: 0 (disabled - no upload errors)
JPEG Quality: 82%
WebP Format: ON (if supported)
Remove All Sizes: ON
Override WordPress Functions: ON
```
**Why:** Upload processing disabled = no upload errors. All optimization happens on-demand when images are first viewed. Most reliable approach.

### Photography Website
```
Max Dimension: 2400px (high-quality display)
Max File Size: 3MB
JPEG Quality: 90%
WebP Format: OFF (maintain quality)
Remove All Sizes: ON
Override WordPress Functions: ON
```
**Note:** Requires powerful server. For shared hosting, use "Most Sites" settings above.

### Blog/Content Site
```
Max Dimension: 0 (disabled for reliability)
Max File Size: 0 (disabled for reliability)
JPEG Quality: 82%
WebP Format: ON
Remove All Sizes: ON
Override WordPress Functions: ON
```

### eCommerce Site (WooCommerce)
```
Max Dimension: 0 (disabled for reliability)
Max File Size: 0 (disabled for reliability)
JPEG Quality: 85%
WebP Format: ON (if supported)
Remove All Sizes: OFF
Override WordPress Functions: ON
Disabled: Theme sizes only
Enabled: WooCommerce + Core sizes
```

### Shared Hosting / Limited Resources
```
Max Dimension: 0 (IMPORTANT - prevents upload errors)
Max File Size: 0 (IMPORTANT - prevents upload errors)
JPEG Quality: 82%
WebP Format: Check server support first
Remove All Sizes: ON
Override WordPress Functions: ON
```
**Critical:** Low memory limits require upload optimization disabled.

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

**Upload Processing:**
- **JPEG/JPG**: Full optimization with quality control
- **PNG**: Dimension resizing, transparency preserved
- **GIF**: Dimension resizing, animation preserved
- **WebP**: Basic upload handling

**Cached Image Generation:**
- All formats can be generated as WebP (if enabled and supported)
- Original upload format preserved in Media Library
- Only cached/dynamic images affected by WebP setting

### WebP Compatibility

**Browser Support:**
- Chrome 23+, Firefox 65+, Edge 18+, Safari 14+, Opera 12.1+
- ~95% of global browser usage (2024)

**Server Requirements:**
- GD Library: PHP 5.5+ with --with-vpx-dir flag
- Imagick: ImageMagick 6.3.7+
- Check: Plugin automatically detects support

**Benefits:**
- 25-35% smaller file sizes vs JPEG
- Same visual quality
- Faster page loads
- Less bandwidth usage

### Performance

**Upload Processing:** <1 second per image (if enabled)  
**First Dynamic Request:** 100-200ms (generation + cache)  
**Cached Requests:** 0ms (direct file access)  
**Override Mode:** ~5ms (cache check + return URL)  
**WebP Generation:** Same speed as JPEG, smaller output files

### Compatibility

- WordPress 5.0+
- PHP 7.0+
- Works with: Media Library, Featured Images, ACF Image fields
- Compatible with: Most page builders, WooCommerce, Elementor
- Override mode compatible with: All themes using standard WordPress image functions

### Server Requirements

**Minimum:**
- GD or Imagick PHP extension
- PHP memory_limit: 128M (256M for upload optimization)
- Write permissions to `/wp-content/uploads/`

**Recommended:**
- Imagick extension (better than GD)
- PHP memory_limit: 256M+ (especially for upload optimization)
- PHP max_execution_time: 300
- WebP support for smaller cached files

**For Upload Optimization:**
- Requires more memory and processing power
- Not recommended for shared hosting
- Set to 0/0 if experiencing upload errors
- On-demand optimization works on any server

## Troubleshooting

### Upload errors: "Server cannot process the image" ⚠️ COMMON ISSUE
**Quick Fix:**
1. Go to Settings > Simpli Images > Uploads
2. Set "Max Image Dimension" to **0**
3. Set "Max Image File Size" to **0**
4. Save settings and try uploading again

**Why this happens:**
- Upload optimization can fail on servers with low memory
- Common on shared hosting
- Large images require more processing power

**Solution:**
- Disable upload optimization (set both to 0)
- All optimization happens on-demand instead
- Much more reliable, no upload errors
- You still get all other plugin benefits

**If you want upload optimization:**
- Increase PHP memory_limit to 256M or higher
- Test with smaller images first
- Gradually increase dimension/size limits
- See `UPLOAD_TROUBLESHOOTING.md` for detailed diagnostics

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
- Verify "Override WordPress Functions" is ON
- Regenerate thumbnails after changing settings

### Thumbnails missing after activation
- This is expected if "Remove All Sizes" is enabled
- Enable "Override WordPress Functions" for automatic handling
- Or use `simplimg()` function to render images
- Or disable "Remove All" and regenerate thumbnails

### Theme images not showing with override enabled
- Check that image sizes are registered in WordPress
- Try disabling and re-enabling override
- Clear cache and reload page
- Check browser console for 404 errors

### Override mode not working
- Verify "Override WordPress Functions" is checked and saved
- Check that theme uses standard WordPress image functions
- Clear all caches (plugin cache, page cache, browser cache)
- Test with a default theme to isolate theme-specific issues

### WebP images not generating
- Check if your server supports WebP
- Go to Settings > Simpli Images > Uploads
- Disable WebP Format checkbox
- Enable WordPress debug logging to check for errors
- GD library needs WebP support (PHP 5.5+ with --with-vpx-dir)
- Or Imagick extension with ImageMagick 6.3.7+

### Cache not clearing when toggling WebP
- This should happen automatically
- If not, manually clear cache via Settings > Uploads tab
- Or use `simplimg_clear_all_cache()` function

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

### 1.3.0
- NEW: WebP format support for cached images (25-35% smaller files)
- NEW: Automatic cache clearing when WebP setting is toggled
- NEW: Comprehensive error handling and logging for upload optimization
- NEW: Option to disable upload optimization to prevent errors
- IMPROVED: More robust error handling throughout the plugin
- IMPROVED: Better validation in image processing
- IMPROVED: Detailed troubleshooting documentation
- FIX: Upload errors on servers with limited resources
- FIX: Proper WebP mime type handling
- FIX: Memory and timeout issues during upload processing

### 1.2.0
- NEW: Override WordPress image functions (wp_get_attachment_image, the_post_thumbnail, etc.)
- NEW: Automatic dynamic image generation for all WordPress image requests
- NEW: Toggle to enable/disable WordPress function override
- IMPROVED: Seamless integration with existing themes and plugins
- IMPROVED: Zero code changes needed for theme compatibility

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