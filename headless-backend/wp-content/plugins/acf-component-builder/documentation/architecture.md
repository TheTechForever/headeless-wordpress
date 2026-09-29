# Architecture & Storage Decision

## Layers

```
ACF (fields/values)
  → Component_Registry (discovers components from ACF)
    → Template_Resolver (theme override chain → auto-generate)
      → Renderer (walks node tree / includes PHP template)
        → Layout engine (Bootstrap / Flex / Grid)
          → Field renderers (text, image, wysiwyg, link, gallery, repeater, group)
            → Safe HTML
```

## Storage

No custom database tables. We use:

- **Options** (`acb_settings`) for plugin configuration.
- **Transient** (`acb_discovered_components`) to cache discovery.
- **Custom Post Types** `acb_component` and `acb_template` (both **private**,
  `show_ui=false`) to persist user-authored components/templates. Config is
  stored as JSON in post meta (`_acb_config`, `_acb_layout`).

Why CPTs over a bespoke table: we get authors, dates, revisions, capabilities,
and native export for free, and avoid schema migrations. ACF remains the actual
content layer — ACB only stores *presentation* configuration.

Template configuration is always **structured JSON**, never executable PHP, so
imports can never inject code.
