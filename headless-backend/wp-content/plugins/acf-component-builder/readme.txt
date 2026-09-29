=== ACF Component Builder ===
Contributors: yourname
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A reusable component / template / layout / rendering layer on top of ACF Pro.
Turns ACF Flexible Content layouts into reusable, theme-overridable, Bootstrap /
Flex / Grid rendered sections.

== Description ==

ACF Component Builder sits ABOVE ACF Pro. ACF stays the field & data layer;
this plugin adds a component, template, layout and rendering layer so you stop
re-writing the same PHP/HTML for every section.

Pipeline: ACF Fields -> Component -> Template -> Layout (Bootstrap/Flex/Grid)
-> Custom classes -> Frontend HTML.

Features (Phase 1):
* Requires ACF Pro (graceful admin notice if missing — never fatals).
* Auto-discovers components from your ACF Flexible Content fields.
* Renders whole flexible fields or single layouts with one call.
* Recursive field resolution including nested repeaters and groups.
* Bootstrap 4/5, Flexbox and CSS Grid layout engines.
* Theme override system (child theme > parent theme > plugin > auto-generated).
* Safe ACF JSON importer with detection/preview.
* Manual CSS class / id / data-attribute support with sanitisation.

== Installation ==

1. Install and activate Advanced Custom Fields PRO.
2. Upload the `acf-component-builder` folder to `/wp-content/plugins/`.
3. Activate the plugin.
4. Go to "ACF Components" -> "Import ACF JSON" and upload your export.
5. Render in your theme: `echo ACB::render_flexible('services_inner_detail_section');`

== Frequently Asked Questions ==

= Does it replace ACF? =
No. ACF Pro is required and remains the field/data layer.

= Do I have to build templates manually? =
No. Components render out of the box using an auto-generated template. You can
override any component with a PHP file in your theme or a saved template.

== Changelog ==

= 1.3.0 =
* New "ACF Component" Gutenberg block — place any component on any page/post with no code, with a live editor preview.
* Usability: "How to display this" panel with copy-able block, shortcode, and PHP snippets (Components screen + builder).
* Usability: contextual help icons with hover/click tooltips across the builder inspector, Components, and Settings.
* Dashboard now explains the ACF → component → display flow up front.

= 1.2.0 =
* Phase 3: drag-and-drop node reordering and nesting in the builder tree.
* Phase 3: reusable block library (Component Library) — save any node/subtree and insert it into any template.
* Phase 3: template import / export as portable JSON (file download or paste).
* Phase 3: version history — every save snapshots the previous tree; restore any of the last 20.
* New reusable-block CPT (acb_block) and expanded, nonce-guarded AJAX API.
* Fix: JSON stored in post meta is now slash-safe (preserves escaped characters).

= 1.1.0 =
* Phase 2: Template Manager (create, edit, duplicate, delete saved templates).
* Phase 2: Visual Template Builder — nested structure editor over the JSON node tree.
* Phase 2: Responsive controls per node (Bootstrap breakpoints, Flexbox, CSS Grid column counts).
* Phase 2: Live, device-width preview rendered server-side against real ACF content.
* New AJAX API (nonce + capability guarded) and a strict node-tree sanitizer.

= 1.0.0 =
* Initial Phase 1 release.
