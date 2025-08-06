/**
 * Smart Restock & Waitlist Manager - Enhanced Frontend JavaScript
 */

jQuery(document).ready(function($) {
    
    // Initialize visual effects
    initVisualEffects();
    
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
                        updateWaitlistDisplay($form, response);
                        animateSuccess($form);
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
     * Initialize visual effects
     */
    function initVisualEffects() {
        // Animate count numbers
        animateCountNumbers();
        
        // Add scroll animations
        addScrollAnimations();
        
        // Initialize progress bars
        initProgressBars();
        
        // Add hover effects
        addHoverEffects();
    }
    
    /**
     * Animate count numbers
     */
    function animateCountNumbers() {
        $('.srwm-count-number').each(function() {
            const $element = $(this);
            const targetCount = parseInt($element.data('count')) || 0;
            
            if (targetCount > 0) {
                animateNumber($element, 0, targetCount, 1000);
            }
        });
    }
    
    /**
     * Animate number from start to end
     */
    function animateNumber($element, start, end, duration) {
        const startTime = performance.now();
        const difference = end - start;
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = Math.floor(start + (difference * easeOutQuart));
            
            $element.text(current.toLocaleString());
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        }
        
        requestAnimationFrame(updateNumber);
    }
    
    /**
     * Add scroll animations
     */
    function addScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Observe elements for animation
        $('.srwm-waitlist-container').each(function() {
            observer.observe(this);
        });
    }
    
    /**
     * Initialize progress bars
     */
    function initProgressBars() {
        $('.srwm-progress-fill').each(function() {
            const $progress = $(this);
            const width = $progress.css('width');
            
            // Reset width for animation
            $progress.css('width', '0%');
            
            // Animate to target width
            setTimeout(function() {
                $progress.css('width', width);
            }, 300);
        });
    }
    
    /**
     * Add hover effects
     */
    function addHoverEffects() {
        // Add hover effects to form inputs
        $('.srwm-field-group input').on('focus', function() {
            $(this).closest('.srwm-field-group').addClass('focused');
        }).on('blur', function() {
            $(this).closest('.srwm-field-group').removeClass('focused');
        });
        
        // Add ripple effect to submit button
        $('.srwm-waitlist-submit').on('click', function(e) {
            const $button = $(this);
            const $ripple = $('<span class="ripple"></span>');
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            $ripple.css({
                width: size + 'px',
                height: size + 'px',
                left: x + 'px',
                top: y + 'px'
            });
            
            $button.append($ripple);
            
            setTimeout(function() {
                $ripple.remove();
            }, 600);
        });
    }
    
    /**
     * Validate form data
     */
    function validateForm(data) {
        const $message = $('.srwm-waitlist-message');
        
        // Clear previous messages
        $message.removeClass('success error').hide();
        
        // Validate email
        if (!data.email || !isValidEmail(data.email)) {
            showErrorMessage($message, 'Please enter a valid email address.');
            return false;
        }
        
        // Validate name
        if (!data.name || data.name.trim().length < 2) {
            showErrorMessage($message, 'Please enter your full name.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if email is valid
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage($message, text) {
        $message
            .removeClass('error')
            .addClass('success')
            .text(text)
            .show()
            .addClass('animate-in');
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut();
        }, 5000);
    }
    
    /**
     * Show error message
     */
    function showErrorMessage($message, text) {
        $message
            .removeClass('success')
            .addClass('error')
            .text(text)
            .show()
            .addClass('animate-in');
        
        // Auto-hide after 8 seconds
        setTimeout(function() {
            $message.fadeOut();
        }, 8000);
    }
    
    /**
     * Update waitlist display after successful submission
     */
    function updateWaitlistDisplay($form, response) {
        const $container = $form.closest('.srwm-waitlist-container');
        
        // Hide form container
        $form.closest('.srwm-waitlist-form-container').fadeOut(300, function() {
            // Show success status
            const successHtml = `
                <div class="srwm-waitlist-status">
                    <div class="srwm-status-card active">
                        <div class="srwm-status-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="srwm-status-content">
                            <h4>You're on the waitlist!</h4>
                            <p>We'll notify you as soon as this product is back in stock.</p>
                            <div class="srwm-queue-position">
                                <div class="srwm-queue-info">
                                    <span class="srwm-queue-label">Your position:</span>
                                    <span class="srwm-queue-number">#1</span>
                                </div>
                                <div class="srwm-queue-progress">
                                    <div class="srwm-progress-bar">
                                        <div class="srwm-progress-fill" style="width: 100%"></div>
                                    </div>
                                    <small>You're first in line!</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $container.find('.srwm-waitlist-header').after(successHtml);
            
            // Animate the new status card
            setTimeout(function() {
                $container.find('.srwm-status-card').addClass('animate-in');
            }, 100);
        });
    }
    
    /**
     * Animate success state
     */
    function animateSuccess($form) {
        const $container = $form.closest('.srwm-waitlist-container');
        
        // Add success animation class
        $container.addClass('success-animation');
        
        // Remove animation class after animation completes
        setTimeout(function() {
            $container.removeClass('success-animation');
        }, 1000);
    }
    
    /**
     * Debounce function for performance
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Throttle function for scroll events
     */
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // Add CSS for animations
    const animationCSS = `
        <style>
            .srwm-waitlist-container {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.6s ease, transform 0.6s ease;
            }
            
            .srwm-waitlist-container.animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            
            .srwm-status-card {
                opacity: 0;
                transform: scale(0.9);
                transition: opacity 0.4s ease, transform 0.4s ease;
            }
            
            .srwm-status-card.animate-in {
                opacity: 1;
                transform: scale(1);
            }
            
            .srwm-field-group.focused input {
                transform: scale(1.02);
            }
            
            .srwm-waitlist-submit .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .srwm-waitlist-container.success-animation {
                animation: successPulse 0.6s ease;
            }
            
            @keyframes successPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
        </style>
    `;
    
    $('head').append(animationCSS);
    
    // Initialize animations on page load
    setTimeout(function() {
        $('.srwm-waitlist-container').addClass('animate-in');
    }, 100);
    
});