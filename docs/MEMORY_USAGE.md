# MEMORY_USAGE

The plugin reduces memory usage during LLM calls by streaming chunks directly to handlers and
discarding processed data. Additionally, `RTBCB_Response_Parser::parse()` can skip storing the full raw
payload unless requested, lowering peak memory.

- The LLM client now processes stream chunks incrementally and logs peak memory after each call.
- Pass `true` as the second argument to `RTBCB_Response_Parser::parse()` when raw response details are needed.
- Run `tests/report-memory-usage.test.php` and `tests/parse-gpt5-response-raw-option.test.php` to monitor for regressions.
