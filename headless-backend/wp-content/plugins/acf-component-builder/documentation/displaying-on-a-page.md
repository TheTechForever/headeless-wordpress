# Displaying a component on a page

This is the step most people miss. **ACF stores content; it does not display it.**
This plugin is the display layer — but it still needs you to tell it *where* to
output a component. Building a template only changes *how* a component looks; it
does not place it on any page by itself.

There are three ways to display a component. Pick whichever suits you.

## 1. The "ACF Component" block (no code — recommended)

1. Edit the page or post where you want the component to appear.
2. Add a block and search for **ACF Component**.
3. In the block settings on the right, choose your component from the dropdown.
4. You'll see a live preview using that page's ACF data. Update the page.

The block renders through the same engine as everything else, so if you later
edit the component's template, every page using the block updates automatically.

If the preview says *"this component has no ACF data on the current post"*, it
means the page you're editing has no content entered in that ACF field yet — add
some rows to the field and the block will fill in.

## 2. Shortcode

Add a **Shortcode** block (or use any shortcode-capable area) and paste one of:

```
[acf_component flexible="services_inner_detail_section"]
[acf_component name="hero"]
```

By default it reads the current page's data. To pull from a specific page, add
its ID:

```
[acf_component flexible="services_inner_detail_section" post_id="42"]
```

You can also add a wrapper class: `class="my-extra-class"`.

## 3. PHP (theme templates)

For developers, call the API inside The Loop in your theme (e.g. `page.php`,
`single.php`, a block/template part):

```php
<?php echo ACB::render_flexible('services_inner_detail_section'); ?>
<?php echo ACB::render('hero'); ?>
```

## Finding the exact snippet

Go to **ACF Components → Components** and click **How to display** next to any
component. You'll get its ready-to-copy block name, shortcode, and PHP line. The
same panel is available inside the builder via **How to display this**.

## Why nothing shows — quick checklist

1. Is **ACF PRO** active?
2. Does the page actually have **content entered** in the ACF field? An empty
   field renders nothing (by design).
3. Is the field group's **Location Rule** set to show on that page?
4. Did you place a block / shortcode / PHP call on the page? Without one, the
   component never renders.
5. Turn on `WP_DEBUG` — the renderer leaves an HTML comment explaining any
   empty render (e.g. "no rows for field").
