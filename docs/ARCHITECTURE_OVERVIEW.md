# Architecture Overview

This document outlines the primary components of Real Treasury Business Case
Builder.

## Bootstrap

- `RTBCB_Main` (in `real-treasury-business-case-builder.php`) defines constants,
    loads files under `inc/`, and registers hooks.
- Shortcodes, scripts, and AJAX endpoints are registered during initialization.

## Request Flow

1. `[rt_business_case_builder]` renders a multi-step wizard.
2. `public/js/rtbcb-wizard.js` submits sanitized form data to
    `RTBCB_Ajax::generate_comprehensive_case()`.
3. `RTBCB_Background_Job` queues the task.
4. `RTBCB_Ajax::process_comprehensive_case()` orchestrates ROI calculations,
    category recommendations, RAG retrieval, and LLM requests.
5. The assembled report is rendered with
    `templates/comprehensive-report-template.php` and enhanced by
    `public/js/rtbcb-report.js`.

## Major Classes

- `RTBCB_Ajax` – AJAX handlers and background processing.
- `RTBCB_Calculator` / `RTBCB_Enhanced_Calculator` – ROI scenarios and sensitivity
    analysis.
- `RTBCB_Category_Recommender` – rule-based technology category mapping.
- `RTBCB_LLM_Unified` – OpenAI client wrapper using `RTBCB_Response_Parser` and
    `RTBCB_Response_Integrity`.
- `RTBCB_RAG` – management of the retrieval index and snippet lookups.
- `RTBCB_Workflow_Tracker` – step-level diagnostics for debugging and analytics.
- `RTBCB_Leads` and `RTBCB_DB` – database table management.
- `RTBCB_API_Log` and `RTBCB_Logger` – logging of external requests.
- `RTBCB_Intelligent_Recommender` – vendor suggestions based on collected data.

## Admin Pages

- Settings (`admin/settings-page.php`)
- Leads (`admin/leads-page-enhanced.php`)
- Reports (`admin/reports-page.php`)
- Analytics (`admin/analytics-page.php`)
- API Logs (`admin/api-logs-page.php`)
- Workflow Visualizer (`admin/workflow-visualizer-page.php`)
- Test Dashboard (`admin/test-dashboard-page.php`)

Each page verifies capabilities, escapes output, and enqueues scripts from
`admin/js/`.

## Assets and Templates

- Front-end assets are located in `public/css` and `public/js`.
- Templates in `templates/` generate the wizard and final reports.
- `RTBCB_Workflow_Tracker` outputs data for the workflow visualizer.

