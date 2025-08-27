<?php
/**
 * Comprehensive business case report template
 *
 * @package RealTreasuryBusinessCaseBuilder
 * @var array $business_case_data Enhanced business case data from LLM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company_name = $business_case_data['company_name'] ?? __( 'Your Company', 'rtbcb' );
$analysis_date = $business_case_data['analysis_date'] ?? current_time( 'Y-m-d' );
$executive_summary = $business_case_data['executive_summary'] ?? [];
$operational_analysis = $business_case_data['operational_analysis'] ?? [];
$industry_insights = $business_case_data['industry_insights'] ?? [];
$tech_recommendations = $business_case_data['technology_recommendations'] ?? [];
$financial_analysis = $business_case_data['financial_analysis'] ?? [];
$risk_mitigation = $business_case_data['risk_mitigation'] ?? [];
$next_steps = $business_case_data['next_steps'] ?? [];
$confidence_level = round( ( $business_case_data['confidence_level'] ?? 0.85 ) * 100 );
?>

<div class="rtbcb-comprehensive-report">
    <!-- Report Header -->
    <div class="rtbcb-report-header">
        <div class="rtbcb-report-badge">
            <span class="rtbcb-badge-icon">🏆</span>
            <span class="rtbcb-badge-text"><?php echo esc_html__( 'COMPREHENSIVE ANALYSIS', 'rtbcb' ); ?></span>
            <span class="rtbcb-confidence"><?php echo esc_html( $confidence_level ); ?>% <?php echo esc_html__( 'Confidence', 'rtbcb' ); ?></span>
        </div>
        <h1 class="rtbcb-report-title"><?php echo esc_html( $company_name ); ?> <?php echo esc_html__( 'Treasury Technology Business Case', 'rtbcb' ); ?></h1>
        <div class="rtbcb-report-meta">
            <span class="rtbcb-report-date"><?php printf( esc_html__( 'Analysis Date: %s', 'rtbcb' ), esc_html( $analysis_date ) ); ?></span>
            <span class="rtbcb-report-type"><?php echo esc_html__( 'Strategic Assessment & ROI Analysis', 'rtbcb' ); ?></span>
        </div>
    </div>

    <!-- Executive Summary Section -->
    <?php if ( ! empty( $executive_summary ) ) : ?>
    <div class="rtbcb-section rtbcb-executive-summary">
        <div class="rtbcb-section-header">
            <h2><span class="rtbcb-section-icon">📋</span><?php echo esc_html__( 'Executive Summary', 'rtbcb' ); ?></h2>
            <div class="rtbcb-business-case-strength <?php echo esc_attr( strtolower( $executive_summary['business_case_strength'] ?? 'strong' ) ); ?>">
                <?php echo esc_html( $executive_summary['business_case_strength'] ?? esc_html__( 'Strong', 'rtbcb' ) ); ?> <?php echo esc_html__( 'Business Case', 'rtbcb' ); ?>
            </div>
        </div>
        
        <div class="rtbcb-executive-content">
            <?php if ( ! empty( $executive_summary['strategic_positioning'] ) ) : ?>
            <div class="rtbcb-strategic-positioning">
                <h3><?php echo esc_html__( 'Strategic Positioning', 'rtbcb' ); ?></h3>
                <p><?php echo esc_html( $executive_summary['strategic_positioning'] ); ?></p>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $executive_summary['key_value_drivers'] ) ) : ?>
            <div class="rtbcb-value-drivers">
                <h3><?php echo esc_html__( 'Key Value Drivers', 'rtbcb' ); ?></h3>
                <div class="rtbcb-value-drivers-grid">
                    <?php foreach ( $executive_summary['key_value_drivers'] as $index => $driver ) : ?>
                    <div class="rtbcb-value-driver">
                        <div class="rtbcb-driver-number"><?php echo esc_html( $index + 1 ); ?></div>
                        <div class="rtbcb-driver-text"><?php echo esc_html( $driver ); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $executive_summary['executive_recommendation'] ) ) : ?>
            <div class="rtbcb-executive-recommendation">
                <h3><?php echo esc_html__( 'Executive Recommendation', 'rtbcb' ); ?></h3>
                <div class="rtbcb-recommendation-content">
                    <?php echo esc_html( $executive_summary['executive_recommendation'] ); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Operational Analysis Section -->
    <?php if ( ! empty( $operational_analysis ) ) : ?>
    <div class="rtbcb-section rtbcb-operational-analysis">
        <div class="rtbcb-section-header">
            <h2><span class="rtbcb-section-icon">⚙️</span><?php echo esc_html__( 'Operational Analysis', 'rtbcb' ); ?></h2>
        </div>

        <?php if ( ! empty( $operational_analysis['current_state_assessment'] ) ) : ?>
        <div class="rtbcb-current-state">
            <h3><?php echo esc_html__( 'Current State Assessment', 'rtbcb' ); ?></h3>
            <div class="rtbcb-assessment-grid">
                <?php $assessment = $operational_analysis['current_state_assessment']; ?>
                
                <div class="rtbcb-assessment-card">
                    <div class="rtbcb-assessment-label"><?php echo esc_html__( 'Efficiency Rating', 'rtbcb' ); ?></div>
                    <div class="rtbcb-assessment-value <?php echo esc_attr( strtolower( $assessment['efficiency_rating'] ?? 'fair' ) ); ?>">
                        <?php echo esc_html( $assessment['efficiency_rating'] ?? esc_html__( 'Fair', 'rtbcb' ) ); ?>
                    </div>
                </div>

                <div class="rtbcb-assessment-detail">
                    <h4><?php echo esc_html__( 'Industry Benchmark Comparison', 'rtbcb' ); ?></h4>
                    <p><?php echo esc_html( $assessment['benchmark_comparison'] ?? '' ); ?></p>
                </div>

                <div class="rtbcb-assessment-detail">
                    <h4><?php echo esc_html__( 'Capacity Utilization', 'rtbcb' ); ?></h4>
                    <p><?php echo esc_html( $assessment['capacity_utilization'] ?? '' ); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $operational_analysis['process_inefficiencies'] ) ) : ?>
        <div class="rtbcb-process-inefficiencies">
            <h3><?php echo esc_html__( 'Process Inefficiencies Identified', 'rtbcb' ); ?></h3>
            <div class="rtbcb-inefficiencies-list">
                <?php foreach ( $operational_analysis['process_inefficiencies'] as $inefficiency ) : ?>
                <div class="rtbcb-inefficiency-item">
                    <div class="rtbcb-inefficiency-header">
                        <span class="rtbcb-inefficiency-process"><?php echo esc_html( $inefficiency['process'] ?? '' ); ?></span>
                        <span class="rtbcb-inefficiency-impact <?php echo esc_attr( strtolower( $inefficiency['impact'] ?? 'medium' ) ); ?>">
                            <?php echo esc_html( $inefficiency['impact'] ?? esc_html__( 'Medium', 'rtbcb' ) ); ?> <?php echo esc_html__( 'Impact', 'rtbcb' ); ?>
                        </span>
                    </div>
                    <div class="rtbcb-inefficiency-description">
                        <?php echo esc_html( $inefficiency['description'] ?? '' ); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $operational_analysis['automation_opportunities'] ) ) : ?>
        <div class="rtbcb-automation-opportunities">
            <h3><?php echo esc_html__( 'Automation Opportunities', 'rtbcb' ); ?></h3>
            <div class="rtbcb-opportunities-grid">
                <?php foreach ( $operational_analysis['automation_opportunities'] as $opportunity ) : ?>
                <div class="rtbcb-opportunity-card">
                    <div class="rtbcb-opportunity-area"><?php echo esc_html( $opportunity['area'] ?? '' ); ?></div>
                    <div class="rtbcb-opportunity-savings">
                        <span class="rtbcb-savings-number"><?php echo esc_html( $opportunity['potential_hours_saved'] ?? 0 ); ?></span>
                        <span class="rtbcb-savings-label"><?php echo esc_html__( 'Hours/Week Saved', 'rtbcb' ); ?></span>
                    </div>
                    <div class="rtbcb-opportunity-complexity <?php echo esc_attr( strtolower( $opportunity['complexity'] ?? 'medium' ) ); ?>">
                        <?php echo esc_html( $opportunity['complexity'] ?? esc_html__( 'Medium', 'rtbcb' ) ); ?> <?php echo esc_html__( 'Complexity', 'rtbcb' ); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Industry Insights Section -->
    <?php if ( ! empty( $industry_insights ) ) : ?>
    <div class="rtbcb-section rtbcb-industry-insights">
        <div class="rtbcb-section-header">
            <h2><span class="rtbcb-section-icon">📈</span><?php echo esc_html__( 'Industry Context & Insights', 'rtbcb' ); ?></h2>
        </div>

        <div class="rtbcb-insights-grid">
            <?php if ( ! empty( $industry_insights['sector_trends'] ) ) : ?>
            <div class="rtbcb-insight-card">
                <h3><span class="rtbcb-insight-icon">📊</span><?php echo esc_html__( 'Sector Trends', 'rtbcb' ); ?></h3>
                <p><?php echo esc_html( $industry_insights['sector_trends'] ); ?></p>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $industry_insights['competitive_benchmarks'] ) ) : ?>
            <div class="rtbcb-insight-card">
                <h3><span class="rtbcb-insight-icon">🏁</span><?php echo esc_html__( 'Competitive Benchmarks', 'rtbcb' ); ?></h3>
                <p><?php echo esc_html( $industry_insights['competitive_benchmarks'] ); ?></p>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $industry_insights['regulatory_considerations'] ) ) : ?>
            <div class="rtbcb-insight-card">
                <h3><span class="rtbcb-insight-icon">⚖️</span><?php echo esc_html__( 'Regulatory Considerations', 'rtbcb' ); ?></h3>
                <p><?php echo esc_html( $industry_insights['regulatory_considerations'] ); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Technology Recommendations Section -->
    <?php if ( ! empty( $tech_recommendations ) ) : ?>
    <div class="rtbcb-section rtbcb-tech-recommendations">
        <div class="rtbcb-section-header">
            <h2><span class="rtbcb-section-icon">💡</span><?php echo esc_html__( 'Technology Recommendations', 'rtbcb' ); ?></h2>
        </div>

        <?php if ( ! empty( $tech_recommendations['primary_solution'] ) ) : ?>
        <div class="rtbcb-primary-solution">
            <h3><?php echo esc_html__( 'Recommended Solution Category', 'rtbcb' ); ?></h3>
            <div class="rtbcb-solution-card">
                <div class="rtbcb-solution-header">
                    <span class="rtbcb-solution-category"><?php echo esc_html( $tech_recommendations['primary_solution']['category'] ?? '' ); ?></span>
                    <span class="rtbcb-solution-badge"><?php echo esc_html__( 'RECOMMENDED', 'rtbcb' ); ?></span>
                </div>
                <div class="rtbcb-solution-rationale">
                    <?php echo esc_html( $tech_recommendations['primary_solution']['rationale'] ?? '' ); ?>
                </div>
                <?php if ( ! empty( $tech_recommendations['primary_solution']['key_features'] ) ) : ?>
                <div class="rtbcb-solution-features">
                    <h4><?php printf( esc_html__( 'Key Features for %s', 'rtbcb' ), esc_html( $company_name ) ); ?></h4>
                    <ul class="rtbcb-features-list">
                        <?php foreach ( $tech_recommendations['primary_solution']['key_features'] as $feature ) : ?>
                        <li><?php echo esc_html( $feature ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $tech_recommendations['implementation_approach'] ) ) : ?>
        <div class="rtbcb-implementation-approach">
            <h3><?php echo esc_html__( 'Recommended Implementation Approach', 'rtbcb' ); ?></h3>
            <div class="rtbcb-implementation-phases">
                <?php $approach = $tech_recommendations['implementation_approach']; ?>
                
                <?php if ( ! empty( $approach['phase_1'] ) ) : ?>
                <div class="rtbcb-phase-card">
                    <div class="rtbcb-phase-number">1</div>
                    <div class="rtbcb-phase-content">
                        <h4><?php echo esc_html__( 'Phase 1: Foundation', 'rtbcb' ); ?></h4>
                        <p><?php echo esc_html( $approach['phase_1'] ); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $approach['phase_2'] ) ) : ?>
                <div class="rtbcb-phase-card">
                    <div class="rtbcb-phase-number">2</div>
                    <div class="rtbcb-phase-content">
                        <h4><?php echo esc_html__( 'Phase 2: Expansion', 'rtbcb' ); ?></h4>
                        <p><?php echo esc_html( $approach['phase_2'] ); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $approach['success_metrics'] ) ) : ?>
            <div class="rtbcb-success-metrics">
                <h4><?php echo esc_html__( 'Success Metrics', 'rtbcb' ); ?></h4>
                <div class="rtbcb-metrics-grid">
                    <?php foreach ( $approach['success_metrics'] as $metric ) : ?>
                    <div class="rtbcb-metric-item">
                        <span class="rtbcb-metric-icon">📏</span>
                        <?php echo esc_html( $metric ); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Financial Analysis Section -->
    <?php if ( ! empty( $financial_analysis ) ) : ?>
    <div class="rtbcb-section rtbcb-financial-analysis">
        <div class="rtbcb-section-header">
            <h2><span class="rtbcb-section-icon">💰</span><?php echo esc_html__( 'Financial Analysis', 'rtbcb' ); ?></h2>
        </div>

        <?php if ( ! empty( $financial_analysis['investment_breakdown'] ) ) : ?>
        <div class="rtbcb-investment-breakdown">
            <h3><?php echo esc_html__( 'Investment Breakdown', 'rtbcb' ); ?></h3>
            <div class="rtbcb-investment-grid">
                <?php foreach ( $financial_analysis['investment_breakdown'] as $category => $cost ) : ?>
                <div class="rtbcb-investment-item">
                    <div class="rtbcb-investment-category"><?php echo esc_html( ucwords( str_replace( '_', ' ', $category ) ) ); ?></div>
                    <div class="rtbcb-investment-cost"><?php echo esc_html( $cost ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $financial_analysis['payback_analysis'] ) ) : ?>
        <div class="rtbcb-payback-analysis">
            <h3><?php echo esc_html__( 'Return on Investment Analysis', 'rtbcb' ); ?></h3>
            <div class="rtbcb-payback-grid">
                <?php $payback = $financial_analysis['payback_analysis']; ?>
                
                <div class="rtbcb-payback-metric">
                    <div class="rtbcb-metric-value"><?php echo esc_html( $payback['payback_months'] ?? esc_html__( 'N/A', 'rtbcb' ) ); ?></div>
                    <div class="rtbcb-metric-label"><?php echo esc_html__( 'Months to Payback', 'rtbcb' ); ?></div>
                </div>

                <div class="rtbcb-payback-metric">
                    <div class="rtbcb-metric-value"><?php echo esc_html( $payback['roi_3_year'] ?? esc_html__( 'N/A', 'rtbcb' ) ); ?>%</div>
                    <div class="rtbcb-metric-label"><?php echo esc_html__( '3-Year ROI', 'rtbcb' ); ?></div>
                </div>

                <div class="rtbcb-payback-detail">
                    <h4><?php echo esc_html__( 'Net Present Value Analysis', 'rtbcb' ); ?></h4>
                    <p><?php echo esc_html( $payback['npv_analysis'] ?? '' ); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Risk Mitigation Section -->
    <?php if ( ! empty( $risk_mitigation ) ) : ?>
    <div class="rtbcb-section rtbcb-risk-mitigation">
        <div class="rtbcb-section-header">
            <h2><span class="rtbcb-section-icon">🛡️</span><?php echo esc_html__( 'Risk Assessment & Mitigation', 'rtbcb' ); ?></h2>
        </div>

        <?php if ( ! empty( $risk_mitigation['implementation_risks'] ) ) : ?>
        <div class="rtbcb-implementation-risks">
            <h3><?php echo esc_html__( 'Key Implementation Risks', 'rtbcb' ); ?></h3>
            <div class="rtbcb-risks-list">
                <?php foreach ( $risk_mitigation['implementation_risks'] as $risk ) : ?>
                <div class="rtbcb-risk-item">
                    <span class="rtbcb-risk-icon">⚠️</span>
                    <span class="rtbcb-risk-text"><?php echo esc_html( $risk ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $risk_mitigation['mitigation_strategies'] ) ) : ?>
        <div class="rtbcb-mitigation-strategies">
            <h3><?php echo esc_html__( 'Mitigation Strategies', 'rtbcb' ); ?></h3>
            <div class="rtbcb-strategies-grid">
                <?php foreach ( $risk_mitigation['mitigation_strategies'] as $risk_key => $strategy ) : ?>
                <div class="rtbcb-strategy-card">
                    <div class="rtbcb-strategy-title"><?php echo esc_html( ucwords( str_replace( '_', ' ', $risk_key ) ) ); ?></div>
                    <div class="rtbcb-strategy-content"><?php echo esc_html( $strategy ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Next Steps Section -->
    <?php if ( ! empty( $next_steps ) ) : ?>
    <div class="rtbcb-section rtbcb-next-steps">
        <div class="rtbcb-section-header">
            <h2><span class="rtbcb-section-icon">🚀</span><?php echo esc_html__( 'Recommended Next Steps', 'rtbcb' ); ?></h2>
        </div>

        <div class="rtbcb-steps-timeline">
            <?php foreach ( $next_steps as $index => $step ) : ?>
            <div class="rtbcb-step-item">
                <div class="rtbcb-step-number"><?php echo esc_html( $index + 1 ); ?></div>
                <div class="rtbcb-step-content">
                    <div class="rtbcb-step-text"><?php echo esc_html( $step ); ?></div>
                </div>
                <?php if ( $index < count( $next_steps ) - 1 ) : ?>
                <div class="rtbcb-step-connector"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Footer -->
    <div class="rtbcb-report-footer">
        <div class="rtbcb-footer-content">
            <div class="rtbcb-disclaimer">
                <p><strong><?php echo esc_html__( 'Disclaimer:', 'rtbcb' ); ?></strong> <?php echo esc_html__( 'This analysis is based on the information provided and industry benchmarks. Actual results may vary depending on implementation approach, vendor selection, and organizational factors.', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-report-meta-footer">
                <span><?php echo esc_html__( 'Generated by Real Treasury Business Case Builder', 'rtbcb' ); ?></span>
                <span><?php printf( esc_html__( 'Confidence Level: %s%%', 'rtbcb' ), esc_html( $confidence_level ) ); ?></span>
                <span><?php printf( esc_html__( 'Analysis Date: %s', 'rtbcb' ), esc_html( $analysis_date ) ); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced Report Styling */
.rtbcb-comprehensive-report {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #2c3e50;
}

.rtbcb-report-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
}

.rtbcb-report-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    margin-bottom: 16px;
    font-size: 14px;
    font-weight: 600;
}

.rtbcb-report-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 12px 0;
}

.rtbcb-report-meta {
    display: flex;
    justify-content: center;
    gap: 20px;
    font-size: 14px;
    opacity: 0.9;
}

.rtbcb-section {
    margin-bottom: 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.rtbcb-section-header {
    background: #f8f9ff;
    padding: 20px 30px;
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rtbcb-section-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rtbcb-business-case-strength {
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.rtbcb-business-case-strength.strong {
    background: #10b981;
    color: white;
}

.rtbcb-executive-content {
    padding: 30px;
}

.rtbcb-strategic-positioning {
    margin-bottom: 30px;
}

.rtbcb-value-drivers-grid {
    display: grid;
    gap: 16px;
    margin-top: 16px;
}

.rtbcb-value-driver {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: #f8f9ff;
    border-radius: 8px;
    border-left: 4px solid #7216f4;
}

.rtbcb-driver-number {
    background: #7216f4;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.rtbcb-executive-recommendation {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 8px;
    margin-top: 24px;
}

.rtbcb-assessment-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
    padding: 30px;
    align-items: start;
}

.rtbcb-assessment-card {
    background: #f8f9ff;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.rtbcb-assessment-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #4b5563;
    margin-bottom: 8px;
}

.rtbcb-assessment-value {
    font-size: 18px;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 20px;
}

.rtbcb-assessment-value.excellent { background: #10b981; color: white; }
.rtbcb-assessment-value.good { background: #22c55e; color: white; }
.rtbcb-assessment-value.fair { background: #f59e0b; color: white; }
.rtbcb-assessment-value.poor { background: #ef4444; color: white; }

.rtbcb-inefficiencies-list {
    padding: 30px;
}

.rtbcb-inefficiency-item {
    margin-bottom: 20px;
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.rtbcb-inefficiency-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.rtbcb-inefficiency-process {
    font-weight: 600;
    color: #1f2937;
}

.rtbcb-inefficiency-impact {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.rtbcb-inefficiency-impact.high { background: #fef2f2; color: #dc2626; }
.rtbcb-inefficiency-impact.medium { background: #fffbeb; color: #d97706; }
.rtbcb-inefficiency-impact.low { background: #f0fdf4; color: #16a34a; }

.rtbcb-opportunities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 30px;
}

.rtbcb-opportunity-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.rtbcb-opportunity-area {
    font-weight: 600;
    margin-bottom: 12px;
    color: #1f2937;
}

.rtbcb-savings-number {
    font-size: 24px;
    font-weight: 700;
    color: #10b981;
    display: block;
}

.rtbcb-savings-label {
    font-size: 12px;
    color: #4b5563;
}

.rtbcb-insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 30px;
}

.rtbcb-insight-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.rtbcb-insight-card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: #1f2937;
}

.rtbcb-solution-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 30px;
    margin: 20px 30px;
}

.rtbcb-solution-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.rtbcb-solution-category {
    font-size: 24px;
    font-weight: 700;
}

.rtbcb-solution-badge {
    background: rgba(255,255,255,0.2);
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.rtbcb-features-list {
    margin-top: 16px;
    list-style: none;
    padding: 0;
}

.rtbcb-features-list li {
    padding: 8px 0;
    padding-left: 20px;
    position: relative;
}

.rtbcb-features-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
}

.rtbcb-implementation-phases {
    display: flex;
    gap: 20px;
    padding: 30px;
}

.rtbcb-phase-card {
    flex: 1;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    gap: 16px;
}

.rtbcb-phase-number {
    background: #7216f4;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.rtbcb-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.rtbcb-metric-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f8f9ff;
    border-radius: 6px;
}

.rtbcb-steps-timeline {
    padding: 30px;
}

.rtbcb-step-item {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    position: relative;
}

.rtbcb-step-number {
    background: #7216f4;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.rtbcb-step-connector {
    position: absolute;
    left: 20px;
    top: 40px;
    width: 2px;
    height: 20px;
    background: #e2e8f0;
}

.rtbcb-report-footer {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 12px;
    margin-top: 40px;
}

.rtbcb-disclaimer {
    font-size: 14px;
    color: #4b5563;
    margin-bottom: 20px;
}

.rtbcb-report-meta-footer {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #9ca3af;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .rtbcb-assessment-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-implementation-phases {
        flex-direction: column;
    }
    
    .rtbcb-report-meta {
        flex-direction: column;
        gap: 8px;
    }
    
    .rtbcb-report-meta-footer {
        flex-direction: column;
        gap: 8px;
    }
}
</style>
