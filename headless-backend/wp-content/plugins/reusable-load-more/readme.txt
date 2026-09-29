=== Reusable Load More ===
Requires at least: 5.5
Tested up to: 6.6
Stable tag: 3.5.0
License: GPLv2 or later

Multi-instance "Load More" + lightbox + dynamic post grids for any theme.
Configure unlimited named sections from the admin, then drop them via shortcode
or the rlm_load_more() helper.

== Features ==
* Unlimited named sections, each with its own target/scope/classes.
* Per-section: Enable Load More, Enable Popup (Magnific lightbox), Render posts.
* Selector mode: reveal items already on the page in batches.
* Posts mode: query + render WordPress posts through a card template.
  - Card source: default WordPress layout (theme's own content template part,
    with a built-in card fallback — zero config, works on any theme) OR inline
    template (with placeholders) OR a theme template part (PHP file via
    get_template_part) for full developer control.
  - Placeholders: {permalink} {title} {excerpt} {thumbnail} {thumbnail_url}
    {acf_image} {acf_image_alt} {date} {author}
  - Query source:
      * Manual filters -- pick post type / category / tag / author from
        dropdowns populated with THIS site's real content (with a manual
        fallback field for each, so you can still type a slug/ID by hand).
      * Auto (follow current page) -- the section mirrors whatever archive the
        visitor is on: current category, tag, author, date, search, custom
        taxonomy term, or custom-post-type archive. Theme-independent: it uses
        WordPress core conditional tags and the queried object, so it works on
        category.php, tag.php, author.php, the generic archive.php, classic
        themes AND block (FSE) themes -- no per-term sections required.
  - Exclude current post (for related lists), custom no-results text.
  - Layout: Bootstrap column class OR custom width %, column gap, item
    padding & margin.
* Animation: fade / slide / none, with duration. Auto-load on scroll.
* Button: label, icon (media library) before/after, alignment, CSS class.
* Admin UX: accordion sections (one open at a time), per-section AJAX save,
  Save All, unsaved-changes warning, import/export (file download + upload).

== Works with any theme ==
The plugin never depends on a specific theme template file. In "Auto" query
source it reads the current page context from WordPress itself (is_category(),
is_tag(), is_author(), is_tax(), is_date(), is_search(),
is_post_type_archive()) and from get_queried_object(), which are available on
every theme and every modern WordPress version. Drop one Auto section into your
archive area and it serves the correct posts on each archive automatically.

== Dynamic archive use (one section for all archives) ==
Preferred: a single Posts-mode section with Query source = "Auto -- follow the
current page" covers every category / tag / author / date / search page.

Fine-grained alternative (Manual filters), still theme-agnostic:
* Author archive   -> Author filter  = "Current author page (auto-detect)"
* Category archive -> Category filter = "Current category page (auto-detect)"
* Tag archive      -> Tag filter      = "Current tag page (auto-detect)"

== Usage ==
Shortcode: [load_more id="all-blogs"]
PHP:       rlm_load_more('all-blogs');
Re-init after AJAX: if (window.RLM) { window.RLM.init(container); }

== Changelog ==
= 3.3.0 =
* NEW Card source "Default WordPress layout": renders each post with the active
  theme's own content template part (template-parts/content-{format}.php,
  template-parts/content.php, content-{format}.php or content.php; child theme
  first), with a clean built-in card fallback when the theme has none. No path
  to type, no HTML to write — matches the theme's normal posts on any theme.
= 3.2.0 =
* NEW Query source "Auto -- follow the current page": one Posts-mode section
  serves every category / tag / author / date / search / custom-taxonomy /
  custom-post-type archive, on ANY theme (classic or block/FSE). Uses core
  conditional tags + the queried object, so it no longer depends on
  category.php / tag.php / author.php existing in the theme.
* NEW dropdown selectors populated from the site's real content for Post type,
  Category, Tag and Author -- each with a manual "type it by hand" fallback.
* Hardened "current page" auto-detect for Manual filters: reads the queried
  WP_Term / WP_User, so a category or tag ID can never be mistaken for an
  author ID (and vice versa).
* "Exclude current post" now guarded to singular views only.
* Documentation refreshed throughout (Help tab + field tooltips + readme).
= 3.1.1 =
* Maintenance release.
= 3.0.0 =
* Author / category / tag filters with "current page" auto-detect.
* Exclude current post, custom no-results text.
* Theme template-part card source.
= 2.0.0 =
* Multi-instance, posts mode, popup, layout controls, per-section save.
= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 3.3.0 =
Adds a zero-config "Default WordPress layout" card source that reuses the
theme's own post template. Fully backward compatible.
= 3.2.0 =
Adds site-aware dropdowns and a theme-independent "Auto" archive mode. Fully
backward compatible: existing sections keep working with their saved settings.
