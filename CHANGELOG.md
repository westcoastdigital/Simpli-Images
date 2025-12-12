# Simpli Images - Changelog

## Version 1.1.3 (AJAX Implementation)

**Changed:**
- Image Sizes tab now uses AJAX for saving settings (more reliable)
- Real-time save feedback with success/error messages
- Spinner indicator during save operation
- No page refresh required when saving settings

**Fixed:**
- Image Sizes tab settings now save reliably
- Better error handling and user feedback
- Improved form validation

**Technical Changes:**
- Added `ajax_save_sizes()` method for AJAX handling
- Converted sizes form to AJAX submission
- Added jQuery AJAX handler with proper error handling
- Enhanced CSS for save feedback UI

## Version 1.1.2 (Critical Bug Fix)

**Fixed:**
- CRITICAL: "Save Image Size Settings" button now works correctly
- Fixed nested form structure that prevented Image Sizes tab from saving
- Moved "Regenerate Thumbnails" form outside the settings form
- Improved form validation and submission handling

**Technical Changes:**
- Restructured render_sizes_tab() to separate settings form from action forms
- Updated admin.js to properly handle regenerate confirmation
- Added CSS styling for regenerate thumbnails section

## Version 1.1.1 (Bug Fixes)

**Fixed:**
- Duplicate "Settings Saved" notice no longer appears when saving Upload Settings
- Image Sizes tab settings now save correctly and stay on the correct tab
- Settings page now properly preserves tab parameter when saving settings

**Technical Changes:**
- Added `preserve_tab_on_redirect()` filter to maintain tab state during form submissions
- Improved settings error handling to suppress WordPress default messages
- Better form routing for tabbed settings interface

## Version 1.1.0

**New Features:**
- Tabbed settings interface (Uploads, Image Sizes)
- Selective image size control grouped by plugin/theme
- Thumbnail regeneration tool
- Cache management with statistics
- Auto-regenerate on deactivation option
- Refactored code structure with separate files

**Added:**
- `inc/Settings.php` - Settings page and admin UI
- `inc/helpers.php` - Dynamic image generation functions
- `assets/admin.css` - Admin styles
- `assets/admin.js` - Admin JavaScript

## Version 1.0.0

- Initial release
- Remove intermediate image sizes
- Max dimension resizing
- File size compression
- JPEG quality control
- Dynamic image generation with `simplimg()`
- Automatic caching
