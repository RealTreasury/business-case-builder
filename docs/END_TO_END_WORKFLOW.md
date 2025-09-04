# End-to-End Workflow

This document summarizes the workflow from internal company research to
delivering the public report. It reflects the current implementation in the LLM
classes and related components. For a detailed walkthrough of the public wizard
and API interactions, see
[Wizard Form & API Flow](WIZARD_FORM_API_FLOW.md).

## 1. Admin Research (Internal)

- Admins can generate a company overview through the test dashboard.
- Uses `RTBCB_LLM::generate_company_overview()` to call the configured GPT model
  with a strict JSON schema for analysis, recommendations, references, and key
  metrics.
- See `inc/class-rtbcb-llm.php` for the system and user prompts that drive this
  call.

## 2. User Form Submission (Public)

- A public user completes the multi-step form rendered by
  `templates/business-case-form.php`.
- Input data is sanitized and stored for downstream processing.

## 3. Business Case Generation

- `RTBCB_LLM::generate_business_case()` combines sanitized user inputs with ROI
  data and optional RAG context.
- The LLM returns JSON with executive summaries, operational insights,
  industry insights, and financial analysis blocks.

## 4. Category Recommendation

- The plugin categorizes the user's challenges without an LLM and refines ROI.
- `RTBCB_Category_Recommender::recommend_category()` scores the input against
  predefined categories and returns a recommendation with reasoning and
  confidence.
- `RTBCB_Calculator::calculate_category_refined_roi()` recalculates ROI using
  the recommended category.

## 5. Final Report Assembly

- Outputs from the LLM and category recommender are merged with ROI
  calculations.
- The result is rendered via `templates/comprehensive-report-template.php` and
  enhanced in `public/js/rtbcb-report.js` before being shown to the user.
