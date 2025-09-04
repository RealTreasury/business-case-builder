# LLM Response Handling on WordPress.com

`RTBCB_LLM` generates model responses and `RTBCB_Response_Parser` validates the
returned content. When deploying this plugin on WordPress.com, developers must account for
platform-specific credential storage and hosting constraints.

## Generating Responses with RTBCB_LLM

- `RTBCB_LLM` boots with configuration, prompt, transport, and parser helpers.
- The constructor fetches the OpenAI key from `RTBCB_LLM_Config`, which pulls from the
    `RTBCB_OPENAI_API_KEY` environment variable or the `rtbcb_openai_api_key` option.
- Prompts are assembled by `RTBCB_LLM_Prompt` and sent via `RTBCB_LLM_Transport` to the
    OpenAI API.
- Raw responses pass to `RTBCB_Response_Parser::process_openai_response()` for
    cleanup and JSON extraction.

See [End-to-End Workflow](END_TO_END_WORKFLOW.md) and
[Wizard Form & API Flow](WIZARD_FORM_API_FLOW.md) for how these calls fit into the
overall report generation process.

## Parsing Responses on WordPress.com

- `RTBCB_Response_Parser` strips BOM characters, normalizes encoding, and attempts
    a standard `json_decode()`.
- If decoding fails, the parser searches for fenced JSON blocks, mixed content, or
    streaming chunks before giving up.
- The parser returns arrays when valid JSON is found or a raw string otherwise, enabling
    the calling code to handle partial failures gracefully.

## WordPress.com Deployment Notes

- Store the OpenAI API key as a secret environment variable (`RTBCB_OPENAI_API_KEY`) in
    your WordPress.com dashboard or via the `rtbcb_openai_api_key` option. Avoid committing
    keys to the repository.
- WordPress.com uses a shared hosting model. Ensure outbound HTTP requests to OpenAI are
    allowed and that the site’s plan includes external API access.
- Server-sent events are blocked on WordPress.com. `RTBCB_Ajax::stream_analysis` and
    `rtbcb_proxy_openai_responses` detect this hosting and return a
    `streaming_unsupported` error so clients can fall back to polling.
- Responses may include unexpected prefixes or truncated streaming data. Monitor logs for
    `RTBCB` entries to catch parsing warnings or errors.

## Common Pitfalls

- Missing API credentials will cause `RTBCB_LLM` to log an error and return failures.
- Non‑UTF‑8 responses or leading BOM markers can break parsing if the parser’s cleanup
    steps are bypassed.
- Overly long outputs might exceed the configured token limit, producing incomplete JSON
    that the parser cannot recover.

## References

- [`RTBCB_LLM`](../inc/class-rtbcb-llm.php)
- [`RTBCB_Response_Parser`](../inc/class-rtbcb-response-parser.php)
- [End-to-End Workflow](END_TO_END_WORKFLOW.md)
- [Wizard Form & API Flow](WIZARD_FORM_API_FLOW.md)
