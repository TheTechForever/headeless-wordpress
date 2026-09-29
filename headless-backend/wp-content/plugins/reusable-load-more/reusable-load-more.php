<?php
/**
 * Plugin Name: Reusable Load More
 * Description: A reusable, multi-instance "Load More" component. Configure as
 *              many named sections as you like from the admin (each with its
 *              own target/scope/classes), then drop them via shortcode or
 *              helper. Includes fade/slide animation,
 *              auto-load on scroll, custom CSS, and import/export.
 * Version:     3.5.0
 * Author:      Tejas Panchal
 * License:     GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RLM_VERSION', '3.5.0');
define('RLM_OPTION', 'rlm_settings');
define('RLM_FILE', __FILE__);

/* =========================================================================
   DATA MODEL
========================================================================= */
function rlm_default_instance()
{
    return [
        'id'           => '',
        'label'        => '',
        'target'       => '',
        'scope'        => '',
        'enable_loadmore' => 1,
        'visible'      => 6,
        'step'         => 6,
        'button_label' => 'Load More',
        'icon_url'     => '',
        'icon_pos'      => 'before', // before | after
        'enable_popup'  => 0,
        'popup_delegate'=> 'a',
        'autoload'     => 0,
        'animation'    => 'fade',   // fade | slide | none
        'duration'     => 350,
        'align'        => 'center', // left | center | right
        'button_class' => 'black-border-btn font-w600',
        'enabled'      => 1,
        // --- Posts source (Option B) ---
        'source'          => 'selector', // selector | posts
        // How the posts query is scoped:
        //   manual = use the filters chosen below (post type / category / tag / author)
        //   auto   = follow the current page (mirror WordPress' main query on any
        //            category / tag / author / date / search / taxonomy / CPT archive,
        //            on ANY theme — relies on core conditional tags, not category.php)
        'query_context'   => 'manual', // manual | auto
        'post_type'       => 'post',
        'posts_per_page'  => -1,
        'category'        => '',
        'cat_mode'        => 'fixed',  // fixed | current
        'tag'             => '',
        'tag_mode'        => 'none',   // none | fixed | current
        'exclude_current' => 0,        // exclude the post being viewed (for related lists)
        'no_results_text' => 'No posts found.',
        'author_mode'     => 'none',   // none | current | specific
        'author_id'       => '',       // used when author_mode = specific
        'orderby'         => 'date',
        'order'           => 'DESC',
        'grid_wrap_class' => 'row g-4',
        'item_wrap_class' => 'col-12 col-md-6 col-lg-4',
        // --- Layout controls ---
        'col_mode'        => 'auto',   // auto | class | percent
        'col_width'       => '33.33',  // % when col_mode = percent
        'col_min'         => '280px',  // min card width when col_mode = auto
        'col_gap'         => '',       // grid gap, e.g. 24px or 1.5rem
        'item_padding'    => '',       // each card padding, e.g. 16px
        'item_margin'     => '',       // each card margin, e.g. 0 0 24px
        'card_source'     => 'inline',  // inline | template
        'card_template_part' => '',     // e.g. templates/common/blog-card
        'card_template'   => "<a href=\"{permalink}\" class=\"blog-img\">{thumbnail}</a>\n<div class=\"blog-content\">\n    <a href=\"{permalink}\"><h3 class=\"blog-title font-w700 pb-10\">{title}</h3></a>\n    <p class=\"blog-excerpt pb-10\">{excerpt}</p>\n    <a href=\"{permalink}\" class=\"read-more h5 font-w700\">Read More &gt;&gt;</a>\n</div>",
    ];
}

function rlm_default_settings()
{
    return [
        'custom_css' => '',
        'instances'  => [
            array_merge(rlm_default_instance(), [
                'id' => 'services', 'label' => 'Our Services',
                'target' => '.services-grid-item', 'scope' => '.services-grid-wrap',
                'visible' => 3, 'step' => 3, 'animation' => 'fade',
            ]),
        ],
    ];
}

function rlm_get_settings()
{
    $saved = get_option(RLM_OPTION, []);
    if (!is_array($saved)) {
        $saved = [];
    }
    $s = wp_parse_args($saved, rlm_default_settings());
    if (empty($s['instances']) || !is_array($s['instances'])) {
        $s['instances'] = [];
    }
    return $s;
}

function rlm_get_instance($id)
{
    foreach (rlm_get_settings()['instances'] as $inst) {
        if ($inst['id'] === $id) {
            return wp_parse_args($inst, rlm_default_instance());
        }
    }
    return null;
}

register_activation_hook(__FILE__, function () {
    if (get_option(RLM_OPTION) === false) {
        add_option(RLM_OPTION, rlm_default_settings());
    }
});

/* =========================================================================
   ADMIN CHOICE HELPERS
   These read the real content of whatever site the plugin is installed on,
   so the admin dropdowns list this site's actual post types / categories /
   tags / authors. They are written defensively to work across WordPress
   versions and to degrade gracefully (empty array) if anything is missing.
========================================================================= */

/* Public, selectable post types on this site (skips attachments & internals). */
function rlm_get_post_type_choices()
{
    $out  = [];
    $skip = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation'];
    if (!function_exists('get_post_types')) {
        return $out;
    }
    $types = get_post_types(['public' => true], 'objects');
    if (empty($types) || !is_array($types)) {
        return $out;
    }
    foreach ($types as $slug => $obj) {
        if (in_array($slug, $skip, true)) {
            continue;
        }
        $label = isset($obj->labels->singular_name) && $obj->labels->singular_name
            ? $obj->labels->singular_name
            : (isset($obj->label) ? $obj->label : $slug);
        $out[$slug] = $label;
    }
    return $out; // [ slug => Label ]
}

/* Categories on this site. Uses get_categories() for maximum version safety. */
function rlm_get_category_choices()
{
    $out = [];
    if (!function_exists('get_categories')) {
        return $out;
    }
    $terms = get_categories(['hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
    if (empty($terms) || is_wp_error($terms)) {
        return $out;
    }
    foreach ($terms as $t) {
        // Store by slug (portable between sites); show name + count for clarity.
        $out[$t->slug] = $t->name . ' (' . (int) $t->count . ')';
    }
    return $out; // [ slug => "Name (count)" ]
}

/* Tags on this site. */
function rlm_get_tag_choices()
{
    $out = [];
    if (!function_exists('get_tags')) {
        return $out;
    }
    $terms = get_tags(['hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
    if (empty($terms) || is_wp_error($terms)) {
        return $out;
    }
    foreach ($terms as $t) {
        $out[$t->slug] = $t->name . ' (' . (int) $t->count . ')';
    }
    return $out; // [ slug => "Name (count)" ]
}

/* Authors (users who have published posts, falling back to all users). */
function rlm_get_author_choices()
{
    $out = [];
    if (!function_exists('get_users')) {
        return $out;
    }
    // has_published_posts has existed since WP 4.4, so this is safe on any
    // reasonably modern install; if it yields nothing we fall back to all users.
    $args  = ['orderby' => 'display_name', 'order' => 'ASC', 'number' => 500, 'has_published_posts' => true];
    $users = get_users($args);
    if (empty($users)) {
        unset($args['has_published_posts']);
        $users = get_users($args);
    }
    if (empty($users)) {
        return $out;
    }
    foreach ($users as $u) {
        $name = $u->display_name ? $u->display_name : $u->user_login;
        $out[(int) $u->ID] = $name . ' (#' . (int) $u->ID . ')';
    }
    return $out; // [ ID => "Name (#ID)" ]
}

/* =========================================================================
   FRONT-END ASSETS
========================================================================= */
function rlm_enqueue_assets()
{
    $base = plugin_dir_url(__FILE__);
    $path = plugin_dir_path(__FILE__);
    $s    = rlm_get_settings();

    wp_enqueue_style('rlm-load-more', $base . 'assets/load-more.css', [], filemtime($path . 'assets/load-more.css'));
    if (!empty($s['custom_css'])) {
        wp_add_inline_style('rlm-load-more', wp_strip_all_tags($s['custom_css']));
    }

    // Does any section use the Magnific popup?
    $needs_popup = false;
    foreach ($s['instances'] as $inst) {
        if (!empty($inst['enable_popup'])) {
            $needs_popup = true;
            break;
        }
    }

    $deps = [];
    if ($needs_popup) {
        // Use the theme's Magnific if it's already registered; otherwise load our own from CDN.
        if (wp_script_is('magnific-js', 'registered') || wp_script_is('magnific-js', 'enqueued')) {
            wp_enqueue_style('magnific-css');
            wp_enqueue_script('magnific-js');
            $deps = ['jquery', 'magnific-js'];
        } else {
            wp_enqueue_style('rlm-magnific', 'https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/magnific-popup.css', [], '1.1.0');
            wp_enqueue_script('rlm-magnific', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js', ['jquery'], '1.1.0', true);
            $deps = ['jquery', 'rlm-magnific'];
        }
    }

    wp_enqueue_script('rlm-load-more', $base . 'assets/load-more.js', $deps, filemtime($path . 'assets/load-more.js'), true);
}
add_action('wp_enqueue_scripts', 'rlm_enqueue_assets', 30);

/* =========================================================================
   RENDER — by instance id, with optional inline overrides
========================================================================= */
function rlm_load_more($id, $overrides = [])
{
    $inst = rlm_get_instance($id);
    if (!$inst) {
        if (current_user_can('manage_options')) {
            echo '<!-- Load More: no instance "' . esc_html($id) . '" -->';
        }
        return;
    }
    if (empty($inst['enabled'])) {
        return;
    }
    $a = wp_parse_args($overrides, $inst);

    // --- POSTS SOURCE (Option B): only when this section explicitly opted in.
    // Selector sections never reach this branch, so they are unaffected.
    // NOTE: which "card" field counts as "filled in" depends on Card source —
    //   template   -> needs card_template_part (path to a theme PHP file)
    //   inline     -> needs the inline card_template textarea
    //   wp_default -> needs nothing: it auto-detects the theme's own post
    //                 template part and falls back to a built-in card, so it
    //                 always has something to render.
    if (($a['source'] ?? 'selector') === 'posts') {
        $cs = $a['card_source'] ?? 'inline';
        if ($cs === 'wp_default') {
            $has_card_source = true;
        } elseif ($cs === 'template') {
            $has_card_source = !empty($a['card_template_part']);
        } else {
            $has_card_source = !empty($a['card_template']);
        }
        if ($has_card_source) {
            rlm_render_posts_section($a, $id);
            return;
        }

        // Posts mode with nothing to render a card from. Falling through to
        // selector mode here would quietly do the wrong thing (and usually show
        // an orphan button), so stop and tell an administrator what to fix.
        if (current_user_can('manage_options')) {
            $needs = ($cs === 'template')
                ? 'a “Template part path” (Card source = Theme template part)'
                : 'a “Card template” (Card source = Inline template)';
            echo '<div class="rlm-admin-warning" style="border:1px solid #dba617;background:#fcf9e8;padding:12px 16px;border-radius:8px;margin:10px 0;font-size:13px;line-height:1.6;">'
                . '<strong>Load More (visible to administrators only):</strong> section <code>' . esc_html($id) . '</code> is in Posts mode but has no card to render. It needs ' . esc_html($needs) . '. '
                . 'Set it under <em>Load More → ' . esc_html($id) . ' → Card source</em>, or choose “Default WordPress layout”, which needs no configuration.'
                . '</div>';
        }
        return;
    }

    // --- SELECTOR SOURCE (point at existing items on the page) ---
    if (empty($a['target'])) {
        return;
    }

    $do_more  = !empty($a['enable_loadmore']);
    $do_popup = !empty($a['enable_popup']);

    // Nothing enabled -> render nothing.
    if (!$do_more && !$do_popup) {
        return;
    }

    // POPUP ONLY (no Load More button): output a tiny hidden config marker so
    // the JS can bind Magnific to the items without revealing/hiding anything.
    if (!$do_more && $do_popup) {
        ?>
        <span class="js-rlm-popup" style="display:none;"
            data-lm-target="<?php echo esc_attr($a['target']); ?>"
            <?php if (!empty($a['scope'])) : ?>data-lm-scope="<?php echo esc_attr($a['scope']); ?>"<?php endif; ?>
            data-lm-popup-delegate="<?php echo esc_attr($a['popup_delegate'] ?: 'a'); ?>"></span>
        <?php
        return;
    }

    // LOAD MORE (with or without popup).
    rlm_render_button($a, $a['target'], $a['scope'], $do_popup);
}

/* Renders the Load More button. */
function rlm_render_button($a, $target, $scope, $do_popup)
{
    $align_class = 'lm-align-' . sanitize_html_class($a['align']);
    ?>
    <div class="load-more-wrap <?php echo esc_attr($align_class); ?>">
        <a class="js-load-more <?php echo esc_attr($a['button_class']); ?>"
            href="javascript:void(0);"
            role="button"
            data-lm-target="<?php echo esc_attr($target); ?>"
            <?php if (!empty($scope)) : ?>data-lm-scope="<?php echo esc_attr($scope); ?>"<?php endif; ?>
            data-lm-visible="<?php echo esc_attr((int) $a['visible']); ?>"
            data-lm-step="<?php echo esc_attr((int) $a['step']); ?>"
            data-lm-animation="<?php echo esc_attr($a['animation']); ?>"
            data-lm-duration="<?php echo esc_attr((int) $a['duration']); ?>"
            data-lm-autoload="<?php echo esc_attr((int) $a['autoload']); ?>"
            data-lm-popup="<?php echo esc_attr((int) $do_popup); ?>"
            data-lm-popup-delegate="<?php echo esc_attr($a['popup_delegate'] ?: 'a'); ?>"
            data-lm-label="<?php echo esc_attr($a['button_label']); ?>">
            <?php
            $icon_html = '';
            if (!empty($a['icon_url'])) {
                $icon_html = '<img class="lm-btn-icon" src="' . esc_url($a['icon_url']) . '" alt="" aria-hidden="true" />';
            }
            $pos = ($a['icon_pos'] === 'after') ? 'after' : 'before';
            if ($icon_html && $pos === 'before') {
                echo $icon_html;
            }
            ?>
            <span class="lm-btn-text"><?php echo esc_html($a['button_label']); ?></span>
            <?php
            if ($icon_html && $pos === 'after') {
                echo $icon_html;
            }
            ?>
        </a>
    </div>
    <?php
}

/* Fill a card template's placeholders for the current post in the loop. */
function rlm_fill_template($tpl)
{
    $thumb = has_post_thumbnail() ? get_the_post_thumbnail(get_the_ID(), 'large') : '';

    // ACF banner image with fallback chain: blog_banner_image -> featured -> options default.
    $acf_url = '';
    $acf_alt = '';
    if (function_exists('get_field')) {
        $banner = get_field('blog_banner_image');
        if (is_array($banner) && !empty($banner['url'])) {
            $acf_url = $banner['url'];
            $acf_alt = $banner['alt'] ?? '';
        }
    }
    if ($acf_url === '' && has_post_thumbnail()) {
        $acf_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
        $acf_alt = get_the_title();
    }
    if ($acf_url === '' && function_exists('get_field')) {
        $default = get_field('default_blog_image', 'options');
        if (is_array($default) && !empty($default['url'])) {
            $acf_url = $default['url'];
            $acf_alt = $default['alt'] ?? '';
        }
    }

    $repl = [
        '{permalink}'      => esc_url(get_permalink()),
        '{title}'          => esc_html(get_the_title()),
        '{excerpt}'        => esc_html(wp_trim_words(get_the_excerpt(), 20)),
        '{thumbnail}'      => $thumb, // safe WP-generated markup
        '{thumbnail_url}'  => esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')),
        '{acf_image}'      => esc_url($acf_url),   // banner -> featured -> default
        '{acf_image_alt}'  => esc_attr($acf_alt),
        '{date}'           => esc_html(get_the_date()),
        '{author}'         => esc_html(get_the_author()),
    ];
    return strtr($tpl, $repl);
}

/*
 * "Default WordPress layout" card source.
 *
 * There is no single core function that prints "the default post layout", so
 * this reproduces what a normal theme does in its own post loop, in a fully
 * theme-independent way:
 *
 *   1. If the active theme (or its parent) ships a standard content template
 *      part — template-parts/content-{format}.php, template-parts/content.php,
 *      content-{format}.php or content.php — we render that via
 *      get_template_part(), so each card looks exactly like the theme's own
 *      posts (this is the convention used by Twenty* and most classic themes).
 *   2. If no such part exists (a minimal theme, or a block/FSE theme that has
 *      no PHP content part), we fall back to a clean built-in post card so the
 *      section still renders sensibly on ANY theme with zero configuration.
 *
 * Must be called inside the loop (after the_post()).
 */

/* Return the relative path of the theme's standard content part, or '' if none. */
function rlm_locate_default_content_part()
{
    $format     = function_exists('get_post_format') ? get_post_format() : false;
    $candidates = [];
    if ($format) {
        $candidates[] = 'template-parts/content-' . $format . '.php';
    }
    $candidates[] = 'template-parts/content.php';
    if ($format) {
        $candidates[] = 'content-' . $format . '.php';
    }
    $candidates[] = 'content.php';

    foreach ($candidates as $rel) {
        // locate_template() checks child theme first, then parent — no load.
        if (locate_template($rel, false)) {
            return $rel;
        }
    }
    return '';
}

/* Built-in, theme-independent post card used when the theme has no content part. */
function rlm_render_default_card()
{
    ?>
    <article <?php post_class('rlm-default-card'); ?>>
        <?php if (has_post_thumbnail()) : ?>
            <a class="rlm-default-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
                <?php the_post_thumbnail('large'); ?>
            </a>
        <?php endif; ?>
        <div class="rlm-default-body">
            <h2 class="rlm-default-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
            <div class="rlm-default-meta">
                <?php echo esc_html(get_the_date()); ?>
                <?php $rlm_author = get_the_author(); if ($rlm_author) : ?>
                    <span class="rlm-default-sep">&middot;</span> <?php echo esc_html($rlm_author); ?>
                <?php endif; ?>
            </div>
            <div class="rlm-default-excerpt">
                <?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?>
            </div>
            <a class="rlm-default-more" href="<?php the_permalink(); ?>">
                <?php echo esc_html__('Read More', 'reusable-load-more'); ?> &raquo;
            </a>
        </div>
    </article>
    <?php
}

/* Render one post using the theme's default layout (part) or the built-in card. */
function rlm_render_default_layout()
{
    $rel = rlm_locate_default_content_part();
    if ($rel !== '') {
        // Pass the path without .php as the slug; get_template_part() then loads
        // that exact file and fires the standard get_template_part_* hooks, so
        // child-theme overrides and template filters all keep working.
        $slug = preg_replace('/\.php$/', '', $rel);
        get_template_part($slug);
        return;
    }
    rlm_render_default_card();
}

/*
 * Mirror the CURRENT PAGE's main query onto $args, using only WordPress core
 * conditional tags and the queried object. This is what makes the plugin
 * theme-independent: it does NOT rely on a theme having category.php / tag.php
 * / author.php. It works equally on archive.php, on classic themes, and on
 * block (FSE) themes — anywhere WordPress has resolved an archive context.
 *
 * Handles: category, tag, any custom taxonomy term, author, date (Y/M/D),
 * search, and custom-post-type archives.
 */
function rlm_apply_current_context(&$args)
{
    if (is_category()) {
        $obj = get_queried_object();
        if ($obj instanceof WP_Term) {
            $args['cat'] = (int) $obj->term_id;
        }
    } elseif (is_tag()) {
        $obj = get_queried_object();
        if ($obj instanceof WP_Term) {
            $args['tag_id'] = (int) $obj->term_id;
        }
    } elseif (is_tax()) {
        // Any custom taxonomy archive.
        $obj = get_queried_object();
        if ($obj instanceof WP_Term) {
            $args['tax_query'] = [[
                'taxonomy' => $obj->taxonomy,
                'field'    => 'term_id',
                'terms'    => [(int) $obj->term_id],
            ]];
        }
    } elseif (is_author()) {
        $obj = get_queried_object();
        if ($obj instanceof WP_User) {
            $args['author'] = (int) $obj->ID;
        }
    } elseif (is_date()) {
        $y = (int) get_query_var('year');
        $m = (int) get_query_var('monthnum');
        $d = (int) get_query_var('day');
        if ($y) { $args['year'] = $y; }
        if ($m) { $args['monthnum'] = $m; }
        if ($d) { $args['day'] = $d; }
    } elseif (is_search()) {
        $args['s'] = get_search_query();
    } elseif (is_post_type_archive()) {
        $pt = get_query_var('post_type');
        if ($pt) {
            $args['post_type'] = $pt;
        }
    }
}

/* Renders a posts grid (queried) + the Load More button. */
function rlm_render_posts_section($a, $id)
{
    $post_type = $a['post_type'] ?: 'post';

    // A post type that is not registered makes WP_Query return nothing, which
    // looks exactly like "the plugin is broken". Tell an administrator what is
    // actually wrong (visitors just see the normal no-results text).
    if (!post_type_exists($post_type)) {
        if (current_user_can('manage_options')) {
            $known = function_exists('rlm_get_post_type_choices') ? array_keys(rlm_get_post_type_choices()) : [];
            echo '<div class="rlm-admin-warning" style="border:1px solid #dba617;background:#fcf9e8;padding:12px 16px;border-radius:8px;margin:10px 0;font-size:13px;line-height:1.6;">'
                . '<strong>Load More (visible to administrators only):</strong> section <code>' . esc_html($id) . '</code> is set to post type <code>' . esc_html($post_type) . '</code>, which is not registered on this site, so the query returns nothing.'
                . ($known ? ' Available post types: <code>' . esc_html(implode('</code>, <code>', $known)) . '</code>.' : '')
                . ' Ordinary blog posts use <code>post</code>. Fix this under <em>Load More → ' . esc_html($id) . ' → Post type</em>.'
                . '</div>';
        } elseif (!empty($a['no_results_text'])) {
            echo '<p class="rlm-no-results text-center">' . esc_html($a['no_results_text']) . '</p>';
        }
        return;
    }

    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => (int) $a['posts_per_page'],   // -1 = all (revealed in batches)
        'orderby'        => $a['orderby'] ?: 'date',
        'order'          => $a['order'] ?: 'DESC',
        'ignore_sticky_posts' => true,
    ];

    $context = $a['query_context'] ?? 'manual';

    if ($context === 'auto') {
        // Follow whatever archive the visitor is on — no per-term section needed.
        // One "auto" section can power every category, tag, author, date and
        // search page on the site, on any theme.
        rlm_apply_current_context($args);
    } else {
        // -------- MANUAL FILTERS --------
        $cat_mode = $a['cat_mode'] ?? 'fixed';
        if ($cat_mode === 'current') {
            // Robust: only apply when we are genuinely on a category archive, and
            // read it from the queried WP_Term so it can never mistake an author
            // or tag ID for a category ID (works on any theme, not just category.php).
            $obj = get_queried_object();
            if ($obj instanceof WP_Term && $obj->taxonomy === 'category') {
                $args['cat'] = (int) $obj->term_id;
            }
        } elseif (!empty($a['category'])) {
            // Accept a category slug or ID.
            if (is_numeric($a['category'])) {
                $args['cat'] = (int) $a['category'];
            } else {
                $args['category_name'] = $a['category'];
            }
        }

        // Author filter.
        $author_mode = $a['author_mode'] ?? 'none';
        if ($author_mode === 'current') {
            $obj = get_queried_object();
            if ($obj instanceof WP_User) {
                $args['author'] = (int) $obj->ID;
            }
        } elseif ($author_mode === 'specific' && !empty($a['author_id'])) {
            $args['author'] = (int) $a['author_id'];
        }

        // Tag filter.
        $tag_mode = $a['tag_mode'] ?? 'none';
        if ($tag_mode === 'current') {
            $obj = get_queried_object();
            if ($obj instanceof WP_Term && $obj->taxonomy === 'post_tag') {
                $args['tag_id'] = (int) $obj->term_id;
            }
        } elseif ($tag_mode === 'fixed' && !empty($a['tag'])) {
            if (is_numeric($a['tag'])) {
                $args['tag_id'] = (int) $a['tag'];
            } else {
                $args['tag'] = sanitize_title($a['tag']);
            }
        }
    }

    // Exclude the post currently being viewed (handy for "related posts" lists).
    // Guarded to a singular view so it never removes a term/author ID by mistake.
    if (!empty($a['exclude_current']) && is_singular()) {
        $current = (int) get_queried_object_id();
        if ($current) {
            $args['post__not_in'] = [$current];
        }
    }

    $q = new WP_Query($args);
    if (!$q->have_posts()) {
        wp_reset_postdata();
        if (!empty($a['no_results_text'])) {
            echo '<p class="rlm-no-results text-center">' . esc_html($a['no_results_text']) . '</p>';
        }
        return;
    }

    $wrap_id   = 'rlm-posts-' . sanitize_html_class($id);
    $template  = $a['card_template'];

    // --- Build grid + item inline styles from the layout controls ---
    $col_mode     = $a['col_mode'] ?? 'auto';
    $percent_mode = ($col_mode === 'percent');
    $auto_mode    = ($col_mode === 'auto');

    // Auto mode uses CSS Grid with no framework classes at all, so the grid
    // looks right on any theme (Twenty Twenty-Five, other block themes, or a
    // classic theme without Bootstrap). Percent mode uses inline flexbox.
    $gap = !empty($a['col_gap']) ? $a['col_gap'] : '24px';

    $grid_style = '';
    if ($auto_mode) {
        $min = !empty($a['col_min']) ? $a['col_min'] : '280px';
        $grid_style .= 'display:grid;grid-template-columns:repeat(auto-fill,minmax(min(' . esc_attr($min) . ',100%),1fr));';
        $grid_style .= 'gap:' . esc_attr($gap) . ';';
    } else {
        if ($percent_mode) {
            $grid_style .= 'display:flex;flex-wrap:wrap;';
        }
        if (!empty($a['col_gap'])) {
            $grid_style .= 'gap:' . esc_attr($a['col_gap']) . ';';
        }
    }

    $item_style = '';
    if ($percent_mode && $a['col_width'] !== '') {
        // Subtract the gap so items fit per row when a gap is set.
        $w = floatval($a['col_width']);
        if (!empty($a['col_gap'])) {
            $item_style .= 'flex:0 0 calc(' . $w . '% - ' . esc_attr($a['col_gap']) . ');max-width:calc(' . $w . '% - ' . esc_attr($a['col_gap']) . ');';
        } else {
            $item_style .= 'flex:0 0 ' . $w . '%;max-width:' . $w . '%;';
        }
    }
    if ($a['item_padding'] !== '') {
        $item_style .= 'padding:' . esc_attr($a['item_padding']) . ';';
    }
    if ($a['item_margin'] !== '') {
        $item_style .= 'margin:' . esc_attr($a['item_margin']) . ';';
    }

    // Framework column classes only make sense in "class" mode.
    $use_classes = (!$percent_mode && !$auto_mode);
    $item_class  = trim(($use_classes ? $a['item_wrap_class'] : '') . ' rlm-post-item');
    $grid_class  = $use_classes ? ('rlm-posts-wrap ' . $a['grid_wrap_class']) : 'rlm-posts-wrap';
    // Card source: 'template' (theme PHP file via get_template_part),
    // 'wp_default' (theme's own content part / built-in fallback), or 'inline'.
    $card_source = $a['card_source'] ?? 'inline';
    $use_part    = ($card_source === 'template') && !empty($a['card_template_part']);
    $use_default = ($card_source === 'wp_default');
    $part      = $a['card_template_part'];
    $part_slug = '';
    $part_name = null;
    if ($use_part) {
        // Split "templates/common/blog-card" -> slug "templates/common/blog", name "card".
        // get_template_part($slug, $name) loads {slug}-{name}.php.
        $part = preg_replace('/\.php$/', '', trim($part));
        if (strpos($part, '-') !== false) {
            $pos = strrpos($part, '-');
            $part_slug = substr($part, 0, $pos);
            $part_name = substr($part, $pos + 1);
        } else {
            $part_slug = $part;
        }
    }
    ?>
    <div id="<?php echo esc_attr($wrap_id); ?>" class="<?php echo esc_attr($grid_class); ?>"<?php echo $grid_style ? ' style="' . esc_attr($grid_style) . '"' : ''; ?>>
        <?php while ($q->have_posts()) : $q->the_post(); ?>
            <div class="<?php echo esc_attr($item_class); ?>"<?php echo $item_style ? ' style="' . esc_attr($item_style) . '"' : ''; ?>>
                <?php
                if ($use_default) {
                    // The theme's own post layout (content template part), with a
                    // built-in card fallback for themes that don't ship one.
                    rlm_render_default_layout();
                } elseif ($use_part) {
                    // Full PHP control inside the loop: the file can use the_permalink(),
                    // get_field(), the_post_thumbnail(), etc.
                    get_template_part($part_slug, $part_name);
                } else {
                    echo rlm_fill_template($template);
                }
                ?>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();

    // Reveal in batches with the Load More button (scoped to this grid).
    if (!empty($a['enable_loadmore'])) {
        rlm_render_button($a, '.rlm-post-item', '#' . $wrap_id, !empty($a['enable_popup']));
    }
}

/* Shortcode: [load_more id="services"] (+ any field as an override attribute) */
function rlm_shortcode($atts)
{
    // IMPORTANT: $atts contains ONLY the attributes actually typed in the
    // shortcode. Do not pass it through shortcode_atts() with the full default
    // instance — that fills in every default and turns them into explicit
    // overrides, which then beat the section's own saved settings in
    // wp_parse_args(). Most damagingly it forced `source` back to "selector",
    // so a Posts-mode section rendered no posts at all via the shortcode while
    // the rlm_load_more() helper (no overrides) worked fine.
    $atts = is_array($atts) ? $atts : [];
    $atts = array_change_key_case($atts, CASE_LOWER);

    $id = isset($atts['id']) ? sanitize_key($atts['id']) : '';
    unset($atts['id']);

    // Keep only recognised setting keys the user genuinely supplied.
    $known     = rlm_default_instance();
    $overrides = [];
    foreach ($atts as $k => $v) {
        if (is_string($k) && array_key_exists($k, $known) && $v !== '' && $v !== null) {
            $overrides[$k] = $v;
        }
    }

    ob_start();
    if ($id) {
        rlm_load_more($id, $overrides);
    } elseif (current_user_can('manage_options')) {
        echo '<!-- Load More: shortcode has no id, e.g. [load_more id="my-section"] -->';
    }
    return ob_get_clean();
}
add_shortcode('load_more', 'rlm_shortcode');

/* =========================================================================
   ADMIN
========================================================================= */
function rlm_admin_menu()
{
    add_menu_page('Load More', 'Load More', 'manage_options', 'rlm-dashboard', 'rlm_render_admin', 'dashicons-update', 81);
    add_submenu_page('rlm-dashboard', 'Load More', 'Dashboard & Settings', 'manage_options', 'rlm-dashboard', 'rlm_render_admin');
}
add_action('admin_menu', 'rlm_admin_menu');

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=rlm-dashboard')) . '">Settings</a>');
    return $links;
});

function rlm_admin_assets($hook)
{
    if ($hook !== 'toplevel_page_rlm-dashboard') {
        return;
    }
    $base = plugin_dir_url(__FILE__);
    $path = plugin_dir_path(__FILE__);
    wp_enqueue_media(); // loads wp.media and all its dependencies
    wp_enqueue_style('rlm-admin', $base . 'assets/admin.css', [], filemtime($path . 'assets/admin.css'));
    wp_enqueue_script('rlm-admin', $base . 'assets/admin.js', ['jquery'], filemtime($path . 'assets/admin.js'), true);
    wp_localize_script('rlm-admin', 'RLM_ADMIN', [
        'ajax_url'   => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('rlm_ajax_save'),
        'post_types' => array_keys(rlm_get_post_type_choices()),
    ]);
}
add_action('admin_enqueue_scripts', 'rlm_admin_assets');

/* AJAX: save a single section */
add_action('wp_ajax_rlm_save_section', 'rlm_ajax_save_section');
function rlm_ajax_save_section()
{
    if (!current_user_can('manage_options') || !check_ajax_referer('rlm_ajax_save', 'nonce', false)) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $section = isset($_POST['section']) ? (array) wp_unslash($_POST['section']) : [];
    $inst = rlm_sanitize_instance($section);
    if ($inst['id'] === '') {
        wp_send_json_error(['message' => 'Section needs an ID / slug before saving.']);
    }

    // The slug the section had when the page was rendered. If it differs from
    // the submitted one the user renamed it, and we must know whether they meant
    // to rename this section or spin off a new one.
    $original_id = isset($_POST['original_id']) ? sanitize_key(wp_unslash($_POST['original_id'])) : '';
    $mode        = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : '';

    $settings = rlm_get_settings();

    // Locate the record matching the *submitted* slug and the *original* slug.
    $idx_new = null;
    $idx_old = null;
    foreach ($settings['instances'] as $i => $existing) {
        if ($existing['id'] === $inst['id'] && $idx_new === null) {
            $idx_new = $i;
        }
        if ($original_id !== '' && $existing['id'] === $original_id && $idx_old === null) {
            $idx_old = $i;
        }
    }

    $is_rename = ($original_id !== '' && $original_id !== $inst['id'] && $idx_old !== null);

    if ($is_rename) {
        // Renaming onto a slug that already belongs to a different section would
        // silently destroy that one. Refuse and let the user pick another slug.
        if ($idx_new !== null && $idx_new !== $idx_old) {
            wp_send_json_error(['message' => 'Another section already uses the ID “' . $inst['id'] . '”. Choose a different one.']);
        }

        if ($mode === 'duplicate') {
            // Keep the original untouched and add the edited copy as a new one.
            $settings['instances'][] = $inst;
            update_option(RLM_OPTION, $settings);
            wp_send_json_success([
                'message'  => 'New section created.',
                'id'       => $inst['id'],
                'created'  => true,
                'reload'   => true,
            ]);
        }

        // Default for a rename is to update the existing record in place.
        $settings['instances'][$idx_old] = $inst;
        update_option(RLM_OPTION, $settings);
        wp_send_json_success([
            'message'  => 'Section renamed to “' . $inst['id'] . '”.',
            'id'       => $inst['id'],
            'renamed'  => true,
        ]);
    }

    if ($idx_new !== null) {
        $settings['instances'][$idx_new] = $inst; // update in place
    } else {
        $settings['instances'][] = $inst;         // genuinely new section
    }

    update_option(RLM_OPTION, $settings);
    wp_send_json_success(['message' => 'Section saved.', 'id' => $inst['id']]);
}

/* AJAX: delete a single section */
add_action('wp_ajax_rlm_delete_section', 'rlm_ajax_delete_section');
function rlm_ajax_delete_section()
{
    if (!current_user_can('manage_options') || !check_ajax_referer('rlm_ajax_save', 'nonce', false)) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $id = isset($_POST['id']) ? sanitize_key(wp_unslash($_POST['id'])) : '';
    if ($id === '') {
        wp_send_json_error(['message' => 'No section ID supplied.']);
    }

    $settings = rlm_get_settings();
    $before   = count($settings['instances']);

    $settings['instances'] = array_values(array_filter(
        $settings['instances'],
        function ($existing) use ($id) {
            return $existing['id'] !== $id;
        }
    ));

    if (count($settings['instances']) === $before) {
        // Nothing matched — it was never saved. Not an error: the caller just
        // wants it gone, and it already is.
        wp_send_json_success(['message' => 'Section was not saved yet.', 'id' => $id]);
    }

    update_option(RLM_OPTION, $settings);
    wp_send_json_success(['message' => 'Section deleted.', 'id' => $id]);
}

/* Save handler */
function rlm_maybe_save()
{
    if (!isset($_POST['rlm_nonce']) || !current_user_can('manage_options')) {
        return;
    }
    if (!wp_verify_nonce($_POST['rlm_nonce'], 'rlm_save')) {
        return;
    }

    $out = ['custom_css' => '', 'instances' => []];

    // Import (paste JSON) takes precedence if provided.
    if (!empty($_POST['rlm_import'])) {
        $decoded = json_decode(stripslashes($_POST['rlm_import']), true);
        if (is_array($decoded)) {
            $out['custom_css'] = isset($decoded['custom_css']) ? wp_strip_all_tags($decoded['custom_css']) : '';
            if (!empty($decoded['instances']) && is_array($decoded['instances'])) {
                foreach ($decoded['instances'] as $row) {
                    $out['instances'][] = rlm_sanitize_instance($row);
                }
            }
            update_option(RLM_OPTION, $out);
            add_settings_error('rlm', 'rlm_imported', 'Settings imported.', 'updated');
            return;
        }
        add_settings_error('rlm', 'rlm_import_err', 'Import failed: invalid JSON.', 'error');
    }

    $out['custom_css'] = isset($_POST['custom_css']) ? wp_strip_all_tags($_POST['custom_css']) : '';

    if (!empty($_POST['instances']) && is_array($_POST['instances'])) {
        $seen = [];
        foreach ($_POST['instances'] as $row) {
            $inst = rlm_sanitize_instance($row);
            if ($inst['id'] === '') {
                continue; // skip rows with no id
            }
            // ensure unique id
            $base_id = $inst['id'];
            $n = 2;
            while (in_array($inst['id'], $seen, true)) {
                $inst['id'] = $base_id . '-' . $n++;
            }
            $seen[] = $inst['id'];
            $out['instances'][] = $inst;
        }
    }

    update_option(RLM_OPTION, $out);
    add_settings_error('rlm', 'rlm_saved', 'Settings saved.', 'updated');
}
add_action('admin_init', 'rlm_maybe_save');

function rlm_sanitize_instance($row)
{
    $d = rlm_default_instance();
    return [
        'id'           => isset($row['id']) ? sanitize_title($row['id']) : '',
        'label'        => isset($row['label']) ? sanitize_text_field($row['label']) : '',
        'target'       => isset($row['target']) ? sanitize_text_field($row['target']) : '',
        'scope'        => isset($row['scope']) ? sanitize_text_field($row['scope']) : '',
        'enable_loadmore' => !empty($row['enable_loadmore']) ? 1 : 0,
        'visible'      => isset($row['visible']) ? max(1, (int) $row['visible']) : $d['visible'],
        'step'         => isset($row['step']) ? max(1, (int) $row['step']) : $d['step'],
        'button_label' => isset($row['button_label']) ? sanitize_text_field($row['button_label']) : $d['button_label'],
        'icon_url'     => isset($row['icon_url']) ? esc_url_raw($row['icon_url']) : '',
        'icon_pos'     => in_array(($row['icon_pos'] ?? ''), ['before', 'after'], true) ? $row['icon_pos'] : 'before',
        'enable_popup' => !empty($row['enable_popup']) ? 1 : 0,
        'popup_delegate' => isset($row['popup_delegate']) && $row['popup_delegate'] !== '' ? sanitize_text_field($row['popup_delegate']) : 'a',
        'autoload'     => !empty($row['autoload']) ? 1 : 0,
        'animation'    => in_array(($row['animation'] ?? ''), ['fade', 'slide', 'none'], true) ? $row['animation'] : 'fade',
        'duration'     => isset($row['duration']) ? max(0, (int) $row['duration']) : $d['duration'],
        'align'        => in_array(($row['align'] ?? ''), ['left', 'center', 'right'], true) ? $row['align'] : 'center',
        'button_class' => isset($row['button_class']) ? sanitize_text_field($row['button_class']) : $d['button_class'],
        'enabled'      => !empty($row['enabled']) ? 1 : 0,
        'source'          => in_array(($row['source'] ?? ''), ['selector', 'posts'], true) ? $row['source'] : 'selector',
        'query_context'   => in_array(($row['query_context'] ?? ''), ['manual', 'auto'], true) ? $row['query_context'] : 'manual',
        'post_type'       => isset($row['post_type']) ? sanitize_key($row['post_type']) : 'post',
        'author_mode'     => in_array(($row['author_mode'] ?? ''), ['none', 'current', 'specific'], true) ? $row['author_mode'] : 'none',
        'cat_mode'        => in_array(($row['cat_mode'] ?? ''), ['fixed', 'current'], true) ? $row['cat_mode'] : 'fixed',
        'tag'             => isset($row['tag']) ? sanitize_text_field($row['tag']) : '',
        'tag_mode'        => in_array(($row['tag_mode'] ?? ''), ['none', 'fixed', 'current'], true) ? $row['tag_mode'] : 'none',
        'exclude_current' => !empty($row['exclude_current']) ? 1 : 0,
        'no_results_text' => isset($row['no_results_text']) ? sanitize_text_field($row['no_results_text']) : 'No posts found.',
        'author_id'       => isset($row['author_id']) ? preg_replace('/[^0-9]/', '', $row['author_id']) : '',
        'posts_per_page'  => isset($row['posts_per_page']) ? (int) $row['posts_per_page'] : -1,
        'category'        => isset($row['category']) ? sanitize_text_field($row['category']) : '',
        'orderby'         => in_array(($row['orderby'] ?? ''), ['date', 'title', 'menu_order', 'rand'], true) ? $row['orderby'] : 'date',
        'order'           => in_array(strtoupper($row['order'] ?? ''), ['ASC', 'DESC'], true) ? strtoupper($row['order']) : 'DESC',
        'grid_wrap_class' => isset($row['grid_wrap_class']) ? sanitize_text_field($row['grid_wrap_class']) : 'row g-4',
        'item_wrap_class' => isset($row['item_wrap_class']) ? sanitize_text_field($row['item_wrap_class']) : 'col-12 col-md-6 col-lg-4',
        'col_mode'        => in_array(($row['col_mode'] ?? ''), ['auto', 'class', 'percent'], true) ? $row['col_mode'] : 'auto',
        'col_min'         => isset($row['col_min']) ? sanitize_text_field($row['col_min']) : '280px',
        'col_width'       => isset($row['col_width']) ? preg_replace('/[^0-9.]/', '', $row['col_width']) : '33.33',
        'col_gap'         => isset($row['col_gap']) ? sanitize_text_field($row['col_gap']) : '',
        'item_padding'    => isset($row['item_padding']) ? sanitize_text_field($row['item_padding']) : '',
        'item_margin'     => isset($row['item_margin']) ? sanitize_text_field($row['item_margin']) : '',
        'card_source'     => in_array(($row['card_source'] ?? ''), ['inline', 'template', 'wp_default'], true) ? $row['card_source'] : 'inline',
        'card_template_part' => isset($row['card_template_part']) ? sanitize_text_field($row['card_template_part']) : '',
        'card_template'   => isset($row['card_template']) ? wp_kses_post($row['card_template']) : '',
    ];
}

require plugin_dir_path(__FILE__) . 'admin-page.php';
