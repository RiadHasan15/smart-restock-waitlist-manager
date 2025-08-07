/**
 * Smart Restock & Waitlist Manager - Enhanced Frontend JavaScript
 */

jQuery(document).ready(function($) {
    
    // Initialize enhanced features
    initEnhancedFeatures();
    
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
        
        // Show loading state with enhanced animation
        showLoadingState($submitBtn);
        
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
                hideLoadingState($submitBtn);
            }
        });
    });
    
    /**
     * Initialize enhanced features
     */
    function initEnhancedFeatures() {
        // Animate count numbers
        animateCountNumbers();
        
        // Add scroll animations
        addScrollAnimations();
        
        // Initialize progress bars
        initProgressBars();
        
        // Add hover effects
        addHoverEffects();
        
        // Add form enhancements
        addFormEnhancements();
    }
    
    /**
     * Animate count numbers with enhanced effects
     */
    function animateCountNumbers() {
        $('.srwm-count-number').each(function() {
            const $element = $(this);
            const targetCount = parseInt($element.data('count')) || 0;
            const startCount = 0;
            const duration = 1500;
            
            animateNumber($element, startCount, targetCount, duration);
        });
    }
    
    /**
     * Animate number from start to end with enhanced easing
     */
    function animateNumber($element, start, end, duration) {
        const startTime = performance.now();
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Enhanced easing function
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = Math.floor(start + (end - start) * easeOutQuart);
            
            $element.text(current.toLocaleString());
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                // Add completion effect
                $element.addClass('count-complete');
                setTimeout(() => $element.removeClass('count-complete'), 500);
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
     * Initialize progress bars with enhanced animations
     */
    function initProgressBars() {
        $('.srwm-progress-fill, .srwm-queue-fill').each(function() {
            const $progress = $(this);
            const width = $progress.css('width');
            
            // Reset width for animation
            $progress.css('width', '0%');
            
            // Animate to target width with delay
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
        
        // Add hover effects to stat items
        $('.srwm-stat-item').on('mouseenter', function() {
            $(this).addClass('hovered');
        }).on('mouseleave', function() {
            $(this).removeClass('hovered');
        });
    }
    
    /**
     * Add form enhancements
     */
    function addFormEnhancements() {
        // Add character counter for name field
        $('.srwm-field-group input[name="name"]').on('input', function() {
            const $input = $(this);
            const $fieldGroup = $input.closest('.srwm-field-group');
            const maxLength = 50;
            const currentLength = $input.val().length;
            
            // Remove existing counter
            $fieldGroup.find('.char-counter').remove();
            
            // Add counter if there's text
            if (currentLength > 0) {
                const $counter = $(`<span class="char-counter">${currentLength}/${maxLength}</span>`);
                $fieldGroup.append($counter);
                
                // Add visual feedback
                if (currentLength > maxLength * 0.8) {
                    $counter.addClass('warning');
                }
            }
        });
        
        // Add email validation feedback
        $('.srwm-field-group input[name="email"]').on('blur', function() {
            const $input = $(this);
            const email = $input.val();
            const $fieldGroup = $input.closest('.srwm-field-group');
            
            if (email && !isValidEmail(email)) {
                $fieldGroup.addClass('error');
                showFieldError($fieldGroup, 'Please enter a valid email address');
            } else {
                $fieldGroup.removeClass('error');
                hideFieldError($fieldGroup);
            }
        });
    }
    
    /**
     * Show field error
     */
    function showFieldError($fieldGroup, message) {
        $fieldGroup.find('.field-error').remove();
        const $error = $(`<span class="field-error">${message}</span>`);
        $fieldGroup.append($error);
    }
    
    /**
     * Hide field error
     */
    function hideFieldError($fieldGroup) {
        $fieldGroup.find('.field-error').remove();
    }
    
    /**
     * Show loading state with enhanced animation
     */
    function showLoadingState($button) {
        $button.addClass('loading').prop('disabled', true);
        $button.find('.srwm-submit-text').text('Joining...');
        
        // Add loading animation
        $button.addClass('loading-animation');
    }
    
    /**
     * Hide loading state
     */
    function hideLoadingState($button) {
        $button.removeClass('loading loading-animation').prop('disabled', false);
        $button.find('.srwm-submit-text').text('Join Waitlist');
    }
    
    /**
     * Validate form data
     */
    function validateForm(data) {
        if (!data.name || data.name.trim() === '') {
            showErrorMessage($('.srwm-waitlist-message'), 'Please enter your name.');
            return false;
        }
        
        if (data.name.trim().length < 2) {
            showErrorMessage($('.srwm-waitlist-message'), 'Please enter your full name (at least 2 characters).');
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
     * Show success message with enhanced animation
     */
    function showSuccessMessage($message, text) {
        $message
            .removeClass('error')
            .addClass('success')
            .html(`<span class="message-icon">✓</span> ${text}`)
            .fadeIn()
            .addClass('animate-in');
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut();
        }, 5000);
    }
    
    /**
     * Show error message with enhanced animation
     */
    function showErrorMessage($message, text) {
        $message
            .removeClass('success')
            .addClass('error')
            .html(`<span class="message-icon">⚠</span> ${text}`)
            .fadeIn()
            .addClass('animate-in');
        
        // Auto-hide after 8 seconds
        setTimeout(function() {
            $message.fadeOut();
        }, 8000);
    }
    
    /**
     * Reload the entire waitlist section
     */
    function reloadWaitlistSection($form) {
        const $container = $form.closest('.srwm-waitlist-container');
        const productId = $form.find('input[name="product_id"]').val();
        
        // Show loading state with enhanced animation
        showContainerLoading($container);
        
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
                        // Replace the entire container content with fade effect
                        $container.fadeOut(300, function() {
                            $container.html(response.html);
                            $container.fadeIn(300, function() {
                                // Re-initialize enhanced features
                                setTimeout(function() {
                                    initEnhancedFeatures();
                                }, 100);
                            });
                        });
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
                hideContainerLoading($container);
            }
        });
    }
    
    /**
     * Show container loading state
     */
    function showContainerLoading($container) {
        $container.addClass('loading');
    }
    
    /**
     * Hide container loading state
     */
    function hideContainerLoading($container) {
        $container.removeClass('loading');
    }
    
    // Add enhanced CSS for animations
    const enhancedCSS = `
        <style>
            .srwm-waitlist-container {
                opacity: 0;
                transform: translateY(30px);
                transition: opacity 0.8s ease, transform 0.8s ease;
            }
            
            .srwm-waitlist-container.animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            
            .srwm-status-card {
                opacity: 0;
                transform: scale(0.9);
                transition: opacity 0.6s ease, transform 0.6s ease;
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
            
            .srwm-waitlist-submit.loading-animation {
                animation: loadingPulse 1.5s infinite;
            }
            
            @keyframes loadingPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.02); }
            }
            
            .srwm-stat-item.hovered {
                transform: translateY(-4px) scale(1.02);
            }
            
            .char-counter {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
                color: rgba(0, 0, 0, 0.5);
                font-weight: 500;
            }
            
            .char-counter.warning {
                color: #f59e0b;
            }
            
            .field-error {
                display: block;
                color: #dc2626;
                font-size: 12px;
                margin-top: 4px;
                font-weight: 500;
            }
            
            .srwm-field-group.error input {
                border-color: #dc2626;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            }
            
            .message-icon {
                margin-right: 8px;
                font-weight: bold;
            }
            
            .count-complete {
                animation: countComplete 0.5s ease;
            }
            
            @keyframes countComplete {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
        </style>
    `;
    
    $('head').append(enhancedCSS);
    
    // Initialize enhanced features on page load
    setTimeout(function() {
        $('.srwm-waitlist-container').addClass('animate-in');
    }, 100);
});