# Layout Engines

Set the default engine in **Settings**, or per template via the JSON
`layout_engine` key (`bootstrap` | `flex` | `grid`).

## Bootstrap

Column settings map to responsive classes:

```json
{ "type": "column", "settings": { "xs": 12, "md": 6, "lg": 6 } }
```
→ `class="col-12 col-md-6 col-lg-6"`

Row settings: `gutter` (0–5), `justify`, `align`. Column extras: `offset_md`,
`order`, `align_self`.

## Flexbox

Row `settings`: `direction`, `wrap`, `justify`, `align`, `gap`. Item settings:
`grow`, `basis`. Emitted as inline styles on `.acb-flex` / `.acb-flex-item`
so no framework is required.

## CSS Grid

Row `settings`: `columns` (desktop), `tablet`, `mobile`, `gap`, `column_gap`,
`row_gap`, `align`. Responsive column counts drive CSS custom properties read
by media queries in `frontend.css`.
