<?php
/**
 * Enhanced template for the business case form.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

// Default values for template arguments
$style = $style ?? 'default';
$title = $title ?? __( 'Treasury Technology Business Case Builder', 'rtbcb' );
$subtitle = $subtitle ?? __( 'Generate a data-driven business case for your treasury technology investment.', 'rtbcb' );

// Get categories for display
$categories = RTBCB_Category_Recommender::get_all_categories();
?>

<div class="rtbcb-container rtbcb-style-<?php echo esc_attr( $style ); ?>" id="rtbcbContainer">
    <div class="rtbcb-header">
        <h2><?php echo esc_html( $title ); ?></h2>
        <p><?php echo esc_html( $subtitle ); ?></p>
        <div class="rtbcb-progress-indicator">
            <div class="rtbcb-progress-bar">
                <div class="rtbcb-progress-fill" style="width: 0%"></div>
            </div>
            <div class="rtbcb-progress-text">
                <?php esc_html_e( 'Complete the form to generate your business case', 'rtbcb' ); ?>
            </div>
        </div>
    </div>
    
    <form id="rtbcbForm" class="rtbcb-form" method="post" novalidate>
        <?php wp_nonce_field( 'rtbcb_generate', 'rtbcb_nonce' ); ?>
        
        <!-- Step 1: Company Profile -->
        <div class="rtbcb-section rtbcb-step" data-step="1">
            <div class="rtbcb-section-header">
                <div class="rtbcb-step-number">1</div>
                <div class="rtbcb-step-content">
                    <h3><?php esc_html_e( 'Company Profile', 'rtbcb' ); ?></h3>
                    <p class="rtbcb-step-description">
                        <?php esc_html_e( 'Tell us about your organization to provide relevant recommendations.', 'rtbcb' ); ?>
                    </p>
                </div>
            </div>

            <div class="rtbcb-form-grid">
                <div class="rtbcb-field rtbcb-field-required">
                    <label for="company_size">
                        <?php esc_html_e( 'Company Size (Annual Revenue)', 'rtbcb' ); ?>
                        <span class="rtbcb-required">*</span>
                    </label>
                    <select name="company_size" id="company_size" required>
                        <option value=""><?php esc_html_e( 'Select your company size...', 'rtbcb' ); ?></option>
                        <option value="<$50M"><?php esc_html_e( 'Small Business (<$50M)', 'rtbcb' ); ?></option>
                        <option value="$50M-$500M"><?php esc_html_e( 'Mid-Market ($50M-$500M)', 'rtbcb' ); ?></option>
                        <option value="$500M-$2B"><?php esc_html_e( 'Large Enterprise ($500M-$2B)', 'rtbcb' ); ?></option>
                        <option value=">$2B"><?php esc_html_e( 'Fortune 500 (>$2B)', 'rtbcb' ); ?></option>
                    </select>
                    <div class="rtbcb-field-help">
                        <?php esc_html_e( 'Annual revenue helps us recommend the right category of treasury solution.', 'rtbcb' ); ?>
                    </div>
                </div>

                <div class="rtbcb-field">
                    <label for="industry"><?php esc_html_e( 'Industry', 'rtbcb' ); ?></label>
                    <select name="industry" id="industry">
                        <option value=""><?php esc_html_e( 'Select your industry...', 'rtbcb' ); ?></option>
                        <option value="manufacturing"><?php esc_html_e( 'Manufacturing', 'rtbcb' ); ?></option>
                        <option value="retail"><?php esc_html_e( 'Retail & E-commerce', 'rtbcb' ); ?></option>
                        <option value="healthcare"><?php esc_html_e( 'Healthcare', 'rtbcb' ); ?></option>
                        <option value="technology"><?php esc_html_e( 'Technology', 'rtbcb' ); ?></option>
                        <option value="financial_services"><?php esc_html_e( 'Financial Services', 'rtbcb' ); ?></option>
                        <option value="energy"><?php esc_html_e( 'Energy & Utilities', 'rtbcb' ); ?></option>
                        <option value="real_estate"><?php esc_html_e( 'Real Estate', 'rtbcb' ); ?></option>
                        <option value="professional_services"><?php esc_html_e( 'Professional Services', 'rtbcb' ); ?></option>
                        <option value="transportation"><?php esc_html_e( 'Transportation & Logistics', 'rtbcb' ); ?></option>
                        <option value="education"><?php esc_html_e( 'Education', 'rtbcb' ); ?></option>
                        <option value="government"><?php esc_html_e( 'Government', 'rtbcb' ); ?></option>
                        <option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Treasury Operations -->
        <div class="rtbcb-section rtbcb-step" data-step="2">
            <div class="rtbcb-section-header">
                <div class="rtbcb-step-number">2</div>
                <div class="rtbcb-step-content">
                    <h3><?php esc_html_e( 'Current Treasury Operations', 'rtbcb' ); ?></h3>
                    <p class="rtbcb-step-description">
                        <?php esc_html_e( 'Help us understand your current treasury workload and processes.', 'rtbcb' ); ?>
                    </p>
                </div>
            </div>

            <div class="rtbcb-form-grid">
                <div class="rtbcb-field rtbcb-field-required">
                    <label for="hours_reconciliation">
                        <?php esc_html_e( 'Weekly Hours: Bank Reconciliation', 'rtbcb' ); ?>
                        <span class="rtbcb-required">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="hours_reconciliation" 
                        id="hours_reconciliation" 
                        min="0" 
                        max="168" 
                        step="0.5"
                        placeholder="0"
                        required 
                    />
                    <div class="rtbcb-field-help">
                        <?php esc_html_e( 'Total weekly hours spent on bank reconciliation tasks', 'rtbcb' ); ?>
                    </div>
                </div>

                <div class="rtbcb-field rtbcb-field-required">
                    <label for="hours_cash_positioning">
                        <?php esc_html_e( 'Weekly Hours: Cash Positioning', 'rtbcb' ); ?>
                        <span class="rtbcb-required">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="hours_cash_positioning" 
                        id="hours_cash_positioning" 
                        min="0" 
                        max="168" 
                        step="0.5"
                        placeholder="0"
                        required 
                    />
                    <div class="rtbcb-field-help">
                        <?php esc_html_e( 'Time spent on cash visibility, forecasting, and positioning', 'rtbcb' ); ?>
                    </div>
                </div>

                <div class="rtbcb-field rtbcb-field-required">
                    <label for="num_banks">
                        <?php esc_html_e( 'Number of Banking Relationships', 'rtbcb' ); ?>
                        <span class="rtbcb-required">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="num_banks" 
                        id="num_banks" 
                        min="1" 
                        max="50" 
                        placeholder="0"
                        required 
                    />
                    <div class="rtbcb-field-help">
                        <?php esc_html_e( 'Total number of banks where your company maintains accounts', 'rtbcb' ); ?>
                    </div>
                </div>

                <div class="rtbcb-field rtbcb-field-required">
                    <label for="ftes">
                        <?php esc_html_e( 'Treasury Team Size (FTEs)', 'rtbcb' ); ?>
                        <span class="rtbcb-required">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="ftes" 
                        id="ftes" 
                        min="0.5" 
                        max="100" 
                        step="0.5" 
                        placeholder="0"
                        required 
                    />
                    <div class="rtbcb-field-help">
                        <?php esc_html_e( 'Full-time equivalent employees dedicated to treasury functions', 'rtbcb' ); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Pain Points & Challenges -->
        <div class="rtbcb-section rtbcb-step" data-step="3">
            <div class="rtbcb-section-header">
                <div class="rtbcb-step-number">3</div>
                <div class="rtbcb-step-content">
                    <h3><?php esc_html_e( 'Treasury Challenges', 'rtbcb' ); ?></h3>
                    <p class="rtbcb-step-description">
                        <?php esc_html_e( 'Select the pain points that best describe your current treasury challenges.', 'rtbcb' ); ?>
                    </p>
                </div>
            </div>

            <div class="rtbcb-pain-points-grid">
                <div class="rtbcb-pain-point-card">
                    <label class="rtbcb-pain-point-label">
                        <input type="checkbox" name="pain_points[]" value="manual_processes" />
                        <div class="rtbcb-pain-point-content">
                            <div class="rtbcb-pain-point-icon">⚙️</div>
                            <div class="rtbcb-pain-point-title"><?php esc_html_e( 'Manual Processes', 'rtbcb' ); ?></div>
                            <div class="rtbcb-pain-point-description">
                                <?php esc_html_e( 'Time-consuming manual data entry and reconciliation', 'rtbcb' ); ?>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="rtbcb-pain-point-card">
                    <label class="rtbcb-pain-point-label">
                        <input type="checkbox" name="pain_points[]" value="poor_visibility" />
                        <div class="rtbcb-pain-point-content">
                            <div class="rtbcb-pain-point-icon">👁️</div>
                            <div class="rtbcb-pain-point-title"><?php esc_html_e( 'Poor Cash Visibility', 'rtbcb' ); ?></div>
                            <div class="rtbcb-pain-point-description">
                                <?php esc_html_e( 'Lack of real-time visibility into cash positions', 'rtbcb' ); ?>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="rtbcb-pain-point-card">
                    <label class="rtbcb-pain-point-label">
                        <input type="checkbox" name="pain_points[]" value="forecast_accuracy" />
                        <div class="rtbcb-pain-point-content">
                            <div class="rtbcb-pain-point-icon">📊</div>
                            <div class="rtbcb-pain-point-title"><?php esc_html_e( 'Forecast Accuracy', 'rtbcb' ); ?></div>
                            <div class="rtbcb-pain-point-description">
                                <?php esc_html_e( 'Inaccurate cash forecasting and planning', 'rtbcb' ); ?>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="rtbcb-pain-point-card">
                    <label class="rtbcb-pain-point-label">
                        <input type="checkbox" name="pain_points[]" value="compliance_risk" />
                        <div class="rtbcb-pain-point-content">
                            <div class="rtbcb-pain-point-icon">🛡️</div>
                            <div class="rtbcb-pain-point-title"><?php esc_html_e( 'Compliance & Risk', 'rtbcb' ); ?></div>
                            <div class="rtbcb-pain-point-description">
                                <?php esc_html_e( 'Regulatory compliance and risk management concerns', 'rtbcb' ); ?>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="rtbcb-pain-point-card">
                    <label class="rtbcb-pain-point-label">
                        <input type="checkbox" name="pain_points[]" value="bank_fees" />
                        <div class="rtbcb-pain-point-content">
                            <div class="rtbcb-pain-point-icon">💰</div>
                            <div class="rtbcb-pain-point-title"><?php esc_html_e( 'High Bank Fees', 'rtbcb' ); ?></div>
                            <div class="rtbcb-pain-point-description">
                                <?php esc_html_e( 'Excessive banking fees and suboptimal cash positioning', 'rtbcb' ); ?>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="rtbcb-pain-point-card">
                    <label class="rtbcb-pain-point-label">
                        <input type="checkbox" name="pain_points[]" value="integration_issues" />
                        <div class="rtbcb-pain-point-content">
                            <div class="rtbcb-pain-point-icon">🔗</div>
                            <div class="rtbcb-pain-point-title"><?php esc_html_e( 'System Integration', 'rtbcb' ); ?></div>
                            <div class="rtbcb-pain-point-description">
                                <?php esc_html_e( 'Disconnected systems and data silos', 'rtbcb' ); ?>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="rtbcb-pain-points-validation">
                <div class="rtbcb-validation-message" style="display: none;">
                    <?php esc_html_e( 'Please select at least one challenge that applies to your organization.', 'rtbcb' ); ?>
                </div>
            </div>
        </div>
        
        <!-- Step 4: Contact Information -->
        <div class="rtbcb-section rtbcb-step" data-step="4">
            <div class="rtbcb-section-header">
                <div class="rtbcb-step-number">4</div>
                <div class="rtbcb-step-content">
                    <h3><?php esc_html_e( 'Get Your Results', 'rtbcb' ); ?></h3>
                    <p class="rtbcb-step-description">
                        <?php esc_html_e( 'Enter your email to receive your personalized business case report.', 'rtbcb' ); ?>
                    </p>
                </div>
            </div>

            <div class="rtbcb-form-grid">
                <div class="rtbcb-field rtbcb-field-required rtbcb-field-full">
                    <label for="email">
                        <?php esc_html_e( 'Business Email Address', 'rtbcb' ); ?>
                        <span class="rtbcb-required">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        placeholder="yourname@company.com"
                        required 
                    />
                    <div class="rtbcb-field-help">
                        <?php esc_html_e( 'We\'ll send your business case report to this email address', 'rtbcb' ); ?>
                    </div>
                </div>

                <div class="rtbcb-field rtbcb-field-full">
                    <div class="rtbcb-consent-wrapper">
                        <label class="rtbcb-consent-label">
                            <input type="checkbox" name="consent" required />
                            <span class="rtbcb-consent-text">
                                <?php 
                                printf(
                                    wp_kses(
                                        __( 'I agree to receive my business case report and occasional treasury insights from Real Treasury. You can unsubscribe at any time. View our <a href="%s" target="_blank">privacy policy</a>.', 'rtbcb' ),
                                        [ 'a' => [ 'href' => [], 'target' => [] ] ]
                                    ),
                                    '#' // Replace with actual privacy policy URL
                                );
                                ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- What to Expect -->
        <div class="rtbcb-expectation-section">
            <h4><?php esc_html_e( 'What You\'ll Receive', 'rtbcb' ); ?></h4>
            <div class="rtbcb-expectations-grid">
                <div class="rtbcb-expectation-item">
                    <div class="rtbcb-expectation-icon">📊</div>
                    <div class="rtbcb-expectation-content">
                        <div class="rtbcb-expectation-title"><?php esc_html_e( 'ROI Analysis', 'rtbcb' ); ?></div>
                        <div class="rtbcb-expectation-description">
                            <?php esc_html_e( 'Conservative, base case, and optimistic ROI projections', 'rtbcb' ); ?>
                        </div>
                    </div>
                </div>

                <div class="rtbcb-expectation-item">
                    <div class="rtbcb-expectation-icon">🎯</div>
                    <div class="rtbcb-expectation-content">
                        <div class="rtbcb-expectation-title"><?php esc_html_e( 'Solution Recommendation', 'rtbcb' ); ?></div>
                        <div class="rtbcb-expectation-description">
                            <?php esc_html_e( 'Personalized category recommendation based on your profile', 'rtbcb' ); ?>
                        </div>
                    </div>
                </div>

                <div class="rtbcb-expectation-item">
                    <div class="rtbcb-expectation-icon">📄</div>
                    <div class="rtbcb-expectation-content">
                        <div class="rtbcb-expectation-title"><?php esc_html_e( 'Professional Report', 'rtbcb' ); ?></div>
                        <div class="rtbcb-expectation-description">
                            <?php esc_html_e( 'Comprehensive PDF report ready for stakeholder presentation', 'rtbcb' ); ?>
                        </div>
                    </div>
                </div>

                <div class="rtbcb-expectation-item">
                    <div class="rtbcb-expectation-icon">🗺️</div>
                    <div class="rtbcb-expectation-content">
                        <div class="rtbcb-expectation-title"><?php esc_html_e( 'Next Steps Guide', 'rtbcb' ); ?></div>
                        <div class="rtbcb-expectation-description">
                            <?php esc_html_e( 'Clear action plan for your treasury technology journey', 'rtbcb' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Submit Section -->
        <div class="rtbcb-actions">
            <button type="submit" class="rtbcb-submit-btn" disabled>
                <span class="rtbcb-submit-text"><?php esc_html_e( 'Generate My Business Case', 'rtbcb' ); ?></span>
                <span class="rtbcb-submit-icon">🚀</span>
            </button>
            <div class="rtbcb-submit-help">
                <?php esc_html_e( 'Complete all required fields to generate your business case', 'rtbcb' ); ?>
            </div>
        </div>
    </form>
    
    <!-- Results Section -->
    <div id="rtbcbResults" class="rtbcb-results" style="display: none;">
        <!-- Results will be populated by JavaScript -->
    </div>

    <!-- Powered By -->
    <div class="rtbcb-powered-by">
        <p>
            <?php 
            printf(
                wp_kses(
                    __( 'Powered by <strong>Real Treasury</strong> - Empowering treasury teams with data-driven insights', 'rtbcb' ),
                    [ 'strong' => [] ]
                )
            );
            ?>
        </p>
    </div>
</div>

<!-- Category Information Modal (Hidden by default) -->
<div id="rtbcb-category-modal" class="rtbcb-modal" style="display: none;">
    <div class="rtbcb-modal-content">
        <div class="rtbcb-modal-header">
            <h3><?php esc_html_e( 'Treasury Solution Categories', 'rtbcb' ); ?></h3>
            <button type="button" class="rtbcb-modal-close">&times;</button>
        </div>
        <div class="rtbcb-modal-body">
            <?php foreach ( $categories as $key => $category ) : ?>
                <div class="rtbcb-category-info">
                    <h4><?php echo esc_html( $category['name'] ); ?></h4>
                    <p><?php echo esc_html( $category['description'] ); ?></p>
                    <div class="rtbcb-category-features">
                        <strong><?php esc_html_e( 'Key Features:', 'rtbcb' ); ?></strong>
                        <ul>
                            <?php foreach ( array_slice( $category['features'], 0, 3 ) as $feature ) : ?>
                                <li><?php echo esc_html( $feature ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="rtbcb-category-ideal">
                        <strong><?php esc_html_e( 'Ideal for:', 'rtbcb' ); ?></strong>
                        <?php echo esc_html( $category['ideal_for'] ); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* Form-specific enhanced styles */
.rtbcb-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.rtbcb-field-full {
    grid-column: 1 / -1;
}

.rtbcb-step {
    opacity: 1;
    transition: all 0.3s ease;
}

.rtbcb-step.rtbcb-step-disabled {
    opacity: 0.6;
    pointer-events: none;
}

.rtbcb-section-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
}

.rtbcb-step-number {
    width: 40px;
    height: 40px;
    background: var(--primary-purple);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    flex-shrink: 0;
}

.rtbcb-step-content h3 {
    margin: 0 0 8px 0;
    color: var(--dark-text);
    font-size: 20px;
}

.rtbcb-step-description {
    margin: 0;
    color: var(--gray-text);
    font-size: 14px;
    line-height: 1.5;
}

.rtbcb-progress-indicator {
    margin-top: 20px;
}

.rtbcb-progress-bar {
    width: 100%;
    height: 4px;
    background: rgba(114,22,244,0.1);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 8px;
}

.rtbcb-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-purple), var(--secondary-purple));
    border-radius: 2px;
    transition: width 0.3s ease;
}

.rtbcb-progress-text {
    font-size: 12px;
    color: var(--gray-text);
    text-align: center;
}

.rtbcb-required {
    color: var(--error-red);
    font-weight: 600;
}

.rtbcb-field-help {
    font-size: 12px;
    color: var(--gray-text);
    margin-top: 4px;
    line-height: 1.4;
}

/* Pain Points Grid */
.rtbcb-pain-points-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.rtbcb-pain-point-card {
    background: white;
    border: 2px solid var(--border-light);
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.rtbcb-pain-point-card:hover {
    border-color: var(--light-purple);
    box-shadow: 0 4px 12px rgba(114,22,244,0.1);
}

.rtbcb-pain-point-card.rtbcb-selected {
    border-color: var(--primary-purple);
    background: linear-gradient(135deg, #f8f9ff, #ffffff);
}

.rtbcb-pain-point-label {
    display: block;
    padding: 20px;
    cursor: pointer;
    margin: 0;
}

.rtbcb-pain-point-label input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.rtbcb-pain-point-content {
    text-align: center;
}

.rtbcb-pain-point-icon {
    font-size: 32px;
    margin-bottom: 12px;
    display: block;
}

.rtbcb-pain-point-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--dark-text);
    margin-bottom: 8px;
}

.rtbcb-pain-point-description {
    font-size: 13px;
    color: var(--gray-text);
    line-height: 1.4;
}

/* Expectations */
.rtbcb-expectation-section {
    background: #f8f9ff;
    border: 1px solid rgba(114,22,244,0.1);
    border-radius: 12px;
    padding: 24px;
    margin: 24px 0;
}

.rtbcb-expectation-section h4 {
    margin: 0 0 20px 0;
    color: var(--dark-text);
    text-align: center;
    font-size: 18px;
}

.rtbcb-expectations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.rtbcb-expectation-item {
    text-align: center;
}

.rtbcb-expectation-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.rtbcb-expectation-title {
    font-weight: 600;
    color: var(--dark-text);
    margin-bottom: 4px;
    font-size: 14px;
}

.rtbcb-expectation-description {
    font-size: 12px;
    color: var(--gray-text);
    line-height: 1.4;
}

/* Consent */
.rtbcb-consent-wrapper {
    background: #f9fafb;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 16px;
}

.rtbcb-consent-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    cursor: pointer;
    margin: 0;
}

.rtbcb-consent-label input[type="checkbox"] {
    margin-top: 2px;
    width: auto;
    flex-shrink: 0;
}

.rtbcb-consent-text {
    font-size: 13px;
    color: var(--gray-text);
    line-height: 1.5;
}

/* Enhanced Submit Button */
.rtbcb-submit-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
}

.rtbcb-submit-btn:disabled {
    background: #d1d5db !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    transform: none !important;
    box-shadow: none !important;
}

.rtbcb-submit-help {
    text-align: center;
    font-size: 12px;
    color: var(--gray-text);
    margin-top: 8px;
}

.rtbcb-powered-by {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid var(--border-light);
    color: var(--gray-text);
    font-size: 12px;
}

/* Validation */
.rtbcb-pain-points-validation {
    margin-top: 16px;
}

.rtbcb-validation-message {
    background: #fef2f2;
    color: var(--error-red);
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 13px;
}

/* Modal */
.rtbcb-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.rtbcb-modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 600px;
    width: 100%;
    max-height: 80vh;
    overflow: hidden;
}

.rtbcb-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rtbcb-modal-header h3 {
    margin: 0;
    color: var(--dark-text);
}

.rtbcb-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--gray-text);
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.rtbcb-modal-close:hover {
    background: #f3f4f6;
    color: var(--dark-text);
}

.rtbcb-modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(80vh - 100px);
}

.rtbcb-category-info {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border-light);
}

.rtbcb-category-info:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.rtbcb-category-info h4 {
    margin: 0 0 8px 0;
    color: var(--primary-purple);
}

.rtbcb-category-features,
.rtbcb-category-ideal {
    margin-top: 12px;
    font-size: 14px;
}

.rtbcb-category-features ul {
    margin: 4px 0 0 16px;
    padding: 0;
}

.rtbcb-category-features li {
    margin-bottom: 4px;
}

@media (max-width: 768px) {
    .rtbcb-form-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-pain-points-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-expectations-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .rtbcb-section-header {
        flex-direction: column;
        text-align: center;
    }
    
    .rtbcb-step-number {
        align-self: center;
    }
}

@media (max-width: 480px) {
    .rtbcb-expectations-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-pain-point-card {
        min-height: auto;
    }
    
    .rtbcb-pain-point-label {
        padding: 16px;
    }
}
</style> 
