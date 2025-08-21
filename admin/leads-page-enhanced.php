<?php
/**
 * Enhanced Leads admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_page = $leads_data['current_page'] ?? 1;
$total_pages  = $leads_data['total_pages'] ?? 1;
$total_leads  = $leads_data['total'] ?? 0;
$leads        = $leads_data['leads'] ?? [];
?>

<div class="wrap rtbcb-admin-page">
    <div class="rtbcb-admin-header">
        <h1 class="wp-heading-inline">
            <?php echo esc_html__( 'Leads Management', 'rtbcb' ); ?>
            <span class="rtbcb-count-badge"><?php echo esc_html( number_format( $total_leads ) ); ?></span>
        </h1>
        <div class="rtbcb-admin-actions">
            <button id="rtbcb-export-leads" class="button button-secondary">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export CSV', 'rtbcb' ); ?>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="rtbcb-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="rtbcb-leads" />
            
            <div class="rtbcb-filter-row">
                <div class="rtbcb-filter-group">
                    <label for="search"><?php esc_html_e( 'Search Email:', 'rtbcb' ); ?></label>
                    <input type="text" id="search" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Enter email address...', 'rtbcb' ); ?>" />
                </div>
                
                <div class="rtbcb-filter-group">
                    <label for="category"><?php esc_html_e( 'Category:', 'rtbcb' ); ?></label>
                    <select id="category" name="category">
                        <option value=""><?php esc_html_e( 'All Categories', 'rtbcb' ); ?></option>
                        <?php foreach ( $categories as $key => $cat_info ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $category, $key ); ?>>
                                <?php echo esc_html( $cat_info['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="rtbcb-filter-group">
                    <label for="date_from"><?php esc_html_e( 'From Date:', 'rtbcb' ); ?></label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
                </div>
                
                <div class="rtbcb-filter-group">
                    <label for="date_to"><?php esc_html_e( 'To Date:', 'rtbcb' ); ?></label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
                </div>
                
                <div class="rtbcb-filter-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'rtbcb' ); ?></button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Clear', 'rtbcb' ); ?></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="rtbcb-bulk-actions">
        <form id="rtbcb-bulk-form">
            <select id="rtbcb-bulk-action">
                <option value=""><?php esc_html_e( 'Bulk Actions', 'rtbcb' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete', 'rtbcb' ); ?></option>
            </select>
            <button type="submit" class="button button-secondary" disabled><?php esc_html_e( 'Apply', 'rtbcb' ); ?></button>
        </form>
    </div>

    <!-- Leads Table -->
    <?php if ( ! empty( $leads ) ) : ?>
        <div class="rtbcb-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="rtbcb-select-all" />
                        </td>
                        <th class="manage-column column-email column-primary">
                            <a href="<?php echo esc_url( add_query_arg( [ 'orderby' => 'email', 'order' => ( $_GET['orderby'] ?? '' ) === 'email' && ( $_GET['order'] ?? '' ) === 'ASC' ? 'DESC' : 'ASC' ] ) ); ?>">
                                <?php esc_html_e( 'Email', 'rtbcb' ); ?>
                                <?php if ( ( $_GET['orderby'] ?? '' ) === 'email' ) : ?>
                                    <span class="sorting-indicator"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="manage-column column-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></th>
                        <th class="manage-column column-category"><?php esc_html_e( 'Recommended Category', 'rtbcb' ); ?></th>
                        <th class="manage-column column-roi"><?php esc_html_e( 'Base ROI', 'rtbcb' ); ?></th>
                        <th class="manage-column column-date">
                            <a href="<?php echo esc_url( add_query_arg( [ 'orderby' => 'created_at', 'order' => ( $_GET['orderby'] ?? '' ) === 'created_at' && ( $_GET['order'] ?? '' ) === 'ASC' ? 'DESC' : 'ASC' ] ) ); ?>">
                                <?php esc_html_e( 'Date', 'rtbcb' ); ?>
                                <?php if ( ( $_GET['orderby'] ?? '' ) === 'created_at' || empty( $_GET['orderby'] ) ) : ?>
                                    <span class="sorting-indicator"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="manage-column column-actions"><?php esc_html_e( 'Actions', 'rtbcb' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $leads as $lead ) : ?>
                        <tr data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" class="rtbcb-lead-checkbox" value="<?php echo esc_attr( $lead['id'] ); ?>" />
                            </th>
                            <td class="column-email column-primary" data-colname="<?php esc_attr_e( 'Email', 'rtbcb' ); ?>">
                                <strong><?php echo esc_html( $lead['email'] ); ?></strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="rtbcb-view-lead" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                                            <?php esc_html_e( 'View Details', 'rtbcb' ); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="rtbcb-delete-lead submitdelete" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                                            <?php esc_html_e( 'Delete', 'rtbcb' ); ?>
                                        </a>
                                    </span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'rtbcb' ); ?></span></button>
                            </td>
                            <td class="column-company-size" data-colname="<?php esc_attr_e( 'Company Size', 'rtbcb' ); ?>">
                                <span class="rtbcb-company-size-badge rtbcb-size-<?php echo esc_attr( sanitize_title( $lead['company_size'] ) ); ?>">
                                    <?php echo esc_html( $lead['company_size'] ); ?>
                                </span>
                            </td>
                            <td class="column-category" data-colname="<?php esc_attr_e( 'Category', 'rtbcb' ); ?>">
                                <?php if ( ! empty( $lead['recommended_category'] ) ) : ?>
                                    <?php
                                    $cat_info = $categories[ $lead['recommended_category'] ] ?? [];
                                    $cat_name = $cat_info['name'] ?? ucfirst( str_replace( '_', ' ', $lead['recommended_category'] ) );
                                    ?>
                                    <span class="rtbcb-category-badge rtbcb-cat-<?php echo esc_attr( $lead['recommended_category'] ); ?>">
                                        <?php echo esc_html( $cat_name ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="rtbcb-no-data"><?php esc_html_e( 'Not categorized', 'rtbcb' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-roi" data-colname="<?php esc_attr_e( 'ROI', 'rtbcb' ); ?>">
                                <?php if ( $lead['roi_base'] > 0 ) : ?>
                                    <span class="rtbcb-roi-amount">$<?php echo esc_html( number_format( $lead['roi_base'] ) ); ?></span>
                                <?php else : ?>
                                    <span class="rtbcb-no-data"><?php esc_html_e( 'No data', 'rtbcb' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-date" data-colname="<?php esc_attr_e( 'Date', 'rtbcb' ); ?>">
                                <span title="<?php echo esc_attr( $lead['created_at'] ); ?>">
                                    <?php echo esc_html( human_time_diff( strtotime( $lead['created_at'] ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'rtbcb' ); ?>
                                </span>
                            </td>
                            <td class="column-actions" data-colname="<?php esc_attr_e( 'Actions', 'rtbcb' ); ?>">
                                <div class="rtbcb-action-buttons">
                                    <button type="button" class="button button-small rtbcb-view-lead" data-lead-id="<?php echo esc_attr( $lead['id'] ); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php esc_html_e( 'View', 'rtbcb' ); ?>
                                    </button>
                                    <?php if ( $lead['pdf_generated'] && ! empty( $lead['pdf_path'] ) ) : ?>
                                        <a href="<?php echo esc_url( RTBCB_PDF::get_download_url( $lead['pdf_path'] ) ); ?>" class="button button-small" target="_blank">
                                            <span class="dashicons dashicons-media-document"></span>
                                            <?php esc_html_e( 'PDF', 'rtbcb' ); ?>
                                        </a>
                                    <?php endif; ?>
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
                $pagination_args = [
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo; ' . __( 'Previous', 'rtbcb' ),
                    'next_text' => __( 'Next', 'rtbcb' ) . ' &raquo;',
                ];
                echo wp_kses_post( paginate_links( $pagination_args ) );
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="rtbcb-no-data">
            <div class="rtbcb-no-data-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <h3><?php esc_html_e( 'No leads found', 'rtbcb' ); ?></h3>
            <p><?php esc_html_e( 'No one has completed the business case form yet, or your current filters don\'t match any leads.', 'rtbcb' ); ?></p>
            <?php if ( ! empty( array_filter( [ $search, $category, $date_from, $date_to ] ) ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-leads' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Clear Filters', 'rtbcb' ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Lead Details Modal -->
<div id="rtbcb-lead-modal" class="rtbcb-modal" style="display: none;">
    <div class="rtbcb-modal-content">
        <div class="rtbcb-modal-header">
            <h2><?php esc_html_e( 'Lead Details', 'rtbcb' ); ?></h2>
            <button type="button" class="rtbcb-modal-close">&times;</button>
        </div>
        <div class="rtbcb-modal-body">
            <div id="rtbcb-lead-details">
                <!-- Lead details will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>
