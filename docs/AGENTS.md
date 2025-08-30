# Documentation Guidelines

- Write documentation in Markdown using a single `#` title at the top.
- Keep lines under 100 characters when possible.
- Use `-` for unordered lists and indent nested items with four spaces.
- Provide language identifiers for fenced code blocks.
- Use relative links for files within this repository.
- Prefer file names in `UPPERCASE_WITH_UNDERSCORES.md`.
- Before committing changes in this directory:
    - Lint Markdown with `npx markdownlint docs/**/*.md`.
    - Check links with `npx markdown-link-check <file>`.
