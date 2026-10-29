# Launch checklist

Code and content are moved separately:

- **Code** (this repo): deploy only `mu-plugins/ff-*.php` and `themes/Facility-Flooring/`. Do not deploy `docs/` or the project files to the web root.
- **Content** (database + uploads): move it once, at launch, with a migration tool, and replace `http://sandbox.local` with the live domain. After launch, never overwrite the production database.

## Before

- [ ] Take a full backup of the current live site.
- [ ] Export the Redirection rules (JSON) from live.
- [ ] Check the live site's Code Snippets and either port or drop each one.

## Go live

- [ ] Migrate the database and uploads, and deploy the code.
- [ ] In `wp-config.php`, set `WP_ENVIRONMENT_TYPE` to `production` and `WP_DEBUG` to `false`.
- [ ] Set up SMTP so the forms send real email.
- [ ] Import the Redirection rules and test some old URLs.
- [ ] **In Complianz, set Wizard → Statistics → "add GTM" to Yes.** GTM is paused on the sandbox.
- [ ] In Complianz, run the cookie scan and the cookiedatabase.org sync.
- [ ] Check that Settings → Reading → "Discourage search engines" is off.
- [ ] Remove WordPress Importer and Child Theme Wizard.
- [ ] Revoke any application passwords created during development.

## After

- [ ] Run Elementor → Tools → Clear Files & Data, and purge the host cache.
- [ ] Check that the Site Guide shows all green and GTM is "On".
- [ ] Submit each of the 4 forms and confirm the emails arrive.
- [ ] Confirm tags fire only after the cookie banner is accepted (use Tag Assistant).
- [ ] Submit the sitemap in Google Search Console.
