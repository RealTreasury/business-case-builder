<?php
/**
 * Enhanced Dashboard admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_leads       = $stats['total_leads'] ?? 0;
$recent_leads      = $stats['recent_leads'] ?? 0;
$category_stats    = $stats['by_category'] ?? [];
$size_stats        = $stats['by_company_size'] ?? [];
$roi_stats         = $stats['average_roi'] ?? [];
$leads             = $recent_leads_data['leads'] ?? [];

// System health checks
$api_key_configured = ! empty( get_option( 'rtbcb_openai_api_key' ) );
$portal_active      = (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
$last_indexed       = get_option( 'rtbcb_last_indexed', '' );

// Quick stats
$conversion_rate = $total_leads > 0 ? ( $recent_leads / $total_leads ) * 100 : 0;
$avg_roi         = intval( $roi_stats['avg_base'] ?? 0 );
?>

<div class="wrap rtbcb-admin-page rtbcb-dashboard">
    <div class="rtbcb-dashboard-header">
        <div class="rtbcb-dashboard-title">
            <h1><?php echo esc_html__( 'Business Case Builder Dashboard', 'rtbcb' ); ?></h1>
            <p class="rtbcb-dashboard-subtitle">
                <?php esc_html_e( 'Monitor lead generation, analyze performance, and manage your treasury technology business case builder.', 'rtbcb' ); ?>
            </p>
        </div>
        <div class="rtbcb-dashboard-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'View All Leads', 'rtbcb' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-analytics' ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Analytics', 'rtbcb' ); ?>
            </a>
        </div>
    </div>

    <!-- System Status -->
    <div class="rtbcb-system-status">
        <h3><?php esc_html_e( 'System Status', 'rtbcb' ); ?></h3>
        <div class="rtbcb-status-grid">
            <div class="rtbcb-status-item <?php echo $api_key_configured ? 'rtbcb-status-good' : 'rtbcb-status-warning'; ?>">
                <div class="rtbcb-status-icon">
                    <span class="dashicons <?php echo $api_key_configured ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                </div>
                <div class="rtbcb-status-content">
                    <div class="rtbcb-status-label"><?php esc_html_e( 'OpenAI API', 'rtbcb' ); ?></div>
                    <div class="rtbcb-status-value">
                        <?php echo $api_key_configured ? esc_html__( 'Configured', 'rtbcb' ) : esc_html__( 'Not configured', 'rtbcb' ); ?>
                    </div>
                </div>
                <?php if ( ! $api_key_configured ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-settings' ) ); ?>" class="rtbcb-status-action">
                        <?php esc_html_e( 'Configure', 'rtbcb' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="rtbcb-status-item <?php echo $portal_active ? 'rtbcb-status-good' : 'rtbcb-status-info'; ?>">
                <div class="rtbcb-status-icon">
                    <span class="dashicons <?php echo $portal_active ? 'dashicons-yes-alt' : 'dashicons-info'; ?>"></span>
                </div>
                <div class="rtbcb-status-content">
                    <div class="rtbcb-status-label"><?php esc_html_e( 'Portal Integration', 'rtbcb' ); ?></div>
                    <div class="rtbcb-status-value">
                        <?php echo $portal_active ? esc_html__( 'Active', 'rtbcb' ) : esc_html__( 'Inactive', 'rtbcb' ); ?>
                    </div>
                </div>
            </div>

            <div class="rtbcb-status-item <?php echo ! empty( $last_indexed ) ? 'rtbcb-status-good' : 'rtbcb-status-warning'; ?>">
                <div class="rtbcb-status-icon">
                    <span class="dashicons <?php echo ! empty( $last_indexed ) ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                </div>
                <div class="rtbcb-status-content">
                    <div class="rtbcb-status-label"><?php esc_html_e( 'RAG Index', 'rtbcb' ); ?></div>
                    <div class="rtbcb-status-value">
                        <?php
                        if ( ! empty( $last_indexed ) ) {
                            echo esc_html( human_time_diff( strtotime( $last_indexed ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'rtbcb' );
                        } else {
                            esc_html_e( 'Never indexed', 'rtbcb' );
                        }
                        ?>
                    </div>
                </div>
                <button type="button" id="rtbcb-rebuild-index" class="rtbcb-status-action">
                    <?php esc_html_e( 'Rebuild', 'rtbcb' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Overview -->
    <div class="rtbcb-metrics-overview">
        <div class="rtbcb-metric-large">
            <div class="rtbcb-metric-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="rtbcb-metric-content">
                <div class="rtbcb-metric-value"><?php echo esc_html( number_format( $total_leads ) ); ?></div>
                <div class="rtbcb-metric-label"><?php esc_html_e( 'Total Leads Generated', 'rtbcb' ); ?></div>
                <div class="rtbcb-metric-subtitle">
                    <?php
                    printf(
                        esc_html__( '%d new leads this month', 'rtbcb' ),
                        esc_html( $recent_leads )
                    );
                    ?>
                </div>
            </div>
        </div>

        <div class="rtbcb-metrics-grid">
            <div class="rtbcb-metric-card">
                <div class="rtbcb-metric-header">
                    <span class="dashicons dashicons-chart-line"></span>
                    <span><?php esc_html_e( 'Average ROI', 'rtbcb' ); ?></span>
                </div>
                <div class="rtbcb-metric-value">$<?php echo esc_html( number_format( $avg_roi ) ); ?></div>
                <div class="rtbcb-metric-change rtbcb-metric-positive">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                    <?php esc_html_e( 'Strong value proposition', 'rtbcb' ); ?>
                </div>
            </div>

            <div class="rtbcb-metric-card">
                <div class="rtbcb-metric-header">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php esc_html_e( 'Monthly Growth', 'rtbcb' ); ?></span>
                </div>
                <div class="rtbcb-metric-value"><?php echo esc_html( number_format( $conversion_rate, 1 ) ); ?>%</div>
                <div class="rtbcb-metric-change">
                    <?php esc_html_e( 'Lead generation rate', 'rtbcb' ); ?>
                </div>
            </div>

            <div class="rtbcb-metric-card">
                <div class="rtbcb-metric-header">
                    <span class="dashicons dashicons-star-filled"></span>
                    <span><?php esc_html_e( 'Top Category', 'rtbcb' ); ?></span>
                </div>
                <div class="rtbcb-metric-value">
                    <?php
                    if ( ! empty( $category_stats ) ) {
                        $top_category = array_reduce( $category_stats, function( $carry, $item ) {
                            return ( ! $carry || $item['count'] > $carry['count'] ) ? $item : $carry;
                        } );

                        $categories   = RTBCB_Category_Recommender::get_all_categories();
                        $top_cat_info = $categories[ $top_category['recommended_category'] ] ?? [];
                        echo esc_html( $top_cat_info['name'] ?? 'TMS' );
                    } else {
                        esc_html_e( 'N/A', 'rtbcb' );
                    }
                    ?>
                </div>
                <div class="rtbcb-metric-change">
                    <?php esc_html_e( 'Most recommended', 'rtbcb' ); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Insights and Recent Activity -->
    <div class="rtbcb-dashboard-content">
        <!-- Recent Leads -->
        <div class="rtbcb-dashboard-section">
            <div class="rtbcb-section-header">
                <h3><?php esc_html_e( 'Recent Leads', 'rtbcb' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="rtbcb-section-link">
                    <?php esc_html_e( 'View All', 'rtbcb' ); ?>
                </a>
            </div>

            <?php if ( ! empty( $leads ) ) : ?>
                <div class="rtbcb-recent-leads">
                    <?php foreach ( array_slice( $leads, 0, 5 ) as $lead ) : ?>
                        <div class="rtbcb-lead-item">
                            <div class="rtbcb-lead-avatar">
                                <?php echo esc_html( strtoupper( substr( $lead['email'], 0, 1 ) ) ); ?>
                            </div>
                            <div class="rtbcb-lead-info">
                                <div class="rtbcb-lead-email"><?php echo esc_html( $lead['email'] ); ?></div>
                                <div class="rtbcb-lead-meta">
                                    <span class="rtbcb-company-size"><?php echo esc_html( $lead['company_size'] ); ?></span>
                                    <?php if ( $lead['roi_base'] > 0 ) : ?>
                                        <span class="rtbcb-lead-separator">•</span>
                                        <span class="rtbcb-roi">$<?php echo esc_html( number_format( $lead['roi_base'] ) ); ?> ROI</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="rtbcb-lead-time">
                                <?php echo esc_html( human_time_diff( strtotime( $lead['created_at'] ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'rtbcb' ); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="rtbcb-empty-state">
                    <span class="dashicons dashicons-email-alt"></span>
                    <p><?php esc_html_e( 'No leads yet. Share your business case builder to start generating leads!', 'rtbcb' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="rtbcb-dashboard-section">
            <div class="rtbcb-section-header">
                <h3><?php esc_html_e( 'Quick Actions', 'rtbcb' ); ?></h3>
            </div>

            <div class="rtbcb-quick-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-settings' ) ); ?>" class="rtbcb-action-card">
                    <div class="rtbcb-action-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                    <div class="rtbcb-action-content">
                        <h4><?php esc_html_e( 'Settings', 'rtbcb' ); ?></h4>
                        <p><?php esc_html_e( 'Configure API keys and ROI assumptions', 'rtbcb' ); ?></p>
                    </div>
                </a>

                <button type="button" id="rtbcb-test-api" class="rtbcb-action-card">
                    <div class="rtbcb-action-icon">
                        <span class="dashicons dashicons-cloud"></span>
                    </div>
                    <div class="rtbcb-action-content">
                        <h4><?php esc_html_e( 'Test API', 'rtbcb' ); ?></h4>
                        <p><?php esc_html_e( 'Verify OpenAI connection', 'rtbcb' ); ?></p>
                    </div>
                </button>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-analytics' ) ); ?>" class="rtbcb-action-card">
                    <div class="rtbcb-action-icon">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>
                    <div class="rtbcb-action-content">
                        <h4><?php esc_html_e( 'Analytics', 'rtbcb' ); ?></h4>
                        <p><?php esc_html_e( 'View detailed reports and insights', 'rtbcb' ); ?></p>
                    </div>
                </a>

                <button type="button" id="rtbcb-export-data" class="rtbcb-action-card">
                    <div class="rtbcb-action-icon">
                        <span class="dashicons dashicons-download"></span>
                    </div>
                    <div class="rtbcb-action-content">
                        <h4><?php esc_html_e( 'Export Data', 'rtbcb' ); ?></h4>
                        <p><?php esc_html_e( 'Download leads as CSV', 'rtbcb' ); ?></p>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Category Distribution Summary -->
    <?php if ( ! empty( $category_stats ) ) : ?>
        <div class="rtbcb-dashboard-section rtbcb-section-full">
            <div class="rtbcb-section-header">
                <h3><?php esc_html_e( 'Category Distribution', 'rtbcb' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-analytics' ) ); ?>" class="rtbcb-section-link">
                    <?php esc_html_e( 'Detailed Analytics', 'rtbcb' ); ?>
                </a>
            </div>

            <div class="rtbcb-category-summary">
                <?php foreach ( $category_stats as $stat ) : ?>
                    <?php
                    $categories = RTBCB_Category_Recommender::get_all_categories();
                    $cat_info   = $categories[ $stat['recommended_category'] ] ?? [];
                    $cat_name   = $cat_info['name'] ?? ucfirst( str_replace( '_', ' ', $stat['recommended_category'] ) );
                    $percentage = $total_leads > 0 ? ( $stat['count'] / $total_leads ) * 100 : 0;
                    ?>
                    <div class="rtbcb-category-item">
                        <div class="rtbcb-category-header">
                            <span class="rtbcb-category-name"><?php echo esc_html( $cat_name ); ?></span>
                            <span class="rtbcb-category-count"><?php echo esc_html( $stat['count'] ); ?> <?php esc_html_e( 'leads', 'rtbcb' ); ?></span>
                        </div>
                        <div class="rtbcb-category-bar">
                            <div class="rtbcb-category-fill rtbcb-cat-<?php echo esc_attr( $stat['recommended_category'] ); ?>" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
                        </div>
                        <div class="rtbcb-category-percentage"><?php echo esc_html( number_format( $percentage, 1 ) ); ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
