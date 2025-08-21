# Real Treasury Business Case Builder

Real Treasury Business Case Builder is a WordPress plugin that helps treasury teams estimate the return on investment of treasury technology and generate a concise business case.

## Features

- ROI calculator that models low, base, and high benefit scenarios.
- Generates executive narratives using LLM models.
- Shortcode `[rt_business_case_builder]` renders an interactive form on the front end.
- Admin pages for configuring default assumptions, OpenAI models, viewing leads, and monitoring data health.
- Integrates with the Real Treasury portal for vendor research.

## Installation

1. Upload the plugin to the `wp-content/plugins` directory.
2. Activate **Real Treasury - Business Case Builder** through the WordPress Plugins screen.
3. Go to **Real Treasury → Settings** to enter API keys and adjust ROI assumptions.

## Usage

Insert the `[rt_business_case_builder]` shortcode into any post or page to display the calculator. Users receive estimated annual benefits and a generated narrative summarizing the case for treasury technology.

## Development

This repository contains the plugin source. Do not modify files in the `vendor/` directory.
