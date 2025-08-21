<?php
/**
 * Analytics admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories     = RTBCB_Category_Recommender::get_all_categories();
$total_leads    = $stats['total_leads'] ?? 0;
$recent_leads   = $stats['recent_leads'] ?? 0;
$category_stats = $stats['by_category'] ?? [];
$size_stats     = $stats['by_company_size'] ?? [];
$roi_stats      = $stats['average_roi'] ?? [];
$monthly_trends = $monthly_trends ?? [];
?>
<div
    class="wrap rtbcb-admin-page"
    data-categories="<?php echo esc_attr( wp_json_encode( $categories ) ); ?>"
    data-category-stats="<?php echo esc_attr( wp_json_encode( $category_stats ) ); ?>"
    data-size-stats="<?php echo esc_attr( wp_json_encode( $size_stats ) ); ?>"
    data-monthly-trends="<?php echo esc_attr( wp_json_encode( $monthly_trends ) ); ?>"
    data-total-leads="<?php echo esc_attr( $total_leads ); ?>"
>
    <div class="rtbcb-admin-header">
        <h1><?php echo esc_html__( 'Analytics & Insights', 'rtbcb' ); ?></h1>
        <div class="rtbcb-date-range">
            <select id="rtbcb-analytics-period">
                <option value="30"><?php esc_html_e( 'Last 30 days', 'rtbcb' ); ?></option>
                <option value="90"><?php esc_html_e( 'Last 90 days', 'rtbcb' ); ?></option>
                <option value="365"><?php esc_html_e( 'Last year', 'rtbcb' ); ?></option>
                <option value="all"><?php esc_html_e( 'All time', 'rtbcb' ); ?></option>
            </select>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="rtbcb-metrics-grid">
        <div class="rtbcb-metric-card">
            <div class="rtbcb-metric-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="rtbcb-metric-content">
                <div class="rtbcb-metric-value"><?php echo esc_html( number_format( $total_leads ) ); ?></div>
                <div class="rtbcb-metric-label"><?php esc_html_e( 'Total Leads', 'rtbcb' ); ?></div>
            </div>
        </div>

        <div class="rtbcb-metric-card">
            <div class="rtbcb-metric-icon rtbcb-metric-trend-up">
                <span class="dashicons dashicons-arrow-up-alt2"></span>
            </div>
            <div class="rtbcb-metric-content">
                <div class="rtbcb-metric-value"><?php echo esc_html( number_format( $recent_leads ) ); ?></div>
                <div class="rtbcb-metric-label"><?php esc_html_e( 'This Month', 'rtbcb' ); ?></div>
            </div>
        </div>

        <div class="rtbcb-metric-card">
            <div class="rtbcb-metric-icon rtbcb-metric-roi">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="rtbcb-metric-content">
                <div class="rtbcb-metric-value">
                    $<?php echo esc_html( number_format( intval( $roi_stats['avg_base'] ?? 0 ) ) ); ?>
                </div>
                <div class="rtbcb-metric-label"><?php esc_html_e( 'Avg. ROI', 'rtbcb' ); ?></div>
            </div>
        </div>

        <div class="rtbcb-metric-card">
            <div class="rtbcb-metric-icon rtbcb-metric-conversion">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="rtbcb-metric-content">
                <div class="rtbcb-metric-value">
                    <?php echo $total_leads > 0 ? esc_html( number_format( ( $recent_leads / max( 1, $total_leads ) ) * 100, 1 ) ) : '0'; ?>%
                </div>
                <div class="rtbcb-metric-label"><?php esc_html_e( 'Growth Rate', 'rtbcb' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="rtbcb-charts-grid">
        <!-- Category Distribution Chart -->
        <div class="rtbcb-chart-card">
            <div class="rtbcb-chart-header">
                <h3><?php esc_html_e( 'Recommended Categories', 'rtbcb' ); ?></h3>
                <p class="rtbcb-chart-description">
                    <?php esc_html_e( 'Distribution of solution categories recommended to leads', 'rtbcb' ); ?>
                </p>
            </div>
            <div class="rtbcb-chart-container">
                <canvas id="rtbcb-category-chart" width="400" height="200"></canvas>
            </div>
            <div class="rtbcb-chart-legend">
                <?php foreach ( $category_stats as $stat ) : ?>
                    <?php
                    $category_info = $categories[ $stat['recommended_category'] ] ?? [];
                    $category_name = $category_info['name'] ?? ucfirst( str_replace( '_', ' ', $stat['recommended_category'] ) );
                    ?>
                    <div class="rtbcb-legend-item">
                        <span class="rtbcb-legend-color rtbcb-cat-<?php echo esc_attr( $stat['recommended_category'] ); ?>"></span>
                        <span class="rtbcb-legend-label"><?php echo esc_html( $category_name ); ?></span>
                        <span class="rtbcb-legend-value"><?php echo esc_html( $stat['count'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Company Size Distribution -->
        <div class="rtbcb-chart-card">
            <div class="rtbcb-chart-header">
                <h3><?php esc_html_e( 'Company Size Distribution', 'rtbcb' ); ?></h3>
                <p class="rtbcb-chart-description">
                    <?php esc_html_e( 'Breakdown of leads by company revenue size', 'rtbcb' ); ?>
                </p>
            </div>
            <div class="rtbcb-chart-container">
                <canvas id="rtbcb-size-chart" width="400" height="200"></canvas>
            </div>
            <div class="rtbcb-chart-stats">
                <?php foreach ( $size_stats as $stat ) : ?>
                    <div class="rtbcb-stat-item">
                        <div class="rtbcb-stat-label"><?php echo esc_html( $stat['company_size'] ); ?></div>
                        <div class="rtbcb-stat-value"><?php echo esc_html( $stat['count'] ); ?> <?php esc_html_e( 'leads', 'rtbcb' ); ?></div>
                        <div class="rtbcb-stat-percentage">
                            <?php echo $total_leads > 0 ? esc_html( number_format( ( $stat['count'] / $total_leads ) * 100, 1 ) ) : '0'; ?>%
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ROI Analysis -->
        <div class="rtbcb-chart-card rtbcb-chart-wide">
            <div class="rtbcb-chart-header">
                <h3><?php esc_html_e( 'ROI Analysis', 'rtbcb' ); ?></h3>
                <p class="rtbcb-chart-description">
                    <?php esc_html_e( 'Average ROI projections across all scenarios', 'rtbcb' ); ?>
                </p>
            </div>
            <div class="rtbcb-roi-overview">
                <div class="rtbcb-roi-stat">
                    <div class="rtbcb-roi-label"><?php esc_html_e( 'Conservative', 'rtbcb' ); ?></div>
                    <div class="rtbcb-roi-value">$<?php echo esc_html( number_format( intval( $roi_stats['avg_low'] ?? 0 ) ) ); ?></div>
                </div>
                <div class="rtbcb-roi-stat rtbcb-roi-primary">
                    <div class="rtbcb-roi-label"><?php esc_html_e( 'Base Case', 'rtbcb' ); ?></div>
                    <div class="rtbcb-roi-value">$<?php echo esc_html( number_format( intval( $roi_stats['avg_base'] ?? 0 ) ) ); ?></div>
                </div>
                <div class="rtbcb-roi-stat">
                    <div class="rtbcb-roi-label"><?php esc_html_e( 'Optimistic', 'rtbcb' ); ?></div>
                    <div class="rtbcb-roi-value">$<?php echo esc_html( number_format( intval( $roi_stats['avg_high'] ?? 0 ) ) ); ?></div>
                </div>
            </div>
        </div>

        <!-- Monthly Trends -->
        <div class="rtbcb-chart-card rtbcb-chart-wide">
            <div class="rtbcb-chart-header">
                <h3><?php esc_html_e( 'Lead Generation Trends', 'rtbcb' ); ?></h3>
                <p class="rtbcb-chart-description">
                    <?php esc_html_e( 'Monthly lead volume and average ROI over time', 'rtbcb' ); ?>
                </p>
            </div>
            <div class="rtbcb-chart-container">
                <canvas id="rtbcb-trends-chart" width="800" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Insights Section -->
    <div class="rtbcb-insights-section">
        <h2><?php esc_html_e( 'Key Insights', 'rtbcb' ); ?></h2>
        <div class="rtbcb-insights-grid">
            <?php if ( ! empty( $category_stats ) ) : ?>
                <?php
                $top_category = array_reduce(
                    $category_stats,
                    function( $carry, $item ) {
                        return ( ! $carry || $item['count'] > $carry['count'] ) ? $item : $carry;
                    }
                );

                if ( $top_category ) {
                    $top_cat_info   = $categories[ $top_category['recommended_category'] ] ?? [];
                    $top_cat_name   = $top_cat_info['name'] ?? '';
                    $top_percentage = $total_leads > 0 ? ( $top_category['count'] / $total_leads ) * 100 : 0;
                }
                ?>
                <div class="rtbcb-insight-card">
                    <div class="rtbcb-insight-icon">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="rtbcb-insight-content">
                        <h4><?php esc_html_e( 'Most Popular Category', 'rtbcb' ); ?></h4>
                        <p>
                            <?php
                            printf(
                                esc_html__( '%1$s is recommended for %2$d%% of leads (%3$d out of %4$d).', 'rtbcb' ),
                                esc_html( $top_cat_name ?? 'Unknown' ),
                                esc_html( number_format( $top_percentage ?? 0, 1 ) ),
                                esc_html( $top_category['count'] ?? 0 ),
                                esc_html( $total_leads )
                            );
                            ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $size_stats ) ) : ?>
                <?php
                $enterprise_count = 0;
                $smb_count        = 0;

                foreach ( $size_stats as $stat ) {
                    if ( in_array( $stat['company_size'], [ '>$2B', '$500M-$2B' ], true ) ) {
                        $enterprise_count += $stat['count'];
                    } else {
                        $smb_count += $stat['count'];
                    }
                }

                $enterprise_percentage = $total_leads > 0 ? ( $enterprise_count / $total_leads ) * 100 : 0;
                ?>
                <div class="rtbcb-insight-card">
                    <div class="rtbcb-insight-icon">
                        <span class="dashicons dashicons-building"></span>
                    </div>
                    <div class="rtbcb-insight-content">
                        <h4><?php esc_html_e( 'Market Segment', 'rtbcb' ); ?></h4>
                        <p>
                            <?php
                            printf(
                                esc_html__( '%1$d%% of leads are enterprise-level companies ($500M+ revenue), indicating strong interest from larger organizations.', 'rtbcb' ),
                                esc_html( number_format( $enterprise_percentage, 1 ) )
                            );
                            ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="rtbcb-insight-card">
                <div class="rtbcb-insight-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="rtbcb-insight-content">
                    <h4><?php esc_html_e( 'ROI Potential', 'rtbcb' ); ?></h4>
                    <p>
                        <?php
                        $avg_roi_value = intval( $roi_stats['avg_base'] ?? 0 );
                        if ( $avg_roi_value > 100000 ) {
                            printf(
                                esc_html__( 'Average projected ROI of $%s demonstrates strong value proposition for treasury technology investments.', 'rtbcb' ),
                                esc_html( number_format( $avg_roi_value ) )
                            );
                        } else {
                            esc_html_e( 'ROI calculations show significant potential for operational efficiency improvements.', 'rtbcb' );
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="rtbcb-insight-card">
                <div class="rtbcb-insight-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="rtbcb-insight-content">
                    <h4><?php esc_html_e( 'Recent Activity', 'rtbcb' ); ?></h4>
                    <p>
                        <?php
                        if ( $recent_leads > 0 ) {
                            printf(
                                esc_html__( '%1$d new leads in the last 30 days shows growing interest in treasury technology solutions.', 'rtbcb' ),
                                esc_html( $recent_leads )
                            );
                        } else {
                            esc_html_e( 'Consider marketing efforts to drive more lead generation activity.', 'rtbcb' );
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
