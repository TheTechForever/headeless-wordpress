# Getting Started

ACF Component Builder (ACB) is a rendering layer on top of **ACF Pro**. ACF
stays the field/data layer; ACB turns your Flexible Content layouts into
reusable, theme-overridable sections.

## Pipeline

```
ACF Fields → Component → Template → Layout (Bootstrap/Flex/Grid) → Classes → HTML
```

## Install

1. Install & activate **ACF Pro** (required).
2. Copy `acf-component-builder/` into `wp-content/plugins/`.
3. Activate **ACF Component Builder**.
4. Open **ACF Components → Import ACF JSON** and upload your export
   (e.g. `scf-export-2026-08-07.json`).

## Render your first flexible field

```php
<?php echo ACB::render_flexible('services_inner_detail_section'); ?>
```

That single call iterates every row, detects each layout, finds its component,
resolves a template (yours or auto-generated) and outputs safe HTML.

## Zero-config

Components are **auto-discovered** from ACF. You do not have to create anything
in the admin to render — importing the fields is enough.
