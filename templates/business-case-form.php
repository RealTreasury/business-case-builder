<?php
/**
 * Business case form template.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
?>
<div id="rtbcbModalOverlay" class="rtbcb-modal-overlay">
    <div class="rtbcb-modal">
        <div class="rtbcb-modal-header">
            <h2><?php echo esc_html( $title ?? __( 'Business Case Builder', 'rtbcb' ) ); ?></h2>
            <button type="button" class="rtbcb-modal-close" onclick="window.closeBusinessCaseModal()">&times;</button>
        </div>
        <div class="rtbcb-modal-body">
            <div class="rtbcb-form-container">
                <form id="rtbcbForm" class="rtbcb-wizard-form">
                    <?php wp_nonce_field( 'rtbcb_generate', 'rtbcb_nonce' ); ?>

                    <!-- Step 1: Company Info -->
                    <div class="rtbcb-wizard-step active" data-step="1">
                        <h3><?php esc_html_e( 'Company Information', 'rtbcb' ); ?></h3>
                        <div class="rtbcb-field">
                            <label for="company_name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?> *</label>
                            <input type="text" id="company_name" name="company_name" required>
                        </div>
                        <div class="rtbcb-field">
                            <label for="company_size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?> *</label>
                            <select id="company_size" name="company_size" required>
                                <option value=""><?php esc_html_e( 'Select size', 'rtbcb' ); ?></option>
                                <option value="&lt;$50M"><?php esc_html_e( 'Less than $50M', 'rtbcb' ); ?></option>
                                <option value="$50M-$500M"><?php esc_html_e( '$50M - $500M', 'rtbcb' ); ?></option>
                                <option value="$500M-$2B"><?php esc_html_e( '$500M - $2B', 'rtbcb' ); ?></option>
                                <option value="&gt;$2B"><?php esc_html_e( 'Greater than $2B', 'rtbcb' ); ?></option>
                            </select>
                        </div>
                        <div class="rtbcb-field">
                            <label for="industry"><?php esc_html_e( 'Industry', 'rtbcb' ); ?> *</label>
                            <select id="industry" name="industry" required>
                                <option value=""><?php esc_html_e( 'Select industry', 'rtbcb' ); ?></option>
                                <option value="manufacturing"><?php esc_html_e( 'Manufacturing', 'rtbcb' ); ?></option>
                                <option value="technology"><?php esc_html_e( 'Technology', 'rtbcb' ); ?></option>
                                <option value="retail"><?php esc_html_e( 'Retail', 'rtbcb' ); ?></option>
                                <option value="healthcare"><?php esc_html_e( 'Healthcare', 'rtbcb' ); ?></option>
                                <option value="financial"><?php esc_html_e( 'Financial Services', 'rtbcb' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Additional steps would go here -->
                    <div class="rtbcb-wizard-step" data-step="5">
                        <h3><?php esc_html_e( 'Contact Information', 'rtbcb' ); ?></h3>
                        <div class="rtbcb-field">
                            <label for="email"><?php esc_html_e( 'Email Address', 'rtbcb' ); ?> *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="rtbcb-wizard-navigation">
                        <button type="button" class="rtbcb-nav-prev"><?php esc_html_e( 'Previous', 'rtbcb' ); ?></button>
                        <button type="button" class="rtbcb-nav-next"><?php esc_html_e( 'Next', 'rtbcb' ); ?></button>
                        <button type="submit" class="rtbcb-nav-submit" style="display:none;">
                            <?php esc_html_e( 'Generate Business Case', 'rtbcb' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="rtbcbResults" style="display:none;"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const button = document.createElement('button');
    button.textContent = <?php echo wp_json_encode( __( 'Generate Business Case', 'rtbcb' ) ); ?>;
    button.className = 'button button-primary';
    button.onclick = window.openBusinessCaseModal;
    document.body.appendChild(button);
});
</script>
