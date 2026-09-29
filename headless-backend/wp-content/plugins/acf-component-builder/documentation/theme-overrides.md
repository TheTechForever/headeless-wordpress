# Theme Overrides

You can override any component's markup without touching the plugin.

## Resolution order (highest wins)

1. Saved template (admin `acb_template`) bound to the component slug.
2. **Child theme:** `wp-content/themes/<child>/acb-components/<slug>.php`
3. **Parent theme:** `wp-content/themes/<parent>/acb-components/<slug>.php`
4. **Plugin template:** `templates/<parent-field-slug>/<slug>.php` or `templates/<slug>.php`
5. Auto-generated template (field introspection).

`<slug>` is the layout slug, e.g.
`services-inner-detail-left-image-right-content-section`.

## Example override

Create `your-theme/acb-components/services-inner-detail-content-only-section.php`:

```php
<?php
// This file runs inside the Flexible Content the_row() context.
$head = new \ACB\Render\Heading_Renderer();
$wys  = new \ACB\Render\Wysiwyg_Renderer();
?>
<section class="my-content-only">
  <?php echo $head->render(get_sub_field('services_inner_content_only_title'), ['tag'=>'h2']); ?>
  <?php if (have_rows('services_inner_content_only_wrap')): while (have_rows('services_inner_content_only_wrap')): the_row(); ?>
    <?php echo $head->render(get_sub_field('content_title'), ['tag'=>'h3']); ?>
    <?php echo $wys->render(get_sub_field('content_text'), []); ?>
  <?php endwhile; endif; ?>
</section>
```

Inside a flexible layout template use `get_sub_field()` / `have_rows()` — the
row context is already active.

## Available variables in PHP templates

`$acb` array is injected: `component`, `post_id`, `in_loop`, `args`, and
`field` (a closure `fn(string $name)` that respects loop context).
