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
        initDashboardTabs();
        initInteractiveTables();
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

        // Global period selector
        $('#srwm-global-period').on('change', function() {
            const days = $(this).val();
            console.log('SRWM Dashboard: Global period changed to', days, 'days');
            
            // Update all chart period selectors to match
            $('.srwm-chart-period').val(days);
            
            // Load new data for the selected period
            loadDashboardData(days);
        });
        
        // Chart period selector (sync with global)
        $('.srwm-chart-period').on('change', function() {
            const days = $(this).val();
            console.log('SRWM Dashboard: Chart period changed to', days, 'days');
            
            // Update global period selector to match
            $('#srwm-global-period').val(days);
            
            // Load chart data for the selected period
            loadChartData(days);
        });

        // Dashboard refresh button
        $('#srwm-refresh-dashboard').on('click', function() {
            console.log('SRWM Dashboard: Refresh button clicked');
            
            // Get current period
            const currentPeriod = $('#srwm-global-period').val() || 7;
            
            // Load dashboard data for current period
            loadDashboardData(currentPeriod).always(function() {
                // Show success message
                showMessage('success', 'Dashboard data refreshed successfully!');
                console.log('SRWM Dashboard: Refresh completed');
            });
        });

        // Stat card interactions - Enhanced with detailed data
        $('.srwm-stat-card').on('click', function() {
            const statType = $(this).data('stat');
            console.log('SRWM Dashboard: Stat card clicked:', statType);
            handleStatCardClick(statType);
        });
        
        // Demo button interactions
        $(document).on('click', '.view-waitlist[data-product-id^="demo"]', function() {
            const productId = $(this).data('product-id');
            showMessage('info', 'Demo: View waitlist for ' + productId + ' (This would show actual waitlist data)');
        });
        
        $(document).on('click', '.restock-product[data-product-id^="demo"]', function() {
            const productId = $(this).data('product-id');
            showMessage('info', 'Demo: Restock product ' + productId + ' (This would trigger restock process)');
        });

        // Activity item interactions
        $(document).on('click', '.srwm-activity-item', function() {
            const activityType = $(this).data('type');
            handleActivityClick(activityType, $(this));
        });
        
        // Modal close functionality
        $(document).on('click', '.srwm-modal-close', function() {
            $(this).closest('.srwm-modal').hide();
        });
        
        // Close modal when clicking outside
        $(document).on('click', '.srwm-modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    }

    /**
     * Handle stat card clicks with detailed data
     */
    function handleStatCardClick(statType) {
        console.log('SRWM Dashboard: Handling stat card click for:', statType);
        
        // Show loading state
        showStatCardLoading(statType);
        
        // Load detailed data for the stat card
        loadStatCardDetails(statType);
    }
    
    /**
     * Show loading state for stat card
     */
    function showStatCardLoading(statType) {
        const $modal = $('#srwm-stat-detail-modal');
        const $content = $('#srwm-stat-modal-content');
        
        // Set modal title
        const titles = {
            'total_waitlist_customers': 'Total Waitlist Customers',
            'waitlist_products': 'Products with Waitlist',
            'avg_restock_time': 'Average Restock Time',
            'today_waitlists': 'Today\'s Waitlists',
            'today_restocks': 'Today\'s Restocks',
            'pending_notifications': 'Pending Notifications',
            'low_stock_products': 'Low Stock Products'
        };
        
        $('#srwm-stat-modal-title').text(titles[statType] || 'Statistics Details');
        
        // Show loading content
        $content.html(`
            <div class="srwm-loading-content">
                <div class="srwm-loading-spinner"></div>
                <p>Loading detailed data...</p>
            </div>
        `);
        
        $modal.show();
    }
    
    /**
     * Load detailed data for stat card
     */
    function loadStatCardDetails(statType) {
        console.log('SRWM Dashboard: Loading details for:', statType);
        
        const $content = $('#srwm-stat-modal-content');
        
        // Make AJAX call to get real data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'srwm_get_stat_card_details',
                stat_type: statType,
                nonce: srwm_dashboard.nonce
            },
            success: function(response) {
                console.log('SRWM Dashboard: Stat details response:', response);
                
                if (response.success && response.data) {
                    const data = response.data;
                    
                    if (data.error) {
                        $content.html('<p class="srwm-error">Error: ' + data.error + '</p>');
                        return;
                    }
                    
                    switch(statType) {
                        case 'total_waitlist_customers':
                            $content.html(generateWaitlistCustomersDetails(data));
                            break;
                        case 'waitlist_products':
                            $content.html(generateWaitlistProductsDetails(data));
                            break;
                        case 'avg_restock_time':
                            $content.html(generateRestockTimeDetails(data));
                            break;
                        case 'today_waitlists':
                            $content.html(generateTodayWaitlistsDetails(data));
                            break;
                        case 'today_restocks':
                            $content.html(generateTodayRestocksDetails(data));
                            break;
                        case 'pending_notifications':
                            $content.html(generatePendingNotificationsDetails(data));
                            break;
                        case 'low_stock_products':
                            $content.html(generateLowStockProductsDetails(data));
                            break;
                        default:
                            $content.html('<p>No detailed data available for this statistic.</p>');
                    }
                } else {
                    $content.html('<p class="srwm-error">Failed to load data. Please try again.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('SRWM Dashboard: AJAX error:', error);
                $content.html('<p class="srwm-error">Error loading data. Please try again.</p>');
            }
        });
    }
    
    /**
     * Generate waitlist customers details
     */
    function generateWaitlistCustomersDetails(data) {
        const summary = data.summary || {};
        const recentActivity = data.recent_activity || [];
        
        let activityRows = '';
        if (recentActivity.length > 0) {
            recentActivity.forEach(function(activity) {
                const status = activity.notified == 1 ? 'Notified' : 'Waiting';
                const statusClass = activity.notified == 1 ? 'srwm-status-notified' : 'srwm-status-waiting';
                const productName = activity.product_name || `Product ID: ${activity.product_id}`;
                activityRows += `
                    <tr>
                        <td>${activity.email}</td>
                        <td>${productName}</td>
                        <td>${activity.date_added}</td>
                        <td><span class="srwm-status ${statusClass}">${status}</span></td>
                    </tr>
                `;
            });
        } else {
            activityRows = '<tr><td colspan="4">No recent activity</td></tr>';
        }
        
        return `
            <div class="srwm-stat-detail-grid">
                <div class="srwm-stat-detail-card">
                    <h4>Total Customers</h4>
                    <div class="value">${summary.total_customers || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Active Waitlists</h4>
                    <div class="value">${summary.active_waitlists || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Average Wait Time</h4>
                    <div class="value">${summary.avg_wait_time || 'N/A'}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Conversion Rate</h4>
                    <div class="value">${summary.conversion_rate || '0%'}</div>
                </div>
            </div>
            <h3>Recent Waitlist Activity</h3>
            <table class="srwm-stat-detail-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Date Added</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${activityRows}
                </tbody>
            </table>
        `;
    }
    
    /**
     * Generate waitlist products details
     */
    function generateWaitlistProductsDetails(data) {
        const summary = data.summary || {};
        const topProducts = data.top_products || [];
        
        let productRows = '';
        if (topProducts.length > 0) {
            topProducts.forEach(function(product) {
                const productName = product.product_name || `Product ID: ${product.product_id}`;
                productRows += `
                    <tr>
                        <td>${productName}</td>
                        <td>${product.waitlist_count}</td>
                        <td>${product.active_count}</td>
                        <td><span class="srwm-status srwm-status-waiting">Active</span></td>
                    </tr>
                `;
            });
        } else {
            productRows = '<tr><td colspan="4">No products with waitlists</td></tr>';
        }
        
        return `
            <div class="srwm-stat-detail-grid">
                <div class="srwm-stat-detail-card">
                    <h4>Total Products</h4>
                    <div class="value">${summary.total_products || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>High Demand</h4>
                    <div class="value">${summary.high_demand || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Out of Stock</h4>
                    <div class="value">${summary.out_of_stock || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Low Stock</h4>
                    <div class="value">${summary.low_stock || 0}</div>
                </div>
            </div>
            <h3>Top Waitlisted Products</h3>
            <table class="srwm-stat-detail-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Waitlist Count</th>
                        <th>Active Waitlists</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${productRows}
                </tbody>
            </table>
        `;
    }
    
    /**
     * Generate restock time details
     */
    function generateRestockTimeDetails(data) {
        const summary = data.summary || {};
        const recentActivity = data.recent_activity || [];
        
        let activityRows = '';
        if (recentActivity.length > 0) {
            recentActivity.forEach(function(activity) {
                const date = new Date(activity.timestamp).toLocaleDateString();
                const productName = activity.product_name || `Product ID: ${activity.product_id}`;
                activityRows += `
                    <tr>
                        <td>${productName}</td>
                        <td>${date}</td>
                        <td>${activity.quantity} units</td>
                        <td>${activity.method}</td>
                    </tr>
                `;
            });
        } else {
            activityRows = '<tr><td colspan="4">No recent restock activity</td></tr>';
        }
        
        return `
            <div class="srwm-stat-detail-grid">
                <div class="srwm-stat-detail-card">
                    <h4>Total Restocks</h4>
                    <div class="value">${summary.total_restocks || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Methods Used</h4>
                    <div class="value">${Object.keys(summary.methods || {}).length}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Most Popular</h4>
                    <div class="value">${getMostPopularMethod(summary.methods)}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Recent Activity</h4>
                    <div class="value">${recentActivity.length} items</div>
                </div>
            </div>
            <h3>Recent Restock Activity</h3>
            <table class="srwm-stat-detail-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Restock Date</th>
                        <th>Quantity</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    ${activityRows}
                </tbody>
            </table>
        `;
    }
    
    /**
     * Get most popular method
     */
    function getMostPopularMethod(methods) {
        if (!methods || Object.keys(methods).length === 0) {
            return 'N/A';
        }
        
        let mostPopular = '';
        let maxCount = 0;
        
        for (const [method, count] of Object.entries(methods)) {
            if (count > maxCount) {
                maxCount = count;
                mostPopular = method;
            }
        }
        
        return mostPopular;
    }
    
    /**
     * Generate today's waitlists details
     */
    function generateTodayWaitlistsDetails(data) {
        const summary = data.summary || {};
        const recentActivity = data.recent_activity || [];
        
        let activityRows = '';
        if (recentActivity.length > 0) {
            recentActivity.forEach(function(activity) {
                const time = new Date(activity.date_added).toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false 
                });
                const productName = activity.product_name || `Product ID: ${activity.product_id}`;
                activityRows += `
                    <tr>
                        <td>${time}</td>
                        <td>${activity.email}</td>
                        <td>${productName}</td>
                        <td><span class="srwm-status srwm-status-waiting">Added</span></td>
                    </tr>
                `;
            });
        } else {
            activityRows = '<tr><td colspan="4">No waitlists added today</td></tr>';
        }
        
        return `
            <div class="srwm-stat-detail-grid">
                <div class="srwm-stat-detail-card">
                    <h4>New Today</h4>
                    <div class="value">${summary.new_today || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Hourly Breakdown</h4>
                    <div class="value">${Object.keys(summary.hourly_breakdown || {}).length} hours</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Peak Hour</h4>
                    <div class="value">${getPeakHour(summary.hourly_breakdown)}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Average per Hour</h4>
                    <div class="value">${getAveragePerHour(summary.hourly_breakdown, summary.new_today)}</div>
                </div>
            </div>
            <h3>Today's Waitlist Activity</h3>
            <table class="srwm-stat-detail-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${activityRows}
                </tbody>
            </table>
        `;
    }
    
    /**
     * Get peak hour from hourly breakdown
     */
    function getPeakHour(hourlyBreakdown) {
        if (!hourlyBreakdown || Object.keys(hourlyBreakdown).length === 0) {
            return 'N/A';
        }
        
        let peakHour = '';
        let maxCount = 0;
        
        for (const [hour, count] of Object.entries(hourlyBreakdown)) {
            if (count > maxCount) {
                maxCount = count;
                peakHour = hour + ':00';
            }
        }
        
        return peakHour;
    }
    
    /**
     * Get average per hour
     */
    function getAveragePerHour(hourlyBreakdown, total) {
        if (!hourlyBreakdown || Object.keys(hourlyBreakdown).length === 0 || !total) {
            return '0';
        }
        
        const activeHours = Object.keys(hourlyBreakdown).length;
        return (total / activeHours).toFixed(1);
    }
    
    /**
     * Generate today's restocks details
     */
    function generateTodayRestocksDetails(data) {
        const summary = data.summary || {};
        const recentActivity = data.recent_activity || [];
        
        let activityRows = '';
        if (recentActivity.length > 0) {
            recentActivity.forEach(function(activity) {
                const time = new Date(activity.timestamp).toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false 
                });
                const productName = activity.product_name || `Product ID: ${activity.product_id}`;
                activityRows += `
                    <tr>
                        <td>${time}</td>
                        <td>${productName}</td>
                        <td>${activity.quantity}</td>
                        <td>${activity.method}</td>
                    </tr>
                `;
            });
        } else {
            activityRows = '<tr><td colspan="4">No restocks today</td></tr>';
        }
        
        return `
            <div class="srwm-stat-detail-grid">
                <div class="srwm-stat-detail-card">
                    <h4>Restocks Today</h4>
                    <div class="value">${summary.restocks_today || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Total Stock Added</h4>
                    <div class="value">${summary.total_stock_added || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Average per Restock</h4>
                    <div class="value">${summary.restocks_today > 0 ? Math.round(summary.total_stock_added / summary.restocks_today) : 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Methods Used</h4>
                    <div class="value">${Object.keys(summary.methods || {}).length}</div>
                </div>
            </div>
            <h3>Today's Restock Activity</h3>
            <table class="srwm-stat-detail-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Product</th>
                        <th>Stock Added</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    ${activityRows}
                </tbody>
            </table>
        `;
    }
    
    /**
     * Generate pending notifications details
     */
    function generatePendingNotificationsDetails(data) {
        const summary = data.summary || {};
        const recentActivity = data.recent_activity || [];
        
        let activityRows = '';
        if (recentActivity.length > 0) {
            recentActivity.forEach(function(activity) {
                activityRows += `
                    <tr>
                        <td>${activity.email}</td>
                        <td>Product ID: ${activity.product_id}</td>
                        <td>${activity.type}</td>
                        <td><span class="srwm-status srwm-status-pending">${activity.status}</span></td>
                    </tr>
                `;
            });
        } else {
            activityRows = '<tr><td colspan="4">No pending notifications</td></tr>';
        }
        
        return `
            <div class="srwm-stat-detail-grid">
                <div class="srwm-stat-detail-card">
                    <h4>Pending</h4>
                    <div class="value">${summary.pending || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Email</h4>
                    <div class="value">${summary.email || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>SMS</h4>
                    <div class="value">${summary.sms || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>WhatsApp</h4>
                    <div class="value">${summary.whatsapp || 0}</div>
                </div>
            </div>
            <h3>Pending Notifications</h3>
            <table class="srwm-stat-detail-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${activityRows}
                </tbody>
            </table>
        `;
    }
    
    /**
     * Generate low stock products details
     */
    function generateLowStockProductsDetails(data) {
        const summary = data.summary || {};
        const recentActivity = data.recent_activity || [];
        
        let activityRows = '';
        if (recentActivity.length > 0) {
            recentActivity.forEach(function(activity) {
                const statusClass = getStockStatusClass(activity.status);
                activityRows += `
                    <tr>
                        <td>Product ID: ${activity.product_id}</td>
                        <td>${activity.current_stock}</td>
                        <td>${activity.threshold}</td>
                        <td><span class="srwm-status ${statusClass}">${activity.status}</span></td>
                    </tr>
                `;
            });
        } else {
            activityRows = '<tr><td colspan="4">No low stock products</td></tr>';
        }
        
        return `
            <div class="srwm-stat-detail-grid">
                <div class="srwm-stat-detail-card">
                    <h4>Low Stock</h4>
                    <div class="value">${summary.low_stock || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Out of Stock</h4>
                    <div class="value">${summary.out_of_stock || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Critical Level</h4>
                    <div class="value">${summary.critical_level || 0}</div>
                </div>
                <div class="srwm-stat-detail-card">
                    <h4>Total Value</h4>
                    <div class="value">${summary.total_value || '$0'}</div>
                </div>
            </div>
            <h3>Low Stock Products</h3>
            <table class="srwm-stat-detail-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${activityRows}
                </tbody>
            </table>
        `;
    }
    
    /**
     * Get stock status class
     */
    function getStockStatusClass(status) {
        switch(status) {
            case 'Out of Stock': return 'srwm-status-out';
            case 'Low Stock': return 'srwm-status-low';
            case 'Critical': return 'srwm-status-critical';
            default: return 'srwm-status-waiting';
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
     * Load dashboard data (charts + statistics) for a specific period
     */
    function loadDashboardData(days = 7) {
        console.log('SRWM Dashboard: Loading dashboard data for', days, 'days');
        
        // Show loading state
        showDashboardLoading();
        
        // Load both charts and statistics
        $.when(
            loadChartData(days),
            loadStatisticsData(days)
        ).always(function() {
            // Hide loading state
            hideDashboardLoading();
            console.log('SRWM Dashboard: Dashboard data loading completed');
        });
    }
    
    /**
     * Load statistics data for a specific period
     */
    function loadStatisticsData(days = 7) {
        console.log('SRWM Dashboard: Loading statistics data for', days, 'days');
        
        return $.ajax({
            url: srwm_dashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'srwm_get_dashboard_data',
                nonce: srwm_dashboard.nonce,
                days: days
            },
            success: function(response) {
                console.log('SRWM Dashboard: Statistics response:', response);
                
                if (response.success && response.data) {
                    const data = response.data;
                    
                    // Update statistics cards
                    updateStatCard('total_waitlist_customers', data.total_waitlist_customers || 0);
                    updateStatCard('waitlist_products', data.waitlist_products || 0);
                    updateStatCard('avg_restock_time', data.avg_restock_time || 0);
                    updateStatCard('today_waitlists', data.today_waitlists || 0);
                    updateStatCard('today_restocks', data.today_restocks || 0);
                    updateStatCard('pending_notifications', data.pending_notifications || 0);
                    updateStatCard('low_stock_products', data.low_stock_products || 0);
                    
                    console.log('SRWM Dashboard: Statistics updated successfully');
                } else {
                    console.error('SRWM Dashboard: Failed to load statistics:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('SRWM Dashboard: Error loading statistics:', error);
            }
        });
    }
    
    /**
     * Show dashboard loading state
     */
    function showDashboardLoading() {
        // Add loading class to dashboard
        $('.srwm-dashboard').addClass('srwm-loading');
        
        // Show loading indicator on refresh button
        const $refreshBtn = $('#srwm-refresh-dashboard');
        if ($refreshBtn.length) {
            $refreshBtn.prop('disabled', true);
            $refreshBtn.find('.dashicons').addClass('dashicons-update-alt');
            $refreshBtn.find('.dashicons').css('animation', 'spin 1s linear infinite');
        }
    }
    
    /**
     * Hide dashboard loading state
     */
    function hideDashboardLoading() {
        // Remove loading class from dashboard
        $('.srwm-dashboard').removeClass('srwm-loading');
        
        // Hide loading indicator on refresh button
        const $refreshBtn = $('#srwm-refresh-dashboard');
        if ($refreshBtn.length) {
            $refreshBtn.prop('disabled', false);
            $refreshBtn.find('.dashicons').removeClass('dashicons-update-alt');
            $refreshBtn.find('.dashicons').css('animation', '');
        }
    }
    
    /**
     * Initialize interactive tables
     */
    function initInteractiveTables() {
        console.log('SRWM Dashboard: Initializing interactive tables...');
        
        // Check if table exists
        const $table = $('#srwm-waitlist-table');
        if ($table.length) {
            console.log('SRWM Dashboard: Found waitlist table, initializing features...');
            
            // Initialize waitlist table
            initTableSorting('#srwm-waitlist-table');
            initTableFiltering('#srwm-waitlist-table');
            initTableSearch('#srwm-waitlist-table');
            
            console.log('SRWM Dashboard: Interactive tables initialized successfully');
        } else {
            console.log('SRWM Dashboard: Waitlist table not found');
        }
    }
    
    /**
     * Initialize table sorting
     */
    function initTableSorting(tableSelector) {
        const $table = $(tableSelector);
        if (!$table.length) return;
        
        $table.find('.srwm-sortable').on('click', function() {
            const $header = $(this);
            const sortType = $header.data('sort');
            const currentOrder = $header.hasClass('sorted-asc') ? 'desc' : 'asc';
            
            console.log('SRWM Dashboard: Sorting table by', sortType, 'in', currentOrder, 'order');
            
            // Remove sort classes from all headers
            $table.find('.srwm-sortable').removeClass('sorted-asc sorted-desc');
            
            // Add sort class to clicked header
            $header.addClass('sorted-' + currentOrder);
            
            // Sort table rows
            sortTableRows($table, sortType, currentOrder);
        });
    }
    
    /**
     * Sort table rows
     */
    function sortTableRows($table, sortType, order) {
        const $tbody = $table.find('tbody');
        const $rows = $tbody.find('tr').toArray();
        
        $rows.sort(function(a, b) {
            let aValue, bValue;
            
            switch(sortType) {
                case 'product':
                    aValue = $(a).find('.srwm-product-info strong').text().toLowerCase();
                    bValue = $(b).find('.srwm-product-info strong').text().toLowerCase();
                    break;
                case 'stock':
                    aValue = parseInt($(a).find('.srwm-stock-badge').text()) || 0;
                    bValue = parseInt($(b).find('.srwm-stock-badge').text()) || 0;
                    break;
                case 'waitlist':
                    aValue = parseInt($(a).find('.srwm-waitlist-count').text()) || 0;
                    bValue = parseInt($(b).find('.srwm-waitlist-count').text()) || 0;
                    break;
                case 'status':
                    aValue = $(a).find('.srwm-status').text().toLowerCase();
                    bValue = $(b).find('.srwm-status').text().toLowerCase();
                    break;
                default:
                    return 0;
            }
            
            if (order === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
        
        // Re-append sorted rows
        $tbody.empty().append($rows);
    }
    
    /**
     * Initialize table filtering
     */
    function initTableFiltering(tableSelector) {
        const $table = $(tableSelector);
        if (!$table.length) return;
        
        // Status filter
        $('#srwm-status-filter').on('change', function() {
            const filterValue = $(this).val();
            console.log('SRWM Dashboard: Filtering table by status:', filterValue);
            
            $table.find('tbody tr').each(function() {
                const $row = $(this);
                const statusText = $row.find('.srwm-status').text().toLowerCase();
                
                if (!filterValue || statusText.includes(filterValue)) {
                    $row.removeClass('srwm-table-row-hidden');
                } else {
                    $row.addClass('srwm-table-row-hidden');
                }
            });
        });
    }
    
    /**
     * Initialize table search
     */
    function initTableSearch(tableSelector) {
        const $table = $(tableSelector);
        if (!$table.length) return;
        
        $('#srwm-waitlist-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            console.log('SRWM Dashboard: Searching table for:', searchTerm);
            
            $table.find('tbody tr').each(function() {
                const $row = $(this);
                const productName = $row.find('.srwm-product-info strong').text().toLowerCase();
                const sku = $row.find('.srwm-product-info small').text().toLowerCase();
                
                if (productName.includes(searchTerm) || sku.includes(searchTerm)) {
                    $row.removeClass('srwm-table-row-hidden');
                    if (searchTerm) {
                        $row.addClass('srwm-table-row-highlight');
                    } else {
                        $row.removeClass('srwm-table-row-highlight');
                    }
                } else {
                    $row.addClass('srwm-table-row-hidden');
                    $row.removeClass('srwm-table-row-highlight');
                }
            });
        });
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
     * Initialize dashboard tabs
     */
    function initDashboardTabs() {
        console.log('SRWM Dashboard: Initializing tabs...');
        
        // Tab switching functionality
        $('.srwm-tab-button').on('click', function() {
            const tabName = $(this).data('tab');
            console.log('SRWM Dashboard: Switching to tab:', tabName);
            
            // Remove active class from all tabs and content
            $('.srwm-tab-button').removeClass('active');
            $('.srwm-tab-content').removeClass('active');
            
            // Add active class to clicked tab and corresponding content
            $(this).addClass('active');
            $(`.srwm-tab-content[data-tab="${tabName}"]`).addClass('active');
            
            // Load tab-specific data if needed
            loadTabData(tabName);
        });
        
        console.log('SRWM Dashboard: Tabs initialized');
    }
    
    /**
     * Load tab-specific data
     */
    function loadTabData(tabName) {
        console.log('SRWM Dashboard: Loading data for tab:', tabName);
        
        switch(tabName) {
            case 'overview':
                // Overview tab data is already loaded
                break;
            case 'analytics':
                // Analytics tab - could load additional analytics data
                console.log('SRWM Dashboard: Analytics tab selected');
                break;
            case 'reports':
                // Reports tab - could load report templates
                console.log('SRWM Dashboard: Reports tab selected');
                break;
            case 'actions':
                // Actions tab - could load quick action shortcuts
                console.log('SRWM Dashboard: Actions tab selected');
                break;
        }
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
        let messageClass;
        let icon;
        
        switch(type) {
            case 'success':
                messageClass = 'notice-success';
                icon = 'dashicons-yes-alt';
                break;
            case 'error':
                messageClass = 'notice-error';
                icon = 'dashicons-dismiss';
                break;
            case 'info':
                messageClass = 'notice-info';
                icon = 'dashicons-info';
                break;
            default:
                messageClass = 'notice-info';
                icon = 'dashicons-info';
        }
        
        const notice = $(`
            <div class="notice ${messageClass} is-dismissible">
                <p><span class="dashicons ${icon}"></span> ${message}</p>
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