<?php
/**
 * Modern Analytics Dashboard View for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Initialize analytics processor
require_once RTBCB_DIR . 'admin/includes/analytics-processor.php';
$analytics = new RTBCB_Analytics_Processor();

// Get analytics summary
$summary = $analytics->get_analytics_summary( 30 );

// Date range options
$date_ranges = [
    '7'   => __( 'Last 7 Days', 'rtbcb' ),
    '30'  => __( 'Last 30 Days', 'rtbcb' ),
    '90'  => __( 'Last 90 Days', 'rtbcb' ),
    '365' => __( 'Last Year', 'rtbcb' ),
];

$current_range = sanitize_text_field( $_GET['range'] ?? '30' );
?>

<div class="rtbcb-admin-page rtbcb-analytics-page">
    <div class="rtbcb-page-header">
        <h1><?php esc_html_e( 'Analytics & Reports', 'rtbcb' ); ?></h1>
        <div class="rtbcb-page-actions">
            <select class="rtbcb-form-select rtbcb-date-range-selector" data-current="<?php echo esc_attr( $current_range ); ?>">
                <?php foreach ( $date_ranges as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="rtbcb-btn rtbcb-btn-outline rtbcb-export-btn" data-export="analytics">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export Report', 'rtbcb' ); ?>
            </button>
            <button class="rtbcb-btn rtbcb-btn-primary" data-action="refresh-analytics">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Refresh', 'rtbcb' ); ?>
            </button>
        </div>
    </div>

    <!-- Key Metrics Summary -->
    <div class="rtbcb-dashboard-grid">
        <div class="rtbcb-stat-card rtbcb-stat-primary">
            <div class="rtbcb-stat-number"><?php echo esc_html( number_format( $summary['total_leads'] ) ); ?></div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Total Leads', 'rtbcb' ); ?></div>
            <div class="rtbcb-stat-change">
                <?php if ( $summary['todays_leads'] > 0 ) : ?>
                    <span class="rtbcb-stat-change-positive">+<?php echo esc_html( $summary['todays_leads'] ); ?> <?php esc_html_e( 'today', 'rtbcb' ); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="rtbcb-stat-card rtbcb-stat-success">
            <div class="rtbcb-stat-number"><?php echo esc_html( $summary['conversion_rate'] ); ?>%</div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Conversion Rate', 'rtbcb' ); ?></div>
            <div class="rtbcb-stat-change">
                <span class="rtbcb-stat-details"><?php echo esc_html( number_format( $summary['converted_leads'] ) ); ?> <?php esc_html_e( 'converted', 'rtbcb' ); ?></span>
            </div>
        </div>
        
        <div class="rtbcb-stat-card rtbcb-stat-info">
            <div class="rtbcb-stat-number">$<?php echo esc_html( number_format( $summary['avg_roi'] ) ); ?></div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Average ROI', 'rtbcb' ); ?></div>
            <div class="rtbcb-stat-change">
                <?php if ( $summary['max_roi'] > 0 ) : ?>
                    <span class="rtbcb-stat-details"><?php esc_html_e( 'Max:', 'rtbcb' ); ?> $<?php echo esc_html( number_format( $summary['max_roi'] ) ); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="rtbcb-stat-card rtbcb-stat-warning">
            <div class="rtbcb-stat-number"><?php echo esc_html( $date_ranges[ $current_range ] ); ?></div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Time Period', 'rtbcb' ); ?></div>
            <div class="rtbcb-stat-change">
                <span class="rtbcb-stat-details"><?php esc_html_e( 'Data Range', 'rtbcb' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="rtbcb-charts-grid">
        <!-- Leads Over Time -->
        <div class="rtbcb-chart-container rtbcb-chart-full">
            <div class="rtbcb-chart-header">
                <h3 class="rtbcb-chart-title"><?php esc_html_e( 'Leads Over Time', 'rtbcb' ); ?></h3>
                <select class="rtbcb-chart-filter" data-chart="leads-over-time-chart" data-chart-type="leads_over_time">
                    <?php foreach ( $date_ranges as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rtbcb-chart-wrapper">
                <canvas id="leads-over-time-chart" data-chart-type="leads_over_time"></canvas>
            </div>
        </div>

        <!-- ROI Distribution -->
        <div class="rtbcb-chart-container">
            <div class="rtbcb-chart-header">
                <h3 class="rtbcb-chart-title"><?php esc_html_e( 'ROI Distribution', 'rtbcb' ); ?></h3>
                <select class="rtbcb-chart-filter" data-chart="roi-distribution-chart" data-chart-type="roi_distribution">
                    <?php foreach ( $date_ranges as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rtbcb-chart-wrapper">
                <canvas id="roi-distribution-chart" data-chart-type="roi_distribution"></canvas>
            </div>
        </div>

        <!-- Industry Breakdown -->
        <div class="rtbcb-chart-container">
            <div class="rtbcb-chart-header">
                <h3 class="rtbcb-chart-title"><?php esc_html_e( 'Industry Breakdown', 'rtbcb' ); ?></h3>
                <select class="rtbcb-chart-filter" data-chart="industry-breakdown-chart" data-chart-type="industry_breakdown">
                    <?php foreach ( $date_ranges as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rtbcb-chart-wrapper">
                <canvas id="industry-breakdown-chart" data-chart-type="industry_breakdown"></canvas>
            </div>
        </div>
    </div>

    <!-- Additional Charts Row -->
    <div class="rtbcb-charts-grid">
        <!-- Company Size Breakdown -->
        <div class="rtbcb-chart-container">
            <div class="rtbcb-chart-header">
                <h3 class="rtbcb-chart-title"><?php esc_html_e( 'Company Size Breakdown', 'rtbcb' ); ?></h3>
                <select class="rtbcb-chart-filter" data-chart="company-size-chart" data-chart-type="company_size_breakdown">
                    <?php foreach ( $date_ranges as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rtbcb-chart-wrapper">
                <canvas id="company-size-chart" data-chart-type="company_size_breakdown"></canvas>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="rtbcb-chart-container">
            <div class="rtbcb-chart-header">
                <h3 class="rtbcb-chart-title"><?php esc_html_e( 'Lead Status', 'rtbcb' ); ?></h3>
                <select class="rtbcb-chart-filter" data-chart="status-breakdown-chart" data-chart-type="status_breakdown">
                    <?php foreach ( $date_ranges as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rtbcb-chart-wrapper">
                <canvas id="status-breakdown-chart" data-chart-type="status_breakdown"></canvas>
            </div>
        </div>

        <!-- Conversion Funnel -->
        <div class="rtbcb-chart-container">
            <div class="rtbcb-chart-header">
                <h3 class="rtbcb-chart-title"><?php esc_html_e( 'Conversion Funnel', 'rtbcb' ); ?></h3>
                <select class="rtbcb-chart-filter" data-chart="conversion-funnel-chart" data-chart-type="conversion_funnel">
                    <?php foreach ( $date_ranges as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rtbcb-chart-wrapper">
                <canvas id="conversion-funnel-chart" data-chart-type="conversion_funnel"></canvas>
            </div>
        </div>
    </div>

    <!-- UTM Sources -->
    <div class="rtbcb-chart-container rtbcb-chart-full">
        <div class="rtbcb-chart-header">
            <h3 class="rtbcb-chart-title"><?php esc_html_e( 'Traffic Sources', 'rtbcb' ); ?></h3>
            <select class="rtbcb-chart-filter" data-chart="utm-sources-chart" data-chart-type="utm_sources">
                <?php foreach ( $date_ranges as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_range, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rtbcb-chart-wrapper">
            <canvas id="utm-sources-chart" data-chart-type="utm_sources"></canvas>
        </div>
    </div>

    <!-- Quick Actions Card -->
    <div class="rtbcb-card">
        <div class="rtbcb-card-header">
            <h2 class="rtbcb-card-title"><?php esc_html_e( 'Quick Actions', 'rtbcb' ); ?></h2>
            <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Export data and generate reports', 'rtbcb' ); ?></p>
        </div>
        
        <div class="rtbcb-analytics-actions">
            <div class="rtbcb-action-item">
                <div class="rtbcb-action-content">
                    <h4><?php esc_html_e( 'Export Leads Data', 'rtbcb' ); ?></h4>
                    <p><?php esc_html_e( 'Download all leads data in CSV format for external analysis.', 'rtbcb' ); ?></p>
                </div>
                <button class="rtbcb-btn rtbcb-btn-outline rtbcb-export-btn" data-export="csv">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e( 'Export CSV', 'rtbcb' ); ?>
                </button>
            </div>
            
            <div class="rtbcb-action-item">
                <div class="rtbcb-action-content">
                    <h4><?php esc_html_e( 'Analytics Report', 'rtbcb' ); ?></h4>
                    <p><?php esc_html_e( 'Generate a comprehensive analytics report with charts and insights.', 'rtbcb' ); ?></p>
                </div>
                <button class="rtbcb-btn rtbcb-btn-primary rtbcb-export-btn" data-export="analytics">
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php esc_html_e( 'Generate Report', 'rtbcb' ); ?>
                </button>
            </div>
            
            <div class="rtbcb-action-item">
                <div class="rtbcb-action-content">
                    <h4><?php esc_html_e( 'Manage Leads', 'rtbcb' ); ?></h4>
                    <p><?php esc_html_e( 'View, filter, and manage individual leads and their status.', 'rtbcb' ); ?></p>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="rtbcb-btn rtbcb-btn-secondary">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e( 'Manage Leads', 'rtbcb' ); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize analytics charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (typeof RTBCBAdmin !== 'undefined') {
        RTBCBAdmin.initAnalyticsCharts();
    }
});
</script>

<style>
/* Analytics page specific styles */
.rtbcb-analytics-page {
    background: #f8fafc;
}

.rtbcb-stat-card {
    position: relative;
    overflow: hidden;
}

.rtbcb-stat-primary { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.rtbcb-stat-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.rtbcb-stat-info { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
.rtbcb-stat-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.rtbcb-stat-change {
    margin-top: 8px;
    position: relative;
    z-index: 1;
}

.rtbcb-stat-change-positive {
    color: rgba(255, 255, 255, 0.9);
    font-size: 12px;
    font-weight: 500;
}

.rtbcb-stat-details {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
}

.rtbcb-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.rtbcb-chart-container {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    position: relative;
    min-height: 400px;
}

.rtbcb-chart-full {
    grid-column: 1 / -1;
}

.rtbcb-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.rtbcb-chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.rtbcb-chart-filter {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    min-width: 120px;
}

.rtbcb-chart-wrapper {
    position: relative;
    height: 300px;
}

.rtbcb-chart-wrapper canvas {
    max-height: 300px;
}

.rtbcb-date-range-selector {
    min-width: 150px;
}

.rtbcb-analytics-actions {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.rtbcb-action-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}

.rtbcb-action-content h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.rtbcb-action-content p {
    margin: 0;
    font-size: 14px;
    color: #64748b;
}

@media (max-width: 1200px) {
    .rtbcb-charts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .rtbcb-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .rtbcb-charts-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-chart-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .rtbcb-action-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
}

/* Loading states for charts */
.rtbcb-chart-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    color: #64748b;
}

.rtbcb-chart-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: rtbcb-spin 1s linear infinite;
}

/* Empty state for charts */
.rtbcb-chart-empty {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #9ca3af;
}

.rtbcb-chart-empty .dashicons {
    font-size: 48px;
    margin-bottom: 12px;
    display: block;
}
</style>