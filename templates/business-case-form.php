<?php
defined( 'ABSPATH' ) || exit;

/**
	* Enhanced template for the business case form.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

// Default values for template arguments
$style   = $style ?? 'default';
$title   = $title ?? __( 'Treasury Tech Business Case Builder', 'rtbcb' );
$subtitle = $subtitle ?? __( 'Generate a data-driven business case for your treasury technology investment.', 'rtbcb' );

// Get categories for display
$categories = RTBCB_Category_Recommender::get_all_categories();
?>

<!-- Trigger Button -->
<div class="rtbcb-trigger-container">
	<button type="button" class="rtbcb-trigger-btn" id="rtbcb-open-btn">
		<span class="rtbcb-trigger-icon">📊</span>
		<span class="rtbcb-trigger-text"><?php esc_html_e( 'Build Your Business Case', 'rtbcb' ); ?></span>
		<span class="rtbcb-trigger-subtitle"><?php esc_html_e( 'Generate ROI analysis in minutes', 'rtbcb' ); ?></span>
	</button>
</div>

<!-- Modal Overlay -->
<div class="rtbcb-modal-overlay" id="rtbcbModalOverlay">
       <div class="rtbcb-modal-container">
               <div class="rtbcb-modal">
                       <!-- Modal Header -->
                       <div class="rtbcb-modal-header">
                               <button type="button" class="rtbcb-modal-close" id="rtbcb-close-btn">&times;</button>
                               <h2 class="rtbcb-modal-title"><?php echo esc_html( $title ); ?></h2>
                               <p class="rtbcb-modal-subtitle"><?php echo esc_html( $subtitle ); ?></p>
                       </div>

                       <!-- Modal Body -->
                       <div class="rtbcb-modal-body">
                               <div class="rtbcb-form-container">
                                       <form id="rtbcbForm" class="rtbcb-form rtbcb-wizard" method="post" novalidate>

					<!-- Progress Indicator -->
                                        <div class="rtbcb-wizard-progress">
                                                <div class="rtbcb-progress-line"></div>
                                                <div class="rtbcb-progress-steps">
                                                        <div class="rtbcb-progress-step active" data-step="1">
                                                                <div class="rtbcb-progress-number">1</div>
                                                                <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Report', 'rtbcb' ); ?>"><?php esc_html_e( 'Report', 'rtbcb' ); ?></div>
                                                        </div>
                                                        <div class="rtbcb-progress-step" data-step="2">
                                                                <div class="rtbcb-progress-number">2</div>
                                                                <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Company', 'rtbcb' ); ?>"><?php esc_html_e( 'Company', 'rtbcb' ); ?></div>
                                                        </div>
                                                       <div class="rtbcb-progress-step" data-step="3">
                                                               <div class="rtbcb-progress-number">3</div>
                                                               <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Scope', 'rtbcb' ); ?>"><?php esc_html_e( 'Scope', 'rtbcb' ); ?></div>
                                                       </div>
                                                       <div class="rtbcb-progress-step" data-step="4">
                                                               <div class="rtbcb-progress-number">4</div>
                                                               <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Workload', 'rtbcb' ); ?>"><?php esc_html_e( 'Workload', 'rtbcb' ); ?></div>
                                                       </div>
                                                       <div class="rtbcb-progress-step" data-step="5">
                                                               <div class="rtbcb-progress-number">5</div>
                                                               <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Technology', 'rtbcb' ); ?>"><?php esc_html_e( 'Technology', 'rtbcb' ); ?></div>
                                                       </div>
                                                       <div class="rtbcb-progress-step" data-step="6">
                                                               <div class="rtbcb-progress-number">6</div>
                                                               <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Compliance', 'rtbcb' ); ?>"><?php esc_html_e( 'Compliance', 'rtbcb' ); ?></div>
                                                       </div>
                                                        <div class="rtbcb-progress-step" data-step="7">
                                                               <div class="rtbcb-progress-number">7</div>
                                                               <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Challenges', 'rtbcb' ); ?>"><?php esc_html_e( 'Challenges', 'rtbcb' ); ?></div>
                                                        </div>
                                                        <div class="rtbcb-progress-step" data-step="8">
                                                               <div class="rtbcb-progress-number">8</div>
                                                               <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Strategy', 'rtbcb' ); ?>"><?php esc_html_e( 'Strategy', 'rtbcb' ); ?></div>
                                                        </div>
                                                        <div class="rtbcb-progress-step" data-step="9">
                                                               <div class="rtbcb-progress-number">9</div>
                                                               <div class="rtbcb-progress-label" title="<?php echo esc_attr__( 'Contact', 'rtbcb' ); ?>"><?php esc_html_e( 'Contact', 'rtbcb' ); ?></div>
                                                        </div>
                                                </div>
                                        </div>

                                        <!-- Steps Container -->
                                        <div class="rtbcb-wizard-steps">
                        <!-- Step 1: Report Type Selection -->
                        <div class="rtbcb-wizard-step active" data-step="1">
                                <div class="rtbcb-step-header">
                                        <h3><?php esc_html_e( 'Choose your report type', 'rtbcb' ); ?></h3>
                                        <p><?php esc_html_e( 'Select Basic for a quick summary or Enhanced for full analysis.', 'rtbcb' ); ?></p>
                                </div>

                                <div class="rtbcb-step-content">
                                        <div class="rtbcb-field rtbcb-field-required">
                                                <div class="rtbcb-report-type-grid">
							<div class="rtbcb-report-type-card">
                                                                <label class="rtbcb-report-type-label">
                                                                        <input type="radio" name="report_type" value="basic" checked />
                                                                        <div class="rtbcb-report-type-content">
                                                                                <div class="rtbcb-report-type-icon">📄</div>
                                                                                <div class="rtbcb-report-type-title"><?php esc_html_e( "Basic", "rtbcb" ); ?></div>
                                                                        </div>
                                                                </label>
                                                        </div>
                                                        <div class="rtbcb-report-type-card">
                                                                <label class="rtbcb-report-type-label">
                                                                        <input type="radio" name="report_type" value="enhanced" />
                                                                        <div class="rtbcb-report-type-content">
                                                                                <div class="rtbcb-report-type-icon">📈</div>
                                                                                <div class="rtbcb-report-type-title"><?php esc_html_e( "Enhanced", "rtbcb" ); ?></div>
                                                                        </div>
                                                                </label>
                                                        </div>
                                                </div>
                                        </div>
                                </div>

               </div>

               <!-- Step 2: Company Information -->
               <div class="rtbcb-wizard-step" data-step="2">
                       <div class="rtbcb-step-header">
                               <h3><?php esc_html_e( 'Tell us about your company', 'rtbcb' ); ?></h3>
                               <p><?php esc_html_e( 'Provide basic information about your organization.', 'rtbcb' ); ?></p>
                       </div>

                       <div class="rtbcb-step-content">
                               <!-- Company Name Field -->
                               <div class="rtbcb-field rtbcb-field-required">
                                       <label for="company_name">
                                               <?php esc_html_e( 'Company Name', 'rtbcb' ); ?>
                                       </label>
                                       <input type="text" name="company_name" id="company_name"
                                               placeholder="<?php esc_attr_e( 'Enter your company name', 'rtbcb' ); ?>"
                                               required
                                               maxlength="100" />
                                       <div class="rtbcb-field-help">
                                               <?php esc_html_e( 'This will be used to personalize your business case report', 'rtbcb' ); ?>
                                       </div>
                               </div>

                               <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                       <label for="company_size">
                                               <?php esc_html_e( 'Company Size (Annual Revenue)', 'rtbcb' ); ?>
                                       </label>
                                       <select name="company_size" id="company_size" required>
                                               <option value=""><?php esc_html_e( 'Select your company size...', 'rtbcb' ); ?></option>
                                               <option value="&lt;$50M"><?php esc_html_e( 'Small Business (&lt;$50M)', 'rtbcb' ); ?></option>
                                               <option value="$50M-$500M"><?php esc_html_e( 'Mid-Market ($50M-$500M)', 'rtbcb' ); ?></option>
                                               <option value="$500M-$2B"><?php esc_html_e( 'Large Enterprise ($500M-$2B)', 'rtbcb' ); ?></option>
                                               <option value="&gt;$2B"><?php esc_html_e( 'Fortune 500 (&gt;$2B)', 'rtbcb' ); ?></option>
                                       </select>
                               </div>

                               <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                       <label for="industry">
                                               <?php esc_html_e( 'Industry', 'rtbcb' ); ?>
                                       </label>
                                       <select name="industry" id="industry" required>
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

                               <!-- Optional: Add current position field for more personalization -->
                               <div class="rtbcb-field rtbcb-enhanced-only">
                                       <label for="job_title"><?php esc_html_e( 'Your Role (Optional)', 'rtbcb' ); ?></label>
                                       <select name="job_title" id="job_title">
                                               <option value=""><?php esc_html_e( 'Select your role...', 'rtbcb' ); ?></option>
                                               <option value="cfo"><?php esc_html_e( 'CFO', 'rtbcb' ); ?></option>
                                               <option value="treasurer"><?php esc_html_e( 'Treasurer', 'rtbcb' ); ?></option>
                                               <option value="finance_director"><?php esc_html_e( 'Finance Director', 'rtbcb' ); ?></option>
                                               <option value="finance_manager"><?php esc_html_e( 'Finance Manager', 'rtbcb' ); ?></option>
                                               <option value="treasury_analyst"><?php esc_html_e( 'Treasury Analyst', 'rtbcb' ); ?></option>
                                               <option value="controller"><?php esc_html_e( 'Controller', 'rtbcb' ); ?></option>
                                               <option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
                                       </select>
                                       <div class="rtbcb-field-help">
                                               <?php esc_html_e( 'Helps us tailor recommendations to your perspective', 'rtbcb' ); ?>
                                       </div>
                               </div>
                       </div>
               </div>

               <!-- Step 3: Treasury Footprint -->
                       <div class="rtbcb-wizard-step" data-step="3">
                               <div class="rtbcb-step-header">
                                       <h3><?php esc_html_e( 'Outline your treasury footprint', 'rtbcb' ); ?></h3>
                                       <p><?php esc_html_e( 'Tell us about your organizational and banking structure.', 'rtbcb' ); ?></p>
                               </div>

                               <div class="rtbcb-step-content">
                                       <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                               <label for="num_entities"><?php esc_html_e( 'Number of Legal Entities', 'rtbcb' ); ?></label>
                                               <input type="number" name="num_entities" id="num_entities" min="1" step="1" required />
                                               <div class="rtbcb-field-help">
                                                       <?php esc_html_e( 'Count of subsidiaries or business units handled by treasury.', 'rtbcb' ); ?>
                                               </div>
                                       </div>

                                       <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                               <label for="num_currencies"><?php esc_html_e( 'Number of Active Currencies', 'rtbcb' ); ?></label>
                                               <input type="number" name="num_currencies" id="num_currencies" min="1" step="1" required />
                                               <div class="rtbcb-field-help">
                                                       <?php esc_html_e( 'How many currencies do you transact in?', 'rtbcb' ); ?>
                                               </div>
                                       </div>

                                       <div class="rtbcb-field rtbcb-field-required">
                                               <label for="num_banks"><?php esc_html_e( 'Number of Banking Relationships', 'rtbcb' ); ?></label>
                                               <input type="number" name="num_banks" id="num_banks" min="1" max="50" placeholder="0" required inputmode="decimal" />
                                               <div class="rtbcb-field-help">
                                                       <?php esc_html_e( 'Total number of banks where your company maintains accounts', 'rtbcb' ); ?>
                                               </div>
                                       </div>
                               </div>
                       </div>

                        <!-- Step 4: Workload -->
                        <div class="rtbcb-wizard-step" data-step="4">
                                <div class="rtbcb-step-header">
                                        <h3><?php esc_html_e( 'Workload', 'rtbcb' ); ?></h3>
                                        <p><?php esc_html_e( 'Help us understand your current effort and team size.', 'rtbcb' ); ?></p>
                                </div>

                                 <div class="rtbcb-step-content">
					<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
						<label for="hours_reconciliation">
							<?php esc_html_e( 'Weekly Hours: Bank Reconciliation', 'rtbcb' ); ?>
						</label>
						<input type="number" name="hours_reconciliation" id="hours_reconciliation"
							min="0" max="168" step="0.5" placeholder="0" required inputmode="decimal" />
						<div class="rtbcb-field-help">
							<?php esc_html_e( 'Total weekly hours spent on bank reconciliation tasks', 'rtbcb' ); ?>
						</div>
					</div>

					<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
						<label for="hours_cash_positioning">
							<?php esc_html_e( 'Weekly Hours: Cash Positioning', 'rtbcb' ); ?>
						</label>
						<input type="number" name="hours_cash_positioning" id="hours_cash_positioning"
							min="0" max="168" step="0.5" placeholder="0" required inputmode="decimal" />
						<div class="rtbcb-field-help">
							<?php esc_html_e( 'Time spent on cash visibility, forecasting, and positioning', 'rtbcb' ); ?>
						</div>
					</div>


                                       <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                               <label for="ftes">
                                                       <?php esc_html_e( 'Treasury Team Size (FTEs)', 'rtbcb' ); ?>
                                               </label>
                                               <input type="number" name="ftes" id="ftes"
                                                       min="0.5" max="100" step="0.5" placeholder="0" required inputmode="decimal" />
                                               <div class="rtbcb-field-help">
                                                       <?php esc_html_e( 'Full-time equivalent employees dedicated to treasury functions', 'rtbcb' ); ?>
                                               </div>
                                       </div>

                 </div>
         </div>

        <!-- Step 5: Technology -->
        <div class="rtbcb-wizard-step" data-step="5">
                <div class="rtbcb-step-header">
                        <h3><?php esc_html_e( 'Technology', 'rtbcb' ); ?></h3>
                        <p><?php esc_html_e( 'Tell us about your systems and reporting cadence.', 'rtbcb' ); ?></p>
                </div>

                 <div class="rtbcb-step-content">
                         <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                 <label for="treasury_automation">
                                         <?php esc_html_e( 'Treasury Workflow Automation Level', 'rtbcb' ); ?>
                                 </label>
                                 <select name="treasury_automation" id="treasury_automation" required>
                                         <option value=""><?php esc_html_e( 'Select automation level...', 'rtbcb' ); ?></option>
                                         <option value="manual"><?php esc_html_e( 'Mostly manual', 'rtbcb' ); ?></option>
                                         <option value="some"><?php esc_html_e( 'Some automation', 'rtbcb' ); ?></option>
                                         <option value="full"><?php esc_html_e( 'Fully automated', 'rtbcb' ); ?></option>
                                 </select>
                                 <div class="rtbcb-field-help">
                                         <?php esc_html_e( 'Assess the degree to which treasury tasks rely on spreadsheets versus software.', 'rtbcb' ); ?>
                                 </div>
                         </div>

                         <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                 <label for="primary_systems">
                                         <?php esc_html_e( 'Primary Treasury Systems in Use', 'rtbcb' ); ?>
                                 </label>
                                 <select name="primary_systems[]" id="primary_systems" multiple required>
                                         <option value="erp"><?php esc_html_e( 'ERP', 'rtbcb' ); ?></option>
                                         <option value="bank_portals"><?php esc_html_e( 'Bank portals', 'rtbcb' ); ?></option>
                                         <option value="spreadsheets"><?php esc_html_e( 'Spreadsheets', 'rtbcb' ); ?></option>
                                         <option value="tms"><?php esc_html_e( 'Dedicated TMS', 'rtbcb' ); ?></option>
                                         <option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
                                 </select>
                                 <div class="rtbcb-field-help">
                                         <?php esc_html_e( 'Select all platforms used today for treasury tasks.', 'rtbcb' ); ?>
                                 </div>
                         </div>

                         <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                 <label for="bank_import_frequency">
                                         <?php esc_html_e( 'Frequency of Bank Statement Imports', 'rtbcb' ); ?>
                                 </label>
                                 <select name="bank_import_frequency" id="bank_import_frequency" required>
                                         <option value=""><?php esc_html_e( 'Select frequency...', 'rtbcb' ); ?></option>
                                         <option value="manual_daily"><?php esc_html_e( 'Manual daily', 'rtbcb' ); ?></option>
                                         <option value="manual_weekly"><?php esc_html_e( 'Manual weekly', 'rtbcb' ); ?></option>
                                         <option value="automated_daily"><?php esc_html_e( 'Automated daily', 'rtbcb' ); ?></option>
                                         <option value="real_time"><?php esc_html_e( 'Real-time', 'rtbcb' ); ?></option>
                                 </select>
                                 <div class="rtbcb-field-help">
                                         <?php esc_html_e( 'How often are bank statements imported into your systems?', 'rtbcb' ); ?>
                                 </div>
                         </div>

                         <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                                 <label for="reporting_cadence">
                                         <?php esc_html_e( 'Reporting Cadence to Stakeholders', 'rtbcb' ); ?>
                                 </label>
                                 <select name="reporting_cadence" id="reporting_cadence" required>
                                         <option value=""><?php esc_html_e( 'Select cadence...', 'rtbcb' ); ?></option>
                                         <option value="ad_hoc"><?php esc_html_e( 'Ad-hoc', 'rtbcb' ); ?></option>
                                         <option value="monthly"><?php esc_html_e( 'Monthly', 'rtbcb' ); ?></option>
                                         <option value="weekly"><?php esc_html_e( 'Weekly', 'rtbcb' ); ?></option>
                                         <option value="daily"><?php esc_html_e( 'Daily', 'rtbcb' ); ?></option>
                                         <option value="real_time"><?php esc_html_e( 'Real-time dashboard', 'rtbcb' ); ?></option>
                                 </select>
                                 <div class="rtbcb-field-help">
                                         <?php esc_html_e( 'Frequency of delivering cash/treasury reports to management.', 'rtbcb' ); ?>
                                 </div>
                         </div>
                 </div>
         </div>

<!-- Step 6: Compliance -->
<div class="rtbcb-wizard-step" data-step="6">
       <div class="rtbcb-step-header">
               <h3><?php esc_html_e( 'Compliance', 'rtbcb' ); ?></h3>
               <p><?php esc_html_e( 'Share your volume, workflows, and control requirements.', 'rtbcb' ); ?></p>
       </div>

        <div class="rtbcb-step-content">
                <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                        <label for="annual_payment_volume">
                                <?php esc_html_e( 'Annual Payment Volume', 'rtbcb' ); ?>
                        </label>
                        <input type="number" name="annual_payment_volume" id="annual_payment_volume" min="0" step="1" required />
                        <div class="rtbcb-field-help">
                                <?php esc_html_e( 'Approximate number of payments processed per year.', 'rtbcb' ); ?>
                        </div>
                </div>

                <div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
                        <label for="payment_approval_workflow">
                                <?php esc_html_e( 'Treasury Payment Approval Workflow', 'rtbcb' ); ?>
                        </label>
                        <select name="payment_approval_workflow" id="payment_approval_workflow" required>
                                <option value=""><?php esc_html_e( 'Select workflow...', 'rtbcb' ); ?></option>
                                <option value="single"><?php esc_html_e( 'Single approver', 'rtbcb' ); ?></option>
                                <option value="dual"><?php esc_html_e( 'Dual approval', 'rtbcb' ); ?></option>
                                <option value="tiered"><?php esc_html_e( 'Tiered/role-based', 'rtbcb' ); ?></option>
                                <option value="none"><?php esc_html_e( 'No formal workflow', 'rtbcb' ); ?></option>
                        </select>
                        <div class="rtbcb-field-help">
                                <?php esc_html_e( 'Describe how payments are authorized.', 'rtbcb' ); ?>
                        </div>
                </div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="reconciliation_method">
<?php esc_html_e( 'Reconciliation Method', 'rtbcb' ); ?>
</label>
<select name="reconciliation_method" id="reconciliation_method" required>
<option value=""><?php esc_html_e( 'Select method...', 'rtbcb' ); ?></option>
<option value="manual"><?php esc_html_e( 'Manual matching', 'rtbcb' ); ?></option>
<option value="rule"><?php esc_html_e( 'Rule-based automation', 'rtbcb' ); ?></option>
<option value="ai"><?php esc_html_e( 'AI/ML-based automation', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'How are transactions reconciled against statements?', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="cash_update_frequency">
<?php esc_html_e( 'Cash Position Update Frequency', 'rtbcb' ); ?>
</label>
<select name="cash_update_frequency" id="cash_update_frequency" required>
<option value=""><?php esc_html_e( 'Select frequency...', 'rtbcb' ); ?></option>
<option value="ad_hoc"><?php esc_html_e( 'Ad-hoc', 'rtbcb' ); ?></option>
<option value="daily"><?php esc_html_e( 'Daily', 'rtbcb' ); ?></option>
<option value="multi_daily"><?php esc_html_e( 'Multiple times per day', 'rtbcb' ); ?></option>
<option value="real_time"><?php esc_html_e( 'Real-time', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'How often do you refresh cash positions?', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-enhanced-only">
<label for="reg_reporting">
<?php esc_html_e( 'Regulatory or Compliance Reporting Needs', 'rtbcb' ); ?>
</label>
<select name="reg_reporting[]" id="reg_reporting" multiple>
<option value="sox"><?php esc_html_e( 'SOX', 'rtbcb' ); ?></option>
<option value="emir"><?php esc_html_e( 'EMIR', 'rtbcb' ); ?></option>
<option value="dodd_frank"><?php esc_html_e( 'Dodd-Frank', 'rtbcb' ); ?></option>
<option value="local_tax"><?php esc_html_e( 'Local tax reporting', 'rtbcb' ); ?></option>
<option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'Select any regulatory frameworks your treasury reports must meet.', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="integration_requirements">
<?php esc_html_e( 'Integration Requirements', 'rtbcb' ); ?>
</label>
<select name="integration_requirements[]" id="integration_requirements" multiple required>
<option value="erp"><?php esc_html_e( 'ERP', 'rtbcb' ); ?></option>
<option value="gl"><?php esc_html_e( 'Accounting/GL', 'rtbcb' ); ?></option>
<option value="ap_ar"><?php esc_html_e( 'AP/AR system', 'rtbcb' ); ?></option>
<option value="payroll"><?php esc_html_e( 'Payroll', 'rtbcb' ); ?></option>
<option value="market_data"><?php esc_html_e( 'Market data', 'rtbcb' ); ?></option>
<option value="none"><?php esc_html_e( 'None', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'Systems you need to connect with treasury tools.', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="forecast_horizon">
<?php esc_html_e( 'Forecasting Horizon', 'rtbcb' ); ?>
</label>
<select name="forecast_horizon" id="forecast_horizon" required>
<option value=""><?php esc_html_e( 'Select horizon...', 'rtbcb' ); ?></option>
<option value="under_1"><?php esc_html_e( 'Under 1 month', 'rtbcb' ); ?></option>
<option value="1_3"><?php esc_html_e( '1–3 months', 'rtbcb' ); ?></option>
<option value="3_12"><?php esc_html_e( '3–12 months', 'rtbcb' ); ?></option>
<option value="over_12"><?php esc_html_e( 'Over 12 months', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'Typical length of cash forecasting.', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="fx_management">
<?php esc_html_e( 'FX Exposure Management', 'rtbcb' ); ?>
</label>
<select name="fx_management" id="fx_management" required>
<option value=""><?php esc_html_e( 'Select approach...', 'rtbcb' ); ?></option>
<option value="none"><?php esc_html_e( 'None', 'rtbcb' ); ?></option>
<option value="basic"><?php esc_html_e( 'Basic tracking', 'rtbcb' ); ?></option>
<option value="manual_hedging"><?php esc_html_e( 'Hedging with manual processes', 'rtbcb' ); ?></option>
<option value="automated_hedging"><?php esc_html_e( 'Automated hedging program', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'Describe your foreign exchange risk approach.', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="investment_activities">
<?php esc_html_e( 'Investment Activities', 'rtbcb' ); ?>
</label>
<select name="investment_activities[]" id="investment_activities" multiple required>
<option value="mmf"><?php esc_html_e( 'MMFs', 'rtbcb' ); ?></option>
<option value="term_deposits"><?php esc_html_e( 'Term deposits', 'rtbcb' ); ?></option>
<option value="bonds"><?php esc_html_e( 'Bonds', 'rtbcb' ); ?></option>
<option value="derivatives"><?php esc_html_e( 'Derivatives', 'rtbcb' ); ?></option>
<option value="none"><?php esc_html_e( 'None', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'Select instruments used for investing excess cash.', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="intercompany_lending">
<?php esc_html_e( 'Intercompany Lending or Netting', 'rtbcb' ); ?>
</label>
<select name="intercompany_lending" id="intercompany_lending" required>
<option value=""><?php esc_html_e( 'Select option...', 'rtbcb' ); ?></option>
<option value="none"><?php esc_html_e( 'None', 'rtbcb' ); ?></option>
<option value="manual"><?php esc_html_e( 'Manual tracking', 'rtbcb' ); ?></option>
<option value="automated_loans"><?php esc_html_e( 'Automated intercompany loans', 'rtbcb' ); ?></option>
<option value="netting_center"><?php esc_html_e( 'Netting center', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'How do you handle intercompany cash movements?', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-enhanced-only">
<label for="treasury_kpis">
<?php esc_html_e( 'Treasury KPIs Tracked', 'rtbcb' ); ?>
</label>
<select name="treasury_kpis[]" id="treasury_kpis" multiple>
<option value="daily_liquidity"><?php esc_html_e( 'Daily liquidity', 'rtbcb' ); ?></option>
<option value="forecast_accuracy"><?php esc_html_e( 'Forecast accuracy', 'rtbcb' ); ?></option>
<option value="cost_of_funds"><?php esc_html_e( 'Cost of funds', 'rtbcb' ); ?></option>
<option value="fx_gain_loss"><?php esc_html_e( 'FX gain/loss', 'rtbcb' ); ?></option>
<option value="bank_fees"><?php esc_html_e( 'Bank fees', 'rtbcb' ); ?></option>
<option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'Metrics you monitor regularly.', 'rtbcb' ); ?>
</div>
</div>

<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="audit_trail">
<?php esc_html_e( 'Audit Trail & Control Requirements', 'rtbcb' ); ?>
</label>
<select name="audit_trail" id="audit_trail" required>
<option value=""><?php esc_html_e( 'Select requirement...', 'rtbcb' ); ?></option>
<option value="none"><?php esc_html_e( 'None', 'rtbcb' ); ?></option>
<option value="basic"><?php esc_html_e( 'Basic logging', 'rtbcb' ); ?></option>
<option value="full"><?php esc_html_e( 'Full audit trail with user roles', 'rtbcb' ); ?></option>
</select>
<div class="rtbcb-field-help">
<?php esc_html_e( 'Level of traceability needed for treasury actions.', 'rtbcb' ); ?>
</div>
</div>
				</div>
			</div>

                       <!-- Step 7: Treasury Challenges -->
                       <div class="rtbcb-wizard-step" data-step="7">
				<div class="rtbcb-step-header">
					<h3><?php esc_html_e( 'What are your biggest challenges?', 'rtbcb' ); ?></h3>
                                       <p><?php esc_html_e( 'Select the challenges that best describe your current treasury situation.', 'rtbcb' ); ?></p>
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

				</div>
			</div>

                         <!-- Step 8: Strategic Context -->
                         <div class="rtbcb-wizard-step" data-step="8">
				<div class="rtbcb-step-header">
					<h3><?php esc_html_e( 'Strategic context for your initiative', 'rtbcb' ); ?></h3>
					<p><?php esc_html_e( 'Help us understand the goals and constraints for your project.', 'rtbcb' ); ?></p>
				</div>

				<div class="rtbcb-step-content">
					<div class="rtbcb-field rtbcb-enhanced-only">
						<label for="current_tech"><?php esc_html_e( 'Current Treasury Technology', 'rtbcb' ); ?></label>
						<input type="text" name="current_tech" id="current_tech" />
					</div>

					<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
						<label for="business_objective">
							<?php esc_html_e( 'Primary Business Objective', 'rtbcb' ); ?>
						</label>
						<select name="business_objective" id="business_objective" required>
							<option value=""><?php esc_html_e( 'Select an objective...', 'rtbcb' ); ?></option>
							<option value="cost_reduction"><?php esc_html_e( 'Cost reduction', 'rtbcb' ); ?></option>
							<option value="risk_management"><?php esc_html_e( 'Risk management', 'rtbcb' ); ?></option>
							<option value="growth_support"><?php esc_html_e( 'Growth support', 'rtbcb' ); ?></option>
							<option value="compliance"><?php esc_html_e( 'Compliance', 'rtbcb' ); ?></option>
						</select>
					</div>

					<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
<label for="implementation_timeline">
<?php esc_html_e( 'Implementation Timeline', 'rtbcb' ); ?>
</label>
<select name="implementation_timeline" id="implementation_timeline" required>
<option value=""><?php esc_html_e( 'Select a timeline...', 'rtbcb' ); ?></option>
<option value="under_3"><?php esc_html_e( '<3 months', 'rtbcb' ); ?></option>
<option value="3_6"><?php esc_html_e( '3–6 months', 'rtbcb' ); ?></option>
<option value="6_12"><?php esc_html_e( '6–12 months', 'rtbcb' ); ?></option>
<option value="over_12"><?php esc_html_e( '>12 months', 'rtbcb' ); ?></option>
</select>
					</div>

					<div class="rtbcb-field rtbcb-enhanced-only">
						<label for="decision_makers"><?php esc_html_e( 'Decision Makers', 'rtbcb' ); ?></label>
						<select name="decision_makers[]" id="decision_makers" multiple>
							<option value="cfo"><?php esc_html_e( 'CFO', 'rtbcb' ); ?></option>
							<option value="treasurer"><?php esc_html_e( 'Treasurer', 'rtbcb' ); ?></option>
							<option value="finance_team"><?php esc_html_e( 'Finance Team', 'rtbcb' ); ?></option>
							<option value="it"><?php esc_html_e( 'IT', 'rtbcb' ); ?></option>
						</select>
					</div>

					<div class="rtbcb-field rtbcb-field-required rtbcb-enhanced-only">
						<label for="budget_range">
							<?php esc_html_e( 'Budget Range', 'rtbcb' ); ?>
						</label>
						<select name="budget_range" id="budget_range" required>
							<option value=""><?php esc_html_e( 'Select a range...', 'rtbcb' ); ?></option>
							<option value="50-100"><?php esc_html_e( '$50K-$100K', 'rtbcb' ); ?></option>
							<option value="100-250"><?php esc_html_e( '$100K-$250K', 'rtbcb' ); ?></option>
							<option value="250-500"><?php esc_html_e( '$250K-$500K', 'rtbcb' ); ?></option>
							<option value="500+"><?php esc_html_e( '$500K+', 'rtbcb' ); ?></option>
						</select>
					</div>
				</div>
			</div>

                         <!-- Step 9: Contact Information -->
                         <div class="rtbcb-wizard-step" data-step="9">
				<div class="rtbcb-step-header">
					<h3><?php esc_html_e( 'Get your business case', 'rtbcb' ); ?></h3>
					<p><?php esc_html_e( 'Enter your email to receive your personalized ROI analysis and recommendations.', 'rtbcb' ); ?></p>
				</div>

				<div class="rtbcb-step-content">
					<div class="rtbcb-field rtbcb-field-required">
						<label for="email">
							<?php esc_html_e( 'Business Email Address', 'rtbcb' ); ?>
						</label>
						<input type="email" name="email" id="email"
							placeholder="yourname@company.com" required />
						<div class="rtbcb-field-help">
							<?php esc_html_e( 'We\'ll send your business case report to this email address', 'rtbcb' ); ?>
						</div>
					</div>

					<div class="rtbcb-consent-container">
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
					</div>

					<!-- What You'll Receive Preview -->
					<div class="rtbcb-preview-container">
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

			<?php wp_nonce_field( 'rtbcb_generate', 'rtbcb_nonce' ); ?>

			<button type="submit" id="rtbcb-submit-button" class="rtbcb-nav-btn rtbcb-nav-submit" style="display: none;">
				<span class="rtbcb-nav-icon">🚀</span>
				<?php esc_html_e( 'Generate Business Case', 'rtbcb' ); ?>
			</button>

		</div>
</form>
<div id="rtbcbSuccessMessage" class="rtbcb-success-message" style="display:none"></div>
</div>

</div>
</div>

    <div id="rtbcb-progress-container" class="rtbcb-progress-overlay" style="display: none;" role="dialog" aria-hidden="true">
        <div class="rtbcb-progress-content">
            <div class="rtbcb-progress-spinner"></div>
            <div class="rtbcb-progress-text"><?php esc_html_e( 'Generating Your Business Case', 'rtbcb' ); ?></div>
            <div class="rtbcb-progress-step">
                <span class="rtbcb-progress-step-text"><?php esc_html_e( 'Preparing your analysis...', 'rtbcb' ); ?></span>
            </div>
                        <div class="rtbcb-progress-partial"><!-- Partial results will be shown here --></div>
                        <button type="button" class="rtbcb-progress-cancel"><?php esc_html_e( 'Cancel and Start Over', 'rtbcb' ); ?></button>
        </div>
    </div>
    </div>
</div>

<?php
// Additional CSS variables to add to your rtbcb-variables.css file
?>
<style>
/* FIXED: Additional CSS variables for better consistency */
:root {
/* Progress loader specific variables */
--loader-bg: rgba(0, 0, 0, 0.8);
--loader-content-bg: #ffffff;
--loader-border: rgba(199, 125, 255, 0.3);
--loader-text-primary: #1f2937;
--loader-text-secondary: #4b5563;
--loader-success: #10b981;
--loader-error: #ef4444;
--loader-spinner: var(--primary-purple);

/* Mobile adjustments */
--loader-mobile-padding: 20px;
--loader-mobile-max-width: 95vw;
}

/* FIXED: Dark mode support */
@media (prefers-color-scheme: dark) {
:root {
--loader-content-bg: #1f2937;
--loader-text-primary: #f9fafb;
--loader-text-secondary: #d1d5db;
--loader-border: rgba(139, 92, 246, 0.3);
}
}
</style>

<?php
// FIXED: Add this JavaScript for better error handling and accessibility
?>
<script>
document.addEventListener( 'DOMContentLoaded', function() {
const progressContainer = document.getElementById( 'rtbcb-progress-container' );

if ( progressContainer ) {
document.addEventListener( 'keydown', function( e ) {
if ( e.key === 'Escape' && progressContainer.style.display === 'flex' ) {
if ( window.businessCaseBuilder && typeof window.businessCaseBuilder.cancelPolling === 'function' ) {
window.businessCaseBuilder.cancelPolling();
}
if ( window.businessCaseBuilder && typeof window.businessCaseBuilder.hideLoading === 'function' ) {
window.businessCaseBuilder.hideLoading();
} else {
progressContainer.style.display = 'none';
const formContainer = document.querySelector( '.rtbcb-form-container' );
if ( formContainer ) {
formContainer.style.display = '';
}
}
document.body.style.overflow = '';
}
} );

progressContainer.addEventListener( 'click', function( e ) {
if ( e.target === progressContainer ) {
const content = progressContainer.querySelector( '.rtbcb-progress-content' );
if ( content && ! content.querySelector( '.rtbcb-progress-spinner' ) ) {
progressContainer.style.display = 'none';
document.body.style.overflow = '';
}
}
} );

const observer = new MutationObserver( function( mutations ) {
mutations.forEach( function( mutation ) {
if ( mutation.type === 'attributes' && mutation.attributeName === 'style' ) {
const isVisible = progressContainer.style.display === 'flex';
progressContainer.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );

if ( isVisible ) {
const announcement = document.createElement( 'div' );
announcement.setAttribute( 'aria-live', 'polite' );
announcement.setAttribute( 'aria-atomic', 'true' );
announcement.style.position = 'absolute';
announcement.style.left = '-10000px';
announcement.textContent = 'Business case generation started';
document.body.appendChild( announcement );

setTimeout( () => {
document.body.removeChild( announcement );
}, 1000 );
}
}
} );
} );

observer.observe( progressContainer, {
attributes: true,
attributeFilter: [ 'style' ],
} );
}
} );
</script>

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


