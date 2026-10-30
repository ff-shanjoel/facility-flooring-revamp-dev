# Facility Flooring — Website Revamp

This is the rebuild of [facilityflooring.ie](https://facilityflooring.ie). It is a WordPress site built with Elementor V4 and Pro Elements. It is in development on a local sandbox (`http://sandbox.local`) and has not launched yet.

This repository is the site's `wp-content` folder, and it tracks the child theme and the project documentation. Pages, templates, design settings, plugin settings and all custom code live in the WordPress database. Custom code is stored in the WPCode plugin, so no custom code is kept in files.

## Stack

| | |
|---|---|
| WordPress | 7.1, PHP 8.2, MySQL 8 |
| Theme | Hello Elementor 3.5.1 + child theme **Facility-Flooring** |
| Page builder | Elementor 4.3.5 (V4 editor) + PRO Elements 4.3.2 |
| Plugins | ACF 6.8, WPCode Lite 2.4, Yoast SEO 28.8, Complianz 7.5.6, Redirection 5.10 |

## What's in this repo

```
wp-content/
├── themes/Facility-Flooring/  Child theme (stylesheet enqueue only)
└── docs/                      Project documentation + ACF and WPCode exports
```

The `.gitignore` is an allow-list, so nothing else is tracked:

- WordPress core and third-party plugins: reinstall them at the versions listed above.
- `uploads/` and the database, which includes the WPCode snippets: move them with a migration tool such as All-in-One WP Migration.
- `wp-config.php`: it holds credentials, so never commit it.

## Local setup

1. Create a site in [Local](https://localwp.com/) (PHP 8.2, nginx, MySQL 8) and import a migration package of the site (database + uploads).
2. Clone this repo into the site's `wp-content` folder:

   ```bash
   cd "<site>/app/public/wp-content"
   git init
   git remote add origin https://github.com/ff-shanjoel/facility-flooring-revamp-dev.git
   git fetch origin
   git checkout -f -B main origin/main
   ```

3. Run **Elementor → Tools → Clear Files & Data**.
4. Open **Site Guide**, the first item in the admin menu, and check that all its status checks are green.

On Local, outgoing email is caught by Mailpit (Local → Tools).

## Documentation

- [How the site works](docs/how-it-works.md): where everything is managed, the content model and the templates
- [Custom code](docs/custom-code.md): the WPCode snippets, including the form spam protection
- [Launch checklist](docs/launch-checklist.md): what must change when the site goes live

## License

The code is released under the [MIT License](LICENSE). The Facility Flooring name, logo, images and site content are not covered by it.
