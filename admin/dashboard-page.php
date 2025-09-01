<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enhanced Dashboard admin page for Real Treasury Business Case Builder plugin.
 */

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$total_leads = $stats['total_leads'] ?? 0;
$recent_leads = $stats['recent_leads'] ?? 0;
$category_stats = $stats['by_category'] ?? [];
$size_stats = $stats['by_company_size'] ?? [];
$roi_stats = $stats['average_roi'] ?? [];
$leads = $recent_leads_data['leads'] ?? [];

// System health checks
$api_key       = rtbcb_get_openai_api_key();
$api_key_configured = rtbcb_has_openai_api_key();
$api_key_valid = $api_key_configured && rtbcb_is_valid_openai_api_key( $api_key );
$portal_active = (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
$last_indexed  = get_option( 'rtbcb_last_indexed', '' );

// Quick stats
$conversion_rate = $total_leads > 0 ? ( $recent_leads / $total_leads ) * 100 : 0;
$avg_roi = intval( $roi_stats['avg_base'] ?? 0 );
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
			<div class="rtbcb-status-item <?php echo $api_key_valid ? 'rtbcb-status-good' : 'rtbcb-status-warning'; ?>">
				<div class="rtbcb-status-icon">
					<span class="dashicons <?php echo $api_key_valid ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
				</div>
				<div class="rtbcb-status-content">
					<div class="rtbcb-status-label"><?php esc_html_e( 'OpenAI API', 'rtbcb' ); ?></div>
					<div class="rtbcb-status-value">
						<?php
						if ( ! $api_key_configured ) {
							esc_html_e( 'Not configured', 'rtbcb' );
						} elseif ( ! $api_key_valid ) {
							esc_html_e( 'Invalid key format', 'rtbcb' );
						} else {
							esc_html_e( 'Configured', 'rtbcb' );
						}
						?>
					</div>
				</div>
				<?php if ( ! $api_key_valid ) : ?>
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

						$categories = RTBCB_Category_Recommender::get_all_categories();
						$top_cat_info = $categories[ $top_category['recommended_category'] ] ?? [];
			$top_cat_name = $top_cat_info['name'] ?? __( 'TMS', 'rtbcb' );
			echo esc_html( $top_cat_name );
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
								<span class="rtbcb-roi"><?php printf( esc_html__( '%s ROI', 'rtbcb' ), esc_html( '$' . number_format( $lead['roi_base'] ) ) ); ?></span>
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
					$cat_info = $categories[ $stat['recommended_category'] ] ?? [];
					$cat_name = $cat_info['name'] ?? ucfirst( str_replace( '_', ' ', $stat['recommended_category'] ) );
					$percentage = $total_leads > 0 ? ( $stat['count'] / $total_leads ) * 100 : 0;
					?>
					<div class="rtbcb-category-item">
						<div class="rtbcb-category-header">
							<span class="rtbcb-category-name"><?php echo esc_html( $cat_name ); ?></span>
								<span class="rtbcb-category-count"><?php printf( esc_html__( '%d leads', 'rtbcb' ), intval( $stat['count'] ) ); ?></span>
						</div>
						<div class="rtbcb-category-bar">
							<div class="rtbcb-category-fill rtbcb-cat-<?php echo esc_attr( $stat['recommended_category'] ); ?>"
								style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
						</div>
						<div class="rtbcb-category-percentage"><?php echo esc_html( number_format( $percentage, 1 ) ); ?>%</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<style>
/* Dashboard specific styles */
.rtbcb-dashboard {
	background: #f1f1f1;
	margin: 0 -20px -20px -2px;
	padding: 20px;
}

.rtbcb-dashboard-header {
	background: white;
	padding: 30px;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	margin-bottom: 30px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.rtbcb-dashboard-title h1 {
	margin: 0 0 8px 0;
	color: #1f2937;
	font-size: 28px;
}

.rtbcb-dashboard-subtitle {
	margin: 0;
	color: #6b7280;
	font-size: 16px;
}

.rtbcb-dashboard-actions {
	display: flex;
	gap: 12px;
}

/* System Status */
.rtbcb-system-status {
	background: white;
	padding: 24px;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	margin-bottom: 30px;
}

.rtbcb-system-status h3 {
	margin: 0 0 20px 0;
	color: #1f2937;
}

.rtbcb-status-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 16px;
}

.rtbcb-status-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px;
	background: #f9fafb;
	border-radius: 6px;
	border-left: 4px solid #d1d5db;
}

.rtbcb-status-good {
	background: #ecfdf5;
	border-left-color: #059669;
}

.rtbcb-status-warning {
	background: #fef3c7;
	border-left-color: #d97706;
}

.rtbcb-status-info {
	background: #eff6ff;
	border-left-color: #2563eb;
}

.rtbcb-status-icon {
	color: #6b7280;
	font-size: 20px;
}

.rtbcb-status-good .rtbcb-status-icon {
	color: #059669;
}

.rtbcb-status-warning .rtbcb-status-icon {
	color: #d97706;
}

.rtbcb-status-info .rtbcb-status-icon {
	color: #2563eb;
}

.rtbcb-status-content {
	flex: 1;
}

.rtbcb-status-label {
	font-weight: 600;
	color: #374151;
	margin-bottom: 2px;
}

.rtbcb-status-value {
	font-size: 14px;
	color: #6b7280;
}

.rtbcb-status-action {
	background: none;
	border: 1px solid #d1d5db;
	padding: 6px 12px;
	border-radius: 4px;
	font-size: 12px;
	color: #374151;
	text-decoration: none;
	cursor: pointer;
}

.rtbcb-status-action:hover {
	background: #f3f4f6;
}

/* Metrics Overview */
.rtbcb-metrics-overview {
	display: grid;
	grid-template-columns: 1fr 2fr;
	gap: 20px;
	margin-bottom: 30px;
}

.rtbcb-metric-large {
	background: linear-gradient(135deg, #7216f4, #8f47f6);
	color: white;
	padding: 30px;
	border-radius: 8px;
	display: flex;
	align-items: center;
	gap: 20px;
}

.rtbcb-metric-large .rtbcb-metric-icon {
	width: 60px;
	height: 60px;
	background: rgba(255,255,255,0.2);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 30px;
}

.rtbcb-metric-large .rtbcb-metric-value {
	font-size: 36px;
	font-weight: 700;
	margin-bottom: 8px;
}

.rtbcb-metric-large .rtbcb-metric-label {
	font-size: 16px;
	opacity: 0.9;
	margin-bottom: 4px;
}

.rtbcb-metric-large .rtbcb-metric-subtitle {
	font-size: 14px;
	opacity: 0.7;
}

.rtbcb-metrics-grid {
	display: grid;
	gap: 20px;
}

.rtbcb-metric-card {
	background: white;
	padding: 20px;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rtbcb-metric-header {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 12px;
	color: #6b7280;
	font-size: 14px;
	font-weight: 500;
}

.rtbcb-metric-card .rtbcb-metric-value {
	font-size: 24px;
	font-weight: 700;
	color: #1f2937;
	margin-bottom: 8px;
}

.rtbcb-metric-change {
	display: flex;
	align-items: center;
	gap: 4px;
	font-size: 12px;
	color: #6b7280;
}

.rtbcb-metric-positive {
	color: #059669;
}

/* Dashboard Content */
.rtbcb-dashboard-content {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
	margin-bottom: 30px;
}

.rtbcb-dashboard-section {
	background: white;
	border-radius: 8px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	overflow: hidden;
}

.rtbcb-section-full {
	grid-column: 1 / -1;
}

.rtbcb-section-header {
	padding: 20px 24px;
	border-bottom: 1px solid #f3f4f6;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.rtbcb-section-header h3 {
	margin: 0;
	color: #1f2937;
	font-size: 18px;
}

.rtbcb-section-link {
	color: #7216f4;
	text-decoration: none;
	font-size: 14px;
	font-weight: 500;
}

.rtbcb-section-link:hover {
	text-decoration: underline;
}

/* Recent Leads */
.rtbcb-recent-leads {
	padding: 0 24px 24px;
}

.rtbcb-lead-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px 0;
	border-bottom: 1px solid #f3f4f6;
}

.rtbcb-lead-item:last-child {
	border-bottom: none;
}

.rtbcb-lead-avatar {
	width: 40px;
	height: 40px;
	background: #7216f4;
	color: white;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: 600;
	font-size: 14px;
}

.rtbcb-lead-info {
	flex: 1;
}

.rtbcb-lead-email {
	font-weight: 500;
	color: #1f2937;
	margin-bottom: 2px;
}

.rtbcb-lead-meta {
	font-size: 12px;
	color: #6b7280;
}

.rtbcb-lead-separator {
	margin: 0 4px;
}

.rtbcb-roi {
	color: #059669;
	font-weight: 500;
}

.rtbcb-lead-time {
	font-size: 12px;
	color: #9ca3af;
}

/* Quick Actions */
.rtbcb-quick-actions {
	padding: 24px;
	display: grid;
	gap: 16px;
}

.rtbcb-action-card {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px;
	background: #f9fafb;
	border: 1px solid #e5e7eb;
	border-radius: 6px;
	text-decoration: none;
	color: inherit;
	cursor: pointer;
	transition: all 0.2s ease;
}

.rtbcb-action-card:hover {
	background: #f3f4f6;
	border-color: #d1d5db;
	transform: translateY(-1px);
}

.rtbcb-action-icon {
	width: 40px;
	height: 40px;
	background: #7216f4;
	color: white;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 16px;
}

.rtbcb-action-content h4 {
	margin: 0 0 4px 0;
	color: #1f2937;
	font-size: 14px;
	font-weight: 600;
}

.rtbcb-action-content p {
	margin: 0;
	color: #6b7280;
	font-size: 12px;
}

/* Category Summary */
.rtbcb-category-summary {
	padding: 24px;
	display: grid;
	gap: 16px;
}

.rtbcb-category-item {
	display: grid;
	gap: 8px;
}

.rtbcb-category-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.rtbcb-category-name {
	font-weight: 600;
	color: #1f2937;
}

.rtbcb-category-count {
	font-size: 14px;
	color: #6b7280;
}

.rtbcb-category-bar {
	width: 100%;
	height: 8px;
	background: #f3f4f6;
	border-radius: 4px;
	overflow: hidden;
}

.rtbcb-category-fill {
	height: 100%;
	border-radius: 4px;
}

.rtbcb-cat-cash_tools { background: #7216f4; }
.rtbcb-cat-tms_lite { background: #8f47f6; }
.rtbcb-cat-trms { background: #c77dff; }

.rtbcb-category-percentage {
	text-align: right;
	font-size: 12px;
	color: #6b7280;
	font-weight: 500;
}

.rtbcb-empty-state {
	padding: 40px 24px;
	text-align: center;
	color: #9ca3af;
}

.rtbcb-empty-state .dashicons {
	font-size: 48px;
	margin-bottom: 16px;
	display: block;
}

@media (max-width: 1200px) {
	.rtbcb-metrics-overview {
		grid-template-columns: 1fr;
	}

	.rtbcb-dashboard-content {
		grid-template-columns: 1fr;
	}
}

@media (max-width: 768px) {
	.rtbcb-dashboard-header {
		flex-direction: column;
		gap: 20px;
		align-items: stretch;
	}

	.rtbcb-dashboard-actions {
		justify-content: center;
	}

	.rtbcb-status-grid {
		grid-template-columns: 1fr;
	}
}
</style>
