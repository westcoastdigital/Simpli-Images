<?php
/**
 * Simpli Images Helper Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simpli_Images_Helpers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generate dynamic image at specified size
     * 
     * @param int $image_id Attachment ID
     * @param string|int $width Width in pixels or aspect ratio number
     * @param string|int $height Height in pixels, 'auto', or aspect ratio number
     * @param string $crop Crop mode: 'crop', 'crop-top', etc., or false for scale
     * @return string|false Image URL or false on failure
     */
    public function generate_dynamic_image($image_id, $width, $height = 'auto', $crop = false) {
        // Get original image path
        $original_path = get_attached_file($image_id);
        
        if (!$original_path || !file_exists($original_path)) {
            return false;
        }
        
        // Parse dimensions
        $dimensions = $this->parse_dimensions($original_path, $width, $height);
        
        if (!$dimensions) {
            return false;
        }
        
        $target_width = $dimensions['width'];
        $target_height = $dimensions['height'];
        
        // Generate cache key
        $cache_key = $this->generate_cache_key($image_id, $target_width, $target_height, $crop);
        
        // Check if cached version exists
        $cached_url = $this->get_cached_image_url($cache_key);
        
        if ($cached_url) {
            return $cached_url;
        }
        
        // Generate new image
        $image_editor = wp_get_image_editor($original_path);
        
        if (is_wp_error($image_editor)) {
            return false;
        }
        
        $original_size = $image_editor->get_size();
        
        // Handle cropping
        if ($crop !== false) {
            $cropped = $this->crop_image($image_editor, $original_size, $target_width, $target_height, $crop);
            
            if (is_wp_error($cropped)) {
                return false;
            }
        } else {
            // Scale to fit
            $image_editor->resize($target_width, $target_height, false);
        }
        
        // Save cached image
        $saved_image = $this->save_cached_image($image_editor, $cache_key, $original_path);
        
        if (is_wp_error($saved_image) || !$saved_image) {
            return false;
        }
        
        return $saved_image['url'];
    }
    
    /**
     * Parse dimensions from input parameters
     */
    private function parse_dimensions($original_path, $width, $height) {
        // Get original dimensions
        $image_size = getimagesize($original_path);
        
        if (!$image_size) {
            return false;
        }
        
        $original_width = $image_size[0];
        $original_height = $image_size[1];
        
        // Remove 'px' suffix if present
        $width = str_replace('px', '', $width);
        $height = str_replace('px', '', $height);
        
        // Handle aspect ratio (e.g., '16', '9')
        if (is_numeric($width) && is_numeric($height) && $height != 'auto') {
            // Check if these are aspect ratio numbers (small numbers like 16:9, 4:3)
            if ($width <= 21 && $height <= 21) {
                // Assume aspect ratio, calculate actual dimensions based on original
                $aspect_ratio = $width / $height;
                
                if ($original_width / $original_height > $aspect_ratio) {
                    // Original is wider, constrain by height
                    $target_height = $original_height;
                    $target_width = round($original_height * $aspect_ratio);
                } else {
                    // Original is taller, constrain by width
                    $target_width = $original_width;
                    $target_height = round($original_width / $aspect_ratio);
                }
                
                return array(
                    'width' => $target_width,
                    'height' => $target_height
                );
            }
        }
        
        // Handle auto height
        if ($height === 'auto') {
            $target_width = intval($width);
            $aspect_ratio = $original_height / $original_width;
            $target_height = round($target_width * $aspect_ratio);
        } else {
            $target_width = intval($width);
            $target_height = intval($height);
        }
        
        return array(
            'width' => $target_width,
            'height' => $target_height
        );
    }
    
    /**
     * Crop image based on position
     */
    private function crop_image($image_editor, $original_size, $target_width, $target_height, $crop_position) {
        $original_width = $original_size['width'];
        $original_height = $original_size['height'];
        
        // Calculate crop dimensions to maintain aspect ratio
        $target_ratio = $target_width / $target_height;
        $original_ratio = $original_width / $original_height;
        
        if ($original_ratio > $target_ratio) {
            // Original is wider, crop width
            $crop_height = $original_height;
            $crop_width = round($original_height * $target_ratio);
        } else {
            // Original is taller, crop height
            $crop_width = $original_width;
            $crop_height = round($original_width / $target_ratio);
        }
        
        // Determine crop position
        $x = 0;
        $y = 0;
        
        switch ($crop_position) {
            case 'crop':
            case 'crop-center':
            case 'center':
                $x = round(($original_width - $crop_width) / 2);
                $y = round(($original_height - $crop_height) / 2);
                break;
                
            case 'crop-top':
            case 'top':
                $x = round(($original_width - $crop_width) / 2);
                $y = 0;
                break;
                
            case 'crop-bottom':
            case 'bottom':
                $x = round(($original_width - $crop_width) / 2);
                $y = $original_height - $crop_height;
                break;
                
            case 'crop-left':
            case 'left':
                $x = 0;
                $y = round(($original_height - $crop_height) / 2);
                break;
                
            case 'crop-right':
            case 'right':
                $x = $original_width - $crop_width;
                $y = round(($original_height - $crop_height) / 2);
                break;
                
            case 'crop-top-left':
            case 'top-left':
                $x = 0;
                $y = 0;
                break;
                
            case 'crop-top-right':
            case 'top-right':
                $x = $original_width - $crop_width;
                $y = 0;
                break;
                
            case 'crop-bottom-left':
            case 'bottom-left':
                $x = 0;
                $y = $original_height - $crop_height;
                break;
                
            case 'crop-bottom-right':
            case 'bottom-right':
                $x = $original_width - $crop_width;
                $y = $original_height - $crop_height;
                break;
        }
        
        // Crop the image
        $result = $image_editor->crop($x, $y, $crop_width, $crop_height, $target_width, $target_height);
        
        return $result;
    }
    
    /**
     * Generate cache key for image
     */
    private function generate_cache_key($image_id, $width, $height, $crop) {
        $crop_suffix = $crop ? '-' . sanitize_title($crop) : '';
        return $image_id . '-' . $width . 'x' . $height . $crop_suffix;
    }
    
    /**
     * Get cached image URL if exists
     */
    private function get_cached_image_url($cache_key) {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/simpli-cache/';
        
        if (!is_dir($cache_dir)) {
            return false;
        }
        
        // Find file with this cache key
        $files = glob($cache_dir . $cache_key . '.*');
        
        if (!empty($files) && file_exists($files[0])) {
            $file_name = basename($files[0]);
            return $upload_dir['baseurl'] . '/simpli-cache/' . $file_name;
        }
        
        return false;
    }
    
    /**
     * Save cached image
     */
    private function save_cached_image($image_editor, $cache_key, $original_path = '') {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/simpli-cache/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
            
            // Add .htaccess to allow direct access
            $htaccess_content = "<IfModule mod_rewrite.c>\n";
            $htaccess_content .= "RewriteEngine Off\n";
            $htaccess_content .= "</IfModule>\n";
            file_put_contents($cache_dir . '.htaccess', $htaccess_content);
        }
        
        // Get file extension from original file
        if ($original_path && file_exists($original_path)) {
            $extension = strtolower(pathinfo($original_path, PATHINFO_EXTENSION));
        } else {
            $extension = 'jpg'; // Default fallback
        }
        
        // Ensure valid extension
        $valid_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($extension, $valid_extensions)) {
            $extension = 'jpg';
        }
        
        $file_name = $cache_key . '.' . $extension;
        $file_path = $cache_dir . $file_name;
        
        // Save the image
        $saved = $image_editor->save($file_path);
        
        if (is_wp_error($saved)) {
            return $saved;
        }
        
        return array(
            'path' => $file_path,
            'url' => $upload_dir['baseurl'] . '/simpli-cache/' . $file_name
        );
    }
}

// Initialize helpers
Simpli_Images_Helpers::get_instance();

/**
 * Helper function to generate dynamic images
 * 
 * @param int $image_id Attachment ID
 * @param string|int $width Width (e.g., '150px', '150', '16' for aspect ratio)
 * @param string|int $height Height (e.g., '150px', 'auto', '9' for aspect ratio)
 * @param string|bool $crop Crop position: 'crop', 'top', 'bottom', 'left', 'right', false for scale
 * @param bool $echo Whether to echo or return the URL
 * @return string|void Image URL or echoes if $echo is true
 */
function simplimg($image_id, $width, $height = 'auto', $crop = false, $echo = true) {
    $instance = Simpli_Images_Helpers::get_instance();
    $url = $instance->generate_dynamic_image($image_id, $width, $height, $crop);
    
    if ($echo) {
        echo esc_url($url);
    } else {
        return $url;
    }
}

/**
 * Clear cached images for specific attachment
 * 
 * @param int $image_id Attachment ID
 */
function simplimg_clear_cache($image_id) {
    $instance = Simpli_Images::get_instance();
    $instance->clear_image_cache($image_id);
}

/**
 * Clear all cached images
 * 
 * @return int Number of files deleted
 */
function simplimg_clear_all_cache() {
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/simpli-cache/';
    
    if (!is_dir($cache_dir)) {
        return 0;
    }
    
    $files = glob($cache_dir . '*');
    $deleted = 0;
    
    foreach ($files as $file) {
        if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'htaccess') {
            @unlink($file);
            $deleted++;
        }
    }
    
    return $deleted;
}
