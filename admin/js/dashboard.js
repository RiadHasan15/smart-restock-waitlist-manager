/**
 * Smart Restock & Waitlist Manager - Dashboard JavaScript
 */

(function($) {
    'use strict';
    
    console.log('SRWM Dashboard: Script loaded successfully');

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
        
        console.log('SRWM Dashboard: Initializing dashboard...');
        
        initCharts();
        initEventHandlers();
        initRealtimeUpdates();
        initTooltips();
        
        // Load initial chart data
        loadChartData();
        
        console.log('SRWM Dashboard: Dashboard initialized successfully');
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
        console.log('SRWM Dashboard: Loading chart data for', days, 'days');
        
        // Check if srwm_dashboard is available
        if (typeof srwm_dashboard === 'undefined') {
            console.error('SRWM Dashboard: srwm_dashboard not available');
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
                console.log('SRWM Dashboard: Chart data response:', response);
                
                if (response.success) {
                    updateCharts(response.data);
                } else {
                    console.error('SRWM Dashboard: Chart data error:', response.data);
                    showMessage('error', response.data || 'Failed to load chart data');
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
        console.log('SRWM Dashboard: Updating charts with data:', data);
        
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('SRWM Dashboard: Chart.js not available for chart updates');
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

        // Dashboard refresh button
        $('#srwm-refresh-dashboard').on('click', function() {
            const $button = $(this);
            const $icon = $button.find('.dashicons');
            
            console.log('SRWM Dashboard: Refresh button clicked');
            
            // Add loading state
            $button.prop('disabled', true);
            $icon.removeClass('dashicons-update').addClass('dashicons-update-alt');
            $icon.css('animation', 'spin 1s linear infinite');
            
            // Reload chart data and refresh statistics
            loadChartData().always(function() {
                // Also refresh statistics cards
                refreshStatistics();
                
                // Remove loading state
                $button.prop('disabled', false);
                $icon.removeClass('dashicons-update-alt').addClass('dashicons-update');
                $icon.css('animation', '');
                
                // Show success message
                showMessage('success', 'Dashboard data refreshed successfully!');
                
                console.log('SRWM Dashboard: Refresh completed');
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
                console.log('SRWM Dashboard: Export response:', response);
                
                if (response.success) {
                    showMessage('success', srwm_dashboard.messages.export_success);
                    
                    // Trigger download
                    const link = document.createElement('a');
                    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data);
                    link.download = 'srwm-dashboard-report-' + new Date().toISOString().split('T')[0] + '.csv';
                    link.click();
                    
                    console.log('SRWM Dashboard: Export download triggered');
                } else {
                    console.error('SRWM Dashboard: Export failed:', response.data);
                    showMessage('error', response.data || srwm_dashboard.messages.export_error);
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
     * Initialize real-time updates
     */
    function initRealtimeUpdates() {
        console.log('SRWM Dashboard: Initializing real-time updates...');
        
        // Start auto-refresh every 30 seconds
        setInterval(function() {
            refreshDashboardData();
        }, 30000); // 30 seconds
        
        console.log('SRWM Dashboard: Real-time updates initialized (30s interval)');
    }
    
    /**
     * Refresh dashboard data (real-time update)
     */
    function refreshDashboardData() {
        console.log('SRWM Dashboard: Auto-refreshing dashboard data...');
        
        // Show subtle refresh indicator
        showRefreshIndicator();
        
        // Refresh statistics
        refreshStatistics();
        
        // Refresh charts
        loadChartData();
    }
    
    /**
     * Show subtle refresh indicator
     */
    function showRefreshIndicator() {
        // Add a subtle pulse animation to the refresh button
        const $refreshBtn = $('#srwm-refresh-dashboard');
        if ($refreshBtn.length) {
            $refreshBtn.addClass('srwm-auto-refresh');
            setTimeout(function() {
                $refreshBtn.removeClass('srwm-auto-refresh');
            }, 2000);
        }
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
     * Refresh statistics cards with real data
     */
    function refreshStatistics() {
        console.log('SRWM Dashboard: Refreshing statistics...');
        
        $.ajax({
            url: srwm_dashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'srwm_get_dashboard_data',
                nonce: srwm_dashboard.nonce
            },
            success: function(response) {
                console.log('SRWM Dashboard: Statistics response:', response);
                
                if (response.success && response.data) {
                    const data = response.data;
                    
                    // Update statistics cards
                    updateStatCard('total_waitlist_customers', data.total_waitlist_customers || 0);
                    updateStatCard('waitlist_products', data.waitlist_products || 0);
                    updateStatCard('avg_restock_time', data.avg_restock_time || 0);
                    
                    // Update additional stats
                    updateStatCard('today_waitlists', data.today_waitlists || 0);
                    updateStatCard('today_restocks', data.today_restocks || 0);
                    updateStatCard('pending_notifications', data.pending_notifications || 0);
                    updateStatCard('low_stock_products', data.low_stock_products || 0);
                    
                    console.log('SRWM Dashboard: Statistics updated successfully');
                } else {
                    console.error('SRWM Dashboard: Failed to refresh statistics:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('SRWM Dashboard: Error refreshing statistics:', error);
            }
        });
    }
    
    /**
     * Update individual stat card
     */
    function updateStatCard(statType, value) {
        // Find the stat card by data attribute
        const $card = $(`.srwm-stat-card[data-stat="${statType}"]`);
        
        if ($card.length > 0) {
            const $statNumber = $card.find('.srwm-stat-number');
            
            // Format the value based on type
            let formattedValue = value;
            if (statType === 'avg_restock_time') {
                formattedValue = parseFloat(value).toFixed(1);
            } else {
                formattedValue = parseInt(value).toLocaleString();
            }
            
            // Update with animation
            $statNumber.fadeOut(200, function() {
                $(this).text(formattedValue).fadeIn(200);
            });
            
            console.log(`SRWM Dashboard: Updated ${statType} to ${formattedValue}`);
        } else {
            console.warn(`SRWM Dashboard: Stat card not found for ${statType}`);
        }
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
    
    // Enhanced UX functions temporarily disabled to fix conflicts

    // Floating action button temporarily disabled

    // Quick actions menu temporarily disabled

    // Quick action handler temporarily disabled

    // Auto-refresh temporarily disabled

    // Smart notifications temporarily disabled

})(jQuery);