<?php
/**
 * Modern Dashboard View for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get dashboard data
$stats = RTBCB_Leads::get_stats();
$total_leads = $stats['total_leads'] ?? 0;
$recent_leads = $stats['recent_leads'] ?? 0;
$conversion_rate = $total_leads > 0 ? round( ( $recent_leads / $total_leads ) * 100, 1 ) : 0;
$avg_roi = intval( $stats['average_roi']['avg_base'] ?? 0 );

// Quick actions
$quick_actions = [
    [
        'title' => __( 'View All Leads', 'rtbcb' ),
        'description' => __( 'Manage and track your business case leads', 'rtbcb' ),
        'url' => admin_url( 'admin.php?page=rtbcb-leads' ),
        'icon' => 'dashicons-groups',
        'color' => 'primary',
    ],
    [
        'title' => __( 'Analytics', 'rtbcb' ),
        'description' => __( 'View performance metrics and reports', 'rtbcb' ),
        'url' => admin_url( 'admin.php?page=rtbcb-analytics' ),
        'icon' => 'dashicons-chart-area',
        'color' => 'success',
    ],
    [
        'title' => __( 'Settings', 'rtbcb' ),
        'description' => __( 'Configure plugin settings and API keys', 'rtbcb' ),
        'url' => admin_url( 'admin.php?page=rtbcb-settings' ),
        'icon' => 'dashicons-admin-settings',
        'color' => 'secondary',
    ],
];

// Get recent leads for activity feed
$recent_leads_data = RTBCB_Leads::get_all_leads( [
    'per_page' => 5,
    'order_by' => 'created_at',
    'order' => 'DESC',
] );
$recent_leads_list = $recent_leads_data['leads'] ?? [];

// System status checks
$api_key = get_option( 'rtbcb_openai_api_key' );
$api_configured = ! empty( $api_key );
$last_indexed = get_option( 'rtbcb_last_indexed', '' );
?>

<div class="rtbcb-admin-page rtbcb-dashboard">
    <h1><?php esc_html_e( 'Real Treasury Business Case Builder', 'rtbcb' ); ?></h1>
    
    <!-- Key Metrics -->
    <div class="rtbcb-dashboard-grid">
        <div class="rtbcb-stat-card">
            <div class="rtbcb-stat-number"><?php echo esc_html( number_format( $total_leads ) ); ?></div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Total Leads', 'rtbcb' ); ?></div>
        </div>
        
        <div class="rtbcb-stat-card">
            <div class="rtbcb-stat-number"><?php echo esc_html( number_format( $recent_leads ) ); ?></div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Last 30 Days', 'rtbcb' ); ?></div>
        </div>
        
        <div class="rtbcb-stat-card">
            <div class="rtbcb-stat-number"><?php echo esc_html( $conversion_rate ); ?>%</div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Growth Rate', 'rtbcb' ); ?></div>
        </div>
        
        <div class="rtbcb-stat-card">
            <div class="rtbcb-stat-number">$<?php echo esc_html( number_format( $avg_roi ) ); ?></div>
            <div class="rtbcb-stat-label"><?php esc_html_e( 'Avg ROI', 'rtbcb' ); ?></div>
        </div>
    </div>

    <div class="rtbcb-dashboard-grid">
        <!-- Quick Actions -->
        <div class="rtbcb-card">
            <div class="rtbcb-card-header">
                <h2 class="rtbcb-card-title"><?php esc_html_e( 'Quick Actions', 'rtbcb' ); ?></h2>
                <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Access key features and settings', 'rtbcb' ); ?></p>
            </div>
            
            <div class="rtbcb-quick-actions">
                <?php foreach ( $quick_actions as $action ) : ?>
                    <a href="<?php echo esc_url( $action['url'] ); ?>" class="rtbcb-quick-action-card rtbcb-quick-action-<?php echo esc_attr( $action['color'] ); ?>">
                        <div class="rtbcb-quick-action-icon">
                            <span class="dashicons <?php echo esc_attr( $action['icon'] ); ?>"></span>
                        </div>
                        <div class="rtbcb-quick-action-content">
                            <h3><?php echo esc_html( $action['title'] ); ?></h3>
                            <p><?php echo esc_html( $action['description'] ); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="rtbcb-card">
            <div class="rtbcb-card-header">
                <h2 class="rtbcb-card-title"><?php esc_html_e( 'System Status', 'rtbcb' ); ?></h2>
                <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Monitor plugin health and configuration', 'rtbcb' ); ?></p>
            </div>
            
            <div class="rtbcb-status-list">
                <div class="rtbcb-status-item">
                    <span class="rtbcb-status-label"><?php esc_html_e( 'OpenAI API Key', 'rtbcb' ); ?></span>
                    <span class="rtbcb-status-value">
                        <?php if ( $api_configured ) : ?>
                            <span class="rtbcb-status-badge rtbcb-status-qualified"><?php esc_html_e( 'Configured', 'rtbcb' ); ?></span>
                        <?php else : ?>
                            <span class="rtbcb-status-badge rtbcb-status-lost"><?php esc_html_e( 'Not Set', 'rtbcb' ); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="rtbcb-status-item">
                    <span class="rtbcb-status-label"><?php esc_html_e( 'Database Tables', 'rtbcb' ); ?></span>
                    <span class="rtbcb-status-value">
                        <span class="rtbcb-status-badge rtbcb-status-qualified"><?php esc_html_e( 'Healthy', 'rtbcb' ); ?></span>
                    </span>
                </div>
                
                <div class="rtbcb-status-item">
                    <span class="rtbcb-status-label"><?php esc_html_e( 'RAG Index', 'rtbcb' ); ?></span>
                    <span class="rtbcb-status-value">
                        <?php if ( $last_indexed ) : ?>
                            <span class="rtbcb-status-badge rtbcb-status-qualified"><?php esc_html_e( 'Up to Date', 'rtbcb' ); ?></span>
                        <?php else : ?>
                            <span class="rtbcb-status-badge rtbcb-status-contacted"><?php esc_html_e( 'Needs Rebuild', 'rtbcb' ); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="rtbcb-status-item">
                    <span class="rtbcb-status-label"><?php esc_html_e( 'WordPress Version', 'rtbcb' ); ?></span>
                    <span class="rtbcb-status-value">
                        <?php if ( version_compare( get_bloginfo( 'version' ), '5.0', '>=' ) ) : ?>
                            <span class="rtbcb-status-badge rtbcb-status-qualified"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
                        <?php else : ?>
                            <span class="rtbcb-status-badge rtbcb-status-lost"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <?php if ( ! $api_configured ) : ?>
                <div class="rtbcb-status-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-settings' ) ); ?>" class="rtbcb-btn rtbcb-btn-primary">
                        <?php esc_html_e( 'Configure API Key', 'rtbcb' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="rtbcb-card">
        <div class="rtbcb-card-header">
            <h2 class="rtbcb-card-title"><?php esc_html_e( 'Recent Activity', 'rtbcb' ); ?></h2>
            <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Latest business case submissions', 'rtbcb' ); ?></p>
        </div>
        
        <?php if ( ! empty( $recent_leads_list ) ) : ?>
            <div class="rtbcb-activity-list">
                <?php foreach ( $recent_leads_list as $lead ) : ?>
                    <div class="rtbcb-activity-item">
                        <div class="rtbcb-activity-avatar">
                            <?php echo esc_html( strtoupper( substr( $lead['email'], 0, 1 ) ) ); ?>
                        </div>
                        <div class="rtbcb-activity-content">
                            <div class="rtbcb-activity-title">
                                <strong><?php echo esc_html( $lead['email'] ); ?></strong>
                                <?php if ( ! empty( $lead['company_size'] ) ) : ?>
                                    <span class="rtbcb-activity-meta"><?php echo esc_html( $lead['company_size'] ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="rtbcb-activity-meta">
                                <?php if ( ! empty( $lead['roi_base'] ) ) : ?>
                                    <span><?php echo esc_html( sprintf( __( 'ROI: $%s', 'rtbcb' ), number_format( $lead['roi_base'] ) ) ); ?></span>
                                <?php endif; ?>
                                <span><?php echo esc_html( human_time_diff( strtotime( $lead['created_at'] ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'rtbcb' ); ?></span>
                            </div>
                        </div>
                        <div class="rtbcb-activity-actions">
                            <button class="rtbcb-action-btn rtbcb-view-lead" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>" title="<?php esc_attr_e( 'View Details', 'rtbcb' ); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="rtbcb-activity-footer">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="rtbcb-btn rtbcb-btn-outline">
                    <?php esc_html_e( 'View All Leads', 'rtbcb' ); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="rtbcb-empty-state">
                <div class="rtbcb-empty-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <h3><?php esc_html_e( 'No Leads Yet', 'rtbcb' ); ?></h3>
                <p><?php esc_html_e( 'Business case submissions will appear here once users start using your calculator.', 'rtbcb' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-settings' ) ); ?>" class="rtbcb-btn rtbcb-btn-primary">
                    <?php esc_html_e( 'Configure Plugin', 'rtbcb' ); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Dashboard-specific styles */
.rtbcb-quick-actions {
    display: grid;
    gap: 16px;
}

.rtbcb-quick-action-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    background: white;
}

.rtbcb-quick-action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: inherit;
}

.rtbcb-quick-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.rtbcb-quick-action-primary .rtbcb-quick-action-icon { background: #3b82f6; }
.rtbcb-quick-action-success .rtbcb-quick-action-icon { background: #10b981; }
.rtbcb-quick-action-secondary .rtbcb-quick-action-icon { background: #6b7280; }

.rtbcb-quick-action-content h3 {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.rtbcb-quick-action-content p {
    margin: 0;
    font-size: 14px;
    color: #64748b;
}

.rtbcb-status-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rtbcb-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.rtbcb-status-item:last-child {
    border-bottom: none;
}

.rtbcb-status-label {
    font-weight: 500;
    color: #374151;
}

.rtbcb-status-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.rtbcb-activity-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.rtbcb-activity-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
}

.rtbcb-activity-item:last-child {
    border-bottom: none;
}

.rtbcb-activity-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
}

.rtbcb-activity-content {
    flex: 1;
}

.rtbcb-activity-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 4px;
}

.rtbcb-activity-meta {
    font-size: 13px;
    color: #64748b;
    display: flex;
    gap: 16px;
}

.rtbcb-activity-footer {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}

.rtbcb-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.rtbcb-empty-icon {
    font-size: 64px;
    color: #e2e8f0;
    margin-bottom: 20px;
}

.rtbcb-empty-state h3 {
    margin: 0 0 12px 0;
    font-size: 20px;
    color: #374151;
}

.rtbcb-empty-state p {
    margin: 0 0 24px 0;
    color: #64748b;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

@media (max-width: 768px) {
    .rtbcb-status-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .rtbcb-activity-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .rtbcb-activity-content {
        width: 100%;
    }
    
    .rtbcb-activity-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>