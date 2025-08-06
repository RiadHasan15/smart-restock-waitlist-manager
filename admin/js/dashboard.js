/**
 * Smart Restock & Waitlist Manager - Dashboard JavaScript
 */

(function($) {
    'use strict';

    // Global variables
    let waitlistChart = null;
    let restockChart = null;
    let realtimeUpdateInterval = null;

    /**
     * Initialize dashboard functionality
     */
    $(document).ready(function() {
        initDashboard();
    });

    /**
     * Initialize dashboard
     */
    function initDashboard() {
        // Only initialize if we're on the dashboard page
        if (!$('.srwm-dashboard').length) {
            return;
        }
        
        initCharts();
        initEventHandlers();
        initRealtimeUpdates();
        initTooltips();
        initEnhancedUX();
    }

    /**
     * Initialize charts
     */
    function initCharts() {
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            showMessage('error', 'Chart library not loaded. Please refresh the page.');
            return;
        }
        
        // Waitlist Growth Chart
        const waitlistCtx = document.getElementById('waitlistChart');
        if (waitlistCtx) {
            waitlistChart = new Chart(waitlistCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Waitlist Signups',
                        data: [],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Restock Activity Chart
        const restockCtx = document.getElementById('restockChart');
        if (restockCtx) {
            restockChart = new Chart(restockCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Restock Actions',
                        data: [],
                        backgroundColor: '#28a745',
                        borderColor: '#28a745',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Load initial chart data
        loadChartData();
    }

    /**
     * Load chart data via AJAX
     */
    function loadChartData(days = 7) {
        // Check if srwm_dashboard is available
        if (typeof srwm_dashboard === 'undefined') {
            showMessage('error', 'Dashboard configuration not loaded');
            return $.Deferred().reject('srwm_dashboard not available');
        }
        
        return $.ajax({
            url: srwm_dashboard.ajax_url,
            type: 'POST',
            timeout: 10000, // 10 second timeout
            data: {
                action: 'srwm_get_dashboard_data',
                nonce: srwm_dashboard.nonce,
                days: days
            },
            success: function(response) {
                // Ensure response is an object
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        showMessage('error', 'Invalid response format');
                        return;
                    }
                }
                
                if (response.success) {
                    updateCharts(response.data);
                } else {
                    showMessage('error', response.message || 'Failed to load chart data');
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    showMessage('error', 'Request timed out. Please try again.');
                } else {
                    showMessage('error', 'Failed to load chart data. Please try again.');
                }
            }
        });
    }

    /**
     * Update charts with new data
     */
    function updateCharts(data) {
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not available for chart updates');
            return;
        }
        
        // Update waitlist chart
        if (waitlistChart) {
            if (data.waitlist_growth && data.waitlist_growth.length > 0) {
                const labels = data.waitlist_growth.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const values = data.waitlist_growth.map(item => parseInt(item.count));
                
                waitlistChart.data.labels = labels;
                waitlistChart.data.datasets[0].data = values;
                waitlistChart.update();
            } else {
                // Show empty state with last 7 days
                const labels = [];
                const values = [];
                const today = new Date();
                
                for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                    values.push(0);
                }
                
                waitlistChart.data.labels = labels;
                waitlistChart.data.datasets[0].data = values;
                waitlistChart.update();
            }
        }

        // Update restock chart
        if (restockChart) {
            if (data.restock_activity && data.restock_activity.length > 0) {
                const labels = data.restock_activity.map(item => {
                    // Format method names for better display
                    const method = item.method || 'Unknown';
                    return method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' ');
                });
                const values = data.restock_activity.map(item => parseInt(item.count));
                
                restockChart.data.labels = labels;
                restockChart.data.datasets[0].data = values;
                restockChart.update();
            } else {
                // Show empty state
                restockChart.data.labels = ['Manual', 'CSV Upload', 'Quick Restock'];
                restockChart.data.datasets[0].data = [0, 0, 0];
                restockChart.update();
            }
        }
    }

    /**
     * Initialize event handlers
     */
    function initEventHandlers() {
        // Quick action buttons
        $('#srwm-view-waitlists').on('click', function() {
            window.location.href = 'admin.php?page=smart-restock-waitlist';
        });

        $('#srwm-export-report').on('click', function() {
            exportDashboardReport();
        });

        $('#srwm-manage-suppliers').on('click', function() {
            window.location.href = 'admin.php?page=smart-restock-waitlist-suppliers';
        });

        $('#srwm-pro-features').on('click', function() {
            window.location.href = 'admin.php?page=smart-restock-waitlist-pro';
        });

        // Chart period selector
        $('.srwm-chart-period').on('change', function() {
            const days = $(this).val();
            loadChartData(days);
        });

        // Chart refresh button
        $('.srwm-btn-refresh-chart').on('click', function() {
            const $button = $(this);
            const $icon = $button.find('.dashicons');
            
            // Add loading state
            $button.prop('disabled', true);
            $icon.removeClass('dashicons-update').addClass('dashicons-update-alt');
            $icon.css('animation', 'spin 1s linear infinite');
            
            // Reload chart data
            loadChartData().always(function() {
                // Remove loading state
                $button.prop('disabled', false);
                $icon.removeClass('dashicons-update-alt').addClass('dashicons-update');
                $icon.css('animation', '');
            });
        });

        // Stat card interactions
        $('.srwm-stat-card').on('click', function() {
            const statType = $(this).find('h3').text().toLowerCase();
            handleStatCardClick(statType);
        });

        // Activity item interactions
        $(document).on('click', '.srwm-activity-item', function() {
            const activityType = $(this).data('type');
            handleActivityClick(activityType, $(this));
        });
    }

    /**
     * Handle stat card clicks
     */
    function handleStatCardClick(statType) {
        switch(statType) {
            case 'today\'s waitlists':
                window.location.href = 'admin.php?page=smart-restock-waitlist';
                break;
            case 'today\'s restocks':
                window.location.href = 'admin.php?page=smart-restock-waitlist-analytics';
                break;
            case 'pending notifications':
                window.location.href = 'admin.php?page=smart-restock-waitlist';
                break;
            case 'low stock products':
                window.location.href = 'admin.php?page=smart-restock-waitlist-settings';
                break;
        }
    }

    /**
     * Handle activity item clicks
     */
    function handleActivityClick(activityType, element) {
        const productName = element.find('.srwm-activity-message').text();
        
        // Show activity details in modal
        showActivityModal(activityType, productName);
    }

    /**
     * Show activity details modal
     */
    function showActivityModal(activityType, productName) {
        const modal = $(`
            <div class="srwm-modal" id="activityModal">
                <div class="srwm-modal-content">
                    <span class="srwm-modal-close">&times;</span>
                    <h3>Activity Details</h3>
                    <div class="srwm-modal-body">
                        <p><strong>Type:</strong> ${activityType}</p>
                        <p><strong>Product:</strong> ${productName}</p>
                        <p><strong>Time:</strong> ${new Date().toLocaleString()}</p>
                    </div>
                </div>
            </div>
        `);

        $('body').append(modal);
        modal.show();

        // Close modal functionality
        modal.find('.srwm-modal-close').on('click', function() {
            modal.remove();
        });

        $(window).on('click', function(e) {
            if (e.target === modal[0]) {
                modal.remove();
            }
        });
    }

    /**
     * Export dashboard report
     */
    function exportDashboardReport() {
        const $button = $('#srwm-export-report');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text(srwm_dashboard.messages.loading);

        $.ajax({
            url: srwm_dashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'srwm_export_dashboard_report',
                nonce: srwm_dashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', srwm_dashboard.messages.export_success);
                    
                    // Trigger download
                    const link = document.createElement('a');
                    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data);
                    link.download = 'srwm-dashboard-report-' + new Date().toISOString().split('T')[0] + '.csv';
                    link.click();
                } else {
                    showMessage('error', srwm_dashboard.messages.export_error);
                }
            },
            error: function() {
                showMessage('error', srwm_dashboard.messages.export_error);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Initialize real-time updates (disabled for now)
     */
    function initRealtimeUpdates() {
        // Real-time updates disabled - will be implemented in future version
        // This prevents unnecessary AJAX calls and improves performance
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            const $element = $(this);
            const tooltipText = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                showTooltip($element, tooltipText);
            }).on('mouseleave', function() {
                hideTooltip();
            });
        });
    }

    /**
     * Show tooltip
     */
    function showTooltip($element, text) {
        const tooltip = $(`
            <div class="srwm-tooltip">
                ${text}
            </div>
        `);
        
        $('body').append(tooltip);
        
        const elementPos = $element.offset();
        const elementWidth = $element.outerWidth();
        const elementHeight = $element.outerHeight();
        
        tooltip.css({
            position: 'absolute',
            top: elementPos.top - tooltip.outerHeight() - 10,
            left: elementPos.left + (elementWidth / 2) - (tooltip.outerWidth() / 2),
            zIndex: 10000
        });
        
        tooltip.fadeIn(200);
    }

    /**
     * Hide tooltip
     */
    function hideTooltip() {
        $('.srwm-tooltip').fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Show message
     */
    function showMessage(type, message) {
        const messageClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $(`
            <div class="notice ${messageClass} is-dismissible">
                <p>${message}</p>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Cleanup on page unload
     */
    $(window).on('beforeunload', function() {
        if (realtimeUpdateInterval) {
            clearInterval(realtimeUpdateInterval);
        }
    });

    // Global functions for external use
    window.srwm_init_charts = initCharts;
    window.srwm_init_realtime_updates = initRealtimeUpdates;
    
    /**
     * Initialize Enhanced UX Features
     */
    function initEnhancedUX() {
        initTabSwitching();
        initPeriodSelector();
        initStatCardInteractions();
        initFloatingActionButton();
        initAutoRefresh();
        initSmartNotifications();
    }

    /**
     * Initialize tab switching
     */
    function initTabSwitching() {
        $('.srwm-tab-button').on('click', function() {
            const tab = $(this).data('tab');
            switchTab(tab);
        });
    }

    /**
     * Switch between dashboard tabs
     */
    function switchTab(tabName) {
        // Update tab buttons
        $('.srwm-tab-button').removeClass('active');
        $(`.srwm-tab-button[data-tab="${tabName}"]`).addClass('active');
        
        // Show/hide content sections
        $('.srwm-tab-content').removeClass('active');
        $(`.srwm-tab-content[data-tab="${tabName}"]`).addClass('active');
        
        // Load tab-specific data
        loadTabData(tabName);
    }

    /**
     * Load tab-specific data
     */
    function loadTabData(tabName) {
        switch(tabName) {
            case 'analytics':
                loadChartData();
                break;
            case 'reports':
                loadReportData();
                break;
            case 'actions':
                loadActionData();
                break;
        }
    }

    /**
     * Initialize period selector
     */
    function initPeriodSelector() {
        $('#srwm-dashboard-period').on('change', function() {
            const period = $(this).val();
            updateDashboardPeriod(period);
        });
    }

    /**
     * Update dashboard period
     */
    function updateDashboardPeriod(period) {
        // Show loading state
        $('.srwm-dashboard-content').addClass('srwm-loading');
        
        // Update charts and data based on period
        loadChartData(period);
        
        // Update URL parameter
        const url = new URL(window.location);
        url.searchParams.set('period', period);
        window.history.replaceState({}, '', url);
    }

    /**
     * Initialize stat card interactions
     */
    function initStatCardInteractions() {
        $('.srwm-stat-card').on('click', function() {
            showCardDetails($(this));
        });
    }

    /**
     * Show stat card details modal
     */
    function showCardDetails(card) {
        const metric = card.data('metric') || 'Metric';
        const value = card.find('.srwm-stat-number').text();
        
        // Create and show modal
        const modal = $(`
            <div class="srwm-modal">
                <div class="srwm-modal-content">
                    <div class="srwm-modal-header">
                        <h3>${metric} Details</h3>
                        <button class="srwm-modal-close">&times;</button>
                    </div>
                    <div class="srwm-modal-body">
                        <div class="srwm-metric-details">
                            <div class="srwm-metric-value">${value}</div>
                            <div class="srwm-metric-chart">
                                <canvas id="metricChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        // Close modal functionality
        modal.find('.srwm-modal-close').on('click', function() {
            modal.remove();
        });
        
        // Click outside to close
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
    }

    /**
     * Initialize floating action button
     */
    function initFloatingActionButton() {
        const fab = $(`
            <button class="srwm-fab" title="Quick Actions">
                <span class="dashicons dashicons-plus"></span>
            </button>
        `);
        
        $('body').append(fab);
        
        fab.on('click', function() {
            showQuickActionsMenu();
        });
    }

    /**
     * Show quick actions menu
     */
    function showQuickActionsMenu() {
        const menu = $(`
            <div class="srwm-quick-actions-menu">
                <div class="srwm-quick-action-item" data-action="add-waitlist">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span>Add to Waitlist</span>
                </div>
                <div class="srwm-quick-action-item" data-action="export-data">
                    <span class="dashicons dashicons-download"></span>
                    <span>Export Data</span>
                </div>
                <div class="srwm-quick-action-item" data-action="refresh">
                    <span class="dashicons dashicons-update"></span>
                    <span>Refresh</span>
                </div>
            </div>
        `);
        
        $('body').append(menu);
        
        // Handle action clicks
        menu.find('.srwm-quick-action-item').on('click', function() {
            const action = $(this).data('action');
            handleQuickAction(action);
            menu.remove();
        });
        
        // Auto-remove after 5 seconds
        setTimeout(() => menu.remove(), 5000);
    }

    /**
     * Handle quick actions
     */
    function handleQuickAction(action) {
        switch(action) {
            case 'add-waitlist':
                window.location.href = 'admin.php?page=smart-restock-waitlist-settings';
                break;
            case 'export-data':
                $('#srwm-export-report').click();
                break;
            case 'refresh':
                $('#srwm-refresh-dashboard').click();
                break;
        }
    }

    /**
     * Initialize auto-refresh
     */
    function initAutoRefresh() {
        let refreshInterval;
        
        // Start auto-refresh every 30 seconds
        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                refreshDashboardData();
            }, 30000);
        }
        
        // Stop auto-refresh
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
        
        // Start auto-refresh when page becomes visible
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });
        
        // Start initially
        startAutoRefresh();
    }

    /**
     * Initialize smart notifications
     */
    function initSmartNotifications() {
        // Check for important updates
        setInterval(() => {
            checkForUpdates();
        }, 60000); // Check every minute
    }

    /**
     * Check for updates
     */
    function checkForUpdates() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'srwm_check_updates',
                nonce: srwm_dashboard_nonce
            },
            success: function(response) {
                if (response.success && response.data.updates) {
                    showSmartNotification(response.data.updates);
                }
            }
        });
    }

    /**
     * Show smart notification
     */
    function showSmartNotification(updates) {
        const notification = $(`
            <div class="srwm-smart-notification">
                <div class="srwm-notification-icon">
                    <span class="dashicons dashicons-bell"></span>
                </div>
                <div class="srwm-notification-content">
                    <h4>New Updates</h4>
                    <p>${updates.message}</p>
                </div>
                <button class="srwm-notification-close">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
        
        // Manual close
        notification.find('.srwm-notification-close').on('click', function() {
            notification.remove();
        });
    }

})(jQuery);