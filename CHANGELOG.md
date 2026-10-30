# Changelog

## Unreleased

- All custom PHP, CSS and JavaScript moved from `mu-plugins/` into WPCode snippets ("FF: …"). The `mu-plugins` folder is gone.
- Site Guide now links to each snippet and warns if one is inactive.
- Added `docs/exports/wpcode-snippets.json` as a backup of the snippets.

## 1.0.0 — 2026-10-29

This is the first version of the revamp, running on the sandbox.

- Pages and Theme Builder templates rebuilt in Elementor V4, using the `ff-*` design system.
- ACF content model: Products, Services, Solutions and Our Work post types, the Product Types taxonomy, and the specification and related-product fields.
- `ff-content-model.php` 2.0.0: spec tables, product grids, the Resources directory, breadcrumbs, the placeholder image and form spam protection.
- `ff-site-guide.php` 1.0.0: a read-only Site Guide admin page.
- Yoast SEO metadata carried over from the live site and improved.
- Complianz cookie banner with Consent Mode and GTM. GTM is paused on the sandbox.
- Redirection plugin installed.
