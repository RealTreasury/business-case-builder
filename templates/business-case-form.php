<?php
/**
 * Template for the business case form.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
?>
<div class="rtbcb-container" id="rtbcbContainer">
    <div class="rtbcb-header">
        <h2><?php esc_html_e( 'Treasury Technology Business Case Builder', 'rtbcb' ); ?></h2>
        <p><?php esc_html_e( 'Generate a data-driven business case for your treasury technology investment.', 'rtbcb' ); ?></p>
    </div>
    
    <form id="rtbcbForm" class="rtbcb-form" method="post">
        <?php wp_nonce_field( 'rtbcb_generate', 'rtbcb_nonce' ); ?>
        
        <div class="rtbcb-section">
            <h3><?php esc_html_e( 'Company Profile', 'rtbcb' ); ?></h3>
            <div class="rtbcb-field">
                <label for="company_size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label>
                <select name="company_size" id="company_size" required>
                    <option value=""><?php esc_html_e( 'Select size...', 'rtbcb' ); ?></option>
                    <option value="<?php echo esc_attr( '<$50M' ); ?>"><?php esc_html_e( 'Small (<$50M revenue)', 'rtbcb' ); ?></option>
                    <option value="<?php echo esc_attr( '$50M-$500M' ); ?>"><?php esc_html_e( 'Mid-market ($50M-$500M)', 'rtbcb' ); ?></option>
                    <option value="<?php echo esc_attr( '$500M-$2B' ); ?>"><?php esc_html_e( 'Large ($500M-$2B)', 'rtbcb' ); ?></option>
                    <option value="<?php echo esc_attr( '>$2B' ); ?>"><?php esc_html_e( 'Enterprise (>$2B)', 'rtbcb' ); ?></option>
                </select>
            </div>
            <div class="rtbcb-field">
                <label for="industry"><?php esc_html_e( 'Industry', 'rtbcb' ); ?></label>
                <select name="industry" id="industry">
                    <option value=""><?php esc_html_e( 'Select industry...', 'rtbcb' ); ?></option>
                    <option value="manufacturing"><?php esc_html_e( 'Manufacturing', 'rtbcb' ); ?></option>
                    <option value="retail"><?php esc_html_e( 'Retail', 'rtbcb' ); ?></option>
                    <option value="healthcare"><?php esc_html_e( 'Healthcare', 'rtbcb' ); ?></option>
                    <option value="technology"><?php esc_html_e( 'Technology', 'rtbcb' ); ?></option>
                    <option value="financial_services"><?php esc_html_e( 'Financial Services', 'rtbcb' ); ?></option>
                    <option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
                </select>
            </div>
        </div>
        
        <div class="rtbcb-section">
            <h3><?php esc_html_e( 'Current Treasury Operations', 'rtbcb' ); ?></h3>
            <div class="rtbcb-field">
                <label for="hours_reconciliation"><?php esc_html_e( 'Hours per week on bank reconciliation', 'rtbcb' ); ?></label>
                <input type="number" name="hours_reconciliation" id="hours_reconciliation" min="0" max="168" required>
            </div>
            <div class="rtbcb-field">
                <label for="hours_cash_positioning"><?php esc_html_e( 'Hours per week on cash positioning', 'rtbcb' ); ?></label>
                <input type="number" name="hours_cash_positioning" id="hours_cash_positioning" min="0" max="168" required>
            </div>
            <div class="rtbcb-field">
                <label for="num_banks"><?php esc_html_e( 'Number of banking relationships', 'rtbcb' ); ?></label>
                <input type="number" name="num_banks" id="num_banks" min="1" max="50" required>
            </div>
            <div class="rtbcb-field">
                <label for="ftes"><?php esc_html_e( 'Treasury team size (FTEs)', 'rtbcb' ); ?></label>
                <input type="number" name="ftes" id="ftes" min="1" max="100" step="0.5" required>
            </div>
        </div>
        
        <div class="rtbcb-section">
            <h3><?php esc_html_e( 'Pain Points', 'rtbcb' ); ?></h3>
            <div class="rtbcb-checkbox-group">
                <label><input type="checkbox" name="pain_points[]" value="manual_processes"> <?php esc_html_e( 'Manual, time-consuming processes', 'rtbcb' ); ?></label>
                <label><input type="checkbox" name="pain_points[]" value="poor_visibility"> <?php esc_html_e( 'Poor cash visibility', 'rtbcb' ); ?></label>
                <label><input type="checkbox" name="pain_points[]" value="forecast_accuracy"> <?php esc_html_e( 'Inaccurate cash forecasting', 'rtbcb' ); ?></label>
                <label><input type="checkbox" name="pain_points[]" value="compliance_risk"> <?php esc_html_e( 'Compliance and risk concerns', 'rtbcb' ); ?></label>
                <label><input type="checkbox" name="pain_points[]" value="bank_fees"> <?php esc_html_e( 'High bank fees', 'rtbcb' ); ?></label>
                <label><input type="checkbox" name="pain_points[]" value="integration_issues"> <?php esc_html_e( 'System integration challenges', 'rtbcb' ); ?></label>
            </div>
        </div>
        
        <div class="rtbcb-section">
            <h3><?php esc_html_e( 'Contact Information', 'rtbcb' ); ?></h3>
            <div class="rtbcb-field">
                <label for="email"><?php esc_html_e( 'Email Address', 'rtbcb' ); ?></label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="rtbcb-field">
                <label>
                    <input type="checkbox" name="consent" required>
                    <?php esc_html_e( 'I agree to receive the business case report and occasional treasury technology insights.', 'rtbcb' ); ?>
                </label>
            </div>
        </div>
        
        <div class="rtbcb-actions">
            <button type="submit" class="rtbcb-submit-btn"><?php esc_html_e( 'Generate Business Case', 'rtbcb' ); ?></button>
        </div>
    </form>
    
    <div id="rtbcbResults" class="rtbcb-results" style="display: none;">
        <!-- Results will be populated by JavaScript -->
    </div>
</div>
