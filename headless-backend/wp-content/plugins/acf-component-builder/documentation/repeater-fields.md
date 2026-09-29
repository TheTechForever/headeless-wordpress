# Developer API

All facade methods **return** HTML (they do not echo), so you can echo or
capture. The `acf_component_*` functions **echo**.

## Facade (returns string)

```php
ACB::render(string $slug, array $args = []): string
ACB::render_flexible(string $field_name, array $args = []): string
ACB::render_layout(string $layout_slug, array $args = []): string
ACB::components(): array   // slug => ACB\Component
```

## Procedural helpers

```php
acf_component('hero');                     // echoes
acf_component_get('hero');                 // returns string
acf_component_flexible('services_inner_detail_section'); // echoes
acf_component_layout('services-inner-detail-content-only-section'); // echoes
```

## `$args`

| key       | type        | meaning                                   |
|-----------|-------------|-------------------------------------------|
| `post_id` | int/string  | Post to read fields from (default current)|
| `class`   | string      | Extra classes merged onto the section     |
| `template`| string      | Named template hint (Phase 2)             |

## Examples

```php
echo ACB::render('hero', ['post_id' => get_the_ID()]);
echo ACB::render_flexible('about_detail_section');
```

## Shortcode

```
[acf_component name="hero" post_id="42" class="my-class"]
[acf_component flexible="services_inner_detail_section"]
```

## Hooks

Actions:
- `acb_booted( ACB\Plugin $plugin )`
- `acb_register_component( ACB\Component_Registry $registry )`
- `acb_before_render_component( ACB\Component $component, array $args )`
- `acb_after_render_component( ACB\Component $component, array $args )`
- `acb_flush_cache()` — call `do_action('acb_flush_cache')` to rebuild discovery.

Filters:
- `acb_component_template( array $resolved, ACB\Component $component )`
- `acb_after_render_component( string $html, ACB\Component $component, array $args )`
- `acb_field_value( mixed $value, string $name, array $node )`
- `acb_field_renderers( array $renderers )`
- `acb_layout_engines( array $builders )`
