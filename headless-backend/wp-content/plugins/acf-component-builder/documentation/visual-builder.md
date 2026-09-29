# Visual Template Builder (Phase 2)

The builder lets you customise a component's markup visually and save it as a
template that automatically takes priority over the auto-generated output.

## Where it lives

**ACF Components → Templates**. From there you can:

- start a new template from any component (seeded with its auto-generated tree),
- edit, duplicate, or delete saved templates.

Each component row on **ACF Components → Components** also has a
**Build / Edit template** button.

## The three panes

1. **Structure** — the node tree. Add nodes (Row, Column, Container, HTML,
   Field, Repeater, Group, Spacer, Divider), nest them with **＋**, reorder with
   the up/down arrows, and delete with the trash icon. Click a node to edit it.
2. **Settings** — the inspector for the selected node, including the responsive
   controls below.
3. **Live preview** — the template rendered server-side against a real post you
   pick, at Desktop / Tablet / Mobile widths. The preview uses the exact
   renderer the front end uses, so what you see is what ships.

## Node types

| Node | Purpose |
|------|---------|
| Row / Column | Layout grid (engine-aware — see below). |
| Container | Wraps children in a Bootstrap container. |
| HTML block | A semantic wrapper (`div`, `section`, `article`, …). |
| Field | Renders one ACF field with a chosen renderer. |
| Repeater / Group | Loops or groups sub-fields; children use the sub-field names. |
| Spacer / Divider | Vertical spacing / a horizontal rule. |

## Responsive controls

The inspector adapts to the selected **layout engine**:

- **Bootstrap** — per-breakpoint column widths (`xs`–`xxl`, 1–12), offset,
  order, alignment; rows get gutter/justify/align.
- **Flexbox** — direction, wrap, justify, align, gap on rows; grow / basis /
  align-self on items.
- **CSS Grid** — desktop / tablet / mobile column counts and gap on rows;
  children flow automatically.

All values are whitelisted and clamped server-side by the node sanitizer, so a
saved template can never contain unexpected markup or executable code.

## Field nodes

Pick an ACF field from the component (top-level fields and repeater/group
sub-fields are listed). The renderer is suggested from the ACF field type
(image → image, wysiwyg/textarea → wysiwyg, link/url → link, gallery →
gallery, everything else → text) and can be overridden. Headings expose a tag
selector (`h1`–`h6`, `p`, `span`).

Inside a Repeater or Group node, place Field nodes that reference the row's
**sub-field** names — they resolve with `get_sub_field()` automatically.

## Saving & priority

Saving writes an `acb_template` post (JSON in post meta — no custom tables).
Because a saved template is first in the resolution order, it immediately
overrides the auto-generated markup for that component slug. Delete the template
to fall back to auto-generation. Theme PHP overrides still win over the plugin's
own defaults; see `theme-overrides.md`.

## Live preview notes

- Choose **No content** to preview structure only (useful before a post has data).
- Bootstrap CSS in the preview follows your **Settings → Load Bootstrap** version.
- The preview is sandboxed in an iframe, so page styles never leak in or out.

## Not in Phase 2

Drag-and-drop reordering, a cross-component reusable block library, and template
versioning are Phase 3. Phase 2 uses explicit add/move controls and per-component
templates.
