# ACF Component Builder

A rendering and template layer on top of **Advanced Custom Fields PRO**. ACF
stays the field/data layer; this plugin turns your Flexible Content layouts into
reusable, theme-overridable, visually-editable sections.

- **Version:** 1.3.0
- **Requires:** WordPress 6.0+, PHP 8.0+, ACF PRO (the plugin degrades gracefully
  with an admin notice if ACF PRO is absent — it never fatals).

```
ACF Fields → Component → Template → Layout (Bootstrap/Flex/Grid) → Classes → HTML
```

## Install

1. Install & activate **ACF PRO**.
2. Copy `acf-component-builder/` into `wp-content/plugins/` and activate it.
3. Go to **ACF Components → Import ACF JSON** and upload your field-group export.
4. Render anywhere: `echo ACB::render_flexible('services_inner_detail_section');`

Components are auto-discovered from your ACF Flexible Content fields, so they
render out of the box with zero manual setup.

## What's included (by phase)

**Phase 1 — Rendering core**
Auto-discovery of components from ACF, a context-aware renderer (top-level vs.
`get_sub_field()` loops, nested repeaters/groups), three layout engines
(Bootstrap / Flexbox / CSS Grid), a field-renderer registry, a theme-override
resolution chain, an auto template generator, and a safe ACF JSON importer.
Public API: `acf_component()`, `ACB::render()`, `ACB::render_flexible()`,
`acf_component_flexible()`, `acf_component_layout()`, and the `[acf_component]`
shortcode.

**Phase 2 — Visual builder**
A Template Manager plus a three-pane visual builder (structure tree, per-node
inspector, live device-width preview) with responsive controls for every engine.
Templates are saved as structured JSON and automatically take priority over the
auto-generated markup.

**Phase 3 — Workflow**
Drag-and-drop reordering/nesting, a reusable **block library** (save any subtree,
insert it anywhere), template **import/export** as portable JSON, and **version
history** with restore.

## Architecture at a glance

- **No custom database tables.** Configuration lives in options, a transient
  cache, and three private CPTs (`acb_component`, `acb_template`, `acb_block`)
  with JSON in post meta.
- **One security boundary.** All builder/library/import/restore input flows
  through `Node_Sanitizer`, which whitelists every node type, setting, and value.
  Saved templates are *data*, never executable PHP.
- **On-demand assets.** Frontend CSS loads only when a component actually renders.
- **Extensible.** Filters for field renderers (`acb_field_renderers`), layout
  engines (`acb_layout_engines`), template resolution (`acb_component_template`),
  and field values (`acb_field_value`); actions around booting and rendering.

## Documentation

See the `documentation/` folder:

| File | Topic |
|------|-------|
| `getting-started.md` | Install & first render |
| `developer-api.md` | Facade, helpers, shortcode, hooks |
| `components.md`, `templates.md` | Core concepts |
| `layouts.md` (+ `bootstrap.md`, `flex.md`, `grid.md`) | Layout engines |
| `theme-overrides.md` | Overriding markup from your theme |
| `repeater-fields.md` | Loop/scope handling |
| `visual-builder.md` | Phase 2 builder & responsive controls |
| `blocks-versioning-portability.md` | Phase 3 drag/drop, blocks, import/export, versions |
| `architecture.md` | Storage decisions |
| `troubleshooting.md` | Common issues |

## Uninstall

Removing the plugin deletes its options, cache, and the three private CPTs. It
**never** deletes your ACF field groups or field data.
