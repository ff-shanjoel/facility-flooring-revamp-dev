<?php
/**
 * Plugin Name: Facility Flooring Site Guide
 * Description: Read-only admin page (Site Guide, first item in the admin menu) documenting the site's custom code and where every other customisation is managed. Shows no code and changes nothing.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_admin() ) {
	return;
}

// Developers: when you add or change a feature in ff-content-model.php, update ff_site_guide_features() to match.

add_action( 'admin_menu', function () {
	// Position 1 places it at the very top, above Dashboard (position 2).
	add_menu_page( 'Site Guide', 'Site Guide', 'manage_options', 'ff-site-guide', 'ff_site_guide_render', 'dashicons-book-alt', '1' );
} );

// Full-width brand teal bar so the guide sits apart from the standard menu items, the same in every state.
add_action( 'admin_head', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<style id="ff-site-guide-menu">
	#adminmenu #toplevel_page_ff-site-guide{margin-bottom:8px}
	#adminmenu #toplevel_page_ff-site-guide > a,#adminmenu #toplevel_page_ff-site-guide > a:hover,#adminmenu #toplevel_page_ff-site-guide > a:focus{background:#0C6A66;color:#fff}
	#adminmenu #toplevel_page_ff-site-guide > a .wp-menu-image::before{color:#fff}
	</style>
	<?php
} );

// "Site Guide" link on the Must-Use plugins row of both helper files.
add_filter( 'plugin_row_meta', function ( $meta, $file ) {
	if ( in_array( $file, array( 'ff-content-model.php', 'ff-site-guide.php' ), true ) && current_user_can( 'manage_options' ) ) {
		$meta[] = '<a href="' . esc_url( admin_url( 'admin.php?page=ff-site-guide' ) ) . '">Site Guide</a>';
	}
	return $meta;
}, 10, 2 );

/**
 * Edit link for a post, or '' when it doesn't exist.
 */
function ff_site_guide_edit_link( $post_id, $label, $elementor = false ) {
	if ( ! $post_id || ! get_post( $post_id ) ) {
		return '';
	}
	$url = $elementor ? admin_url( 'post.php?post=' . (int) $post_id . '&action=elementor' ) : get_edit_post_link( $post_id, 'raw' );
	return $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' : '';
}

/**
 * Status badge: ok / warn / off.
 */
function ff_site_guide_badge( $state, $text ) {
	$colors = array( 'ok' => '#0C6A66', 'warn' => '#b32d2e', 'off' => '#646970' );
	return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;color:#fff;background:' . esc_attr( $colors[ $state ] ?? $colors['off'] ) . '">' . esc_html( $text ) . '</span>';
}

/**
 * Published Elementor documents containing a V4 form, with whether both spam-trap fields are present.
 */
function ff_site_guide_forms() {
	global $wpdb;
	$rows  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_title, pm.meta_value FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_elementor_data'
			 WHERE p.post_status = 'publish' AND pm.meta_value LIKE %s
			 ORDER BY p.post_title",
			'%' . $wpdb->esc_like( '"elType":"e-form"' ) . '%'
		)
	);
	$forms = array();
	foreach ( $rows as $row ) {
		$forms[] = array(
			'id'    => (int) $row->ID,
			'title' => $row->post_title,
			'trap'  => false !== strpos( $row->meta_value, 'ff-hp-website' ) && false !== strpos( $row->meta_value, 'ff-hp-ts' ),
		);
	}
	return $forms;
}

/**
 * The features implemented in ff-content-model.php. Plain descriptions only, no code.
 */
function ff_site_guide_features() {
	$placeholder = defined( 'FF_PLACEHOLDER_ID' ) ? FF_PLACEHOLDER_ID : 0;
	$spec_group  = ( defined( 'FF_SPEC_GROUP' ) && function_exists( 'acf_get_field_group' ) ) ? acf_get_field_group( FF_SPEC_GROUP ) : null;
	$resources   = get_page_by_path( 'resources' );

	return array(
		array(
			'title'  => 'Placeholder image',
			'what'   => 'Shows the branded placeholder wherever a post, page, product, service, solution or case study has no featured image, uses the old blank "coming soon" image, or its file is missing. Social share previews are left alone so Yoast uses the site share image instead.',
			'edit'   => 'To change the picture, replace the file of this media item (keep the same item). Pointing to a different media item needs a developer.',
			'hooks'  => 'post_thumbnail_id, has_post_thumbnail',
			'status' => wp_get_attachment_image( $placeholder, 'thumbnail', false, array( 'style' => 'max-width:60px;height:auto;border-radius:4px' ) ) ?: ff_site_guide_badge( 'warn', 'Placeholder image missing' ),
			'links'  => ff_site_guide_edit_link( $placeholder, 'Open placeholder in Media Library' ),
		),
		array(
			'title'  => 'Product specification table',
			'what'   => 'Adds the spec table to the bottom of every product page, built from the filled-in "Product Specifications" fields. Empty fields are skipped.',
			'edit'   => 'Edit values on each product. Add, rename or reorder spec rows in the ACF field group; the table follows automatically.',
			'hooks'  => 'the_content',
			'status' => $spec_group ? ff_site_guide_badge( 'ok', 'Field group found' ) : ff_site_guide_badge( 'warn', 'Field group missing' ),
			'links'  => $spec_group ? '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $spec_group['ID'] . '&action=edit' ) ) . '">Edit field group</a>' : '',
		),
		array(
			'title'  => '"Explore…" and related cards',
			'what'   => 'On products, services and solutions, adds a card grid of child pages ("Explore …") and of the items picked in the "Related Products" field ("Related products" / "Recommended for …").',
			'edit'   => 'Children come from each item\'s Parent setting. Related items are chosen on each item\'s edit screen.',
			'hooks'  => 'the_content',
			'status' => ff_site_guide_badge( 'ok', 'Automatic' ),
			'links'  => '',
		),
		array(
			'title'  => 'Resources directory',
			'what'   => 'Adds the searchable, tabbed product spec directory to the page with the slug "resources".',
			'edit'   => 'Content comes from the products themselves. Changing that page\'s slug turns the directory off.',
			'hooks'  => 'the_content',
			'status' => $resources ? ff_site_guide_badge( 'ok', 'Page found' ) : ff_site_guide_badge( 'warn', 'No "resources" page' ),
			'links'  => $resources ? ff_site_guide_edit_link( $resources->ID, 'Edit Resources page', true ) : '',
		),
		array(
			'title'  => 'Breadcrumbs',
			'what'   => 'Provides the [ff_breadcrumbs] shortcode used in the hero of the shared product / service / solution / case-study template.',
			'edit'   => 'Placement and styling of the hero are edited in the template. The trail itself follows the page hierarchy.',
			'hooks'  => 'ff_breadcrumbs (shortcode)',
			'status' => ff_site_guide_badge( 'ok', 'Automatic' ),
			'links'  => ff_site_guide_edit_link( 5983, 'Edit single template', true ),
		),
		array(
			'title'  => 'Elementor editing for custom post types',
			'what'   => 'Lets products, services, solutions and case studies be opened in Elementor.',
			'edit'   => 'Nothing to manage.',
			'hooks'  => 'option_elementor_cpt_support',
			'status' => ff_site_guide_badge( 'ok', 'Automatic' ),
			'links'  => '',
		),
		array(
			'title'  => 'Styles for the blocks above',
			'what'   => 'A small stylesheet (cards, spec table, breadcrumbs, Resources tabs) matching the Elementor colours and fonts.',
			'edit'   => 'Needs a developer. Everything else on the site is styled in Elementor.',
			'hooks'  => 'wp_head',
			'status' => ff_site_guide_badge( 'ok', 'Automatic' ),
			'links'  => '',
		),
		array(
			'title'  => 'Form spam protection',
			'what'   => 'Blocks bot submissions without a captcha. Each form has a hidden "Spam Trap" container: a field people never see, plus the time the page loaded. Submissions with the hidden field filled in, or sent within 3 seconds, are refused. The hidden fields are left out of notification emails.',
			'edit'   => 'Keep the "Spam Trap" container in each form (Elementor Navigator) and don\'t change its two CSS IDs. For a new form, copy the container from an existing one. Recipients, subjects and messages are edited in each form\'s settings in Elementor.',
			'hooks'  => 'elementor_pro/atomic_forms/spam_check, elementor_pro/atomic_forms/email_message',
			'status' => '',
			'links'  => '',
			'forms'  => true,
		),
	);
}

/**
 * Customisations that live in the dashboard (no code): label, details, URL, where, status.
 */
function ff_site_guide_dashboard_items() {
	$cmplz = get_option( 'cmplz_options', array() );
	$gtm   = is_array( $cmplz ) && 'google-tag-manager' === ( $cmplz['compile_statistics'] ?? '' ) && 'yes' === ( $cmplz['configuration_by_complianz'] ?? '' );

	return array(
		array( 'Google Tag Manager', 'Added to every page by Complianz, after the Consent Mode defaults. Tracking tags (GA4, Google Ads…) are managed inside GTM itself.', admin_url( 'admin.php?page=complianz#wizard/statistics-configuration' ), 'Complianz → Wizard → Statistics configuration', $gtm ? ff_site_guide_badge( 'ok', 'On' ) : ff_site_guide_badge( 'off', 'Paused' ) ),
		array( 'Cookie banner and policy', 'Banner text, colours and the Cookie Policy page.', admin_url( 'admin.php?page=complianz#banner' ), 'Complianz → Consent Banner', '' ),
		array( 'Header, footer and templates', 'Theme Builder templates for the header, footer, single items, archives, search and 404.', admin_url( 'edit.php?post_type=elementor_library&tabs_group=theme' ), 'Templates → Theme Builder', '' ),
		array( 'Forms', 'Recipients, subjects, success messages and fields, per form.', '', 'Edit the page in Elementor → select the form', '' ),
		array( 'Post types and fields', 'Products, Services, Solutions, Our Work, Product Types and the Product Specifications / Related Products fields.', admin_url( 'edit.php?post_type=acf-field-group' ), 'ACF', '' ),
		array( 'SEO', 'Titles, descriptions, social images and indexing.', admin_url( 'admin.php?page=wpseo_page_settings' ), 'Yoast SEO → Settings, and each page\'s Yoast box', '' ),
		array( 'Menus', 'Main menu and the footer "More Info Menu" (Privacy, Terms, Cookie Policy).', admin_url( 'nav-menus.php' ), 'Appearance → Menus', '' ),
		array( 'Redirects', 'Old URL to new URL redirects.', admin_url( 'tools.php?page=redirection.php' ), 'Tools → Redirection', '' ),
		array( 'Other scripts', 'Non-tracking snippets such as site verification. Tracking belongs in GTM so it respects the cookie banner.', admin_url( 'edit.php?post_type=elementor_snippet' ), 'Elementor → Custom Code', '' ),
	);
}

function ff_site_guide_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}
	?>
	<div class="wrap">
		<h1>Site Guide</h1>
		<p style="max-width:820px">Where this site's customisations live. Almost everything is managed in the dashboard. The few things Elementor and ACF can't do are handled by one developer file, <code>wp-content/mu-plugins/ff-content-model.php</code>: a "must-use" plugin, listed under Plugins → Must-Use, which can't be deactivated by accident. The theme files are unmodified.</p>

		<h2 style="margin-top:28px">Managed in the dashboard</h2>
		<table class="widefat striped" style="max-width:1100px">
			<thead><tr><th style="width:200px">What</th><th>Details</th><th style="width:300px">Where</th><th style="width:90px">Status</th></tr></thead>
			<tbody>
			<?php foreach ( ff_site_guide_dashboard_items() as $item ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $item[0] ); ?></strong></td>
					<td><?php echo esc_html( $item[1] ); ?></td>
					<td><?php echo $item[2] ? '<a href="' . esc_url( $item[2] ) . '">' . esc_html( $item[3] ) . '</a>' : esc_html( $item[3] ); ?></td>
					<td><?php echo wp_kses_post( $item[4] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2 style="margin-top:28px">Handled by the developer file</h2>
		<p style="max-width:820px">These run automatically. Content is edited in the dashboard as described; only the behaviour itself needs a developer. "Code reference" names the WordPress hooks to search for in the file.</p>
		<?php foreach ( ff_site_guide_features() as $feature ) : ?>
			<div class="card" style="max-width:1060px;margin-top:12px">
				<h3 style="margin-top:0;display:flex;gap:10px;align-items:center"><?php echo esc_html( $feature['title'] ); ?> <?php echo wp_kses_post( $feature['status'] ); ?></h3>
				<p><?php echo esc_html( $feature['what'] ); ?></p>
				<p><strong>To change:</strong> <?php echo esc_html( $feature['edit'] ); ?></p>
				<?php if ( ! empty( $feature['forms'] ) ) : ?>
					<table class="widefat striped" style="max-width:600px;margin-bottom:12px">
						<thead><tr><th>Form page</th><th style="width:150px">Spam trap</th></tr></thead>
						<tbody>
						<?php foreach ( ff_site_guide_forms() as $form ) : ?>
							<tr>
								<td><?php echo wp_kses_post( ff_site_guide_edit_link( $form['id'], $form['title'], true ) ); ?></td>
								<td><?php echo wp_kses_post( $form['trap'] ? ff_site_guide_badge( 'ok', 'In place' ) : ff_site_guide_badge( 'warn', 'Missing' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<p style="color:#646970;margin-bottom:0">
					Code reference: <code><?php echo esc_html( $feature['hooks'] ); ?></code>
					<?php echo $feature['links'] ? ' &nbsp;·&nbsp; ' . wp_kses_post( $feature['links'] ) : ''; ?>
				</p>
			</div>
		<?php endforeach; ?>

		<h2 style="margin-top:28px">Changing the developer file</h2>
		<ul style="list-style:disc;padding-left:20px;max-width:820px">
			<li>Edit it in a local or staging copy, keep it in version control, then deploy by Git or SFTP. It is never edited from the dashboard.</li>
			<li>To roll back, redeploy the previous version from version control.</li>
			<li>When you change a feature, update this guide (<code>wp-content/mu-plugins/ff-site-guide.php</code>) to match.</li>
		</ul>
	</div>
	<?php
}
