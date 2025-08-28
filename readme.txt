=== Real Treasury Business Case Builder ===
Contributors: realtreasury
Tags: business, case, builder, roi, treasury
Requires at least: 6.0
Tested up to: 6.0
Stable tag: 2.1.0
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
From the WordPress admin, go to **Real Treasury → Test Dashboard** to run
section tests for company overview, recommended category, and more. The
dashboard replaces the individual test pages from earlier versions. Access
requires the `manage_options` capability and each test action uses a dedicated
nonce such as `rtbcb_test_company_overview` to protect requests.

== Frequently Asked Questions ==
= How do I display the calculator? =
Add the `[rt_business_case_builder]` shortcode to a post or page.

= Does the plugin generate PDF reports? =
Reports are rendered as HTML in the browser. Use your browser's print or save functionality to generate PDFs.

= Why aren't charts showing in analytics? =
The analytics dashboard uses Chart.js for its visualizations. The library is bundled with the plugin to reduce blocking by privacy tools, but strict ad blockers may still prevent it from loading. Allow the plugin's scripts in your browser to enable the charts.

== Changelog ==
= 2.1.0 =
* Introduced HTML report rendering with print-to-PDF support.
* Added an analytics dashboard with Chart.js visualizations.

= 2.0.0 =
* Integrated Real Treasury portal access and narrative summaries powered by AI.

= 1.0.0 =
* Introduced ROI calculator with shortcode embedding.

= 0.1.0 =
* Initial setup of plugin structure.
