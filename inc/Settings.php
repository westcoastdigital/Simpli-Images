<?php
/**
 * Simpli Images Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simpli_Images_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_simplimg_clear_cache', array($this, 'handle_clear_cache'));
        add_action('admin_post_simplimg_regenerate_thumbnails', array($this, 'handle_regenerate_thumbnails'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_simpli_save_sizes', array($this, 'ajax_save_sizes'));
        
        // Add redirect handler to preserve tab parameter
        add_filter('wp_redirect', array($this, 'preserve_tab_on_redirect'), 10, 2);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_simpli-images' !== $hook) {
            return;
        }
        
        wp_enqueue_style('simpli-images-admin', SIMPLI_IMAGES_URL . 'assets/admin.css', array(), SIMPLI_IMAGES_VERSION);
        wp_enqueue_script('simpli-images-admin', SIMPLI_IMAGES_URL . 'assets/admin.js', array('jquery'), SIMPLI_IMAGES_VERSION, true);
        
        wp_localize_script('simpli-images-admin', 'simpliImages', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simpli_images_ajax')
        ));
    }
    
    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page() {
        add_options_page(
            'Simpli Images Settings',
            'Simpli Images',
            'manage_options',
            'simpli-images',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Uploads tab settings
        register_setting('simpli_images_uploads', 'simpli_images_max_dimension', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 1200
        ));
        
        register_setting('simpli_images_uploads', 'simpli_images_max_size', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_file_size'),
            'default' => 1.2
        ));
        
        register_setting('simpli_images_uploads', 'simpli_images_jpeg_quality', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_quality'),
            'default' => 82
        ));
        
        // Sizes tab settings
        register_setting('simpli_images_sizes', 'simpli_images_remove_sizes', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        register_setting('simpli_images_sizes', 'simpli_images_disabled_sizes', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_disabled_sizes'),
            'default' => array()
        ));
        
        register_setting('simpli_images_sizes', 'simpli_images_regenerate_on_deactivation', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
    }
    
    /**
     * Sanitize file size input
     */
    public function sanitize_file_size($value) {
        $value = floatval($value);
        return ($value > 0) ? $value : 1.2;
    }
    
    /**
     * Sanitize JPEG quality
     */
    public function sanitize_quality($value) {
        $value = absint($value);
        if ($value < 1) return 82;
        if ($value > 100) return 100;
        return $value;
    }
    
    /**
     * Sanitize disabled sizes array
     */
    public function sanitize_disabled_sizes($value) {
        if (!is_array($value)) {
            return array();
        }
        return array_map('sanitize_text_field', $value);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'uploads';
        
        // Suppress default WordPress settings messages (we show our own custom messages)
        if (isset($_GET['settings-updated'])) {
            // Clear the default 'settings' errors that WordPress adds
            global $wp_settings_errors;
            if (isset($wp_settings_errors) && is_array($wp_settings_errors)) {
                foreach ($wp_settings_errors as $key => $error) {
                    if (isset($error['setting']) && $error['setting'] === 'general') {
                        unset($wp_settings_errors[$key]);
                    }
                }
            }
        }
        
        // Handle messages
        $this->display_admin_messages();
        
        ?>
        <div class="wrap simpli-images-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=simpli-images&tab=uploads" class="nav-tab <?php echo $active_tab === 'uploads' ? 'nav-tab-active' : ''; ?>">
                    Uploads
                </a>
                <a href="?page=simpli-images&tab=sizes" class="nav-tab <?php echo $active_tab === 'sizes' ? 'nav-tab-active' : ''; ?>">
                    Image Sizes
                </a>
            </h2>
            
            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'sizes':
                        $this->render_sizes_tab();
                        break;
                    case 'uploads':
                    default:
                        $this->render_uploads_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display admin messages
     */
    private function display_admin_messages() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('simpli_images_messages', 'simpli_images_message', 'Settings Saved', 'updated');
        }
        
        if (isset($_GET['cache-cleared'])) {
            $deleted = intval($_GET['cache-cleared']);
            add_settings_error('simpli_images_messages', 'simpli_images_cache_message', 
                sprintf('Cache cleared! %d cached images deleted.', $deleted), 'updated');
        }
        
        if (isset($_GET['thumbnails-regenerated'])) {
            $count = intval($_GET['thumbnails-regenerated']);
            add_settings_error('simpli_images_messages', 'simpli_images_regen_message', 
                sprintf('Thumbnails regenerated for %d images.', $count), 'updated');
        }
        
        if (isset($_GET['thumbnails-error'])) {
            add_settings_error('simpli_images_messages', 'simpli_images_regen_error', 
                'Error regenerating thumbnails. Please try again.', 'error');
        }
        
        settings_errors('simpli_images_messages');
    }
    
    /**
     * Preserve tab parameter when redirecting after settings save
     */
    public function preserve_tab_on_redirect($location, $status) {
        // Only modify redirects to our settings page
        if (strpos($location, 'page=simpli-images') === false) {
            return $location;
        }
        
        // If we're saving settings, preserve the tab
        if (isset($_POST['option_page'])) {
            $tab = '';
            
            // Determine which tab based on the option_page
            if ($_POST['option_page'] === 'simpli_images_uploads') {
                $tab = 'uploads';
            } elseif ($_POST['option_page'] === 'simpli_images_sizes') {
                $tab = 'sizes';
            }
            
            // Add tab parameter if not already present
            if ($tab && strpos($location, 'tab=') === false) {
                $location = add_query_arg('tab', $tab, $location);
            }
        }
        
        return $location;
    }
    
    /**
     * Render Uploads tab
     */
    private function render_uploads_tab() {
        ?>
        <form action="options.php" method="post">
            <?php settings_fields('simpli_images_uploads'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="simpli_images_max_dimension">Max Image Dimension</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="simpli_images_max_dimension" 
                               name="simpli_images_max_dimension" 
                               value="<?php echo esc_attr(get_option('simpli_images_max_dimension', 1200)); ?>" 
                               min="0" 
                               step="1" 
                               class="regular-text">
                        <p class="description">
                            Maximum dimension in pixels (applies to longest edge). Set to 0 to disable resizing.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="simpli_images_max_size">Max Image File Size</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="simpli_images_max_size" 
                               name="simpli_images_max_size" 
                               value="<?php echo esc_attr(get_option('simpli_images_max_size', 1.2)); ?>" 
                               min="0" 
                               step="0.1" 
                               class="regular-text">
                        <p class="description">
                            Maximum file size in MB. Images will be compressed to meet this limit. Set to 0 to disable.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="simpli_images_jpeg_quality">JPEG Compression Quality</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="simpli_images_jpeg_quality" 
                               name="simpli_images_jpeg_quality" 
                               value="<?php echo esc_attr(get_option('simpli_images_jpeg_quality', 82)); ?>" 
                               min="1" 
                               max="100" 
                               step="1" 
                               class="regular-text">
                        <p class="description">
                            Quality for JPEG compression (1-100). Default: 82. Higher = better quality but larger file size.
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Upload Settings'); ?>
        </form>
        
        <hr>
        
        <h2>Cache Management</h2>
        <p>Dynamic images generated with <code>simplimg()</code> are cached for performance.</p>
        
        <?php
        $cache_stats = $this->get_cache_stats();
        ?>
        
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong>Cached Images:</strong></td>
                    <td><?php echo number_format($cache_stats['count']); ?> files</td>
                </tr>
                <tr>
                    <td><strong>Cache Size:</strong></td>
                    <td><?php echo size_format($cache_stats['size']); ?></td>
                </tr>
                <tr>
                    <td><strong>Cache Location:</strong></td>
                    <td><code>/wp-content/uploads/simpli-cache/</code></td>
                </tr>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                <?php wp_nonce_field('simplimg_clear_cache', 'simplimg_cache_nonce'); ?>
                <input type="hidden" name="action" value="simplimg_clear_cache">
                <button type="submit" class="button button-secondary" onclick="return confirm('Are you sure you want to clear all cached images? They will be regenerated on next use.');">
                    Clear All Cache
                </button>
            </form>
        </p>
        <?php
    }
    
    /**
     * Render Sizes tab
     */
    private function render_sizes_tab() {
        $remove_all_sizes = get_option('simpli_images_remove_sizes', true);
        $disabled_sizes = get_option('simpli_images_disabled_sizes', array());
        $all_sizes = $this->get_all_image_sizes_grouped();
        
        ?>
        <form id="simpli-sizes-form">
            <?php wp_nonce_field('simpli_images_sizes_save', 'simpli_sizes_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="simpli_images_remove_sizes">Remove All Image Sizes</label>
                    </th>
                    <td>
                        <input type="checkbox" 
                               id="simpli_images_remove_sizes" 
                               name="simpli_images_remove_sizes" 
                               value="1" 
                               <?php checked($remove_all_sizes, true); ?>>
                        <p class="description">
                            Prevent WordPress from creating <strong>all</strong> intermediate image sizes. 
                            Only the optimized original will be stored. Use <code>simplimg()</code> for dynamic sizing.
                        </p>
                    </td>
                </tr>
            </table>
            
            <div id="selective-sizes" style="<?php echo $remove_all_sizes ? 'display:none;' : ''; ?>">
                <h3>Selective Size Control</h3>
                <p>Choose which image sizes to disable. Sizes are grouped by their source (WordPress Core, Theme, or Plugin).</p>
                
                <?php foreach ($all_sizes as $group_name => $sizes): ?>
                    <div class="size-group">
                        <h4><?php echo esc_html($group_name); ?></h4>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th width="30">Disable</th>
                                    <th>Size Name</th>
                                    <th>Dimensions</th>
                                    <th>Crop</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sizes as $size_name => $size_data): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" 
                                                   name="simpli_images_disabled_sizes[]" 
                                                   value="<?php echo esc_attr($size_name); ?>"
                                                   <?php checked(in_array($size_name, $disabled_sizes)); ?>>
                                        </td>
                                        <td><code><?php echo esc_html($size_name); ?></code></td>
                                        <td>
                                            <?php 
                                            echo esc_html($size_data['width']) . ' x ' . esc_html($size_data['height']); 
                                            ?>
                                        </td>
                                        <td><?php echo $size_data['crop'] ? 'Yes' : 'No'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <hr>
            
            <h3>Thumbnail Regeneration</h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="simpli_images_regenerate_on_deactivation">Regenerate on Deactivation</label>
                    </th>
                    <td>
                        <input type="checkbox" 
                               id="simpli_images_regenerate_on_deactivation" 
                               name="simpli_images_regenerate_on_deactivation" 
                               value="1" 
                               <?php checked(get_option('simpli_images_regenerate_on_deactivation', false), true); ?>>
                        <p class="description">
                            Automatically regenerate thumbnails when this plugin is deactivated. 
                            This ensures your site works normally with other themes/plugins after deactivation.
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary" id="save-sizes-button">
                    Save Image Size Settings
                </button>
                <span class="spinner" style="float: none; margin: 0 0 0 10px;"></span>
                <span id="sizes-save-message" style="margin-left: 10px;"></span>
            </p>
        </form>
        
        <hr>
        
        <h3>Regenerate Thumbnails</h3>
        <p>Use this tool to regenerate all image thumbnails. This is useful when you need to restore WordPress default image sizes.</p>
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="regenerate-form">
            <?php wp_nonce_field('simplimg_regenerate', 'simplimg_regen_nonce'); ?>
            <input type="hidden" name="action" value="simplimg_regenerate_thumbnails">
            <button type="submit" class="button button-secondary">
                Regenerate All Thumbnails Now
            </button>
            <span class="description" style="margin-left: 10px;">This may take several minutes for large media libraries.</span>
        </form>
        <?php
    }
    
    /**
     * Get all registered image sizes grouped by source
     */
    private function get_all_image_sizes_grouped() {
        global $_wp_additional_image_sizes;
        
        $sizes = array();
        
        // WordPress default sizes
        $default_sizes = array('thumbnail', 'medium', 'medium_large', 'large');
        foreach ($default_sizes as $size) {
            $sizes['WordPress Core'][$size] = array(
                'width' => get_option($size . '_size_w'),
                'height' => get_option($size . '_size_h'),
                'crop' => (bool) get_option($size . '_crop')
            );
        }
        
        // Additional sizes from themes and plugins
        if (isset($_wp_additional_image_sizes) && !empty($_wp_additional_image_sizes)) {
            foreach ($_wp_additional_image_sizes as $size_name => $size_data) {
                $source = $this->get_image_size_source($size_name);
                
                $sizes[$source][$size_name] = array(
                    'width' => $size_data['width'],
                    'height' => $size_data['height'],
                    'crop' => $size_data['crop']
                );
            }
        }
        
        return $sizes;
    }
    
    /**
     * Determine the source of an image size
     */
    private function get_image_size_source($size_name) {
        // Check if it's from WooCommerce
        if (strpos($size_name, 'woocommerce_') === 0 || strpos($size_name, 'shop_') === 0) {
            return 'WooCommerce';
        }
        
        // Check if it's from other popular plugins
        if (strpos($size_name, 'yoast') !== false) {
            return 'Yoast SEO';
        }
        
        if (strpos($size_name, 'elementor') !== false) {
            return 'Elementor';
        }
        
        if (strpos($size_name, 'divi') !== false) {
            return 'Divi';
        }
        
        // Check active theme
        $theme = wp_get_theme();
        $theme_slug = strtolower($theme->get('TextDomain'));
        
        if (strpos(strtolower($size_name), $theme_slug) !== false) {
            return $theme->get('Name') . ' (Theme)';
        }
        
        // Check if registered by active plugins
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin) {
            $plugin_slug = dirname($plugin);
            if ($plugin_slug !== '.' && strpos(strtolower($size_name), $plugin_slug) !== false) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                return $plugin_data['Name'] . ' (Plugin)';
            }
        }
        
        // Default to "Other"
        return 'Other';
    }
    
    /**
     * Get cache statistics
     */
    private function get_cache_stats() {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/simpli-cache/';
        
        $stats = array(
            'count' => 0,
            'size' => 0
        );
        
        if (!is_dir($cache_dir)) {
            return $stats;
        }
        
        $files = glob($cache_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'htaccess') {
                $stats['count']++;
                $stats['size'] += filesize($file);
            }
        }
        
        return $stats;
    }
    
    /**
     * Handle cache clearing action
     */
    public function handle_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('simplimg_clear_cache', 'simplimg_cache_nonce');
        
        $deleted = $this->clear_all_cache();
        
        wp_redirect(add_query_arg(array(
            'page' => 'simpli-images',
            'tab' => 'uploads',
            'cache-cleared' => $deleted
        ), admin_url('options-general.php')));
        exit;
    }
    
    /**
     * Clear all cached images
     */
    private function clear_all_cache() {
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
    
    /**
     * Handle thumbnail regeneration
     */
    public function handle_regenerate_thumbnails() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('simplimg_regenerate', 'simplimg_regen_nonce');
        
        // Note: We do NOT remove the image size filter here
        // This ensures regeneration respects current settings (disabled sizes stay disabled)
        
        $count = 0;
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
                
                // Regenerate metadata (respects current image size settings)
                $metadata = wp_generate_attachment_metadata($attachment->ID, $file);
                wp_update_attachment_metadata($attachment->ID, $metadata);
                $count++;
            }
        }
        
        wp_redirect(add_query_arg(array(
            'page' => 'simpli-images',
            'tab' => 'sizes',
            'thumbnails-regenerated' => $count
        ), admin_url('options-general.php')));
        exit;
    }
    
    /**
     * AJAX handler for saving image size settings
     */
    public function ajax_save_sizes() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simpli_images_sizes_save')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Get and sanitize form data
        $remove_all_sizes = isset($_POST['remove_all_sizes']) && $_POST['remove_all_sizes'] === '1';
        $disabled_sizes = isset($_POST['disabled_sizes']) && is_array($_POST['disabled_sizes']) 
            ? array_map('sanitize_text_field', $_POST['disabled_sizes']) 
            : array();
        $regenerate_on_deactivation = isset($_POST['regenerate_on_deactivation']) && $_POST['regenerate_on_deactivation'] === '1';
        
        // Update options
        update_option('simpli_images_remove_sizes', $remove_all_sizes);
        update_option('simpli_images_disabled_sizes', $disabled_sizes);
        update_option('simpli_images_regenerate_on_deactivation', $regenerate_on_deactivation);
        
        // Send success response
        wp_send_json_success(array(
            'message' => 'Settings saved successfully!',
            'settings' => array(
                'remove_all_sizes' => $remove_all_sizes,
                'disabled_sizes' => $disabled_sizes,
                'regenerate_on_deactivation' => $regenerate_on_deactivation
            )
        ));
    }
}

// Initialize settings
Simpli_Images_Settings::get_instance();