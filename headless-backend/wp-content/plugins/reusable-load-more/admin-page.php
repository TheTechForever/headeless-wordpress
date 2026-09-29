<?php
if (!defined('ABSPATH')) {
    exit;
}

/* Renders one instance editor card. $i = array index, $inst = data. */
function rlm_render_instance_row($i, $inst)
{
    $inst = wp_parse_args($inst, rlm_default_instance());
    $name = "instances[$i]";
    $title = $inst['label'] ?: $inst['id'] ?: 'New section';
    ?>
    <div class="rlm-instance is-collapsed" data-index="<?php echo esc_attr($i); ?>" data-original-id="<?php echo esc_attr($inst['id']); ?>">

        <div class="rlm-instance-head js-rlm-collapse">
            <span class="dashicons dashicons-arrow-down-alt2 rlm-chevron"></span>
            <span class="rlm-instance-title">
                <strong class="rlm-title-text"><?php echo esc_html($title); ?></strong>
                <?php if ($inst['id']) : ?><span class="rlm-badge rlm-badge-id"><?php echo esc_html($inst['id']); ?></span><?php endif; ?>
            </span>
            <label class="rlm-toggle" onclick="event.stopPropagation();">
                <input type="checkbox" name="<?php echo $name; ?>[enabled]" value="1" <?php checked(1, (int) $inst['enabled']); ?>> Enabled
            </label>
            <button type="button" class="button-link rlm-remove">
                <span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;"></span> Remove
            </button>
        </div>

        <div class="rlm-instance-body">

            <fieldset class="rlm-fieldset">
                <legend>Identity &amp; Targeting</legend>

                <p class="rlm-mode-banner rlm-mode-posts" style="<?php echo ($inst['source'] === 'posts') ? '' : 'display:none;'; ?>">
                    <strong>Posts mode is ON.</strong> This section queries WordPress posts and builds the grid itself.
                    <strong>Target selector and Scope selector below are NOT used</strong> — you can leave them blank. Configure this section in the <em>Posts &amp; Card Template</em> box further down.
                </p>
                <p class="rlm-mode-banner rlm-mode-selector" style="<?php echo ($inst['source'] === 'posts') ? 'display:none;' : ''; ?>">
                    <strong>Selector mode is ON.</strong> This section does not query anything — it only reveals items that already exist on the page.
                    <strong>Target selector below is required.</strong> Tick "Render WordPress posts" in Features if you want the plugin to fetch posts instead.
                </p>

                <div class="rlm-grid">
                    <p class="rlm-field">
                        <label>ID / Slug <span class="rlm-hint">used in the shortcode</span></label>
                        <input type="text" class="rlm-id" name="<?php echo $name; ?>[id]" value="<?php echo esc_attr($inst['id']); ?>" placeholder="services">
                        <span class="rlm-desc">Unique name, lowercase, no spaces (use dashes). This is what goes in the shortcode: <code>[load_more id="your-slug"]</code>. Changing it later breaks any shortcode still using the old one.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Label <span class="rlm-hint">admin reference</span></label>
                        <input type="text" name="<?php echo $name; ?>[label]" value="<?php echo esc_attr($inst['label']); ?>" placeholder="Our Services">
                        <span class="rlm-desc">A friendly name so you can recognise this section in the list above. Never shown on the website.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Target selector <span class="rlm-hint">items to reveal</span></label>
                        <input type="text" name="<?php echo $name; ?>[target]" value="<?php echo esc_attr($inst['target']); ?>" placeholder=".services-grid-item">
                        <span class="rlm-desc">
                            <span class="rlm-tag rlm-tag-selector">Selector mode only</span>
                            The CSS class of <em>one card/item</em> in your existing grid, starting with a dot — e.g. <code>.services-grid-item</code>.
                            To find it: open the page, right-click a card → <em>Inspect</em>, and read the class on the repeating wrapper div.
                            <strong>Ignored completely when "Render WordPress posts" is ticked.</strong>
                        </span>
                    </p>
                    <p class="rlm-field">
                        <label>Scope selector <span class="rlm-hint">optional container</span></label>
                        <input type="text" name="<?php echo $name; ?>[scope]" value="<?php echo esc_attr($inst['scope']); ?>" placeholder=".services-grid-wrap">
                        <span class="rlm-desc">
                            <span class="rlm-tag rlm-tag-selector">Selector mode only</span>
                            Optional. The CSS class of the container that wraps all those items — e.g. <code>.services-grid-wrap</code>.
                            Only needed if the same item class is used elsewhere on the page and you want this button to affect just this grid.
                            <strong>Ignored completely when "Render WordPress posts" is ticked.</strong>
                        </span>
                    </p>
                </div>
            </fieldset>

            <fieldset class="rlm-fieldset rlm-features">
                <legend>Features</legend>
                <div class="rlm-grid">
                    <p class="rlm-field rlm-checks">
                        <label><input type="checkbox" class="rlm-feat-loadmore" name="<?php echo $name; ?>[enable_loadmore]" value="1" <?php checked(1, (int) $inst['enable_loadmore']); ?>> <strong>Enable Load More</strong></label>
                        <span class="rlm-desc">Shows the button and reveals items in batches. Untick to show everything at once (no button).</span>

                        <label><input type="checkbox" class="rlm-feat-popup rlm-popup-toggle" name="<?php echo $name; ?>[enable_popup]" value="1" <?php checked(1, (int) $inst['enable_popup']); ?>> <strong>Enable Popup (Magnific)</strong></label>
                        <span class="rlm-desc">Opens images inside each item in a lightbox instead of navigating away. Mostly for galleries. Configure it in the <em>Lightbox</em> box at the bottom.</span>

                        <label><input type="checkbox" class="rlm-source-toggle" name="<?php echo $name; ?>[source]" value="posts" <?php checked('posts', $inst['source']); ?>> <strong>Render WordPress posts (template)</strong></label>
                        <span class="rlm-desc">
                            <strong>The most important switch on this page.</strong><br>
                            <strong>Ticked</strong> = the plugin queries posts (by category / tag / author) and builds the grid for you. Use this for blog, category, tag, author and related-post lists. Fill in <em>Posts &amp; Card Template</em>; ignore Target/Scope.<br>
                            <strong>Unticked</strong> = the plugin touches nothing but existing HTML already on the page, and only reveals it in batches. Fill in <em>Target selector</em>; ignore Posts &amp; Card Template.
                        </span>
                    </p>
                </div>
            </fieldset>

            <?php
            // Live choices pulled from THIS site so the dropdowns list real content.
            $rlm_pt_choices   = function_exists('rlm_get_post_type_choices') ? rlm_get_post_type_choices() : [];
            $rlm_cat_choices  = function_exists('rlm_get_category_choices')  ? rlm_get_category_choices()  : [];
            $rlm_tag_choices  = function_exists('rlm_get_tag_choices')       ? rlm_get_tag_choices()       : [];
            $rlm_auth_choices = function_exists('rlm_get_author_choices')    ? rlm_get_author_choices()    : [];

            $rlm_ctx        = $inst['query_context'] ?? 'manual';
            $rlm_is_auto    = ($rlm_ctx === 'auto');
            $rlm_manual_hide = $rlm_is_auto ? 'display:none;' : '';

            $rlm_pt_known   = ($inst['post_type'] !== '' && isset($rlm_pt_choices[$inst['post_type']]));
            $rlm_cat_known  = ($inst['category']  !== '' && isset($rlm_cat_choices[$inst['category']]));
            $rlm_cat_manual = ($inst['category']  !== '' && !$rlm_cat_known);
            $rlm_tag_known  = ($inst['tag']       !== '' && isset($rlm_tag_choices[$inst['tag']]));
            $rlm_tag_manual = ($inst['tag']       !== '' && !$rlm_tag_known);
            $rlm_auth_known = ($inst['author_id'] !== '' && isset($rlm_auth_choices[(int) $inst['author_id']]));
            $rlm_auth_manual= ($inst['author_id'] !== '' && !$rlm_auth_known);
            ?>
            <fieldset class="rlm-fieldset rlm-posts-fieldset" style="<?php echo ($inst['source'] === 'posts') ? '' : 'display:none;'; ?>">
                <legend>Posts &amp; Card Template</legend>
                <div class="rlm-grid">

                    <p class="rlm-field rlm-span2">
                        <label>Query source <span class="rlm-hint">where posts come from</span></label>
                        <select class="rlm-context" name="<?php echo $name; ?>[query_context]">
                            <option value="manual" <?php selected('manual', $rlm_ctx); ?>>Manual filters (choose below)</option>
                            <option value="auto" <?php selected('auto', $rlm_ctx); ?>>Auto — follow the current page (any archive, any theme)</option>
                        </select>
                        <span class="rlm-desc">
                            <strong>Manual filters</strong> = you pick the post type / category / tag / author below; the grid always shows that same set on every page.<br>
                            <strong>Auto</strong> = the section mirrors whatever archive the visitor is on — the current category, tag, author, date, search, custom-taxonomy or custom-post-type archive — using WordPress' own page detection. It is <em>not</em> tied to <code>category.php</code>; it works on <code>archive.php</code>, classic themes and block (FSE) themes alike. Pick this for one reusable section that powers every category/tag/author page on the site. In Auto mode the Category / Tag / Author filters below are ignored.
                        </span>
                    </p>

                    <p class="rlm-field">
                        <label>Post type</label>
                        <select class="rlm-pt-select">
                            <?php foreach ($rlm_pt_choices as $rlm_slug => $rlm_lbl) : ?>
                                <option value="<?php echo esc_attr($rlm_slug); ?>" <?php selected($rlm_pt_known && $inst['post_type'] === $rlm_slug); ?>><?php echo esc_html($rlm_lbl . ' — ' . $rlm_slug); ?></option>
                            <?php endforeach; ?>
                            <option value="__manual__" <?php selected(!$rlm_pt_known); ?>>Enter manually…</option>
                        </select>
                        <input type="text" class="rlm-pt-manual" name="<?php echo $name; ?>[post_type]" value="<?php echo esc_attr($inst['post_type']); ?>" placeholder="post" style="margin-top:6px;<?php echo $rlm_pt_known ? 'display:none;' : ''; ?>">
                        <?php
                        // Flag a slug that is not registered on this site — the most
                        // common cause of "the shortcode shows nothing".
                        $rlm_pt_bad = ($inst['post_type'] !== '' && function_exists('post_type_exists') && !post_type_exists($inst['post_type']));
                        ?>
                        <span class="rlm-pt-warning" style="<?php echo $rlm_pt_bad ? '' : 'display:none;'; ?>">
                            <strong>“<span class="rlm-pt-warning-slug"><?php echo esc_html($inst['post_type']); ?></span>” is not a registered post type</strong> on this site, so this section will find no posts. Ordinary blog posts use <code>post</code>. Pick one from the dropdown above.
                        </span>
                        <span class="rlm-desc">Which content to pull. The dropdown lists the public post types registered on this site. Pick one, or choose <strong>Enter manually…</strong> to type a slug by hand (useful if the post type is registered later by a theme/plugin). <code>post</code> = blog posts, <code>page</code> = pages, or a custom slug like <code>case-studies</code>. Note: “blog” is almost never a post type — the blog page lists the <code>post</code> type.</span>
                    </p>
                    <p class="rlm-field">
                        <label>How many to load <span class="rlm-hint">-1 = all</span></label>
                        <input type="number" name="<?php echo $name; ?>[posts_per_page]" value="<?php echo esc_attr($inst['posts_per_page']); ?>">
                        <span class="rlm-desc">Total posts fetched from the database — <em>not</em> how many are shown at once (that's "Initial visible" below). Leave <code>-1</code> to fetch all and let Load More reveal them gradually.</span>
                    </p>

                    <p class="rlm-field rlm-manual-scope" style="<?php echo $rlm_manual_hide; ?>">
                        <label>Category filter</label>
                        <select class="rlm-catmode" name="<?php echo $name; ?>[cat_mode]">
                            <option value="fixed" <?php selected('fixed', $inst['cat_mode']); ?>>Choose a category (below)</option>
                            <option value="current" <?php selected('current', $inst['cat_mode']); ?>>Current category page (auto-detect)</option>
                        </select>
                        <span class="rlm-desc"><strong>Choose a category</strong> = always the one you select below, on any page. <strong>Current category page</strong> = auto-detects whichever category is being viewed — this now works on any theme's category archive (classic <code>category.php</code>, <code>archive.php</code>, or a block theme), not just <code>category.php</code>. Leave "Choose a category" set to "All categories" for no category filter.</span>
                    </p>
                    <p class="rlm-field rlm-manual-scope rlm-catfixed" style="<?php echo ($rlm_is_auto || $inst['cat_mode'] === 'current') ? 'display:none;' : ''; ?>">
                        <label>Category <span class="rlm-hint">from this site</span></label>
                        <select class="rlm-cat-select">
                            <option value="" <?php selected($inst['category'] === '' && !$rlm_cat_manual); ?>>— All categories —</option>
                            <?php foreach ($rlm_cat_choices as $rlm_slug => $rlm_lbl) : ?>
                                <option value="<?php echo esc_attr($rlm_slug); ?>" <?php selected($rlm_cat_known && $inst['category'] === $rlm_slug); ?>><?php echo esc_html($rlm_lbl); ?></option>
                            <?php endforeach; ?>
                            <option value="__manual__" <?php selected($rlm_cat_manual); ?>>Enter slug / ID manually…</option>
                        </select>
                        <input type="text" class="rlm-cat-manual" name="<?php echo $name; ?>[category]" value="<?php echo esc_attr($inst['category']); ?>" placeholder="category-slug or ID" style="margin-top:6px;<?php echo $rlm_cat_manual ? '' : 'display:none;'; ?>">
                        <span class="rlm-desc">Pick a category from this site (shown with its post count), or choose <strong>Enter manually…</strong> to type a slug or numeric ID. "All categories" = no category filter.</span>
                    </p>

                    <p class="rlm-field rlm-manual-scope" style="<?php echo $rlm_manual_hide; ?>">
                        <label>Tag filter</label>
                        <select class="rlm-tagmode" name="<?php echo $name; ?>[tag_mode]">
                            <option value="none" <?php selected('none', $inst['tag_mode']); ?>>No tag filter</option>
                            <option value="fixed" <?php selected('fixed', $inst['tag_mode']); ?>>Choose a tag (below)</option>
                            <option value="current" <?php selected('current', $inst['tag_mode']); ?>>Current tag page (auto-detect)</option>
                        </select>
                        <span class="rlm-desc">Same idea as Category filter. <strong>Current tag page</strong> auto-detects the tag being viewed on any theme's tag archive. Leave on "No tag filter" unless you need it.</span>
                    </p>
                    <p class="rlm-field rlm-manual-scope rlm-tagfixed" style="<?php echo ($rlm_is_auto || $inst['tag_mode'] !== 'fixed') ? 'display:none;' : ''; ?>">
                        <label>Tag <span class="rlm-hint">from this site</span></label>
                        <select class="rlm-tag-select">
                            <option value="" <?php selected($inst['tag'] === '' && !$rlm_tag_manual); ?>>— Select a tag —</option>
                            <?php foreach ($rlm_tag_choices as $rlm_slug => $rlm_lbl) : ?>
                                <option value="<?php echo esc_attr($rlm_slug); ?>" <?php selected($rlm_tag_known && $inst['tag'] === $rlm_slug); ?>><?php echo esc_html($rlm_lbl); ?></option>
                            <?php endforeach; ?>
                            <option value="__manual__" <?php selected($rlm_tag_manual); ?>>Enter slug / ID manually…</option>
                        </select>
                        <input type="text" class="rlm-tag-manual" name="<?php echo $name; ?>[tag]" value="<?php echo esc_attr($inst['tag']); ?>" placeholder="tag-slug or ID" style="margin-top:6px;<?php echo $rlm_tag_manual ? '' : 'display:none;'; ?>">
                        <span class="rlm-desc">Pick a tag from this site, or choose <strong>Enter manually…</strong> to type a slug or numeric ID.</span>
                    </p>

                    <p class="rlm-field rlm-manual-scope" style="<?php echo $rlm_manual_hide; ?>">
                        <label>Author filter</label>
                        <select class="rlm-authormode" name="<?php echo $name; ?>[author_mode]">
                            <option value="none" <?php selected('none', $inst['author_mode']); ?>>All authors</option>
                            <option value="current" <?php selected('current', $inst['author_mode']); ?>>Current author page (auto-detect)</option>
                            <option value="specific" <?php selected('specific', $inst['author_mode']); ?>>Specific author (choose below)</option>
                        </select>
                        <span class="rlm-desc"><strong>Current author page</strong> auto-detects the author on any theme's author archive (it reads the queried user, so it can never mistake a category or tag for an author). Use <strong>All authors</strong> unless this section is specifically for an author archive.</span>
                    </p>
                    <p class="rlm-field rlm-manual-scope rlm-authorid" style="<?php echo ($rlm_is_auto || $inst['author_mode'] !== 'specific') ? 'display:none;' : ''; ?>">
                        <label>Author <span class="rlm-hint">from this site</span></label>
                        <select class="rlm-author-select">
                            <option value="" <?php selected($inst['author_id'] === '' && !$rlm_auth_manual); ?>>— Select an author —</option>
                            <?php foreach ($rlm_auth_choices as $rlm_uid => $rlm_lbl) : ?>
                                <option value="<?php echo esc_attr($rlm_uid); ?>" <?php selected($rlm_auth_known && (int) $inst['author_id'] === (int) $rlm_uid); ?>><?php echo esc_html($rlm_lbl); ?></option>
                            <?php endforeach; ?>
                            <option value="__manual__" <?php selected($rlm_auth_manual); ?>>Enter user ID manually…</option>
                        </select>
                        <input type="text" class="rlm-author-manual" name="<?php echo $name; ?>[author_id]" value="<?php echo esc_attr($inst['author_id']); ?>" placeholder="1" style="margin-top:6px;<?php echo $rlm_auth_manual ? '' : 'display:none;'; ?>">
                        <span class="rlm-desc">Pick an author from this site (users who have published posts), or choose <strong>Enter manually…</strong> to type a numeric user ID.</span>
                    </p>
                    <p class="rlm-field">
                        <label>No-results text</label>
                        <input type="text" name="<?php echo $name; ?>[no_results_text]" value="<?php echo esc_attr($inst['no_results_text']); ?>" placeholder="No posts found.">
                        <span class="rlm-desc">Shown to visitors when the query finds nothing (e.g. an empty category). Leave blank to show nothing at all.</span>
                    </p>
                    <p class="rlm-field rlm-checks">
                        <label><input type="checkbox" name="<?php echo $name; ?>[exclude_current]" value="1" <?php checked(1, (int) $inst['exclude_current']); ?>> Exclude current post <span class="rlm-hint">(for related lists)</span></label>
                        <span class="rlm-desc">Hides the post being viewed from its own list. Tick this for "Related posts" under a single post; leave unticked on archive pages.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Order</label>
                        <select name="<?php echo $name; ?>[order]">
                            <option value="DESC" <?php selected('DESC', $inst['order']); ?>>Newest first</option>
                            <option value="ASC" <?php selected('ASC', $inst['order']); ?>>Oldest first</option>
                        </select>
                        <span class="rlm-desc">Sorts by publish date. "Newest first" is the normal choice for a blog.</span>
                    </p>
                    <?php $rlm_uses_classes = (($inst['col_mode'] ?? 'auto') === 'class'); ?>
                    <p class="rlm-field rlm-span2 rlm-gridclass" style="<?php echo $rlm_uses_classes ? '' : 'display:none;'; ?>">
                        <label>Grid wrapper class <span class="rlm-hint">row container</span></label>
                        <input type="text" name="<?php echo $name; ?>[grid_wrap_class]" value="<?php echo esc_attr($inst['grid_wrap_class']); ?>" placeholder="row g-4">
                        <span class="rlm-desc">Classes for the div wrapping <em>all</em> the cards. <code>row g-4</code> for a standard Bootstrap row with gutters. Type class names only — no dot. Only used when Column width mode = Framework classes.</span>
                    </p>
                    <p class="rlm-field rlm-span2 rlm-itemclass" style="<?php echo $rlm_uses_classes ? '' : 'display:none;'; ?>">
                        <label>Item (column) class <span class="rlm-hint">used when width mode = Framework classes</span></label>
                        <input type="text" name="<?php echo $name; ?>[item_wrap_class]" value="<?php echo esc_attr($inst['item_wrap_class']); ?>" placeholder="col-12 col-md-6 col-lg-4">
                        <span class="rlm-desc">Classes for the div wrapping <em>each single</em> card — this controls how many fit per row. <code>col-12 col-md-6 col-lg-4</code> = 1 on mobile, 2 on tablet, 3 on desktop. No dot. Only used when Column width mode = Framework classes.</span>
                    </p>

                    <p class="rlm-field">
                        <label>Column width mode</label>
                        <select class="rlm-colmode" name="<?php echo $name; ?>[col_mode]">
                            <option value="auto" <?php selected('auto', $inst['col_mode']); ?>>Auto grid (works on any theme)</option>
                            <option value="class" <?php selected('class', $inst['col_mode']); ?>>Framework classes (Bootstrap etc.)</option>
                            <option value="percent" <?php selected('percent', $inst['col_mode']); ?>>Custom width (%)</option>
                        </select>
                        <span class="rlm-desc">
                            How card widths are decided.<br>
                            <strong>Auto grid</strong> — recommended, and the safe default. Uses CSS Grid with no framework classes, so it lays out correctly on <em>any</em> theme, including block themes like Twenty Twenty-Five that do not load Bootstrap. Cards wrap responsively based on the minimum width below.<br>
                            <strong>Framework classes</strong> — pick this only if your theme actually loads Bootstrap (or a similar grid). Uses the Grid wrapper / Item column class fields above.<br>
                            <strong>Custom width (%)</strong> — inline flexbox at one fixed percentage for every screen size.
                        </span>
                    </p>
                    <p class="rlm-field rlm-colmin" style="<?php echo (($inst['col_mode'] ?? 'auto') === 'auto') ? '' : 'display:none;'; ?>">
                        <label>Min card width <span class="rlm-hint">e.g. 280px</span></label>
                        <input type="text" name="<?php echo $name; ?>[col_min]" value="<?php echo esc_attr($inst['col_min'] ?? '280px'); ?>" placeholder="280px">
                        <span class="rlm-desc">In Auto grid mode, cards are at least this wide and the row fits as many as will comfortably go, wrapping on smaller screens automatically. Include the unit. Smaller value = more cards per row: <code>240px</code> is denser, <code>360px</code> is roomier. Cards never overflow on mobile.</span>
                    </p>
                    <p class="rlm-field rlm-colwidth" style="<?php echo ($inst['col_mode'] === 'percent') ? '' : 'display:none;'; ?>">
                        <label>Column width (%) <span class="rlm-hint">e.g. 33.33 for 3 per row</span></label>
                        <input type="text" name="<?php echo $name; ?>[col_width]" value="<?php echo esc_attr($inst['col_width']); ?>" placeholder="33.33">
                        <span class="rlm-desc">Number only, no % sign. <code>50</code> = 2 per row, <code>33.33</code> = 3 per row, <code>25</code> = 4 per row. Note: this is one fixed width at all screen sizes.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Column gap <span class="rlm-hint">e.g. 24px or 1.5rem</span></label>
                        <input type="text" name="<?php echo $name; ?>[col_gap]" value="<?php echo esc_attr($inst['col_gap']); ?>" placeholder="24px">
                        <span class="rlm-desc">Space between cards. Include the unit (<code>24px</code>, <code>1.5rem</code>). In Auto grid mode this defaults to <code>24px</code> when left empty. Leave empty if your Bootstrap row class (e.g. <code>g-4</code>) already handles spacing.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Item padding <span class="rlm-hint">e.g. 16px or 10px 20px</span></label>
                        <input type="text" name="<?php echo $name; ?>[item_padding]" value="<?php echo esc_attr($inst['item_padding']); ?>" placeholder="0">
                        <span class="rlm-desc">Inline padding on each card's wrapper. Standard CSS shorthand. Usually leave empty and style inside your card template instead.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Item margin <span class="rlm-hint">e.g. 0 0 24px</span></label>
                        <input type="text" name="<?php echo $name; ?>[item_margin]" value="<?php echo esc_attr($inst['item_margin']); ?>" placeholder="0">
                        <span class="rlm-desc">Inline margin on each card's wrapper. Careful with Bootstrap rows — margins can break the column maths. Prefer Column gap.</span>
                    </p>

                    <p class="rlm-field rlm-span2">
                        <label>Card source</label>
                        <select class="rlm-cardsource" name="<?php echo $name; ?>[card_source]">
                            <option value="wp_default" <?php selected('wp_default', $inst['card_source']); ?>>Default WordPress layout (theme's own post template)</option>
                            <option value="inline" <?php selected('inline', $inst['card_source']); ?>>Inline template (HTML + placeholders)</option>
                            <option value="template" <?php selected('template', $inst['card_source']); ?>>Theme template part (PHP file)</option>
                        </select>
                        <span class="rlm-desc">
                            Where the HTML for one card comes from.<br>
                            <strong>Default WordPress layout</strong> = no setup. The plugin renders each post with your theme's own post template (<code>template-parts/content.php</code> or <code>content.php</code>), so cards match the rest of the site automatically. If the theme has no such file (a very minimal or block/FSE theme), a clean built-in post card is used instead. Works on any theme.<br>
                            <strong>Inline template</strong> = type HTML in the big box below using placeholders. Quick, no theme files needed. (Required field: the box must not be empty.)<br>
                            <strong>Theme template part</strong> = point at a specific PHP file in your theme. Most control (full ACF / WP function access). (Required field: the path below.)
                        </span>
                    </p>
                    <p class="rlm-field rlm-span2 rlm-cardpart" style="<?php echo ($inst['card_source'] === 'template') ? '' : 'display:none;'; ?>">
                        <label>Template part path <span class="rlm-hint">e.g. templates/common/blog-card</span></label>
                        <input type="text" name="<?php echo $name; ?>[card_template_part]" value="<?php echo esc_attr($inst['card_template_part']); ?>" placeholder="templates/common/blog-card">
                        <span class="rlm-desc">
                            <span class="rlm-tag rlm-tag-req">Required</span>
                            Path relative to your theme folder, <strong>without <code>.php</code></strong>.<br>
                            File at <code>wp-content/themes/your-theme/templates/common/template-blog-card.php</code> → type <code>templates/common/template-blog-card</code>.<br>
                            Leading slashes, the theme folder name, or <code>.php</code> will all break it.
                        </span>
                    </p>

                    <p class="rlm-field rlm-span4 rlm-carddefault-note" style="grid-column:1 / -1; <?php echo ($inst['card_source'] === 'wp_default') ? '' : 'display:none;'; ?>">
                        <span class="rlm-hint">
                            <strong>Nothing to configure.</strong> Each post is rendered with the theme's own content template part when one exists
                            (checked in this order: <code>template-parts/content-{format}.php</code>, <code>template-parts/content.php</code>,
                            <code>content-{format}.php</code>, <code>content.php</code>; child themes take priority). If none exists, a built-in card
                            (featured image, title, date &middot; author, excerpt, Read More) is used. The layout / column / gap fields above still apply to the wrapper around each card.
                        </span>
                    </p>

                    <p class="rlm-field rlm-span4 rlm-cardinline" style="grid-column:1 / -1; <?php echo ($inst['card_source'] === 'inline') ? '' : 'display:none;'; ?>">
                        <label>Card template <span class="rlm-hint">placeholders: {permalink} {title} {excerpt} {thumbnail} {thumbnail_url} {acf_image} {acf_image_alt} {date} {author}</span></label>
                        <textarea name="<?php echo $name; ?>[card_template]" rows="8" class="large-text code"><?php echo esc_textarea($inst['card_template']); ?></textarea>
                        <span class="rlm-desc">
                            <span class="rlm-tag rlm-tag-req">Required</span>
                            The HTML for <em>one</em> card — the plugin repeats it for every post and swaps the placeholders for real values. Don't add the column/wrapper div; that's built for you from the fields above.
                            <br><strong>Placeholders:</strong>
                            <code>{permalink}</code> post URL ·
                            <code>{title}</code> post title ·
                            <code>{excerpt}</code> excerpt, ~20 words ·
                            <code>{thumbnail}</code> full featured-image <code>&lt;img&gt;</code> tag ·
                            <code>{thumbnail_url}</code> just the featured-image URL (for your own <code>&lt;img src="…"&gt;</code>) ·
                            <code>{acf_image}</code> image URL using the fallback chain ACF <code>blog_banner_image</code> → featured image → ACF options <code>default_blog_image</code> ·
                            <code>{acf_image_alt}</code> its alt text ·
                            <code>{date}</code> publish date ·
                            <code>{author}</code> author name.
                        </span>
                    </p>
                    <p class="rlm-field rlm-span4 rlm-cardpart-note" style="grid-column:1 / -1; <?php echo ($inst['card_source'] === 'template') ? '' : 'display:none;'; ?>">
                        <span class="rlm-hint">The plugin runs <code>get_template_part()</code> for each post inside its loop, so your file can use <code>the_permalink()</code>, <code>the_title()</code>, <code>get_field()</code>, <code>the_post_thumbnail()</code>, etc. directly — no placeholders needed.</span>
                    </p>
                </div>
            </fieldset>

            <fieldset class="rlm-fieldset">
                <legend>Behaviour</legend>
                <div class="rlm-grid">
                    <p class="rlm-field">
                        <label>Initial visible</label>
                        <input type="number" min="1" name="<?php echo $name; ?>[visible]" value="<?php echo esc_attr($inst['visible']); ?>">
                        <span class="rlm-desc">How many cards are on screen before the visitor clicks anything. Set it to a full row (or rows) so the grid doesn't look half-finished — e.g. <code>6</code> for a 3-per-row layout.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Per click</label>
                        <input type="number" min="1" name="<?php echo $name; ?>[step]" value="<?php echo esc_attr($inst['step']); ?>">
                        <span class="rlm-desc">How many more appear on each click of the button. Matching your cards-per-row (e.g. <code>3</code>) keeps rows tidy. The button hides itself once everything is shown.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Animation</label>
                        <select name="<?php echo $name; ?>[animation]">
                            <?php foreach (['fade' => 'Fade', 'slide' => 'Slide up', 'none' => 'None'] as $val => $lbl) : ?>
                                <option value="<?php echo $val; ?>" <?php selected($val, $inst['animation']); ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="rlm-desc">How newly revealed cards appear. <strong>Fade</strong> = gentle opacity, <strong>Slide up</strong> = fade + rise, <strong>None</strong> = instant (best for very long lists).</span>
                    </p>
                    <p class="rlm-field">
                        <label>Duration (ms)</label>
                        <input type="number" min="0" name="<?php echo $name; ?>[duration]" value="<?php echo esc_attr($inst['duration']); ?>">
                        <span class="rlm-desc">Animation length in milliseconds (1000 = 1 second). <code>300</code>–<code>400</code> feels natural; above ~600 starts to feel slow. Ignored when Animation = None.</span>
                    </p>
                    <p class="rlm-field rlm-checks">
                        <label><input type="checkbox" name="<?php echo $name; ?>[autoload]" value="1" <?php checked(1, (int) $inst['autoload']); ?>> Auto-load on scroll</label>
                        <span class="rlm-desc">Infinite-scroll style: more cards load automatically when the button scrolls into view, no click needed. The button stays as a fallback.</span>
                    </p>
                </div>
            </fieldset>

            <fieldset class="rlm-fieldset">
                <legend>Button</legend>
                <div class="rlm-grid">
                    <p class="rlm-field">
                        <label>Button label</label>
                        <input type="text" name="<?php echo $name; ?>[button_label]" value="<?php echo esc_attr($inst['button_label']); ?>">
                        <span class="rlm-desc">The visible text on the button, e.g. "Load More", "View All", "Show more posts".</span>
                    </p>
                    <p class="rlm-field">
                        <label>Alignment</label>
                        <select name="<?php echo $name; ?>[align]">
                            <?php foreach (['left', 'center', 'right'] as $opt) : ?>
                                <option value="<?php echo $opt; ?>" <?php selected($opt, $inst['align']); ?>><?php echo ucfirst($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="rlm-desc">Where the button sits under the grid. Center is the usual choice.</span>
                    </p>
                    <p class="rlm-field rlm-span2">
                        <label>Button CSS class</label>
                        <input type="text" name="<?php echo $name; ?>[button_class]" value="<?php echo esc_attr($inst['button_class']); ?>">
                        <span class="rlm-desc">Your theme's button classes, so it matches the rest of the site — e.g. <code>light-oilve-green-rounded-btn font-w600</code>. Class names only, no dot, space-separated. Copy them from an existing button in your theme.</span>
                    </p>
                    <p class="rlm-field rlm-icon-field">
                        <label>Button icon <span class="rlm-hint">from media library</span></label>
                        <span class="rlm-icon-controls">
                            <input type="text" class="rlm-icon-url" name="<?php echo $name; ?>[icon_url]" value="<?php echo esc_attr($inst['icon_url']); ?>" placeholder="No icon selected" readonly>
                            <button type="button" class="button rlm-pick-icon">Choose</button>
                            <button type="button" class="button-link rlm-clear-icon" title="Remove icon">&times;</button>
                        </span>
                        <img class="rlm-icon-preview" src="<?php echo esc_url($inst['icon_url']); ?>" alt="" <?php echo empty($inst['icon_url']) ? 'style="display:none;"' : ''; ?>>
                        <span class="rlm-desc">Optional small image beside the label. Click <em>Choose</em> to pick from the Media Library (SVG or PNG work best), or <em>&times;</em> to remove it. Leave empty for a text-only button.</span>
                    </p>
                    <p class="rlm-field">
                        <label>Icon position</label>
                        <select name="<?php echo $name; ?>[icon_pos]">
                            <option value="before" <?php selected('before', $inst['icon_pos']); ?>>Before text</option>
                            <option value="after" <?php selected('after', $inst['icon_pos']); ?>>After text</option>
                        </select>
                        <span class="rlm-desc">Icon to the left ("Before") or right ("After") of the label. Only applies if an icon is set above.</span>
                    </p>
                </div>
            </fieldset>

            <fieldset class="rlm-fieldset">
                <legend>Lightbox</legend>
                <div class="rlm-grid">
                    <p class="rlm-field rlm-span2 rlm-popup-delegate" style="<?php echo empty($inst['enable_popup']) ? 'display:none;' : ''; ?>">
                        <label>Image link selector <span class="rlm-hint">anchor inside each item that links to the full image</span></label>
                        <input type="text" name="<?php echo $name; ?>[popup_delegate]" value="<?php echo esc_attr($inst['popup_delegate']); ?>" placeholder="a">
                        <span class="rlm-desc">Which link inside each card opens the lightbox instead of navigating away. <code>a</code> = every link (fine if each card is just one image link). If a card also has a title/read-more link, be specific — e.g. <code>a.blog-img</code> — or clicking those will try to open a lightbox too. The link's <code>href</code> must point at the full-size image file.</span>
                    </p>
                    <p class="rlm-field rlm-popup-hint" style="<?php echo !empty($inst['enable_popup']) ? 'display:none;' : ''; ?>">
                        <span class="rlm-hint">Tick "Enable Popup" above to configure the lightbox.</span>
                    </p>
                </div>
            </fieldset>

            <div class="rlm-section-actions">
                <button type="button" class="button button-primary rlm-save-section">Save Changes</button>
                <span class="rlm-save-status" aria-live="polite"></span>
            </div>

        </div>

        <div class="rlm-shortcode<?php echo $inst['id'] ? '' : ' is-empty'; ?>">
            <span><span class="dashicons dashicons-shortcode" style="vertical-align:middle;"></span> Shortcode:</span>
            <code class="rlm-sc-text">[load_more id="<?php echo esc_attr($inst['id']); ?>"]</code>
            <button type="button" class="button button-small rlm-copy" data-copy='[load_more id="<?php echo esc_attr($inst['id']); ?>"]'>Copy</button>
            <span class="rlm-helper-note">PHP: <code class="rlm-sc-php">rlm_load_more('<?php echo esc_attr($inst['id']); ?>');</code></span>
            <span class="rlm-sc-placeholder">Enter an ID / Slug above to generate the shortcode.</span>
            <span class="rlm-sc-unsaved">Not saved yet — save this section before using the shortcode.</span>
        </div>
    </div>
    <?php
}

function rlm_render_admin()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s = rlm_get_settings();
    $export = wp_json_encode($s, JSON_PRETTY_PRINT);
    ?>
    <div class="wrap rlm-wrap">

        <div class="rlm-header">
            <span class="rlm-logo"><span class="dashicons dashicons-update"></span></span>
            <div>
                <h1>Load More</h1>
                <p>Add a section for any grid or list, point it at your items, and drop in the shortcode.</p>
            </div>
        </div>

        <?php settings_errors('rlm'); ?>

        <h2 class="nav-tab-wrapper rlm-tabs">
            <a href="#tab-sections" class="nav-tab nav-tab-active">Sections</a>
            <a href="#tab-style" class="nav-tab">Style</a>
            <a href="#tab-tools" class="nav-tab">Import / Export</a>
            <a href="#tab-help" class="nav-tab">Help</a>
        </h2>

        <form method="post">
            <?php wp_nonce_field('rlm_save', 'rlm_nonce'); ?>

            <!-- SECTIONS -->
            <div class="rlm-tab-panel" id="tab-sections">
                <div class="rlm-sections-toolbar">
                    <p class="description" style="margin:0;">Click a section header to open it (others close automatically). Save a single section with its own button, or save everything at once.</p>
                    <button type="button" class="button button-primary rlm-save-all-sticky" id="rlm-save-all">Save All Sections</button>
                </div>

                <div id="rlm-instances">
                    <?php
                    if (!empty($s['instances'])) {
                        foreach ($s['instances'] as $i => $inst) {
                            rlm_render_instance_row($i, $inst);
                        }
                    }
                    ?>
                </div>

                <p><button type="button" class="button" id="rlm-add">+ Add Section</button></p>

                <script type="text/template" id="rlm-template">
                    <?php ob_start(); rlm_render_instance_row('__INDEX__', rlm_default_instance()); echo trim(ob_get_clean()); ?>
                </script>
            </div>

            <!-- STYLE -->
            <div class="rlm-tab-panel" id="tab-style" style="display:none;">
                <p class="description">Optional CSS injected on the front end. Target <code>.js-load-more</code>, <code>.load-more-wrap</code>, <code>.lm-btn-icon</code>, or your own classes.</p>
                <textarea name="custom_css" rows="12" class="large-text code" placeholder=".js-load-more { /* ... */ }"><?php echo esc_textarea($s['custom_css']); ?></textarea>
                <p><?php submit_button('Save Style', 'primary', 'submit', false); ?></p>
            </div>

            <!-- TOOLS -->
            <div class="rlm-tab-panel" id="tab-tools" style="display:none;">
                <h3>Export</h3>
                <p class="description">Download your full configuration as a <code>.json</code> file (for backup or moving to another site).</p>
                <textarea readonly rows="10" class="large-text code" id="rlm-export"><?php echo esc_textarea($export); ?></textarea>
                <p>
                    <button type="button" class="button button-primary" id="rlm-download-export">Download JSON file</button>
                    <button type="button" class="button rlm-copy" data-copy-target="#rlm-export">Copy to clipboard</button>
                </p>

                <h3 style="margin-top:24px;">Import</h3>
                <p class="description">Upload a previously exported <code>.json</code> file, or paste the JSON, then click Import. This replaces your current configuration.</p>
                <p>
                    <input type="file" id="rlm-import-file" accept="application/json,.json" />
                </p>
                <textarea name="rlm_import" id="rlm-import-text" rows="10" class="large-text code" placeholder='{ "instances": [ ... ] }'></textarea>
                <p>
                    <button type="submit" class="button button-primary">Import &amp; Save</button>
                    <span class="description">Uploading a file fills the box below; or paste JSON directly.</span>
                </p>
            </div>

            <!-- HELP -->
            <div class="rlm-tab-panel" id="tab-help" style="display:none;">
                <div class="rlm-help">

                    <h3>Two completely different modes — pick one first</h3>
                    <p>Every section works in exactly one of two modes, controlled by the <strong>"Render WordPress posts (template)"</strong> checkbox in Features. This is the single most important choice on the page, because it decides which other fields actually matter:</p>
                    <ul>
                        <li>
                            <strong>Mode A — Selector mode</strong> (checkbox <u>unchecked</u>).
                            Use this when you already have a grid of items on the page (built by a page builder, a shortcode, a manual block, etc.) and you just want a "Load More" button to reveal them a few at a time.
                            The plugin does <em>not</em> query WordPress or generate any HTML for the items — it only hides/reveals elements that already exist in the DOM.
                            In this mode, <strong>Target selector</strong> and <strong>Scope selector</strong> are the fields that matter. Everything under "Posts &amp; Card Template" is ignored.
                        </li>
                        <li>
                            <strong>Mode B — Posts mode</strong> (checkbox <u>checked</u>).
                            Use this when you want the plugin itself to query WordPress posts (by category, tag, author, etc.), build the grid HTML, and show a Load More button for it — this is what "Category Posts", "Tag Posts", "All Blogs", "Author Posts" are for.
                            In this mode, <strong>Target selector and Scope selector are not used at all</strong> — the plugin builds its own internal wrapper and item classes automatically. You can leave those two fields blank, or ignore whatever is in them. What matters instead is the whole "Posts &amp; Card Template" fieldset below.
                            <br>Inside Posts mode there is a further <strong>Query source</strong> choice: <em>Manual filters</em> (you choose post type / category / tag / author from dropdowns of this site's real content) or <em>Auto — follow the current page</em> (the section mirrors whatever archive the visitor is on, on any theme, so a single section can serve every category / tag / author / date / search page).
                        </li>
                    </ul>
                    <p><strong>Common mistake:</strong> duplicating a Selector-mode section (like "Our Services") to make a new Posts-mode section. The old Target/Scope values (e.g. <code>.services-grid-item</code>) get carried over, look important, but do nothing in Posts mode — while the real required field (Card template / Template part path) is the one that actually needs your attention.</p>

                    <h3>Field reference — Identity &amp; Targeting</h3>
                    <table class="widefat rlm-help-table">
                        <tbody>
                            <tr><td><strong>ID / Slug</strong></td><td>A unique short code with no spaces (e.g. <code>category-posts</code>). This is what you put in the shortcode <code>[load_more id="category-posts"]</code> or the PHP helper <code>rlm_load_more('category-posts')</code>. Changing it later breaks any shortcode/PHP call still using the old ID.</td></tr>
                            <tr><td><strong>Label</strong></td><td>Just a friendly name shown in this admin list. Purely cosmetic, never shown on the site.</td></tr>
                            <tr><td><strong>Target selector</strong></td><td><em>Selector mode only.</em> A CSS selector (e.g. <code>.blog-card</code>) matching every individual item you want the Load More button to reveal in batches. Ignored completely in Posts mode.</td></tr>
                            <tr><td><strong>Scope selector</strong></td><td><em>Selector mode only.</em> Optional. A CSS selector for the container to search inside, so the same Target class can be reused elsewhere on the site without the buttons interfering with each other. Ignored completely in Posts mode.</td></tr>
                        </tbody>
                    </table>

                    <h3>Field reference — Features</h3>
                    <table class="widefat rlm-help-table">
                        <tbody>
                            <tr><td><strong>Enable Load More</strong></td><td>Turns the "reveal in batches + button" behaviour on/off. If off (and Popup is also off), the section renders nothing.</td></tr>
                            <tr><td><strong>Enable Popup (Magnific)</strong></td><td>Turns on a lightbox for images inside each item, using the Magnific Popup library. Configure which link inside each item opens the lightbox under "Lightbox" below.</td></tr>
                            <tr><td><strong>Render WordPress posts (template)</strong></td><td>The Mode A/B switch described above. Check this for any section that should query and display actual WordPress posts (blog, category, tag, author archives, related posts, etc.).</td></tr>
                        </tbody>
                    </table>

                    <h3>Field reference — Posts &amp; Card Template (Posts mode only)</h3>
                    <table class="widefat rlm-help-table">
                        <tbody>
                            <tr><td><strong>Query source</strong></td><td><strong>Manual filters</strong> = use the Post type / Category / Tag / Author choices below; the same set shows on every page. <strong>Auto — follow the current page</strong> = the section mirrors whatever archive the visitor is on (current category, tag, author, date, search, custom taxonomy or custom-post-type archive), detected with WordPress' own conditional tags. Because it relies on core detection and the queried object — not on a specific template file — it works on <code>category.php</code>, <code>tag.php</code>, <code>author.php</code>, the generic <code>archive.php</code>, and block/FSE themes alike. One "Auto" section can replace a stack of per-term sections. In Auto mode the Category/Tag/Author filters are ignored.</td></tr>
                            <tr><td><strong>Post type</strong></td><td>The post type to query. The dropdown lists every public post type registered on this site; pick one, or choose <strong>Enter manually…</strong> to type a slug by hand (for a type registered later, or on another site). Examples: <code>post</code>, <code>page</code>, <code>case-studies</code>.</td></tr>
                            <tr><td><strong>How many to load</strong></td><td><code>-1</code> loads all matching posts (then reveals them gradually via Load More). A positive number caps the total query to that many posts.</td></tr>
                            <tr><td><strong>Category filter</strong></td><td>Only used when Query source = Manual. "Current category page (auto-detect)" reads the category being viewed from the queried term, so it works on any theme's category archive (not just <code>category.php</code>). "Choose a category" uses the one you select below — handy for a fixed "Related in X" block anywhere.</td></tr>
                            <tr><td><strong>Category</strong></td><td>Only used when Category filter = Choose a category. A dropdown of this site's categories (with post counts). Pick one, or choose <strong>Enter manually…</strong> to type a slug or numeric ID. "All categories" = no category filter.</td></tr>
                            <tr><td><strong>Tag filter</strong></td><td>Same pattern as Category filter: no tag filter / choose a tag below / current tag page (auto-detects on any theme's tag archive).</td></tr>
                            <tr><td><strong>Tag</strong></td><td>Only used when Tag filter = Choose a tag. A dropdown of this site's tags, or <strong>Enter manually…</strong> for a slug / numeric ID.</td></tr>
                            <tr><td><strong>Author filter</strong></td><td>All authors / "Current author page" (auto-detects the author from the queried user on any theme's author archive — it can't confuse a category or tag for an author) / "Specific author" (choose below).</td></tr>
                            <tr><td><strong>Author</strong></td><td>Only used when Author filter = Specific author. A dropdown of this site's authors (users who have published posts), or <strong>Enter manually…</strong> for a numeric user ID.</td></tr>
                            <tr><td><strong>No-results text</strong></td><td>Message shown when the query returns zero posts (e.g. an empty category).</td></tr>
                            <tr><td><strong>Exclude current post</strong></td><td>Removes the post currently being viewed from the results — turn this on for "related posts" style sections so a post never lists itself.</td></tr>
                            <tr><td><strong>Order</strong></td><td>Newest first (DESC) or Oldest first (ASC), by date.</td></tr>
                            <tr><td><strong>Grid wrapper class</strong></td><td>CSS class(es) put on the outer container that wraps every item, e.g. <code>row g-4</code> for a Bootstrap row. Ignored if Column width mode = Custom width (%).</td></tr>
                            <tr><td><strong>Item (column) class</strong></td><td>CSS class(es) put on each individual item's wrapper div, e.g. <code>col-12 col-md-6 col-lg-4</code> for a 3-per-row Bootstrap layout. Only used when Column width mode = Bootstrap class.</td></tr>
                            <tr><td><strong>Column width mode</strong></td><td>"Bootstrap class" uses the Item (column) class field above for layout (your grid framework's classes do the sizing). "Custom width (%)" switches to inline flexbox and uses the Column width (%) field instead — no Bootstrap/grid framework classes needed.</td></tr>
                            <tr><td><strong>Column width (%)</strong></td><td>Only used when Column width mode = Custom width (%). E.g. <code>33.33</code> for 3 items per row, <code>25</code> for 4 per row.</td></tr>
                            <tr><td><strong>Column gap</strong></td><td>Space between items, e.g. <code>24px</code> or <code>1.5rem</code>. Works in both column width modes.</td></tr>
                            <tr><td><strong>Item padding</strong></td><td>Inline padding applied to each item's wrapper div, e.g. <code>16px</code> or <code>10px 20px</code>.</td></tr>
                            <tr><td><strong>Item margin</strong></td><td>Inline margin applied to each item's wrapper div, e.g. <code>0 0 24px</code>.</td></tr>
                            <tr><td><strong>Card source</strong></td><td>
                                <p style="margin:0 0 6px;"><strong>Default WordPress layout:</strong> zero setup. Each post is rendered with the active theme's own post template part — it looks for <code>template-parts/content-{format}.php</code>, then <code>template-parts/content.php</code>, then <code>content-{format}.php</code>, then <code>content.php</code> (child theme first) — so cards match the theme's normal posts. If the theme ships none of those (a very minimal theme, or a block/FSE theme with no PHP content part), the plugin draws a clean built-in card instead. Works on any theme with no fields to fill in.</p>
                                <p style="margin:0 0 6px;"><strong>Inline template (HTML + placeholders):</strong> write raw HTML directly in the "Card template" box below, using placeholders like <code>{title}</code>, <code>{permalink}</code>, <code>{excerpt}</code>, <code>{thumbnail}</code>, <code>{thumbnail_url}</code>, <code>{acf_image}</code>, <code>{acf_image_alt}</code>, <code>{date}</code>, <code>{author}</code>. Simple and fast, but limited to what those placeholders expose.</p>
                                <p style="margin:0;"><strong>Theme template part (PHP file):</strong> point at an existing theme file (e.g. one you already use for blog cards elsewhere), and the plugin runs it inside its own WordPress loop for every post — so the file can call <code>the_title()</code>, <code>the_permalink()</code>, <code>get_field()</code>, <code>the_post_thumbnail()</code>, etc. directly, exactly like a normal template. Most control, and it's what "Category Posts" style sections typically use. <strong>When you pick this, fill in "Template part path" below — the inline "Card template" box is hidden and not used.</strong></p>
                            </td></tr>
                            <tr><td><strong>Template part path</strong></td><td>Only used when Card source = Theme template part. The path <em>without</em> the <code>.php</code> extension, relative to your theme, e.g. <code>templates/common/template-blog-card</code> for a file at <code>wp-content/themes/your-theme/templates/common/template-blog-card.php</code>. This is required for Posts mode to work when Card source = Theme template part.</td></tr>
                            <tr><td><strong>Card template</strong></td><td>Only used when Card source = Inline template. Raw HTML with placeholders (listed above). Required for Posts mode to work when Card source = Inline template — if left empty, the section will not render any posts.</td></tr>
                        </tbody>
                    </table>

                    <h3>Field reference — Behaviour</h3>
                    <table class="widefat rlm-help-table">
                        <tbody>
                            <tr><td><strong>Initial visible</strong></td><td>How many items are shown before any clicks — e.g. <code>6</code> shows the first 6 items on page load.</td></tr>
                            <tr><td><strong>Per click</strong></td><td>How many additional items get revealed each time the Load More button is clicked.</td></tr>
                            <tr><td><strong>Animation</strong></td><td>Fade, Slide up, or None, applied to newly revealed items.</td></tr>
                            <tr><td><strong>Duration (ms)</strong></td><td>How long the reveal animation takes, in milliseconds. Ignored if Animation = None.</td></tr>
                            <tr><td><strong>Auto-load on scroll</strong></td><td>When on, more items load automatically as the visitor scrolls the button into view, instead of requiring a click.</td></tr>
                        </tbody>
                    </table>

                    <h3>Field reference — Button</h3>
                    <table class="widefat rlm-help-table">
                        <tbody>
                            <tr><td><strong>Button label</strong></td><td>The text on the Load More button, e.g. "Load More".</td></tr>
                            <tr><td><strong>Alignment</strong></td><td>Left / Center / Right alignment of the button within its wrapper.</td></tr>
                            <tr><td><strong>Button CSS class</strong></td><td>CSS class(es) applied to the button for styling, e.g. <code>light-oilve-green-rounded-btn font-w600</code>.</td></tr>
                            <tr><td><strong>Button icon</strong></td><td>Optional icon (from the Media Library) shown next to the button text.</td></tr>
                            <tr><td><strong>Icon position</strong></td><td>Whether the icon appears before or after the button text.</td></tr>
                        </tbody>
                    </table>

                    <h3>Field reference — Lightbox (only relevant if "Enable Popup" is checked)</h3>
                    <table class="widefat rlm-help-table">
                        <tbody>
                            <tr><td><strong>Image link selector</strong></td><td>A CSS selector (e.g. <code>a</code>) matching the anchor tag inside each item that links to the full-size image, which the lightbox should intercept and open in a popup instead of navigating away.</td></tr>
                        </tbody>
                    </table>

                    <h3>Shortcode</h3>
                    <pre>[load_more id="category-posts"]</pre>
                    <p>Override any field inline: <code>[load_more id="category-posts" visible="4" step="2" button_label="See more"]</code></p>

                    <h3>PHP helper (in a template)</h3>
                    <pre>rlm_load_more('category-posts');
rlm_load_more('category-posts', ['visible' =&gt; 4]); // with overrides</pre>

                    <h3>Re-init after AJAX</h3>
                    <pre>if (window.RLM) { window.RLM.init(document.getElementById('blog-post-list')); }</pre>

                    <h3>Quick troubleshooting</h3>
                    <ul>
                        <li><strong>Nothing shows up at all (Posts mode):</strong> check Card source. "Default WordPress layout" always renders something, so this usually points to an empty query. If "Theme template part," make sure "Template part path" is filled in and the file exists in your active theme. If "Inline template," make sure the "Card template" box is not empty.</li>
                        <li><strong>Nothing shows up at all (Selector mode):</strong> open the page, inspect the HTML, and confirm the Target selector actually matches elements that exist in the DOM. If it matches nothing, the button hides itself.</li>
                        <li><strong>Posts show, but Load More button does nothing:</strong> check Initial visible / Per click values, and confirm "Enable Load More" is checked.</li>
                        <li><strong>Category/Tag/Author filter has no effect:</strong> if Query source = Auto, the manual Category/Tag/Author fields are intentionally ignored — the section follows the current page instead. If Query source = Manual, confirm you're on the matching archive when using an "auto-detect" mode, and that "Choose a category" isn't left on "All categories" with nothing selected.</li>
                        <li><strong>One section for every archive:</strong> set Query source = "Auto — follow the current page" and place it in your theme's archive area (or via the shortcode inside <code>archive.php</code> / a block template). It automatically shows the right posts on each category, tag, author, date and search page — no separate section per term.</li>
                    </ul>
                </div>
            </div>

        </form>
    </div>
    <?php
}
