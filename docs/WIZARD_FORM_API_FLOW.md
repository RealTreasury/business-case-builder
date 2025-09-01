# Wizard Form & API Flow

## Wizard Steps and Fields

1. **Company**
   - `company_name`
   - `company_size`
   - `industry`
2. **Operations**
   - `hours_reconciliation`
   - `hours_cash_positioning`
   - `num_banks`
   - `ftes`
3. **Challenges**
   - `pain_points[]` (multiple choice)
4. **Strategy**
   - `current_tech`
   - `business_objective`
   - `implementation_timeline`
   - `decision_makers[]`
   - `budget_range`
5. **Contact**
   - `email`
   - `consent`

Field definitions come from `templates/business-case-form.php` and the field registry in `public/js/rtbcb-wizard.js`.

## AJAX Submission

`public/js/rtbcb-wizard.js` serializes the form and posts it to WordPress’s AJAX endpoint with the action `rtbcb_generate_case`. On success, the returned report data is rendered in the page; otherwise an error overlay is displayed.

## Server-side Processing

`RTBCB_Main::ajax_generate_comprehensive_case()` validates the request and orchestrates report generation:

1. **ROI Calculation** – `RTBCB_Calculator::calculate_roi()` builds conservative, base, and optimistic scenarios.
2. **Category Recommendation & ROI Refinement** – `RTBCB_Category_Recommender::recommend_category()` scores the selected challenges to suggest a treasury solution type, then `RTBCB_Calculator::calculate_category_refined_roi()` recalculates ROI based on that category.
3. **RAG Search** – `RTBCB_RAG::search_similar()` retrieves supporting context using the company profile and pain points.
4. **OpenAI Call** – `RTBCB_LLM::generate_comprehensive_business_case()` combines user inputs, ROI data, and RAG context to produce narrative analysis.
5. **Report Assembly** – `get_comprehensive_report_html()` renders the final HTML which is returned in the AJAX response.

## End-to-End Flow

```mermaid
sequenceDiagram
    participant U as User
    participant JS as Wizard JS
    participant WP as ajax_generate_comprehensive_case
    participant ROI as Calculator
    participant Cat as Recommender
    participant RAG as RAG
    participant LLM as OpenAI
    participant HTML as Report

    U->>JS: Complete wizard
    JS->>WP: POST rtbcb_generate_case
    WP->>ROI: calculate_roi()
    WP->>Cat: recommend_category()
    WP->>ROI: calculate_category_refined_roi()
    WP->>RAG: search_similar()
    WP->>LLM: generate_comprehensive_business_case()
    LLM-->>WP: analysis
    WP->>HTML: get_comprehensive_report_html()
    WP-->>JS: JSON report
    JS-->>U: Display results
    ```
