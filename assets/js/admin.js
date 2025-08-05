/**
 * Smart Restock & Waitlist Manager - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize admin functionality
        initAdmin();
    });
    
    function initAdmin() {
        // Handle restock product button clicks
        $(document).on('click', '.restock-product', function(e) {
            e.preventDefault();
            
            var productId = $(this).data('product-id');
            showRestockModal(productId);
        });
        
        // Handle view waitlist button clicks
        $(document).on('click', '.view-waitlist', function(e) {
            e.preventDefault();
            
            var productId = $(this).data('product-id');
            loadWaitlistData(productId);
        });
        
        // Handle modal close
        $(document).on('click', '.srwm-modal-close', function() {
            $(this).closest('.srwm-modal').hide();
        });
        
        // Handle restock form submission
        $(document).on('submit', '#srwm-restock-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Disable submit button and show loading
            $submitBtn.prop('disabled', true).text('Processing...');
            
            var formData = {
                action: 'srwm_restock_product',
                nonce: srwm_admin.nonce,
                product_id: $form.find('#restock-product-id').val(),
                quantity: $form.find('#restock-quantity').val()
            };
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showAdminMessage('success', srwm_admin.messages.restock_success);
                        $('#srwm-restock-modal').hide();
                        // Reload the page to update the data
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAdminMessage('error', response.message || srwm_admin.messages.restock_error);
                    }
                },
                error: function() {
                    showAdminMessage('error', srwm_admin.messages.restock_error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle export button clicks
        $(document).on('click', '.export-data', function(e) {
            e.preventDefault();
            
            var exportType = $(this).data('export-type') || 'all';
            exportData(exportType);
        });
        
        // Handle quick restock buttons
        $(document).on('click', '.quick-restock-btn', function(e) {
            e.preventDefault();
            
            var quantity = $(this).data('quantity');
            $('#restock-quantity').val(quantity);
        });
        
        // Handle threshold save buttons
        $(document).on('click', '.save-threshold', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var productId = $btn.data('product-id');
            var $input = $('.srwm-threshold-input[data-product-id="' + productId + '"]');
            var threshold = $input.val();
            
            if (!threshold || threshold < 0) {
                showAdminMessage('error', 'Please enter a valid threshold value.');
                return;
            }
            
            // Disable button and show loading
            $btn.prop('disabled', true).text('Saving...');
            
            var ajaxData = {
                action: 'srwm_save_threshold',
                nonce: srwm_admin.nonce,
                product_id: productId,
                threshold: threshold
            };
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: ajaxData,
                success: function(response) {
                    if (response.success === true || response.success === 'true') {
                        showAdminMessage('success', response.message || 'Threshold saved successfully!');
                    } else {
                        showAdminMessage('error', response.message || 'Failed to save threshold.');
                    }
                },
                error: function(xhr, status, error) {
                    showAdminMessage('error', 'Failed to save threshold.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save');
                }
            });
        });
        
        // Handle threshold reset buttons
        $(document).on('click', '.reset-threshold', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var productId = $btn.data('product-id');
            
            if (!confirm('Are you sure you want to reset this threshold to the global default?')) {
                return;
            }
            
            // Disable button and show loading
            $btn.prop('disabled', true).text('Resetting...');
            
            var ajaxData = {
                action: 'srwm_reset_threshold',
                nonce: srwm_admin.nonce,
                product_id: productId
            };
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: ajaxData,
                success: function(response) {
                    if (response.success === true || response.success === 'true') {
                        showAdminMessage('success', response.message || 'Threshold reset successfully!');
                        // Reload the page to update the display
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAdminMessage('error', response.message || 'Failed to reset threshold.');
                    }
                },
                error: function(xhr, status, error) {
                    showAdminMessage('error', 'Failed to reset threshold.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-undo"></span> Reset');
                }
            });
        });
        
        // Handle global threshold save button click
        $(document).on('click', '#srwm-save-global-threshold', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.html();
            var threshold = $('#srwm_global_threshold').val();
            
            if (!threshold || threshold < 0) {
                showAdminMessage('error', 'Please enter a valid threshold value.');
                return;
            }
            
            // Disable button and show loading
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');
            
            var ajaxData = {
                action: 'srwm_save_global_threshold',
                nonce: srwm_admin.nonce,
                global_threshold: threshold
            };
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: ajaxData,
                success: function(response) {
                    if (response.success === true || response.success === 'true') {
                        showAdminMessage('success', response.message || 'Global threshold saved successfully!');
                    } else {
                        showAdminMessage('error', response.message || 'Failed to save global threshold.');
                    }
                },
                error: function(xhr, status, error) {
                    showAdminMessage('error', 'Failed to save global threshold.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Handle notification settings form submission
        $(document).on('submit', '#srwm-notification-settings-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Disable submit button and show loading
            $submitBtn.prop('disabled', true).text('Saving...');
            
            var formData = new FormData($form[0]);
            formData.append('action', 'srwm_save_notification_settings');
            formData.append('nonce', srwm_admin.nonce);
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAdminMessage('success', response.message || 'Notification settings saved successfully!');
                    } else {
                        showAdminMessage('error', response.message || 'Failed to save notification settings.');
                    }
                },
                error: function() {
                    showAdminMessage('error', 'Failed to save notification settings.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle email templates form submission
        $(document).on('submit', '#srwm-email-templates-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Disable submit button and show loading
            $submitBtn.prop('disabled', true).text('Saving...');
            
            var formData = new FormData($form[0]);
            formData.append('action', 'srwm_save_email_templates');
            formData.append('nonce', srwm_admin.nonce);
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAdminMessage('success', response.message || 'Email templates saved successfully!');
                    } else {
                        showAdminMessage('error', response.message || 'Failed to save email templates.');
                    }
                },
                error: function() {
                    showAdminMessage('error', 'Failed to save email templates.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle global threshold form submission
        $(document).on('submit', '#srwm-global-threshold-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            var globalThreshold = $form.find('input[name="srwm_global_threshold"]').val();
            
            if (!globalThreshold || globalThreshold < 0) {
                showAdminMessage('error', 'Please enter a valid global threshold value.');
                return;
            }
            
            // Disable submit button and show loading
            $submitBtn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'srwm_save_global_threshold',
                    nonce: srwm_admin.nonce,
                    global_threshold: globalThreshold
                },
                success: function(response) {
                    if (response.success) {
                        showAdminMessage('success', response.message || 'Global threshold saved successfully!');
                    } else {
                        showAdminMessage('error', response.message || 'Failed to save global threshold.');
                    }
                },
                error: function() {
                    showAdminMessage('error', 'Failed to save global threshold.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle generate restock link buttons
        $(document).on('click', '.generate-restock-link', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var productId = $btn.data('product-id');
            var supplierEmail = prompt('Enter supplier email address:');
            
            if (!supplierEmail || !isValidEmail(supplierEmail)) {
                showAdminMessage('error', 'Please enter a valid email address.');
                return;
            }
            
            // Disable button and show loading
            $btn.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'srwm_generate_restock_link',
                    nonce: srwm_admin.nonce,
                    product_id: productId,
                    supplier_email: supplierEmail
                },
                success: function(response) {
                    if (response.success) {
                        showAdminMessage('success', response.message || 'Restock link generated successfully!');
                        if (response.data && response.data.link) {
                            // Show the generated link
                            var linkText = 'Restock Link: ' + response.data.link;
                            showAdminMessage('info', linkText);
                        }
                    } else {
                        showAdminMessage('error', response.message || 'Failed to generate restock link.');
                    }
                },
                error: function() {
                    showAdminMessage('error', 'Failed to generate restock link.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Generate Link');
                }
            });
        });
        
        // Handle generate PO buttons
        $(document).on('click', '#srwm-generate-po, .generate-po', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var productId = $btn.data('product-id');
            var supplierName = prompt('Enter supplier name:');
            var supplierEmail = prompt('Enter supplier email address:');
            
            if (!supplierName || !supplierEmail || !isValidEmail(supplierEmail)) {
                showAdminMessage('error', 'Please enter valid supplier information.');
                return;
            }
            
            // Disable button and show loading
            $btn.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'srwm_generate_po',
                    nonce: srwm_admin.nonce,
                    product_id: productId,
                    supplier_name: supplierName,
                    supplier_email: supplierEmail
                },
                success: function(response) {
                    if (response.success) {
                        showAdminMessage('success', response.message || 'Purchase order generated successfully!');
                    } else {
                        showAdminMessage('error', response.message || 'Failed to generate purchase order.');
                    }
                },
                error: function() {
                    showAdminMessage('error', 'Failed to generate purchase order.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Generate PO');
                }
            });
        });
        
        // Handle CSV upload link form submission
        $(document).on('submit', '#srwm-generate-csv-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.html();
            var supplierEmail = $('#srwm_supplier_email').val();
            
            if (!supplierEmail || !isValidEmail(supplierEmail)) {
                showAdminMessage('error', 'Please enter a valid email address.');
                return;
            }
            
            // Disable button and show loading
            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Generating...');
            
            $.ajax({
                url: srwm_admin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'srwm_generate_csv_upload_link',
                    nonce: srwm_admin.nonce,
                    supplier_email: supplierEmail
                },
                success: function(response) {
                    console.log('CSV AJAX Success:', response);
                    if (response.success) {
                        showAdminMessage('success', response.message || 'CSV upload link generated successfully!');
                        if (response.data && response.data.link) {
                            // Show the generated link
                            var linkText = 'CSV Upload Link: ' + response.data.link;
                            showAdminMessage('info', linkText);
                        }
                        // Clear the form
                        $form[0].reset();
                    } else {
                        showAdminMessage('error', response.message || 'Failed to generate CSV upload link.');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('CSV AJAX Error:', { xhr: xhr, status: status, error: error });
                    showAdminMessage('error', 'Failed to generate CSV upload link.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Handle CSV template download
        $(document).on('click', '#srwm-download-template', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.html();
            
            // Disable button and show loading
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Downloading...');
            
            // Create a temporary form to trigger download
            var $form = $('<form>', {
                method: 'POST',
                action: srwm_admin.ajax_url,
                target: '_blank'
            });
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'srwm_download_csv_template'
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: srwm_admin.nonce
            }));
            
            $('body').append($form);
            $form.submit();
            $form.remove();
            
            // Re-enable button after a short delay
            setTimeout(function() {
                $btn.prop('disabled', false).html(originalText);
            }, 2000);
        });
        
        // Handle copy CSV link
        $(document).on('click', '.copy-link', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var token = $btn.data('token');
            var link = window.location.origin + '/?srwm_csv_upload=1&token=' + token;
            
            // Copy to clipboard
            navigator.clipboard.writeText(link).then(function() {
                showAdminMessage('success', 'Link copied to clipboard!');
            }).catch(function() {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = link;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showAdminMessage('success', 'Link copied to clipboard!');
            });
        });
        
        // Handle view uploads
        $(document).on('click', '.view-uploads', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var token = $btn.data('token');
            
            showAdminMessage('info', 'Upload history feature coming soon!');
        });
        
        // Initialize tooltips
        initTooltips();
        
        // Initialize data tables
        initDataTables();
    }
    
    // Email validation helper function (moved outside initAdmin for global access)
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showRestockModal(productId) {
        $('#restock-product-id').val(productId);
        $('#srwm-restock-modal').show();
    }
    
    function loadWaitlistData(productId) {
        var $modal = $('#srwm-waitlist-modal');
        var $content = $('#srwm-waitlist-content');
        
        $content.html('<div class="loading">Loading waitlist data...</div>');
        $modal.show();
        
        $.ajax({
            url: srwm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'srwm_get_waitlist_data',
                nonce: srwm_admin.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html('<div class="error">Failed to load waitlist data.</div>');
                }
            },
            error: function() {
                $content.html('<div class="error">Failed to load waitlist data.</div>');
            }
        });
    }
    
    function exportData(type) {
        var url = srwm_admin.ajax_url + '?action=srwm_export_waitlist&type=' + type + '&nonce=' + srwm_admin.nonce;
        
        // Create a temporary link and trigger download
        var $link = $('<a>', {
            href: url,
            download: 'srwm-export-' + type + '-' + new Date().toISOString().split('T')[0] + '.csv'
        });
        
        $('body').append($link);
        $link[0].click();
        $link.remove();
        
        showAdminMessage('success', srwm_admin.messages.export_success);
    }
    
    function showAdminMessage(type, message) {
        var messageClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + messageClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Insert at the top of the page
        var $target = $('.wrap h1');
        
        if ($target.length > 0) {
            $target.after($notice);
        } else {
            // Fallback: insert at the beginning of .wrap
            $('.wrap').prepend($notice);
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    function initTooltips() {
        // Initialize tooltips for help text
        $('.srwm-help').each(function() {
            var $help = $(this);
            var $tooltip = $('<div class="srwm-tooltip">' + $help.attr('title') + '</div>');
            
            $help.removeAttr('title').append($tooltip);
            
            $help.on('mouseenter', function() {
                $tooltip.show();
            }).on('mouseleave', function() {
                $tooltip.hide();
            });
        });
    }
    
    function initDataTables() {
        // Initialize sortable tables if DataTables is available
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.srwm-data-table').DataTable({
                pageLength: 25,
                order: [[0, 'asc']],
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    }
    
    // Utility function to format dates
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
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
    
    // Handle window resize for responsive design
    $(window).on('resize', debounce(function() {
        // Adjust modal positioning on window resize
        $('.srwm-modal').each(function() {
            var $modal = $(this);
            var $content = $modal.find('.srwm-modal-content');
            
            // Center the modal
            $content.css({
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)'
            });
        });
    }, 250));
    
    // Handle escape key to close modals
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // Escape key
            $('.srwm-modal').hide();
        }
    });
    
    // Handle click outside modal to close
    $(document).on('click', '.srwm-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Export functions for potential external use
    window.SRWM = window.SRWM || {};
    window.SRWM.Admin = {
        showMessage: showAdminMessage,
        formatDate: formatDate,
        formatNumber: formatNumber,
        exportData: exportData
    };
    
})(jQuery);