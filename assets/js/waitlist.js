/**
 * Smart Restock & Waitlist Manager - Simple Frontend JavaScript
 */

jQuery(document).ready(function($) {
    
    // Handle form submission
    $('.srwm-waitlist-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('.srwm-waitlist-submit');
        const $message = $form.closest('.srwm-waitlist-container').find('.srwm-waitlist-message');
        
        // Get form data
        const formData = {
            action: 'srwm_add_to_waitlist',
            nonce: srwm_ajax.nonce,
            product_id: $form.find('input[name="product_id"]').val(),
            email: $form.find('input[name="email"]').val(),
            name: $form.find('input[name="name"]').val()
        };
        
        // Validate form
        if (!validateForm(formData)) {
            return;
        }
        
        // Show loading state
        $submitBtn.addClass('loading').prop('disabled', true);
        $submitBtn.find('.srwm-submit-text').text('Joining...');
        
        // Clear previous messages
        $message.removeClass('success error').hide();
        
        // Submit form
        $.ajax({
            url: srwm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 10000,
            success: function(response) {
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if (response.success) {
                        showSuccessMessage($message, response.message);
                        reloadWaitlistSection($form);
                    } else {
                        showErrorMessage($message, response.message);
                    }
                } catch (e) {
                    showErrorMessage($message, 'An unexpected error occurred. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Network error. Please check your connection and try again.';
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // Use default error message
                    }
                }
                
                showErrorMessage($message, errorMessage);
            },
            complete: function() {
                // Reset button state
                $submitBtn.removeClass('loading').prop('disabled', false);
                $submitBtn.find('.srwm-submit-text').text('Join Waitlist');
            }
        });
    });
    
    /**
     * Validate form data
     */
    function validateForm(data) {
        if (!data.name || data.name.trim() === '') {
            showErrorMessage($('.srwm-waitlist-message'), 'Please enter your name.');
            return false;
        }
        
        if (!data.email || !isValidEmail(data.email)) {
            showErrorMessage($('.srwm-waitlist-message'), 'Please enter a valid email address.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage($message, text) {
        $message.removeClass('error').addClass('success').html(text).fadeIn();
    }
    
    /**
     * Show error message
     */
    function showErrorMessage($message, text) {
        $message.removeClass('success').addClass('error').html(text).fadeIn();
    }
    
    /**
     * Reload the entire waitlist section
     */
    function reloadWaitlistSection($form) {
        const $container = $form.closest('.srwm-waitlist-container');
        const productId = $form.find('input[name="product_id"]').val();
        
        // Show loading state
        $container.addClass('loading');
        
        // Make AJAX request to get updated waitlist HTML
        $.ajax({
            url: srwm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'srwm_get_waitlist_html',
                nonce: srwm_ajax.nonce,
                product_id: productId
            },
            success: function(response) {
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if (response.success) {
                        // Replace the entire container content
                        $container.html(response.html);
                        
                        // Re-initialize visual effects
                        setTimeout(function() {
                            animateCountNumbers();
                        }, 100);
                    } else {
                        console.error('Failed to reload waitlist section:', response.message);
                    }
                } catch (e) {
                    console.error('Error parsing reload response:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error reloading waitlist section:', error);
            },
            complete: function() {
                $container.removeClass('loading');
            }
        });
    }
    
    /**
     * Animate count numbers
     */
    function animateCountNumbers() {
        $('.srwm-count-number').each(function() {
            const $element = $(this);
            const targetCount = parseInt($element.data('count')) || 0;
            const startCount = 0;
            const duration = 1200;
            
            animateNumber($element, startCount, targetCount, duration);
        });
    }
    
    /**
     * Animate number from start to end
     */
    function animateNumber($element, start, end, duration) {
        const startTime = performance.now();
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (end - start) * progress);
            $element.text(current);
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        }
        
        requestAnimationFrame(updateNumber);
    }
    
    // Initialize count animations on page load
    animateCountNumbers();
});