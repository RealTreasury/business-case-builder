# Documentation Contribution Guidelines

Follow these instructions for files inside `docs/`.

## Markdown Style

- Use Title Case for headings.
- Leave one blank line after headings and between paragraphs.
- Use `-` for unordered lists and incrementing numbers for ordered lists.
- Keep lines under 120 characters when practical; do not force line wraps in the middle of sentences.
- Add language identifiers to fenced code blocks.

## Linting and Link Checking

Run the following commands from the repository root after modifying documentation:

```bash
npx --yes markdownlint-cli@latest "docs/**/*.md" --disable MD013
npx --yes markdown-link-check@latest docs/<file>.md
```

`markdownlint-cli` enforces style rules with the long-line rule disabled (`MD013`).
`markdown-link-check` verifies that all links resolve.

## Additional Standards

- Store documentation in the `docs/` directory.
- Prefer relative links to other documents in this repository.
- Keep code examples minimal and focused on illustrating behavior.
