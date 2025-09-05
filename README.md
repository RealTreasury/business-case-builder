# Real Treasury Business Case Builder - Enhanced Version 1.3.1

A comprehensive WordPress plugin that helps treasury teams quantify the benefits of modern treasury tools, generate professional business case reports, and track lead engagement with advanced analytics.

## 🚀 What's New in Version 1.3.1

### ✨ Enhancements
- Re-minified assets.
- Refreshed documentation.

## 🧠 How It Works

The plugin bootstraps through `RTBCB_Main`, which loads classes under `inc/` and
registers shortcodes, scripts, and AJAX endpoints. Front-end users launch a modal
wizard with `[rt_business_case_builder]`, and `public/js/rtbcb-wizard.js` posts the
form to `RTBCB_Ajax::generate_comprehensive_case()`.

That handler queues a background job. `RTBCB_Ajax::process_comprehensive_case()`
pulls the job, validates inputs, and coordinates:

- `RTBCB_Calculator` for ROI scenarios
- `RTBCB_Category_Recommender` for solution targeting
- `RTBCB_RAG` for contextual snippets
- `RTBCB_LLM_Unified` for narrative analysis
- `RTBCB_Workflow_Tracker` for step tracking

The assembled report is rendered with
`templates/comprehensive-report-template.php` and enhanced by
`public/js/rtbcb-report.js`, which polls for job status and displays the finished
HTML.

## 📋 Installation & Setup

### Prerequisites
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- OpenAI API key (for business case generation)

### Step 1: Plugin Installation
1. Upload the plugin to `/wp-content/plugins/real-treasury-business-case-builder/`
2. Activate the plugin through the WordPress admin
3. Navigate to **Real Treasury → Settings** in your admin dashboard

### Step 2: Configure OpenAI Integration
1. Sign up for an OpenAI API account at [platform.openai.com](https://platform.openai.com)
2. Generate an API key in your OpenAI dashboard
3. Enter the API key in **Real Treasury → Settings**
4. Configure your preferred models:
   - **Mini Model**: configurable via settings (default `gpt-5-mini` for efficiency)
   - **Premium Model**: `gpt-5` (for complex requests)
   - **Embedding Model**: `text-embedding-3-small` (for RAG)

   **Minimum Output Tokens:** configure `min_output_tokens` (default `256`) to avoid truncated responses.

   ```php
   $client->chat()->create([
   'model' => 'gpt-5-mini',
   'max_output_tokens' => 256,
  ]);
  ```

   ### Structured JSON Prompt Contract
   The plugin sends requests using a fixed system prompt and expects JSON-only responses.
   Each request specifies `reasoning_effort` set to `medium` and uses
   `response_format = {"type": "json_object"}`. The client performs a
   self-validation pass and automatically retries once if the model returns
   invalid JSON.

   The API tester uses this value to verify connectivity.

By default, the plugin configures `max_output_tokens` to `8000` and
`min_output_tokens` to `256` for GPT-5 models. You can adjust these values
via the plugin settings, by setting environment variables, or by creating a
configuration file:

- **Environment variable**: `RTBCB_MAX_OUTPUT_TOKENS=128000`
- **Config file**: create `rtbcb-config.json` in the project root with
`{ "max_output_tokens": 128000 }`

Values outside the `256`–`128000` range are ignored.

#### Model Temperature Support

| Model | Temperature Support |
| --- | --- |
| `gpt-4o` | Yes |
| `gpt-4o-mini` | Yes |
| `gpt-5` | No |
| `gpt-5-mini` | Yes |
| `gpt-4.1` | No |
| `gpt-4.1-mini` | No |
| `text-embedding-3-small` | No (embeddings) |

Update the list in `inc/helpers.php` if OpenAI changes temperature capabilities.
### Parser Architecture
The `RTBCB_Response_Parser` class centralizes extraction of message text, reasoning traces, function calls, and business-case validation. Legacy helpers now proxy to this class for consistent handling across the plugin.


### Step 3: Configure Persistent Database Connections

Reducing connection overhead keeps report generation responsive.

1. Edit `wp-config.php` and prefix the host with `p:`:

    ```php
    define( 'DB_HOST', 'p:localhost' );
    ```

2. Or install a pooling plugin such as [HyperDB](https://wordpress.org/plugins/hyperdb/).

See [docs/DATABASE_CONNECTIONS.md](docs/DATABASE_CONNECTIONS.md) for details.

### Step 4: Enable Persistent Object Cache

To persist cached API responses across requests, configure a persistent object cache such as
[Redis](https://wordpress.org/plugins/redis-cache/) or
[Memcached](https://wordpress.org/plugins/memcached/). See
[docs/OBJECT_CACHE.md](docs/OBJECT_CACHE.md) for details.

### Step 5: Configure Database Tables
The plugin automatically creates required database tables on activation:
- `wp_rtbcb_leads` - Lead tracking and analytics
- `wp_rtbcb_rag_index` - Retrieval-augmented generation index

### Step 6: Display the Form
Add the shortcode to any page or post to display a “Generate Business Case” button that launches the form in a modal:
```
[rt_business_case_builder]
```

**Advanced Shortcode Options:**
```
[rt_business_case_builder
   title="Custom Title"
   subtitle="Custom Description"
   style="modern"]
```

The wizard requires `public/js/rtbcb-wizard.js` to be loaded before the button is clicked.
In WordPress this happens automatically when `enqueue_assets()` runs and your theme
calls both `wp_head()` and `wp_footer()` so enqueued scripts render.
If testing the template outside WordPress, manually include:

```html
<script src="/path/to/public/js/rtbcb-wizard.js"></script>
```

before the closing `</body>` tag.

## Report Types

The `report_type` parameter determines the depth of the generated report:

- `basic` - summary results with minimal narrative.
- `comprehensive` - full report with ROI analysis and detailed sections.
- `fast` - quick preview that skips heavy calculations.

`action_plan`, `operational_insights`, and `risk_analysis` are returned only when
`report_type` is `comprehensive` and `fast_mode` is `false`.

### Example Request

```json
{
    "action": "rtbcb_generate_case",
    "report_type": "comprehensive",
    "fast_mode": false,
    "company_name": "Acme Corp"
}
```

## 🗂️ Repository Structure

- `admin/` – WordPress dashboard pages, settings, and nonces.
- `inc/` – Core classes and helper functions.
- `public/` – Front-end hooks, shortcodes, and assets.
- `templates/` – PHP templates rendered for reports and forms.
- `docs/` – Project documentation including [End-to-End Workflow](docs/END_TO_END_WORKFLOW.md) and [Wizard Form & API Flow](docs/WIZARD_FORM_API_FLOW.md).
- `tests/` – Automated tests and diagnostics.
- `vendor/` – Composer-managed third-party libraries (do not modify).

### Core Architecture

- **ROI calculator** – Computes benefit scenarios. [`inc/class-rtbcb-calculator.php`](inc/class-rtbcb-calculator.php)
- **Database & leads management** – Manages plugin tables and lead records. [`inc/class-rtbcb-db.php`](inc/class-rtbcb-db.php), [`inc/class-rtbcb-leads.php`](inc/class-rtbcb-leads.php)
- **API integrations** – Logs and tests external requests. [`inc/class-rtbcb-api-log.php`](inc/class-rtbcb-api-log.php), [`inc/class-rtbcb-api-tester.php`](inc/class-rtbcb-api-tester.php)
- **Workflow tracking** – Records generation steps and diagnostics. [`inc/class-rtbcb-workflow-tracker.php`](inc/class-rtbcb-workflow-tracker.php)
- **RAG engine** – Builds and queries the vendor knowledge index. [`inc/class-rtbcb-rag.php`](inc/class-rtbcb-rag.php)

### Full Directory Tree

```
.
├── AGENTS.md
├── README.md
├── admin
│  ├── AGENTS.md
│  ├── analytics-page.php
│  ├── class-rtbcb-admin.php
│  ├── css
│  │  └── rtbcb-admin.css
│  ├── dashboard-page.php
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
│  ├── END_TO_END_WORKFLOW.md
│  ├── REPOSITORY_STRUCTURE.md
│  └── TEST_DASHBOARD_FLOW.md
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
│     ├── chart.js
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
│  ├── fast-report-template.php
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

Development guidelines are defined in `AGENTS.md` files. The root `AGENTS.md` outlines global
WordPress standards and test commands, while directories such as `admin/` and `docs/` include
their own `AGENTS.md` with additional rules.

## 🤖 AI Coding Guidelines

- Follow WordPress PHP coding standards with four-space indentation.
- Prefix global functions with `rtbcb_` and classes with `RTBCB_` (`RTBCB_Admin`, `RTBCB_Public`, etc.).
- Sanitize and escape all input and output; wrap user-facing text in translation functions using the `rtbcb` text domain.
- Do not edit files under `vendor/`.
- Run PHP linting on modified PHP files:
  ```bash
  find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
  ```

## 📄 HTML Reports

- By default the plugin renders reports with `templates/comprehensive-report-template.php` when comprehensive analysis is enabled and assets exist.
- If the comprehensive template or its CSS is missing, rendering falls back to `templates/report-template.php`.
- Fast-mode reports use `templates/fast-report-template.php` for minimal output.
- After form submission, `RTBCB_Router` returns the selected template HTML via AJAX as `report_html`.
- `public/js/rtbcb.js` injects this HTML into `#rtbcb-report-container` for immediate viewing.
- The workflow visualizer log records the template used for each submission.
- Reports are not saved as files; only lead metadata is stored in the database.
-- Reports are viewable on the website; export and print options have been removed.

## 🎛️ Admin Dashboard Features

### Main Dashboard
- **System Status**: OpenAI API (configuration and key format), Portal Integration, RAG Index health
- **Key Metrics**: Total leads, recent activity, average ROI
- **Recent Leads**: Latest form submissions with quick actions
- **Quick Actions**: Test API, rebuild index

### Leads Management
- **Advanced Filtering**: Search by email, category, date range, company size
- **Bulk Actions**: Delete multiple leads
- **Individual Views**: Detailed lead information with submission history

### Analytics & Reporting
- **Lead Generation Trends**: Monthly charts with volume and ROI data
- **Category Distribution**: Visual breakdown of recommended solutions
- **Company Size Analysis**: Market segment insights
- **ROI Benchmarking**: Average projections across all leads

### Settings & Configuration
- **API Configuration**: OpenAI models and authentication
- **ROI Assumptions**: Labor costs, efficiency rates, fee baselines
- **Portal Integration**: Real Treasury portal connectivity

## 🔧 Technical Architecture

### Core Components

**Category Recommendation Engine (`RTBCB_Category_Recommender`)**
- Multi-factor scoring algorithm
- Company size, complexity, and pain point analysis
- Confidence scoring and alternative suggestions

**HTML Report Rendering (`RTBCB_Router`)**
 - Uses `templates/comprehensive-report-template.php` by default and falls back to `templates/report-template.php`
 - Returns sanitized HTML for inline display
 - No external rendering dependencies

**Lead Tracking System (`RTBCB_Leads`)**
- Complete audit trail of user interactions
- UTM tracking for marketing attribution
- Advanced querying and analytics capabilities

**Enhanced LLM Integration (`RTBCB_LLM`)**
- Intelligent model routing based on complexity
- RAG integration for contextual responses
- Structured output formatting for consistency

### Database Schema

**Leads Table Structure:**
```sql
CREATE TABLE wp_rtbcb_leads (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    email varchar(255) NOT NULL,
    company_size varchar(50),
    industry varchar(50),
    hours_reconciliation decimal(5,2),
    hours_cash_positioning decimal(5,2),
    num_banks int(3),
    ftes decimal(4,1),
    pain_points longtext,
    recommended_category varchar(50),
    roi_low decimal(12,2),
    roi_base decimal(12,2),
    roi_high decimal(12,2),
    ip_address varchar(45),
    user_agent text,
    utm_source varchar(100),
    utm_medium varchar(100),
    utm_campaign varchar(100),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email_unique (email)
);
```

### API Integration

**OpenAI Models Used:**
- **GPT-5-mini**: Fast responses for simple categorization
- **GPT-5**: Complex business case generation
- **text-embedding-3-small**: RAG search and similarity matching

**Intelligent Model Routing:**
The system automatically selects the appropriate model based on:
- Request complexity (number of pain points, company size)
- Context requirements (amount of RAG data needed)
- Performance requirements (speed vs. quality trade-offs)

## 📊 ROI Calculation Methodology

### Core Assumptions
- **Labor Cost**: $100/hour (fully loaded)
- **Efficiency Gains**: 20-40% reduction in manual tasks
- **Error Reduction**: 20-30% decrease in costly mistakes
- **Bank Fee Optimization**: 5-12% reduction through better positioning

### Calculation Components

**1. Labor Cost Savings**
```
Annual Savings = (Weekly Hours Saved × 52 weeks × $100/hour)
Efficiency Rate = 30% ± 6% (scenario dependent)
```

**2. Bank Fee Reduction**
```
Annual Savings = (Number of Banks × $15,000 baseline × 8% reduction rate)
```

**3. Error Prevention Value**
```
Annual Value = Company Size Factor × Error Cost Baseline × 25% reduction
Size Factors: <$50M=$25k, $50M-$500M=$75k, $500M-$2B=$200k, >$2B=$500k
```

### Scenario Modeling
- **Conservative (80% confidence)**: 80% of base case assumptions
- **Base Case (100% confidence)**: Industry benchmark assumptions
- **Optimistic (120% confidence)**: 120% of base case assumptions

## 🎨 Customization Options

### Styling and Branding
The plugin includes CSS custom properties for easy theming:
```css
:root {
    --primary-purple: #7216f4;
    --primary-purple-light: #a78bfa;
    --secondary-purple: #8f47f6;
    --light-purple: #c77dff;
    --dark-text: #281345;
    --gray-text: #4b5563;
    --neutral-200: #e5e7eb;
    --success-green: #10b981;
}
```

### Custom Templates
Override templates by creating files in your theme:
```
/wp-content/themes/your-theme/rtbcb/business-case-form.php
/wp-content/themes/your-theme/rtbcb/report-template.php
```

### Hooks and Filters
```php
// Modify ROI assumptions
add_filter('rtbcb_roi_assumptions', function($assumptions) {
    $assumptions['labor_cost_per_hour'] = 120;
    return $assumptions;
});

// Customize category scoring
add_filter('rtbcb_category_scores', function($scores, $inputs) {
    // Custom scoring logic
    return $scores;
}, 10, 2);

```

## 📈 Analytics and Reporting

### Key Metrics Tracked
- **Lead Volume**: Daily, weekly, monthly submissions
- **Category Distribution**: Which solutions are most commonly recommended
- **Company Segmentation**: Enterprise vs. SMB lead breakdown
- **ROI Trends**: Average projected returns over time
- **Geographic Analysis**: Lead sources by location (IP-based)
- **Conversion Funnels**: Form completion rates by step

### Export Capabilities
- **CSV Export**: Complete lead data with filters
- **Date Range Filtering**: Custom time periods
- **UTM Attribution**: Marketing campaign performance
- **ROI Benchmarking**: Industry comparison data

## 🔒 Security and Privacy

### Data Protection
- **Email Encryption**: Sensitive data stored with encryption
- **GDPR Compliance**: Built-in consent mechanisms
- **Data Retention**: Configurable retention policies
- **Access Controls**: Role-based permissions

### Security Features
- **Nonce Verification**: All AJAX requests protected
- **Input Sanitization**: Comprehensive data validation
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: All output properly escaped

## 🧪 Testing and Quality Assurance

### Testing Dashboard
Use the unified testing dashboard to verify each report component. Begin by
clicking **Run All Tests** to execute the complete suite; individual test tools
will appear afterward if needed. This dashboard replaces individual test pages
from earlier versions. Navigate to **Real Treasury → Test Dashboard** in the
WordPress admin to access it. The **Connectivity Tests & Status** section
consolidates OpenAI, portal, and RAG index checks—removing the standalone Data
Health page. The page is restricted to users with the `manage_options`
capability. AJAX actions from the dashboard require nonces such as
`rtbcb_test_company_overview`, `rtbcb_test_estimated_benefits`, and
`rtbcb_test_dashboard` when saving results. A visual overview of the end-to-end
reporting flow is available in [docs/TEST_DASHBOARD_FLOW.md](docs/TEST_DASHBOARD_FLOW.md).

### Automated Tests
Run `composer install` before executing `tests/run-tests.sh`. This installs `phpunit/phpunit` in `vendor/bin/phpunit`, which the test script relies on.
The plugin includes integration tests for all major components. These can be run programmatically:
```php
// Run integration tests
$results = RTBCB_Tests::run_integration_tests();
```

#### Configuring the Test Model
JavaScript and PHP tests read the OpenAI model from the `RTBCB_TEST_MODEL` setting so they stay aligned with plugin configuration.

1. **.env file** – Create a `.env` file in the project root:
   ```
   RTBCB_TEST_MODEL=gpt-5-mini
   ```
2. **CLI flag** – Prefix test commands with the variable:
   ```
   RTBCB_TEST_MODEL=gpt-5-mini tests/run-tests.sh
   ```
3. **Config file** – Alternatively, add `tests/test-config.json`:
   ```json
   { "model": "gpt-5-mini" }
   ```

If no configuration is provided, tests default to `gpt-5-test`.

### Runtime Debugging
For manual verification during development, `debug_ajax_handler()` includes runtime checks for common setup issues:

- **Leads Table Test**
  ```php
  global $wpdb;
  $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}rtbcb_leads'" );
  ```
  Use this query to confirm the `rtbcb_leads` table exists.

- **Missing API Key Logging**
  ```php
  if ( ! rtbcb_has_openai_api_key() ) {
      error_log( 'rtbcb_openai_api_key option is empty' );
  }
  ```
  This logs a warning when the OpenAI API key is not set.

`debug_ajax_handler()` executes these checks automatically for runtime verification.

### Performance Monitoring
- **Database Query Optimization**: Indexed tables for fast searches
- **Caching Integration**: Compatible with WordPress caching plugins
- **Asset Optimization**: Minified CSS/JS for production
- **Lazy Loading**: Assets only loaded when shortcode is present

## 📞 Support and Documentation

### Getting Help
1. **Plugin Documentation**: Complete feature documentation in `readme.txt`
2. **WordPress Support Forums**: Community support and troubleshooting
3. **GitHub Issues**: Bug reports and feature requests
4. **Real Treasury Support**: Priority support for enterprise users
5. **Repository Structure**: Directory layout and coding guidelines in [docs/REPOSITORY_STRUCTURE.md](docs/REPOSITORY_STRUCTURE.md)

### Common Issues

**Q: OpenAI API calls return authentication errors**
A: Verify your API key is correct and has sufficient credits in your OpenAI account.

**Q: Charts not displaying in analytics**
A: The analytics dashboard relies on Chart.js for visualizations. The plugin bundles this library locally to avoid ad blockers, but strict privacy extensions may still block it. Ensure your browser allows the plugin's scripts so charts can render correctly.

**Q: Lead data not saving**
A: Check database permissions and ensure tables were created during plugin activation.

### Troubleshooting

If you encounter `Unchecked runtime.lastError` messages, they typically originate from WordPress.com scripts or browser extensions. Allow third-party cookies or disable the offending extension to resolve the issue.

### Performance Optimization
- **Caching**: Use object caching for repeated calculations
- **CDN Integration**: Host assets on CDN for faster loading
- **Database Optimization**: Regular cleanup of old lead data
- **API Rate Limiting**: Built-in throttling for OpenAI requests

### Server Timeouts

Large reports can take several minutes to generate. The plugin defaults to a 300-second API request timeout, configurable up to 600 seconds in the settings. Increase server and PHP timeout limits to avoid gateway errors. In nginx, raise `proxy_read_timeout` and `fastcgi_read_timeout`:

```nginx
location ~ \.php$ {
    proxy_read_timeout 600;
    fastcgi_read_timeout 600;
}
```

Reload nginx after updating the configuration. For PHP-FPM, set `request_terminate_timeout` to a matching value:

```ini
request_terminate_timeout = 600
```

Restart PHP-FPM to apply the change. See [docs/timeout-config.md](docs/timeout-config.md) for full instructions.

## 🛣️ Roadmap and Future Features

### Version 2.2 (Coming Soon)
- **Multi-language Support**: Internationalization for global users
- **Advanced Integrations**: Salesforce, HubSpot, and CRM connectivity
- **White-label Options**: Complete branding customization
- **API Endpoints**: REST API for external integrations

### Version 2.3 (Planned)
- **Machine Learning**: Improved recommendation accuracy
- **A/B Testing**: Form optimization capabilities
- **Advanced Reporting**: Executive dashboard templates
- **Mobile App**: Companion mobile application

## 📄 License and Credits

**License**: GPL v2 or later
**Author**: Real Treasury Team
**Contributors**: Treasury technology experts and WordPress developers

### Third-party Libraries
- **Chart.js**: Data visualization (MIT License)
- **WordPress**: Core framework (GPL License)

---

**Ready to transform your treasury technology evaluation process?** Install the Real Treasury Business Case Builder today and start generating data-driven business cases that drive real results.

For enterprise features, custom development, or priority support, contact the Real Treasury team at [contact@realtreasury.com](mailto:contact@realtreasury.com).

