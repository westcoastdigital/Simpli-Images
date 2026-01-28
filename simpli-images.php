<?php
/*
Plugin Name:  Simpli Images
Plugin URI:   https://simpliweb.com.au
Description:  WordPress media optimiser
Version:      1.3.0
Author:       SimpliWeb
Author URI:   https://simpliweb.com.au
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  simpli-images
Domain Path:  /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIMPLI_IMAGES_VERSION', '1.3.0');
define('SIMPLI_IMAGES_PATH', plugin_dir_path(__FILE__));
define('SIMPLI_IMAGES_URL', plugin_dir_url(__FILE__));

// Include required files
require_once SIMPLI_IMAGES_PATH . 'inc/Settings.php';
require_once SIMPLI_IMAGES_PATH . 'inc/helpers.php';

class Simpli_Images
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Disable intermediate image sizes
        add_filter('intermediate_image_sizes_advanced', array($this, 'disable_image_sizes'));

        // Handle image upload and optimization
        add_filter('wp_handle_upload_prefilter', array($this, 'handle_upload_prefilter'));
        add_filter('wp_handle_upload', array($this, 'optimize_uploaded_image'));

        // Add settings link on plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));

        // Clear cache when attachment is deleted
        add_action('delete_attachment', array($this, 'clear_image_cache'));

        // Activation/Deactivation hooks
        register_deactivation_hook(__FILE__, array($this, 'on_deactivation'));

        // WordPress default image functions override
        add_filter('image_downsize', array($this, 'intercept_image_downsize'), 10, 3);
    }

    /**
     * Disable intermediate image sizes based on settings
     */
    public function disable_image_sizes($sizes)
    {
        // Check if globally disabled
        if (get_option('simpli_images_remove_sizes', true)) {
            return array();
        }

        // Get disabled sizes
        $disabled_sizes = get_option('simpli_images_disabled_sizes', array());

        if (!empty($disabled_sizes) && is_array($disabled_sizes)) {
            foreach ($disabled_sizes as $size_name) {
                unset($sizes[$size_name]);
            }
        }

        return $sizes;
    }

    /**
     * Pre-filter upload to check file type
     */
    public function handle_upload_prefilter($file)
    {
        // Only process images
        if (strpos($file['type'], 'image/') !== 0) {
            return $file;
        }

        return $file;
    }

    /**
     * Optimize uploaded image
     */
    public function optimize_uploaded_image($upload)
    {
        if (!isset($upload['file']) || !isset($upload['type'])) {
            return $upload;
        }

        // Only process images
        if (strpos($upload['type'], 'image/') !== 0) {
            return $upload;
        }

        $file_path = $upload['file'];
        
        // Check if file exists before processing
        if (!file_exists($file_path)) {
            return $upload;
        }
        
        $max_dimension = absint(get_option('simpli_images_max_dimension', 1200));
        $max_size_mb = floatval(get_option('simpli_images_max_size', 1.2));
        $jpeg_quality = absint(get_option('simpli_images_jpeg_quality', 82));

        // If all optimization is disabled, just delete -scaled and return
        if ($max_dimension == 0 && $max_size_mb == 0) {
            $this->delete_scaled_image($file_path);
            return $upload;
        }

        // Get image editor
        $image_editor = wp_get_image_editor($file_path);

        if (is_wp_error($image_editor)) {
            // If image editor fails, just return the upload as-is
            error_log('Simpli Images: Image editor error - ' . $image_editor->get_error_message());
            return $upload;
        }

        $current_size = $image_editor->get_size();
        
        // Validate size was retrieved
        if (!$current_size || !isset($current_size['width']) || !isset($current_size['height'])) {
            error_log('Simpli Images: Could not get image dimensions');
            return $upload;
        }
        
        $needs_resize = false;

        // Check if resizing is needed based on dimensions
        if ($max_dimension > 0) {
            $max_current = max($current_size['width'], $current_size['height']);

            if ($max_current > $max_dimension) {
                $needs_resize = true;

                if ($current_size['width'] > $current_size['height']) {
                    $new_width = $max_dimension;
                    $new_height = intval($current_size['height'] * ($max_dimension / $current_size['width']));
                } else {
                    $new_height = $max_dimension;
                    $new_width = intval($current_size['width'] * ($max_dimension / $current_size['height']));
                }

                $resize_result = $image_editor->resize($new_width, $new_height, false);
                
                // Check if resize failed
                if (is_wp_error($resize_result)) {
                    error_log('Simpli Images: Resize error - ' . $resize_result->get_error_message());
                    return $upload;
                }
            }
        }

        // Set JPEG quality
        if (strpos($upload['type'], 'jpeg') !== false || strpos($upload['type'], 'jpg') !== false) {
            $image_editor->set_quality($jpeg_quality);
        }

        // Save the optimized image
        if ($needs_resize) {
            // Save in the ORIGINAL format, not WebP (WebP is only for cached images)
            $saved = $image_editor->save($file_path);

            if (is_wp_error($saved)) {
                error_log('Simpli Images: Save error - ' . $saved->get_error_message());
                return $upload;
            }
            
            // Verify file still exists after save
            if (!file_exists($file_path)) {
                error_log('Simpli Images: File disappeared after save');
                return $upload;
            }
        }

        // Check file size and compress if needed
        if ($max_size_mb > 0) {
            $max_size_bytes = $max_size_mb * 1024 * 1024;
            $current_file_size = @filesize($file_path);
            
            // Verify filesize worked
            if ($current_file_size === false) {
                error_log('Simpli Images: Could not get file size');
                return $upload;
            }

            if ($current_file_size > $max_size_bytes) {
                // Reload the image editor if we already saved
                if ($needs_resize) {
                    $image_editor = wp_get_image_editor($file_path);
                    if (is_wp_error($image_editor)) {
                        error_log('Simpli Images: Image editor reload error - ' . $image_editor->get_error_message());
                        return $upload;
                    }
                }

                // For JPEGs, reduce quality to meet file size
                if (strpos($upload['type'], 'jpeg') !== false || strpos($upload['type'], 'jpg') !== false) {
                    $quality = $jpeg_quality;
                    $attempts = 0;

                    while ($current_file_size > $max_size_bytes && $quality > 20 && $attempts < 10) {
                        $quality -= 5;
                        $image_editor->set_quality($quality);
                        
                        $save_result = $image_editor->save($file_path);
                        
                        if (is_wp_error($save_result)) {
                            error_log('Simpli Images: Compression save error - ' . $save_result->get_error_message());
                            break; // Stop trying if save fails
                        }
                        
                        $current_file_size = @filesize($file_path);
                        
                        if ($current_file_size === false) {
                            error_log('Simpli Images: Could not get file size during compression');
                            break;
                        }
                        
                        $attempts++;
                    }
                }
            }
        }

        // Delete the -scaled version if it exists
        $this->delete_scaled_image($file_path);
        
        // Final verification that file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            error_log('Simpli Images: Final file check failed');
            return $upload;
        }

        return $upload;
    }

    /**
     * Delete WordPress's auto-generated -scaled image
     */
    private function delete_scaled_image($file_path)
    {
        $path_info = pathinfo($file_path);
        $scaled_path = $path_info['dirname'] . '/' . $path_info['filename'] . '-scaled.' . $path_info['extension'];

        if (file_exists($scaled_path)) {
            @unlink($scaled_path);
        }
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="upload.php?page=simpli-images">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Clear cached images for a specific attachment
     */
    public function clear_image_cache($image_id)
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/simpli-cache/';

        if (!is_dir($cache_dir)) {
            return;
        }

        // Remove all cached versions of this image
        $files = glob($cache_dir . $image_id . '-*');

        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * On plugin deactivation
     */
    public function on_deactivation()
    {
        // Check if user wants to regenerate thumbnails on deactivation
        if (get_option('simpli_images_regenerate_on_deactivation', false)) {
            // Temporarily remove our filter to restore WordPress defaults
            remove_filter('intermediate_image_sizes_advanced', array($this, 'disable_image_sizes'));

            // Regenerate thumbnails for all images
            $args = array(
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'post_status' => 'inherit',
                'posts_per_page' => -1
            );

            $attachments = get_posts($args);

            foreach ($attachments as $attachment) {
                $file = get_attached_file($attachment->ID);

                if ($file && file_exists($file)) {
                    // Delete existing thumbnails
                    $metadata = wp_get_attachment_metadata($attachment->ID);
                    if (!empty($metadata['sizes'])) {
                        foreach ($metadata['sizes'] as $size => $sizeinfo) {
                            $intermediate_file = str_replace(basename($file), $sizeinfo['file'], $file);
                            if (file_exists($intermediate_file)) {
                                @unlink($intermediate_file);
                            }
                        }
                    }

                    // Regenerate with WordPress defaults
                    $metadata = wp_generate_attachment_metadata($attachment->ID, $file);
                    wp_update_attachment_metadata($attachment->ID, $metadata);
                }
            }
        }
    }
    /**
     * Intercept WordPress image requests and serve optimized versions
     * Add this method to your Simpli_Images class
     */
    public function intercept_image_downsize($out, $id, $size)
    {
        // Return false to let WordPress handle non-images
        if (wp_attachment_is('image', $id) === false) {
            return false;
        }

        // Handle different size formats
        $width = null;
        $height = null;
        $crop = false;

        if (is_array($size)) {
            // Array format: array(width, height) or array(width, height, crop)
            $width = isset($size[0]) ? $size[0] : null;
            $height = isset($size[1]) ? $size[1] : 'auto';
            $crop = isset($size[2]) ? $size[2] : false;
        } elseif (is_string($size)) {
            // Named size like 'thumbnail', 'medium', 'large'
            $image_sizes = wp_get_registered_image_subsizes();

            if (isset($image_sizes[$size])) {
                $width = $image_sizes[$size]['width'];
                $height = $image_sizes[$size]['height'];
                $crop = $image_sizes[$size]['crop'] ? 'crop' : false;
            } else {
                // Unknown size name, let WordPress handle it
                return false;
            }
        } else {
            // Invalid size parameter
            return false;
        }

        // If no valid dimensions, return false
        if (!$width) {
            return false;
        }

        // Generate optimized image using your existing method
        $helpers = Simpli_Images_Helpers::get_instance();
        $url = $helpers->generate_dynamic_image($id, $width, $height, $crop);

        if (!$url) {
            // Failed to generate, let WordPress handle it
            return false;
        }

        // Parse dimensions for return
        if ($height === 'auto') {
            // Calculate actual height based on original aspect ratio
            $original_path = get_attached_file($id);
            $image_size = getimagesize($original_path);

            if ($image_size) {
                $aspect_ratio = $image_size[1] / $image_size[0];
                $actual_height = round($width * $aspect_ratio);
            } else {
                $actual_height = $width; // Fallback
            }
        } else {
            $actual_height = $height;
        }

        // Return array in WordPress expected format
        return array(
            $url,           // Image URL
            $width,         // Width
            $actual_height, // Height
            true            // Is intermediate size
        );
    }
}

// Initialize the plugin
Simpli_Images::get_instance();