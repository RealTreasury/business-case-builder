<?php
/**
 * Analytics admin page for Real Treasury Business Case Builder plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = RTBCB_Category_Recommender::get_all_categories();
$total_leads = $stats['total_leads'] ?? 0;
$recent_leads = $stats['recent_leads'] ?? 0;
$category_stats = $stats['by_category'] ?? [];
$size_stats = $stats['by_company_size'] ?? [];
$roi_stats = $stats['average_roi'] ?? [];
?>

<div class="wrap rtbcb-admin-page">
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
                        <div class="rtbcb-stat-value"><?php echo esc_html( $stat['count'] ); ?> leads</div>
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
                $top_category = array_reduce( $category_stats, function( $carry, $item ) {
                    return ( ! $carry || $item['count'] > $carry['count'] ) ? $item : $carry;
                } );
                
                if ( $top_category ) {
                    $top_cat_info = $categories[ $top_category['recommended_category'] ] ?? [];
                    $top_cat_name = $top_cat_info['name'] ?? '';
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
                $smb_count = 0;
                
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
                        $avg_roi = intval( $roi_stats['avg_base'] ?? 0 );
                        if ( $avg_roi > 100000 ) {
                            printf( 
                                esc_html__( 'Average projected ROI of $%s demonstrates strong value proposition for treasury technology investments.', 'rtbcb' ),
                                esc_html( number_format( $avg_roi ) )
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

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    initializeAnalyticsCharts();
});

async function initializeAnalyticsCharts() {
    const fallbackMessage = '<?php echo esc_js( __( 'Chart unavailable', 'rtbcb' ) ); ?>';
    const showFallback = (id) => {
        const canvas = document.getElementById(id);
        if (canvas) {
            const div = document.createElement('div');
            div.textContent = fallbackMessage;
            canvas.replaceWith(div);
        }
    };

    if (typeof Chart === 'undefined') {
        try {
            await import('https://cdn.jsdelivr.net/npm/chart.js');
        } catch (error) {
            console.error('Chart.js failed to load:', error);
            showFallback('rtbcb-category-chart');
            showFallback('rtbcb-size-chart');
            showFallback('rtbcb-trends-chart');
            return;
        }
    }

    // Category Distribution Chart
    const categoryData = <?php echo wp_json_encode( $category_stats ); ?>;
    const categoryLabels = categoryData.map(item => {
        const categories = <?php echo wp_json_encode( $categories ); ?>;
        return categories[item.recommended_category]?.name || item.recommended_category;
    });
    const categoryValues = categoryData.map(item => item.count);
    const categoryColors = ['#7216f4', '#8f47f6', '#c77dff', '#e0aaff', '#f3c4fb'];

    if (categoryData.length > 0) {
        try {
            new Chart(document.getElementById('rtbcb-category-chart'), {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryValues,
                        backgroundColor: categoryColors.slice(0, categoryData.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error rendering category chart:', error);
            showFallback('rtbcb-category-chart');
        }
    } else {
        showFallback('rtbcb-category-chart');
    }

    // Company Size Chart
    const sizeData = <?php echo wp_json_encode( $size_stats ); ?>;
    const sizeLabels = sizeData.map(item => item.company_size);
    const sizeValues = sizeData.map(item => item.count);
    const sizeColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#fde2e8'];

    if (sizeData.length > 0) {
        try {
            new Chart(document.getElementById('rtbcb-size-chart'), {
                type: 'bar',
                data: {
                    labels: sizeLabels,
                    datasets: [{
                        data: sizeValues,
                        backgroundColor: sizeColors.slice(0, sizeData.length),
                        borderColor: ['#1e40af', '#166534', '#92400e', '#be185d'],
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
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error rendering size chart:', error);
            showFallback('rtbcb-size-chart');
        }
    } else {
        showFallback('rtbcb-size-chart');
    }

    // Trends Chart
    const trendsData = <?php echo wp_json_encode( $monthly_trends ); ?>;
    const trendLabels = trendsData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    const leadCounts = trendsData.map(item => parseInt(item.leads));
    const avgROIs = trendsData.map(item => Math.round(parseFloat(item.avg_roi || 0) / 1000)); // Convert to thousands

    if (trendsData.length > 0) {
        try {
            new Chart(document.getElementById('rtbcb-trends-chart'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Leads',
                            data: leadCounts,
                            backgroundColor: 'rgba(114, 22, 244, 0.1)',
                            borderColor: '#7216f4',
                            borderWidth: 2,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Avg ROI (K)',
                            data: avgROIs,
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderColor: '#10b981',
                            borderWidth: 2,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Leads'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Average ROI (Thousands)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error rendering trends chart:', error);
            showFallback('rtbcb-trends-chart');
        }
    } else {
        showFallback('rtbcb-trends-chart');
    }
}
</script>

<style>
/* Analytics page specific styles */
.rtbcb-admin-page {
    background: #f1f1f1;
    margin: 0 -20px -20px -2px;
    padding: 20px;
}

.rtbcb-admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rtbcb-admin-header h1 {
    margin: 0;
    color: #1f2937;
}

.rtbcb-date-range select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

/* Metrics Grid */
.rtbcb-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.rtbcb-metric-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 16px;
}

.rtbcb-metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 24px;
}

.rtbcb-metric-trend-up {
    background: #dcfce7;
    color: #166534;
}

.rtbcb-metric-roi {
    background: #e0e7ff;
    color: #3730a3;
}

.rtbcb-metric-conversion {
    background: #fef3c7;
    color: #92400e;
}

.rtbcb-metric-value {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.rtbcb-metric-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

/* Charts Grid */
.rtbcb-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.rtbcb-chart-wide {
    grid-column: 1 / -1;
}

.rtbcb-chart-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rtbcb-chart-header {
    margin-bottom: 20px;
}

.rtbcb-chart-header h3 {
    margin: 0 0 8px 0;
    color: #111827;
    font-size: 18px;
}

.rtbcb-chart-description {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.rtbcb-chart-container {
    height: 250px;
    position: relative;
}

.rtbcb-chart-legend {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rtbcb-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.rtbcb-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.rtbcb-legend-color.rtbcb-cat-cash_tools { background: #7216f4; }
.rtbcb-legend-color.rtbcb-cat-tms_lite { background: #8f47f6; }
.rtbcb-legend-color.rtbcb-cat-trms { background: #c77dff; }

.rtbcb-legend-label {
    flex: 1;
    color: #374151;
}

.rtbcb-legend-value {
    font-weight: 600;
    color: #111827;
}

.rtbcb-chart-stats {
    margin-top: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}

.rtbcb-stat-item {
    text-align: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.rtbcb-stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}

.rtbcb-stat-value {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
}

.rtbcb-stat-percentage {
    font-size: 11px;
    color: #059669;
    font-weight: 500;
}

/* ROI Overview */
.rtbcb-roi-overview {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 20px;
}

.rtbcb-roi-stat {
    text-align: center;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    border: 2px solid transparent;
}

.rtbcb-roi-primary {
    background: #f0f9ff;
    border-color: #0ea5e9;
}

.rtbcb-roi-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 8px;
    font-weight: 500;
}

.rtbcb-roi-value {
    font-size: 24px;
    font-weight: 700;
    color: #059669;
}

/* Insights Section */
.rtbcb-insights-section {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rtbcb-insights-section h2 {
    margin: 0 0 20px 0;
    color: #111827;
}

.rtbcb-insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.rtbcb-insight-card {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #7216f4;
}

.rtbcb-insight-icon {
    width: 40px;
    height: 40px;
    background: #7216f4;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rtbcb-insight-content h4 {
    margin: 0 0 8px 0;
    color: #111827;
    font-size: 16px;
}

.rtbcb-insight-content p {
    margin: 0;
    color: #4b5563;
    font-size: 14px;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .rtbcb-admin-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .rtbcb-metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-charts-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-roi-overview {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-insights-grid {
        grid-template-columns: 1fr;
    }
}
</style>
