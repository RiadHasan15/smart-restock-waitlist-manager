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
                        showAdminMessage('error', response.data || srwm_admin.messages.restock_error);
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
        
        // Initialize tooltips
        initTooltips();
        
        // Initialize data tables
        initDataTables();
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
        $('.wrap h1').after($notice);
        
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