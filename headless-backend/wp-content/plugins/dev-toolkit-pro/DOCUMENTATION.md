# Dev Toolkit — Documentation

A two-plugin system that turns your WordPress build process into repeatable actions and QA checks.

- **Dev Toolkit (Basic)** — free, standalone.
- **Dev Toolkit Pro** — add-on (requires Basic) with QA, design comparison, AI review and theme tooling.

---

## Installation

1. In WordPress admin go to **Plugins → Add New → Upload Plugin**.
2. Upload `dev-toolkit.zip` and activate it.
3. Upload `dev-toolkit-pro.zip` and activate it (Basic must be active first).
4. Go to **Dev Toolkit → Pro Licence** and enter a key in the format `DTP-XXXX-XXXX-XXXX` to unlock Pro features.

> The demo licence only checks the key *format*. Before selling Pro, replace the check in `class-dtp-license.php → sanitize()` with a call to your real licensing service.

---

## Recommended workflow

1. **Setup & Audit** — toggle site defaults, then **Apply one-click setup** (comments off, media sizes 0, timezone).
2. **Common Fields** — enter email / phone / address / socials once; use the shortcodes everywhere.
3. **Child Theme** (Pro) — generate a child theme for your parent theme.
4. **CSS Boilerplate** (Pro) — inject the `.container` rules; copy the media-query stubs into your stylesheet.
5. **Plugin Stack** (Pro) — install the baseline plugins you actually need.
6. **Design QA** (Pro) — add one reference per page, then **Compare**.
7. **Page Audit** (Pro) — run on-page checks, PageSpeed and the responsive overflow check.
8. **AI review** (Pro) — get a prioritised fix-list and suggested CSS.

---

## Shortcodes (Basic)

| Shortcode | Output |
|---|---|
| `[dt_email]` | mailto link (`link="false"` for plain text) |
| `[dt_phone]` | tel: link |
| `[dt_address]` | address with line breaks |
| `[dt_social]` | `<ul>` of social links, open in new tab |
| `[dt_year start="2019"]` | dynamic year, e.g. `2019–2026` |

---

## Feature reference

### Basic
- **One-click setup** — writes `default_comment_status`, media size options and `timezone_string`; closes comments on existing content.
- **Content filters** — external links get `target="_blank" rel="noopener"`; bare emails become mailto links.
- **Accessibility** — aria-labels added to common slider prev/next arrows.
- **Mobile phone bar** — optional fixed tel: bar at the bottom on small screens.
- **Export** — CSV inventory of Pages, Posts, CPTs, Plugins, Themes (Step 2 backup).
- **Build audit** — pass/warn/fail on comments, media sizes, timezone, permalinks, child theme, 404 template, contact fields, search visibility.

### Pro
- **Form validation** — add `data-dt-validate` to a form; `required`, `type="email"`, `data-dt-password`, `data-dt-match="field"` are enforced (Step 9). A reusable server-side `DTP_Form_Validation::validate()` is included.
- **CSS boilerplate injector** — the exact container rules (Step 8) + fixed header, plus copy-paste media-query stubs (Step 7).
- **Child-theme generator** — creates `style.css` + `functions.php` that enqueues the parent stylesheet.
- **Plugin stack installer** — one-click installs for the standard set (CF7, SCF, Wordfence, Rank Math, etc.).
- **Page Audit** — H1 count, viewport meta, title/description, images without alt, links with no discernible name (slider arrows), external-link targets, mixed content; PageSpeed Insights (Mobile 65+ / Desktop 70+); responsive overflow check across every device width from the doc.
- **Design QA** — upload a design image per page + optional build screenshot; overlay the design on the live page at adjustable opacity; run an AI review.
- **AI review** — uses *your* Anthropic API key; sends design + screenshot + audit findings and returns a fix-list with CSS. Suggestions only — never auto-applied.

---

## Honest limitations

- **No pixel auto-diff of design vs live.** Stock WordPress can't screenshot a live page (no headless browser). Use the **overlay tool** (human-in-the-loop) — this is how design QA is done in practice.
- **`.fig` files can't be parsed** outside Figma. Export frames as PNG/JPG, or pull frame renders via the Figma REST API with a token.
- **PDF→image** needs Imagick/Ghostscript on the server; otherwise upload page images directly.
- **PageSpeed** needs a free Google API key and outbound network access from the server.
- **AI does not edit your files.** It returns suggestions; you apply them deliberately.
- **Layout "looks right", animation smoothness, hover effects, colour distinctness** are visual judgements — kept on the manual checklist by design.

---

## Configuration keys

- **Pro Licence** — `Dev Toolkit → Pro Licence`.
- **AI** — `Dev Toolkit → AI Settings` (Anthropic API key + model string).
- **PageSpeed** — API key field on the `Page Audit` screen.

---

## Developer notes

- Options: `dt_settings`, `dt_common_fields` (Basic); `dtp_license`, `dtp_css`, `dtp_psi_key`, `dtp_ai_key`, `dtp_ai_model` (Pro).
- Design references are a private CPT `dtp_design_ref` with meta `live_url`, `design_id`, `screenshot_id`, `notes`.
- Extend the audit by adding checks in `DTP_Page_Audit::scan_url()`; extend the plugin stack in `DTP_Plugin_Installer::stack()`.
- Pro boots on the `dev_toolkit_loaded` action fired by Basic.
