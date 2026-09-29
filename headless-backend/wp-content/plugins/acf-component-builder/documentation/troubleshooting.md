# Troubleshooting

**"requires Advanced Custom Fields PRO"** — install/activate ACF Pro. ACB never
fatals; it simply shows this notice and stays inert.

**Nothing renders / HTML comment appears** — enable `WP_DEBUG` to see the
reason (`component not found`, `no rows for flexible field`, etc.). Confirm the
field has data on the current post and that the field name is correct.

**Wrong scope for repeater values** — inside a flexible layout use
`get_sub_field()` / `have_rows()` (not `get_field()`). The JSON renderer and the
supplied PHP templates already do this.

**Styles missing** — ACB's `frontend.css` loads only when a component renders.
If you rely on Bootstrap classes, either enable "Load Bootstrap CSS" in Settings
or ensure your theme ships Bootstrap.

**Changes not appearing after import** — discovery is cached for an hour. Saving
Settings or re-importing calls `do_action('acb_flush_cache')` to rebuild it.
