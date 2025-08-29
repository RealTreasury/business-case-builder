# AI Test Guidelines

- Before committing changes, run `bash tests/run-tests.sh` from the repository root.
- The `run-tests.sh` script performs:
    - PHP linting and unit tests via `phpunit`.
    - JavaScript tests using Node.js.
- Ensure the following environment variables are set when running tests:
    - `OPENAI_API_KEY`
    - `RTBCB_OPENAI_API_KEY`
    - `RTBCB_TEST_MODEL`
    - Any other variables required by the test suite.
