# Real Treasury Business Case Builder

Real Treasury Business Case Builder helps treasury teams quantify the value of
modern treasury technology. The plugin combines ROI calculations, category
recommendations, retrieval-augmented generation, and large language models to
produce consultant-style business case reports and analytics.

## Features

- Multi-step wizard rendered with `[rt_business_case_builder]`
- ROI calculator with conservative, base, and optimistic scenarios
- LLM-generated narrative analysis and recommendations
- Retrieval-augmented generation using a local RAG index
- Category recommendation without LLM
- Lead capture with analytics dashboards
- Background job queue and step-level workflow tracking
- API logging and connectivity tester

## Installation

### Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- OpenAI API key

### Steps

1. Upload the plugin to `/wp-content/plugins/real-treasury-business-case-builder/`.
2. Activate the plugin in the WordPress admin.
3. Open **Real Treasury → Settings** to configure defaults.

## Configuration

### OpenAI

- Enter your API key in the settings page or set the `RTBCB_OPENAI_API_KEY`
    environment variable.
- Default models:
    - mini: `gpt-5-mini`
    - premium: `gpt-5`
    - embedding: `text-embedding-3-small`
- Token limits default to `min_output_tokens` **256** and `max_output_tokens`
    **8000**. Override via settings, environment variables
    (`RTBCB_MIN_OUTPUT_TOKENS`, `RTBCB_MAX_OUTPUT_TOKENS`), or an
    `rtbcb-config.json` file.
- `rtbcb_api_timeout` filters the API timeout (default 300 seconds).
- Temperature is disabled for models listed in
    [`inc/model-capabilities.php`](inc/model-capabilities.php).

### Database and Caching

- Activation creates `wp_rtbcb_leads` for lead data and `wp_rtbcb_rag_index`
    for the retrieval index.
- Persistent database connections reduce query overhead. See
    [docs/DATABASE_CONNECTIONS.md](docs/DATABASE_CONNECTIONS.md).
- Configure a persistent object cache such as Redis or Memcached to store API
    results. See [docs/OBJECT_CACHE.md](docs/OBJECT_CACHE.md).

## Usage

### Shortcode
Embed the wizard anywhere with:

```text
[rt_business_case_builder]
```

Optional attributes allow custom titles, subtitles, and styles.

### Admin Tools

- **Settings** – model configuration and API keys.
- **Leads** – captured form submissions.
- **Analytics** – aggregate ROI and engagement metrics.
- **API Logs** – view OpenAI requests and responses.
- **Test Dashboard** – run connectivity and workflow diagnostics.
- **Workflow Visualizer** – inspect step-level processing.

A debug panel is available by appending `?rtbcb_test=1` to an admin URL. It
runs AJAX tests to verify configuration.

## Architecture

The plugin boots through `RTBCB_Main` in
[`real-treasury-business-case-builder.php`](real-treasury-business-case-builder.php).
Core classes include:

- `RTBCB_Ajax` – registers AJAX endpoints and background jobs
- `RTBCB_Calculator` and `RTBCB_Enhanced_Calculator` – ROI and sensitivity analysis
- `RTBCB_Category_Recommender` – maps inputs to technology categories
- `RTBCB_LLM_Unified` – wraps OpenAI calls and uses
    `RTBCB_Response_Parser` and `RTBCB_Response_Integrity`
- `RTBCB_RAG` – manages retrieval index and snippet queries
- `RTBCB_Workflow_Tracker` – captures diagnostics for each generation step
- `RTBCB_Leads` and `RTBCB_DB` – manage custom tables
- `RTBCB_API_Log` and `RTBCB_API_Tester` – logging and connectivity checks
- `RTBCB_Intelligent_Recommender` – provides vendor suggestions

See [docs/ARCHITECTURE_OVERVIEW.md](docs/ARCHITECTURE_OVERVIEW.md) for a
deeper walkthrough.

## Testing

Run the test suite before committing:

```bash
composer install
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
bash tests/run-tests.sh
```

## Additional Documentation
The `docs/` directory includes detailed guides such as the
[End-to-End Workflow](docs/END_TO_END_WORKFLOW.md) and
[Wizard Form & API Flow](docs/WIZARD_FORM_API_FLOW.md).  Repository layout is
described in [docs/REPOSITORY_STRUCTURE.md](docs/REPOSITORY_STRUCTURE.md).

## License
GPL v2 or later. © Real Treasury.

