# Drag & Drop, Blocks, Import/Export & Versioning (Phase 3)

Phase 3 turns the builder from a form editor into a fast, reusable workflow.

## Drag & drop

Drag any node by its header to reorder or re-nest it:

- Drop on the **top edge** of a node → place it *before* that node.
- Drop on the **bottom edge** → place it *after*.
- Drop on the **middle of a container** (Row, Column, Container, HTML, Repeater,
  Group) → place it *inside* as the last child.

A blue cue shows where the node will land. You cannot drop a node into its own
subtree. The up/down arrow buttons remain as a keyboard-friendly fallback.

## Block library (Component Library)

Any node can be saved and reused across templates.

- **Save a block:** select a node and click the ★ icon in its header. Give it a
  name (and optional category / description). The whole subtree is stored.
- **Insert a block:** open **Block library** in the sub-toolbar. Choose *Insert*
  to drop the block into the currently-selected container, or at the end of the
  template if nothing is selected.
- **Manage:** delete blocks you no longer need from the same dialog.

Blocks are stored as a private `acb_block` post type — no custom tables — and are
sanitised on save and on insert, so a reused block is always safe markup.

## Import / Export

Templates are portable between sites.

- **Export** downloads a JSON envelope (`acb-template-<slug>.json`) containing the
  title, component slug and node tree. Save the template first.
- **Import** accepts that JSON (file picker or paste) and creates a **new**
  template, then opens it in the builder. Imported trees are re-sanitised, so an
  export from an untrusted source cannot introduce unsafe markup.

The envelope is versioned (`"acb":"template","version":"…"`), which lets future
releases migrate older exports.

## Version history

Every time you save, the *previous* saved tree is snapshotted automatically. Open
**Version history** to see the last 20 snapshots with their timestamp and author,
and **Restore** any of them. A restore is itself snapshotted first, so it is
always reversible. Snapshots are capped to keep post meta lean and are removed
with the template when it is deleted.

## Data & cleanup

Everything Phase 3 adds lives in post meta or the `acb_block` CPT. Uninstalling
the plugin removes the block CPT, templates, settings and caches — and never
touches your ACF field data.
