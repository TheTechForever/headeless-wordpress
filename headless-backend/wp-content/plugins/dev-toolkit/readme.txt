=== Dev Toolkit (Basic) ===
Contributors: youragency
Tags: workflow, setup, checklist, shortcodes, audit
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Automates the standard WordPress build checklist and audits the parts that can't be automated.

== Description ==
Dev Toolkit turns a manual site-build process document into repeatable actions:

* One-click site setup — disable comments, zero media image sizes, set the timezone (Australia/Sydney UTC+11), optionally lock media uploads.
* Common Fields — email, phone, address and social links edited in one place.
* Shortcodes — [dt_email] [dt_phone] [dt_address] [dt_social] [dt_year start="2019"].
* Content filters — external links open in a new tab with safe rel; bare emails become mailto: links.
* Accessibility — aria-labels added to slider prev/next arrows.
* Mobile — optional fixed phone bar at the bottom on small screens.
* Export — CSV inventory of Pages, Posts, Custom Post Types, Plugins and Themes for the backup step.
* Build audit — pass/warn/fail checks plus a manual reminder checklist for developer-craft items.

Dev Toolkit Pro adds form validation, a CSS boilerplate injector (container + media queries + fixed header), a child-theme generator, bulk install of the standard plugin list, per-page H1 scanning and PageSpeed lookup.

== Shortcodes ==
[dt_email link="true|false"]
[dt_phone link="true|false"]
[dt_address]
[dt_social class="dt-social"]
[dt_year start="2019"]

== Changelog ==
= 1.0.0 =
* Initial release.
