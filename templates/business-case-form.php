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

<!-- Trigger Button -->
<div class="rtbcb-trigger-container">
    <button type="button" class="rtbcb-trigger-btn" onclick="openBusinessCaseModal()">
        <span class="rtbcb-trigger-icon">📊</span>
        <span class="rtbcb-trigger-text"><?php esc_html_e( 'Build Your Business Case', 'rtbcb' ); ?></span>
        <span class="rtbcb-trigger-subtitle"><?php esc_html_e( 'Generate ROI analysis in minutes', 'rtbcb' ); ?></span>
    </button>
</div>

<!-- Modal Overlay -->
<div class="rtbcb-modal-overlay" id="rtbcbModalOverlay">
    <div class="rtbcb-modal-container">
        <!-- Modal Header -->
        <div class="rtbcb-modal-header">
            <button type="button" class="rtbcb-modal-close" onclick="closeBusinessCaseModal()">&times;</button>
            <h2 class="rtbcb-modal-title"><?php echo esc_html( $title ); ?></h2>
            <p class="rtbcb-modal-subtitle"><?php echo esc_html( $subtitle ); ?></p>
        </div>

        <!-- Modal Body -->
        <div class="rtbcb-modal-body">
            <div class="rtbcb-form-container">
                <form id="rtbcbForm" class="rtbcb-form rtbcb-wizard" method="post" novalidate>
                    <?php wp_nonce_field( 'rtbcb_generate', 'rtbcb_nonce' ); ?>

                    <!-- Progress Indicator -->
                    <div class="rtbcb-wizard-progress">
                        <div class="rtbcb-progress-steps">
                            <div class="rtbcb-progress-step active" data-step="1">
                                <div class="rtbcb-progress-number">1</div>
                                <div class="rtbcb-progress-label"><?php esc_html_e( 'Company', 'rtbcb' ); ?></div>
                            </div>
                            <div class="rtbcb-progress-step" data-step="2">
                                <div class="rtbcb-progress-number">2</div>
                                <div class="rtbcb-progress-label"><?php esc_html_e( 'Operations', 'rtbcb' ); ?></div>
                            </div>
                            <div class="rtbcb-progress-step" data-step="3">
                                <div class="rtbcb-progress-number">3</div>
                                <div class="rtbcb-progress-label"><?php esc_html_e( 'Challenges', 'rtbcb' ); ?></div>
                            </div>
                            <div class="rtbcb-progress-step" data-step="4">
                                <div class="rtbcb-progress-number">4</div>
                                <div class="rtbcb-progress-label"><?php esc_html_e( 'Contact', 'rtbcb' ); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Steps Container -->
                    <div class="rtbcb-wizard-steps">
            <!-- Step 1: Company Profile -->
            <div class="rtbcb-wizard-step active" data-step="1">
                <div class="rtbcb-step-header">
                    <h3><?php esc_html_e( 'Tell us about your company', 'rtbcb' ); ?></h3>
                    <p><?php esc_html_e( 'This helps us provide relevant recommendations for your organization.', 'rtbcb' ); ?></p>
                </div>
                
                <div class="rtbcb-step-content">
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
            <div class="rtbcb-wizard-step" data-step="2">
                <div class="rtbcb-step-header">
                    <h3><?php esc_html_e( 'Your current treasury operations', 'rtbcb' ); ?></h3>
                    <p><?php esc_html_e( 'Help us understand your current workload and banking relationships.', 'rtbcb' ); ?></p>
                </div>
                
                <div class="rtbcb-step-content">
                    <div class="rtbcb-field rtbcb-field-required">
                        <label for="hours_reconciliation">
                            <?php esc_html_e( 'Weekly Hours: Bank Reconciliation', 'rtbcb' ); ?>
                            <span class="rtbcb-required">*</span>
                        </label>
                        <input type="number" name="hours_reconciliation" id="hours_reconciliation" 
                               min="0" max="168" step="0.5" placeholder="0" required />
                        <div class="rtbcb-field-help">
                            <?php esc_html_e( 'Total weekly hours spent on bank reconciliation tasks', 'rtbcb' ); ?>
                        </div>
                    </div>

                    <div class="rtbcb-field rtbcb-field-required">
                        <label for="hours_cash_positioning">
                            <?php esc_html_e( 'Weekly Hours: Cash Positioning', 'rtbcb' ); ?>
                            <span class="rtbcb-required">*</span>
                        </label>
                        <input type="number" name="hours_cash_positioning" id="hours_cash_positioning" 
                               min="0" max="168" step="0.5" placeholder="0" required />
                        <div class="rtbcb-field-help">
                            <?php esc_html_e( 'Time spent on cash visibility, forecasting, and positioning', 'rtbcb' ); ?>
                        </div>
                    </div>

                    <div class="rtbcb-field rtbcb-field-required">
                        <label for="num_banks">
                            <?php esc_html_e( 'Number of Banking Relationships', 'rtbcb' ); ?>
                            <span class="rtbcb-required">*</span>
                        </label>
                        <input type="number" name="num_banks" id="num_banks" 
                               min="1" max="50" placeholder="0" required />
                        <div class="rtbcb-field-help">
                            <?php esc_html_e( 'Total number of banks where your company maintains accounts', 'rtbcb' ); ?>
                        </div>
                    </div>

                    <div class="rtbcb-field rtbcb-field-required">
                        <label for="ftes">
                            <?php esc_html_e( 'Treasury Team Size (FTEs)', 'rtbcb' ); ?>
                            <span class="rtbcb-required">*</span>
                        </label>
                        <input type="number" name="ftes" id="ftes" 
                               min="0.5" max="100" step="0.5" placeholder="0" required />
                        <div class="rtbcb-field-help">
                            <?php esc_html_e( 'Full-time equivalent employees dedicated to treasury functions', 'rtbcb' ); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Treasury Challenges -->
            <div class="rtbcb-wizard-step" data-step="3">
                <div class="rtbcb-step-header">
                    <h3><?php esc_html_e( 'What are your biggest challenges?', 'rtbcb' ); ?></h3>
                    <p><?php esc_html_e( 'Select the pain points that best describe your current treasury challenges.', 'rtbcb' ); ?></p>
                </div>
                
                <div class="rtbcb-step-content">
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
            </div>

            <!-- Step 4: Contact Information -->
            <div class="rtbcb-wizard-step" data-step="4">
                <div class="rtbcb-step-header">
                    <h3><?php esc_html_e( 'Get your business case', 'rtbcb' ); ?></h3>
                    <p><?php esc_html_e( 'Enter your email to receive your personalized ROI analysis and recommendations.', 'rtbcb' ); ?></p>
                </div>
                
                <div class="rtbcb-step-content">
                    <div class="rtbcb-field rtbcb-field-required">
                        <label for="email">
                            <?php esc_html_e( 'Business Email Address', 'rtbcb' ); ?>
                            <span class="rtbcb-required">*</span>
                        </label>
                        <input type="email" name="email" id="email" 
                               placeholder="yourname@company.com" required />
                        <div class="rtbcb-field-help">
                            <?php esc_html_e( 'We\'ll send your business case report to this email address', 'rtbcb' ); ?>
                        </div>
                    </div>

                    <div class="rtbcb-field">
                        <div class="rtbcb-consent-wrapper">
                            <label class="rtbcb-consent-label">
                                <input type="checkbox" name="consent" required />
                                <span class="rtbcb-consent-text">
                                    <?php 
                                    printf(
                                        wp_kses(
                                            __( 'I agree to receive my business case report and occasional treasury insights. You can unsubscribe at any time. View our <a href="%s" target="_blank">privacy policy</a>.', 'rtbcb' ),
                                            [ 'a' => [ 'href' => [], 'target' => [] ] ]
                                        ),
                                        '#'
                                    );
                                    ?>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- What You'll Receive Preview -->
                    <div class="rtbcb-results-preview">
                        <h4><?php esc_html_e( 'What You\'ll Receive:', 'rtbcb' ); ?></h4>
                        <ul class="rtbcb-preview-list">
                            <li>📊 <?php esc_html_e( 'Detailed ROI projections (conservative, base case, optimistic)', 'rtbcb' ); ?></li>
                            <li>🎯 <?php esc_html_e( 'Personalized solution category recommendation', 'rtbcb' ); ?></li>
                            <li>📄 <?php esc_html_e( 'Professional PDF report ready for stakeholders', 'rtbcb' ); ?></li>
                            <li>🗺️ <?php esc_html_e( 'Implementation roadmap and next steps', 'rtbcb' ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Controls -->
        <div class="rtbcb-wizard-navigation">
            <button type="button" class="rtbcb-nav-btn rtbcb-nav-prev" style="display: none;">
                <span class="rtbcb-nav-icon">←</span>
                <?php esc_html_e( 'Previous', 'rtbcb' ); ?>
            </button>

            <div class="rtbcb-nav-spacer"></div>

            <button type="button" class="rtbcb-nav-btn rtbcb-nav-next">
                <?php esc_html_e( 'Next', 'rtbcb' ); ?>
                <span class="rtbcb-nav-icon">→</span>
            </button>

            <button type="submit" class="rtbcb-nav-btn rtbcb-nav-submit" style="display: none;">
                <span class="rtbcb-nav-icon">🚀</span>
                <?php esc_html_e( 'Generate Business Case', 'rtbcb' ); ?>
            </button>
        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Results Section (separate from modal) -->
<div id="rtbcbResults" class="rtbcb-results" style="display: none;">
    <!-- Results will be populated by JavaScript -->
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
/* Wizard Base Styles */
.rtbcb-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    box-sizing: border-box;
}

.rtbcb-wizard {
    display: flex;
    flex-direction: column;
    height: auto;
    min-height: 70vh;
}

/* Progress Indicator */
.rtbcb-wizard-progress {
    margin-bottom: 40px;
}

.rtbcb-progress-steps {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin-bottom: 20px;
}

.rtbcb-progress-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--border-light);
    z-index: 1;
}

.rtbcb-progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    background: white;
    padding: 0 10px;
}

.rtbcb-progress-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--border-light);
    color: var(--gray-text);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.rtbcb-progress-step.active .rtbcb-progress-number,
.rtbcb-progress-step.completed .rtbcb-progress-number {
    background: var(--primary-purple);
    color: white;
}

.rtbcb-progress-step.completed .rtbcb-progress-number::after {
    content: '✓';
    font-size: 16px;
}

.rtbcb-progress-label {
    font-size: 12px;
    color: var(--gray-text);
    font-weight: 500;
    text-align: center;
}

.rtbcb-progress-step.active .rtbcb-progress-label {
    color: var(--primary-purple);
    font-weight: 600;
}

/* Steps Container */
.rtbcb-wizard-steps {
    flex: 1;
    position: relative;
    overflow: hidden;
    min-height: 400px;
}

.rtbcb-wizard-step {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    opacity: 0;
    transform: translateX(100px);
    transition: all 0.4s ease;
    pointer-events: none;
    padding: 20px 0;
}

.rtbcb-wizard-step.active {
    opacity: 1;
    transform: translateX(0);
    pointer-events: all;
}

.rtbcb-wizard-step.prev {
    transform: translateX(-100px);
}

.rtbcb-step-header {
    text-align: center;
    margin-bottom: 30px;
}

.rtbcb-step-header h3 {
    margin: 0 0 10px 0;
    color: var(--dark-text);
    font-size: 24px;
    font-weight: 600;
}

.rtbcb-step-header p {
    margin: 0;
    color: var(--gray-text);
    font-size: 16px;
    line-height: 1.5;
}

.rtbcb-step-content {
    max-width: 500px;
    margin: 0 auto;
}

/* Form Fields */
.rtbcb-field {
    margin-bottom: 24px;
}

.rtbcb-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark-text);
    font-size: 15px;
}

.rtbcb-field input,
.rtbcb-field select {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--border-light);
    border-radius: 8px;
    font-size: 16px;
    background: white;
    color: var(--dark-text);
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.rtbcb-field input:focus,
.rtbcb-field select:focus {
    border-color: var(--primary-purple);
    outline: none;
    box-shadow: 0 0 0 3px rgba(114,22,244,0.1);
}

.rtbcb-field-help {
    font-size: 13px;
    color: var(--gray-text);
    margin-top: 6px;
    line-height: 1.4;
}

.rtbcb-required {
    color: var(--error-red);
    font-weight: 600;
}

/* Pain Points Grid (Step 3) */
.rtbcb-pain-points-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
    font-size: 28px;
    margin-bottom: 10px;
    display: block;
}

.rtbcb-pain-point-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--dark-text);
    margin-bottom: 6px;
}

.rtbcb-pain-point-description {
    font-size: 12px;
    color: var(--gray-text);
    line-height: 1.4;
}

/* Consent (Step 4) */
.rtbcb-consent-wrapper {
    background: #f9fafb;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
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

/* Results Preview (Step 4) */
.rtbcb-results-preview {
    background: #f8f9ff;
    border: 1px solid rgba(114,22,244,0.2);
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.rtbcb-results-preview h4 {
    margin: 0 0 12px 0;
    color: var(--primary-purple);
    font-size: 16px;
}

.rtbcb-preview-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.rtbcb-preview-list li {
    padding: 6px 0;
    font-size: 14px;
    color: var(--dark-text);
    line-height: 1.4;
}

/* Navigation */
.rtbcb-wizard-navigation {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid var(--border-light);
}

.rtbcb-nav-spacer {
    flex: 1;
}

.rtbcb-nav-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--primary-purple);
    color: white;
}

.rtbcb-nav-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(114,22,244,0.3);
}

.rtbcb-nav-btn:disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.rtbcb-nav-prev {
    background: white;
    color: var(--primary-purple);
    border: 2px solid var(--primary-purple);
}

.rtbcb-nav-prev:hover {
    background: var(--primary-purple);
    color: white;
}

.rtbcb-nav-submit {
    background: linear-gradient(135deg, var(--secondary-purple), var(--primary-purple));
    box-shadow: 0 4px 16px rgba(114,22,244,0.3);
}

.rtbcb-nav-icon {
    font-size: 16px;
}

/* Validation */
.rtbcb-field-error {
    color: var(--error-red);
    font-size: 12px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.rtbcb-field-invalid {
    border-color: var(--error-red) !important;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.1) !important;
}

.rtbcb-validation-message {
    background: #fef2f2;
    color: var(--error-red);
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 13px;
    margin-top: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .rtbcb-container {
        padding: 15px;
        min-height: 100vh;
    }
    
    .rtbcb-progress-steps {
        margin-bottom: 30px;
    }
    
    .rtbcb-progress-label {
        font-size: 11px;
    }
    
    .rtbcb-step-header h3 {
        font-size: 20px;
    }
    
    .rtbcb-step-header p {
        font-size: 14px;
    }
    
    .rtbcb-pain-points-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-wizard-navigation {
        margin-top: 30px;
    }
    
    .rtbcb-nav-btn {
        padding: 10px 20px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .rtbcb-progress-steps::before {
        display: none;
    }
    
    .rtbcb-progress-step {
        padding: 0 5px;
    }
    
    .rtbcb-progress-number {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .rtbcb-step-content {
        padding: 0 10px;
    }
    
    .rtbcb-pain-point-label {
        padding: 15px;
    }
}

/* Loading States */
.rtbcb-nav-btn.loading {
    position: relative;
    pointer-events: none;
}

.rtbcb-nav-btn.loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid rgba(255,255,255,0.6);
    border-top-color: white;
    border-radius: 50%;
    animation: rtbcb-spin 1s linear infinite;
}

@keyframes rtbcb-spin {
    to { transform: rotate(360deg); }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure global functions are available
    window.openBusinessCaseModal = function() {
        const overlay = document.getElementById('rtbcbModalOverlay');
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Initialize builder after modal is shown
            setTimeout(() => {
                if (!window.businessCaseBuilder || !window.businessCaseBuilder.isInitialized) {
                    window.businessCaseBuilder = new BusinessCaseBuilder();
                } else {
                    // Reinitialize if already exists
                    window.businessCaseBuilder.reinitialize();
                }
            }, 100);
        }
    };

    window.closeBusinessCaseModal = function() {
        const overlay = document.getElementById('rtbcbModalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close modal on overlay click
    const overlay = document.getElementById('rtbcbModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                window.closeBusinessCaseModal();
            }
        });
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closeBusinessCaseModal();
        }
    });
});
</script>
