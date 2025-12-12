/**
 * Simpli Images Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Toggle selective sizes based on "Remove All" checkbox
    $('#simpli_images_remove_sizes').on('change', function() {
        if ($(this).is(':checked')) {
            $('#selective-sizes').slideUp();
        } else {
            $('#selective-sizes').slideDown();
        }
    });
    
    // Handle sizes form submission via AJAX
    $('#simpli-sizes-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $('#save-sizes-button');
        var $spinner = $form.find('.spinner');
        var $message = $('#sizes-save-message');
        
        // Disable button and show spinner
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $message.html('');
        
        // Collect form data
        var formData = {
            action: 'simpli_save_sizes',
            nonce: $('#simpli_sizes_nonce').val(),
            remove_all_sizes: $('#simpli_images_remove_sizes').is(':checked') ? '1' : '0',
            disabled_sizes: [],
            regenerate_on_deactivation: $('#simpli_images_regenerate_on_deactivation').is(':checked') ? '1' : '0'
        };
        
        // Collect disabled sizes
        $('input[name="simpli_images_disabled_sizes[]"]:checked').each(function() {
            formData.disabled_sizes.push($(this).val());
        });
        
        // Send AJAX request
        $.ajax({
            url: simpliImages.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $message.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
                    
                    // Fade out message after 3 seconds
                    setTimeout(function() {
                        $message.fadeOut(function() {
                            $message.html('').show();
                        });
                    }, 3000);
                } else {
                    $message.html('<span style="color: #dc3232;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function(xhr, status, error) {
                $message.html('<span style="color: #dc3232;">✗ Error saving settings. Please try again.</span>');
                console.error('AJAX Error:', error);
            },
            complete: function() {
                // Re-enable button and hide spinner
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Confirm regenerate thumbnails
    $('#regenerate-form').on('submit', function(e) {
        var confirmed = confirm('This will regenerate thumbnails for all images in your media library. This may take several minutes depending on the number of images. Continue?');
        
        if (confirmed) {
            // Show loading indicator
            var $button = $(this).find('button[type="submit"]');
            $button.prop('disabled', true);
            $button.text('Regenerating... Please wait');
            return true;
        }
        
        return false;
    });
    
    // Handle clear cache link in media library list view
    $(document).on('click', '.simpli-clear-cache', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var imageId = $link.data('image-id');
        var originalText = $link.text();
        
        if (!imageId) {
            return;
        }
        
        // Show loading state
        $link.text('Clearing...');
        
        // Send AJAX request
        $.ajax({
            url: simpliImages.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simpli_clear_image_cache',
                nonce: simpliImages.nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    $link.text('✓ ' + response.data.message);
                    
                    // Reset after 2 seconds
                    setTimeout(function() {
                        $link.text(originalText);
                    }, 2000);
                } else {
                    $link.text('✗ ' + response.data.message);
                    
                    setTimeout(function() {
                        $link.text(originalText);
                    }, 2000);
                }
            },
            error: function() {
                $link.text('✗ Error');
                
                setTimeout(function() {
                    $link.text(originalText);
                }, 2000);
            }
        });
    });
    
    // Handle clear cache button in attachment details modal
    $(document).on('click', '.simpli-clear-cache-modal', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $message = $button.siblings('.simpli-cache-message');
        var imageId = $button.data('image-id');
        
        if (!imageId) {
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true).text('Clearing...');
        $message.html('');
        
        // Send AJAX request
        $.ajax({
            url: simpliImages.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simpli_clear_image_cache',
                nonce: simpliImages.nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
                } else {
                    $message.html('<span style="color: #dc3232;">✗ ' + response.data.message + '</span>');
                }
                
                // Reset button
                $button.prop('disabled', false).text('Clear Cache');
                
                // Fade out message after 3 seconds
                setTimeout(function() {
                    $message.fadeOut(function() {
                        $message.html('').show();
                    });
                }, 3000);
            },
            error: function() {
                $message.html('<span style="color: #dc3232;">✗ Error clearing cache</span>');
                $button.prop('disabled', false).text('Clear Cache');
                
                setTimeout(function() {
                    $message.fadeOut(function() {
                        $message.html('').show();
                    });
                }, 3000);
            }
        });
    });
    
});