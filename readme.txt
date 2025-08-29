=== Real Treasury Business Case Builder ===
Contributors: realtreasury
Tags: business, case, builder, roi, treasury
Requires at least: 6.0
Tested up to: 6.0
Stable tag: 2.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that calculates the ROI of treasury technology, builds a narrative business case, renders HTML reports that can be printed or saved as PDFs, and provides an analytics dashboard.

== Description ==
Real Treasury Business Case Builder helps treasury teams quantify the benefits of modern treasury tools. The plugin provides an ROI calculator, generates narrative summaries with large language models, integrates with the Real Treasury portal for vendor research, renders HTML reports that can be printed or saved as PDFs, and includes an analytics dashboard to visualize results.

Use the `[rt_business_case_builder]` shortcode to embed the calculator on any page. Admin pages allow you to configure default assumptions, set OpenAI API keys with format validation, review captured leads, and monitor data health.

== Installation ==
1. Upload `real-treasury-business-case-builder` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Navigate to **Real Treasury → Settings** to configure ROI defaults and API keys.

== Testing Dashboard ==
From the WordPress admin, go to **Real Treasury → Test Dashboard** and click
**Run All Tests** to execute the full suite. Individual section tools appear
after this pass and can be used as needed. The dashboard replaces the individual
test pages from earlier versions. Access requires the `manage_options`
capability and each test action uses a dedicated nonce such as
`rtbcb_test_company_overview` to protect requests.

== Repository Structure ==

```
.
├── AGENTS.md
├── README.md
├── admin
│  ├── AGENTS.md
│  ├── analytics-page.php
│  ├── calculations-page.php
│  ├── class-rtbcb-admin.php
│  ├── css
│  │  └── rtbcb-admin.css
│  ├── dashboard-page.php
│  ├── data-health-page.php
│  ├── js
│  │  ├── company-overview.js
│  │  ├── real-treasury-overview.js
│  │  ├── recommended-category.js
│  │  ├── report-test.js
│  │  ├── rtbcb-admin.js
│  │  ├── rtbcb-test-utils.js
│  │  └── treasury-tech-overview.js
│  ├── leads-page-enhanced.php
│  ├── partials
│  │  ├── dashboard-connectivity.php
│  │  ├── dashboard-test-results.php
│  │  ├── test-company-overview.php
│  │  ├── test-estimated-benefits.php
│  │  ├── test-industry-overview.php
│  │  ├── test-maturity-model.php
│  │  ├── test-rag-market-analysis.php
│  │  ├── test-real-treasury-overview.php
│  │  ├── test-recommended-category.php
│  │  ├── test-report.php
│  │  └── test-value-proposition.php
│  ├── settings-page.php
│  └── test-dashboard-page.php
├── composer.json
├── composer.lock
├── docs
│  └── REPOSITORY_STRUCTURE.md
├── inc
│  ├── AGENTS.md
│  ├── class-rtbcb-api-tester.php
│  ├── class-rtbcb-calculator.php
│  ├── class-rtbcb-category-recommender.php
│  ├── class-rtbcb-db.php
│  ├── class-rtbcb-leads.php
│  ├── class-rtbcb-llm.php
│  ├── class-rtbcb-rag.php
│  ├── class-rtbcb-router.php
│  ├── class-rtbcb-settings.php
│  ├── class-rtbcb-tests.php
│  ├── class-rtbcb-validator.php
│  ├── config.php
│  ├── helpers.php
│  └── model-capabilities.php
├── public
│  ├── AGENTS.md
│  ├── css
│  │  ├── rtbcb-variables.css
│  │  └── rtbcb.css
│  └── js
│     ├── chart.min.js
│     ├── chartjs-license.txt
│     ├── rtbcb-report.js
│     ├── rtbcb-wizard.js
│     ├── rtbcb-wizard.min.js
│     └── rtbcb.js
├── readme.txt
├── real-treasury-business-case-builder.php
├── templates
│  ├── AGENTS.md
│  ├── business-case-form.php
│  ├── comprehensive-report-template.php
│  └── report-template.php
├── tests
│  ├── RTBCB_AdminAjaxReportTest.php
│  ├── RTBCB_AjaxGenerateComprehensiveCaseErrorTest.php
│  ├── api-tester-gpt5-mini.test.php
│  ├── cosine-similarity-search.test.php
│  ├── filters-override.test.php
│  ├── gpt5-responses-api.test.php
│  ├── handle-server-error-display.test.js
│  ├── handle-submit-error.test.js
│  ├── handle-submit-success.test.js
│  ├── helpers
│  │  └── capture-call-openai-body.php
│  ├── json-output-lint.php
│  ├── mini-model-dynamic.test.php
│  ├── openai-api-key-validation.test.php
│  ├── parse-comprehensive-response.test.php
│  ├── reasoning-first-output.test.php
│  ├── render-results-no-narrative.test.js
│  ├── run-tests.sh
│  ├── scenario-selection.test.php
│  └── temperature-model.test.js
└── vendor
   └── AGENTS.md
```

== Frequently Asked Questions ==
= How do I display the calculator? =
Add the `[rt_business_case_builder]` shortcode to a post or page.

= Does the plugin generate PDF reports? =
Reports are rendered as HTML in the browser. Use your browser's print or save functionality to generate PDFs.

= Why aren't charts showing in analytics? =
The analytics dashboard uses Chart.js for its visualizations. The library is bundled with the plugin to reduce blocking by privacy tools, but strict ad blockers may still prevent it from loading. Allow the plugin's scripts in your browser to enable the charts.

== Changelog ==
= 2.1.2 =
* Improved Test Dashboard: `Set Company` uses a single company name input.
* "Run All Tests" now includes the company name parameter for comprehensive checks.
= 2.1.1 =
* Bump plugin version to 2.1.1.

= 2.1.0 =
* Introduced HTML report rendering with print-to-PDF support.
* Added an analytics dashboard with Chart.js visualizations.

= 2.0.0 =
* Integrated Real Treasury portal access and narrative summaries powered by AI.

= 1.0.0 =
* Introduced ROI calculator with shortcode embedding.

= 0.1.0 =
* Initial setup of plugin structure.
