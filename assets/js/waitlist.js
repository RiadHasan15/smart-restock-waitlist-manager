/**
 * Smart Restock & Waitlist Manager - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize waitlist functionality
        initWaitlist();
    });
    
    function initWaitlist() {
        // Handle waitlist form submission
        $(document).on('submit', '.srwm-waitlist-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('.srwm-waitlist-submit');
            var originalText = $submitBtn.text();
            
            // Disable submit button and show loading
            $submitBtn.prop('disabled', true).text('Adding...');
            
            var formData = {
                action: 'srwm_add_to_waitlist',
                nonce: srwm_ajax.nonce,
                product_id: $form.find('input[name="product_id"]').val(),
                name: $form.find('input[name="name"]').val(),
                email: $form.find('input[name="email"]').val()
            };
            
            $.ajax({
                url: srwm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showWaitlistMessage($form, 'success', srwm_ajax.messages.success);
                        updateWaitlistCount($form);
                    } else {
                        showWaitlistMessage($form, 'error', response.message || srwm_ajax.messages.error);
                    }
                },
                error: function() {
                    showWaitlistMessage($form, 'error', srwm_ajax.messages.error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle email validation
        $(document).on('blur', '.srwm-waitlist-form input[name="email"]', function() {
            var email = $(this).val();
            if (email && !isValidEmail(email)) {
                $(this).addClass('error');
                showFieldError($(this), 'Please enter a valid email address.');
            } else {
                $(this).removeClass('error');
                hideFieldError($(this));
            }
        });
        
        // Handle name validation
        $(document).on('blur', '.srwm-waitlist-form input[name="name"]', function() {
            var name = $(this).val();
            if (name && name.length < 2) {
                $(this).addClass('error');
                showFieldError($(this), 'Name must be at least 2 characters long.');
            } else {
                $(this).removeClass('error');
                hideFieldError($(this));
            }
        });
    }
    
    function showWaitlistMessage($form, type, message) {
        var $container = $form.closest('.srwm-waitlist-form');
        var $existingMessage = $container.find('.srwm-message');
        
        if ($existingMessage.length) {
            $existingMessage.remove();
        }
        
        var messageClass = type === 'success' ? 'success' : 'error';
        var $message = $('<div class="srwm-message ' + messageClass + '">' + message + '</div>');
        
        $container.prepend($message);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to message if needed
        if ($message.offset().top < $(window).scrollTop()) {
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 500);
        }
    }
    
    function showFieldError($field, message) {
        var $error = $field.siblings('.field-error');
        if ($error.length === 0) {
            $error = $('<div class="field-error">' + message + '</div>');
            $field.after($error);
        } else {
            $error.text(message);
        }
        $error.show();
    }
    
    function hideFieldError($field) {
        $field.siblings('.field-error').hide();
    }
    
    function updateWaitlistCount($form) {
        var productId = $form.find('input[name="product_id"]').val();
        var $countElement = $form.find('.waitlist-count');
        
        if ($countElement.length) {
            // Update the count display
            var currentCount = parseInt($countElement.text()) || 0;
            $countElement.text(currentCount + 1);
        }
    }
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Utility function to format numbers
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Utility function to debounce function calls
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    // Export functions for potential external use
    window.SRWM = window.SRWM || {};
    window.SRWM.Waitlist = {
        showMessage: showWaitlistMessage,
        isValidEmail: isValidEmail,
        formatNumber: formatNumber
    };
    
})(jQuery);