# urlcv/empathy-checker

Rules-based **Empathy Checker** for hiring and workplace messages. Ships as a Laravel package with a Blade + Alpine.js frontend — **no LLM**, **no external APIs**, **no server-side text processing**.

> **Live tool:** [urlcv.com/tools/empathy-checker](https://urlcv.com/tools/empathy-checker)  
> Part of **[URLCV](https://urlcv.com)** — recruitment tools and candidate presentation.

---

## What it does

Paste rejection emails, interview invites, follow-ups, or internal HR notes. In the browser you get:

- Empathy score (0–100) and a **tone** label (cold, neutral, professional, warm, overly casual)
- Five dimensions: **acknowledgement**, **clarity**, **respect**, **warmth**, **candidate focus**
- Positive / negative / vague phrase detection (extendable lists in the Blade script)
- **Missing empathy** checklist (thank-you, timeline, next steps, etc.)
- **Anxiety triggers** (vague timelines, abrupt endings, passive wording)
- Deterministic **suggestions** and optional **phrase highlighting** in a preview

---

## Requirements

- PHP **8.1+** (for the Laravel adapter)
- Laravel app with the URLCV tools system (`ToolInterface`, `config/tools.php`)

---

## Installation (Laravel)

**From GitHub (after the package repo exists):**

```bash
composer require urlcv/empathy-checker
```

Register the tool class in `config/tools.php`:

```php
\URLCV\EmpathyChecker\Laravel\EmpathyCheckerTool::class,
```

Then:

```bash
php artisan tools:sync
php artisan sitemap:generate
```

**Local path (development):**

```json
"repositories": [
    { "type": "path", "url": "packages/tool-empathy-checker" }
],
"require": {
    "urlcv/empathy-checker": "@dev"
}
```

```bash
composer update urlcv/empathy-checker
php artisan tools:sync
```

---

## Usage

The package exposes a Blade view `empathy-checker::empathy-checker` via `EmpathyCheckerTool::frontendView()`. The main app’s tool page includes this view for slug `empathy-checker`.

All analysis runs in **`analyzeEmpathyMessage()`** in `resources/views/empathy-checker.blade.php` (inside `@push('scripts')`). Adjust phrase lists in the `PHRASES` object to tune detection without changing PHP.

---

## How scoring works (MVP)

1. **Baseline** — Each dimension starts from a mid-range score and moves up or down using **phrase hits**, **structure** (length, opening line), and simple **ratios** (e.g. you/your vs we/our).
2. **Overall score** — Average of the five dimension scores (rounded).
3. **Tone** — Heuristic mix of casual markers, cold vs warm phrase counts, and warmth/acknowledgement levels (not a separate ML model).
4. **Highlights** — Character buffer: positive phrases → green, vague → amber, harsh → red; **per-character max severity** wins so overlaps resolve deterministically.

This is intentionally **readable and editable** — treat it as a lint-style helper, not a psychological assessment.

---

## Licence

MIT
