<?php
/**
 * Modern Leads Management View for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get filter parameters
$search = sanitize_text_field( $_GET['search'] ?? '' );
$status_filter = sanitize_text_field( $_GET['status'] ?? '' );
$industry_filter = sanitize_text_field( $_GET['industry'] ?? '' );
$size_filter = sanitize_text_field( $_GET['company_size'] ?? '' );
$date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
$date_to = sanitize_text_field( $_GET['date_to'] ?? '' );

// Pagination
$current_page = max( 1, intval( $_GET['paged'] ?? 1 ) );
$per_page = 20;

// Build filter args
$filter_args = [
    'per_page' => $per_page,
    'page' => $current_page,
    'order_by' => 'created_at',
    'order' => 'DESC',
];

if ( $search ) {
    $filter_args['search'] = $search;
}

if ( $status_filter ) {
    $filter_args['status'] = $status_filter;
}

if ( $industry_filter ) {
    $filter_args['industry'] = $industry_filter;
}

if ( $size_filter ) {
    $filter_args['company_size'] = $size_filter;
}

if ( $date_from ) {
    $filter_args['date_from'] = $date_from;
}

if ( $date_to ) {
    $filter_args['date_to'] = $date_to;
}

// Get leads data
$leads_data = RTBCB_Leads::get_all_leads( $filter_args );
$leads = $leads_data['leads'] ?? [];
$total_leads = $leads_data['total'] ?? 0;
$total_pages = ceil( $total_leads / $per_page );

// Get filter options
$industries = [ 'Technology', 'Healthcare', 'Finance', 'Manufacturing', 'Retail', 'Other' ];
$company_sizes = [ 'Small (1-50)', 'Medium (51-200)', 'Large (201-1000)', 'Enterprise (1000+)' ];
$statuses = [ 'new', 'contacted', 'qualified', 'converted', 'lost' ];
?>

<div class="rtbcb-admin-page rtbcb-leads-page">
    <div class="rtbcb-page-header">
        <h1><?php esc_html_e( 'Leads Management', 'rtbcb' ); ?></h1>
        <div class="rtbcb-page-actions">
            <button class="rtbcb-btn rtbcb-btn-outline rtbcb-export-btn" data-export="csv">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export CSV', 'rtbcb' ); ?>
            </button>
            <button class="rtbcb-btn rtbcb-btn-primary" data-action="refresh-analytics">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Refresh Data', 'rtbcb' ); ?>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="rtbcb-filters">
        <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="rtbcb-filters-form">
            <input type="hidden" name="page" value="rtbcb-leads">
            
            <div class="rtbcb-search-box">
                <span class="rtbcb-search-icon dashicons dashicons-search"></span>
                <input 
                    type="text" 
                    name="search" 
                    value="<?php echo esc_attr( $search ); ?>" 
                    placeholder="<?php esc_attr_e( 'Search by email or company...', 'rtbcb' ); ?>"
                    class="rtbcb-search-input"
                >
            </div>
            
            <select name="status" class="rtbcb-filter-select" data-filter="status">
                <option value=""><?php esc_html_e( 'All Statuses', 'rtbcb' ); ?></option>
                <?php foreach ( $statuses as $status ) : ?>
                    <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status_filter, $status ); ?>>
                        <?php echo esc_html( ucfirst( $status ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="industry" class="rtbcb-filter-select" data-filter="industry">
                <option value=""><?php esc_html_e( 'All Industries', 'rtbcb' ); ?></option>
                <?php foreach ( $industries as $industry ) : ?>
                    <option value="<?php echo esc_attr( $industry ); ?>" <?php selected( $industry_filter, $industry ); ?>>
                        <?php echo esc_html( $industry ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="company_size" class="rtbcb-filter-select" data-filter="company_size">
                <option value=""><?php esc_html_e( 'All Sizes', 'rtbcb' ); ?></option>
                <?php foreach ( $company_sizes as $size ) : ?>
                    <option value="<?php echo esc_attr( $size ); ?>" <?php selected( $size_filter, $size ); ?>>
                        <?php echo esc_html( $size ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input 
                type="date" 
                name="date_from" 
                value="<?php echo esc_attr( $date_from ); ?>" 
                class="rtbcb-form-input"
                placeholder="<?php esc_attr_e( 'From Date', 'rtbcb' ); ?>"
            >
            
            <input 
                type="date" 
                name="date_to" 
                value="<?php echo esc_attr( $date_to ); ?>" 
                class="rtbcb-form-input"
                placeholder="<?php esc_attr_e( 'To Date', 'rtbcb' ); ?>"
            >
            
            <button type="submit" class="rtbcb-btn rtbcb-btn-primary">
                <?php esc_html_e( 'Filter', 'rtbcb' ); ?>
            </button>
            
            <?php if ( $search || $status_filter || $industry_filter || $size_filter || $date_from || $date_to ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="rtbcb-btn rtbcb-btn-outline">
                    <?php esc_html_e( 'Clear', 'rtbcb' ); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="rtbcb-bulk-actions">
        <span class="rtbcb-bulk-text"><?php esc_html_e( '0 items selected', 'rtbcb' ); ?></span>
        <div class="rtbcb-bulk-buttons">
            <button class="rtbcb-btn rtbcb-btn-small rtbcb-btn-outline rtbcb-export-btn" data-export="csv">
                <?php esc_html_e( 'Export Selected', 'rtbcb' ); ?>
            </button>
            <button class="rtbcb-btn rtbcb-btn-small rtbcb-btn-danger" data-action="bulk-delete" data-confirm="<?php esc_attr_e( 'Are you sure you want to delete the selected leads?', 'rtbcb' ); ?>">
                <?php esc_html_e( 'Delete Selected', 'rtbcb' ); ?>
            </button>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="rtbcb-results-summary">
        <p><?php 
            /* translators: %1$d: current results count, %2$d: total results count */
            echo esc_html( sprintf( _n( 'Showing %1$d of %2$d lead', 'Showing %1$d of %2$d leads', $total_leads, 'rtbcb' ), count( $leads ), $total_leads ) ); 
        ?></p>
    </div>

    <!-- Leads Table -->
    <?php if ( ! empty( $leads ) ) : ?>
        <div class="rtbcb-table-container">
            <table class="rtbcb-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="rtbcb-select-all" class="rtbcb-bulk-checkbox-all">
                        </th>
                        <th><?php esc_html_e( 'Contact', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Company Details', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'ROI Calculated', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Created', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'rtbcb' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $leads as $lead ) : ?>
                        <tr data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                            <td>
                                <input type="checkbox" class="rtbcb-bulk-checkbox" value="<?php echo esc_attr( $lead['id'] ); ?>">
                            </td>
                            <td>
                                <div class="rtbcb-contact-info">
                                    <div class="rtbcb-contact-avatar">
                                        <?php echo esc_html( strtoupper( substr( $lead['email'], 0, 1 ) ) ); ?>
                                    </div>
                                    <div class="rtbcb-contact-details">
                                        <strong><?php echo esc_html( $lead['email'] ); ?></strong>
                                        <?php if ( ! empty( $lead['utm_source'] ) ) : ?>
                                            <div class="rtbcb-contact-source">
                                                <small>
                                                    <?php 
                                                    /* translators: %s: traffic source */
                                                    echo esc_html( sprintf( __( 'Source: %s', 'rtbcb' ), $lead['utm_source'] ) ); 
                                                    ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="rtbcb-company-info">
                                    <?php if ( ! empty( $lead['company_size'] ) ) : ?>
                                        <div><strong><?php echo esc_html( $lead['company_size'] ); ?></strong></div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $lead['industry'] ) ) : ?>
                                        <div><small><?php echo esc_html( $lead['industry'] ); ?></small></div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $lead['ftes'] ) ) : ?>
                                        <div><small><?php echo esc_html( sprintf( __( '%s FTEs', 'rtbcb' ), $lead['ftes'] ) ); ?></small></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="rtbcb-roi-info">
                                    <?php if ( ! empty( $lead['roi_base'] ) ) : ?>
                                        <div class="rtbcb-roi-primary">$<?php echo esc_html( number_format( $lead['roi_base'] ) ); ?></div>
                                        <?php if ( ! empty( $lead['roi_low'] ) && ! empty( $lead['roi_high'] ) ) : ?>
                                            <div class="rtbcb-roi-range">
                                                <small>$<?php echo esc_html( number_format( $lead['roi_low'] ) ); ?> - $<?php echo esc_html( number_format( $lead['roi_high'] ) ); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="rtbcb-no-data"><?php esc_html_e( 'No ROI calculated', 'rtbcb' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <select class="rtbcb-status-select" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>" data-original-value="<?php echo esc_attr( $lead['status'] ?? 'new' ); ?>">
                                    <?php foreach ( $statuses as $status ) : ?>
                                        <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $lead['status'] ?? 'new', $status ); ?>>
                                            <?php echo esc_html( ucfirst( $status ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div class="rtbcb-date-info">
                                    <div><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $lead['created_at'] ) ) ); ?></div>
                                    <small><?php echo esc_html( human_time_diff( strtotime( $lead['created_at'] ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'rtbcb' ); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="rtbcb-actions">
                                    <button class="rtbcb-action-btn rtbcb-view-lead" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>" title="<?php esc_attr_e( 'View Details', 'rtbcb' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <?php if ( ! empty( $lead['report_html'] ) ) : ?>
                                        <button class="rtbcb-action-btn" data-modal="rtbcb-report-modal" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>" title="<?php esc_attr_e( 'View Report', 'rtbcb' ); ?>">
                                            <span class="dashicons dashicons-media-document"></span>
                                        </button>
                                    <?php endif; ?>
                                    <button class="rtbcb-action-btn danger" data-action="delete-lead" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>" data-confirm="<?php esc_attr_e( 'Are you sure you want to delete this lead?', 'rtbcb' ); ?>" title="<?php esc_attr_e( 'Delete Lead', 'rtbcb' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="rtbcb-pagination">
                <?php
                $page_links = paginate_links( [
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page,
                    'type' => 'array',
                ] );

                if ( $page_links ) {
                    foreach ( $page_links as $link ) {
                        $link = str_replace( 'page-numbers', 'rtbcb-pagination-btn', $link );
                        echo wp_kses_post( $link );
                    }
                }
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <!-- Empty State -->
        <div class="rtbcb-empty-state">
            <div class="rtbcb-empty-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <h3><?php esc_html_e( 'No Leads Found', 'rtbcb' ); ?></h3>
            <?php if ( $search || $status_filter || $industry_filter || $size_filter || $date_from || $date_to ) : ?>
                <p><?php esc_html_e( 'No leads match your current filter criteria. Try adjusting your filters or clearing them to see all leads.', 'rtbcb' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="rtbcb-btn rtbcb-btn-primary">
                    <?php esc_html_e( 'Clear Filters', 'rtbcb' ); ?>
                </a>
            <?php else : ?>
                <p><?php esc_html_e( 'No business case submissions yet. Leads will appear here once users start using your calculator.', 'rtbcb' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-settings' ) ); ?>" class="rtbcb-btn rtbcb-btn-primary">
                    <?php esc_html_e( 'Configure Plugin', 'rtbcb' ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Leads page specific styles */
.rtbcb-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e2e8f0;
}

.rtbcb-page-actions {
    display: flex;
    gap: 12px;
}

.rtbcb-filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
}

.rtbcb-results-summary {
    margin-bottom: 16px;
    color: #64748b;
    font-size: 14px;
}

.rtbcb-contact-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rtbcb-contact-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.rtbcb-contact-details strong {
    display: block;
    color: #1e293b;
}

.rtbcb-contact-source {
    color: #64748b;
    margin-top: 2px;
}

.rtbcb-company-info {
    font-size: 14px;
}

.rtbcb-company-info > div {
    margin-bottom: 4px;
}

.rtbcb-company-info > div:last-child {
    margin-bottom: 0;
}

.rtbcb-roi-info {
    text-align: right;
}

.rtbcb-roi-primary {
    font-weight: 600;
    color: #059669;
    font-size: 16px;
}

.rtbcb-roi-range {
    color: #64748b;
    margin-top: 2px;
}

.rtbcb-no-data {
    color: #9ca3af;
    font-style: italic;
}

.rtbcb-status-select {
    padding: 6px 8px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 12px;
    background: white;
    min-width: 100px;
}

.rtbcb-date-info {
    font-size: 14px;
}

.rtbcb-date-info small {
    color: #64748b;
    display: block;
    margin-top: 2px;
}

.rtbcb-bulk-actions {
    justify-content: space-between;
}

.rtbcb-bulk-buttons {
    display: flex;
    gap: 8px;
}

@media (max-width: 1200px) {
    .rtbcb-table {
        min-width: 800px;
    }
}

@media (max-width: 768px) {
    .rtbcb-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .rtbcb-filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .rtbcb-search-box,
    .rtbcb-filter-select,
    .rtbcb-form-input {
        width: 100%;
    }
    
    .rtbcb-contact-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .rtbcb-roi-info {
        text-align: left;
    }
    
    .rtbcb-bulk-actions {
        flex-direction: column;
        gap: 12px;
    }
}
</style>