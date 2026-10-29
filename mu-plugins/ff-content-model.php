<?php
/**
 * Plugin Name: Facility Flooring Theme Helpers
 * Description: Front-end rendering helpers for the Elementor theme templates. The content model itself (post types, Product Types taxonomy, Product Specifications and Related Products fields) is managed in ACF; this file only renders what ACF and Elementor can't: the spec table, child-product and related-product grids, the Resources spec directory, breadcrumbs, the placeholder-image fallback and form spam protection. Documented in the admin Site Guide page.
 * Version: 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Media library image used wherever a post has no usable featured image.
const FF_PLACEHOLDER_ID = 6284;
// Live site's blank "Image is coming soon" upload — treated as no image.
const FF_BLANK_IMAGE_IDS = array( 2696 );
// ACF field group whose fields make up the product spec table.
const FF_SPEC_GROUP = 'group_ff_product_specifications';

/**
 * Placeholder image: use the branded placeholder when a post has no featured image,
 * uses the blank legacy placeholder, or its file is missing from uploads.
 */
add_filter( 'post_thumbnail_id', function ( $thumbnail_id, $post ) {
	if ( is_admin() || ! $post || ! in_array( get_post_type( $post ), array( 'post', 'page', 'product', 'services', 'solutions', 'our_work' ), true ) ) {
		return $thumbnail_id;
	}
	// Leave the <head> alone so Yoast falls back to its default social share image instead of sharing the placeholder.
	if ( doing_action( 'wp_head' ) || doing_action( 'wpseo_head' ) ) {
		return in_array( (int) $thumbnail_id, FF_BLANK_IMAGE_IDS, true ) ? 0 : $thumbnail_id;
	}
	if ( ! $thumbnail_id || in_array( (int) $thumbnail_id, FF_BLANK_IMAGE_IDS, true ) ) {
		return FF_PLACEHOLDER_ID;
	}
	$file = get_attached_file( $thumbnail_id );
	return ( $file && file_exists( $file ) ) ? $thumbnail_id : FF_PLACEHOLDER_ID;
}, 10, 2 );

// has_post_thumbnail() checks meta directly, so report true where the placeholder applies.
add_filter( 'has_post_thumbnail', function ( $has, $post ) {
	return is_admin() ? $has : ( $has || (bool) get_post_thumbnail_id( $post ) );
}, 10, 2 );

/**
 * Form spam protection for the Elementor (V4) forms, without a plugin or captcha.
 * Each form carries two visually hidden fields:
 *  - ff-hp-website: a honeypot people never see; bots fill it in.
 *  - ff-hp-ts: the page render time (Current Date Time dynamic tag, Unix format); submissions under 3s are bots. date_i18n() matches the tag's clock.
 */
add_filter( 'elementor_pro/atomic_forms/spam_check', function ( $is_spam, $form_fields ) {
	if ( $is_spam ) {
		return true;
	}
	foreach ( (array) $form_fields as $field ) {
		// The hidden fields have no label or placeholder, so the form script posts their CSS ID as the label.
		$name  = is_array( $field ) ? (string) ( $field['label'] ?? '' ) : '';
		$value = is_array( $field ) ? trim( (string) ( $field['value'] ?? '' ) ) : '';
		if ( 'ff-hp-website' === $name && '' !== $value ) {
			return true;
		}
		if ( 'ff-hp-ts' === $name && ( ! ctype_digit( $value ) || (int) date_i18n( 'U' ) - (int) $value < 3 ) ) {
			return true;
		}
	}
	return false;
}, 20, 2 );

// Keep the hidden spam-check fields out of notification emails.
add_filter( 'elementor_pro/atomic_forms/email_message', function ( $message ) {
	return preg_replace( '/^ff-hp-(website|ts):[^\n]*(\n|<br>)?/mi', '', (string) $message );
} );

// Allow editing the ACF post types with Elementor.
add_filter( 'option_elementor_cpt_support', function ( $types ) {
	$types = is_array( $types ) ? $types : array( 'post', 'page' );
	return array_values( array_unique( array_merge( $types, array( 'product', 'services', 'solutions', 'our_work' ) ) ) );
} );

/**
 * Auto-render the dynamic blocks after the main content on single views, so post content stays plain prose:
 * spec table (ACF fields), child-product grid (page hierarchy), related products (ACF relationship)
 * and the product spec directory on the Resources page.
 */
add_filter( 'the_content', function ( $content ) {
	if ( is_admin() || ! is_singular() || ! in_the_loop() || get_the_ID() !== get_queried_object_id() ) {
		return $content;
	}
	$post = get_post();
	switch ( $post->post_type ) {
		case 'product':
			$content .= ff_specs_html( $post ) . ff_children_html( $post, 'Explore ' . get_the_title( $post ) ) . ff_related_html( $post, 'Related products' );
			break;
		case 'services':
		case 'solutions':
			$content .= ff_children_html( $post, 'Explore ' . get_the_title( $post ) ) . ff_related_html( $post, 'Recommended for ' . get_the_title( $post ) );
			break;
		case 'page':
			if ( 'resources' === $post->post_name ) {
				$content .= ff_resources_html();
			}
			break;
	}
	return $content;
}, 20 );

function ff_children_html( $post, $title ) {
	$children = get_posts( array(
		'post_type'      => $post->post_type,
		'post_parent'    => $post->ID,
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'post_status'    => 'publish',
	) );
	return $children ? ff_card_grid( $children, $title ) : '';
}

function ff_related_html( $post, $title ) {
	$ids = array_filter( array_map( 'intval', (array) get_post_meta( $post->ID, 'ff_related', true ) ) );
	if ( ! $ids ) {
		return '';
	}
	return ff_card_grid( get_posts( array( 'post_type' => 'any', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => -1 ) ), $title );
}

function ff_card_grid( $posts, $title ) {
	$out = '<div class="ff-sc">';
	if ( $title ) {
		$out .= '<h2 class="ff-sc-title">' . esc_html( $title ) . '</h2>';
	}
	$out .= '<div class="ff-sc-grid">';
	foreach ( $posts as $p ) {
		$excerpt = has_excerpt( $p ) ? get_the_excerpt( $p ) : wp_strip_all_tags( $p->post_content );
		$out    .= '<a class="ff-sc-card" href="' . esc_url( get_permalink( $p ) ) . '">';
		$out    .= get_the_post_thumbnail( $p, 'medium_large', array( 'class' => 'ff-sc-img', 'loading' => 'lazy' ) );
		$out    .= '<span class="ff-sc-body"><span class="ff-sc-name">' . esc_html( get_the_title( $p ) ) . '</span>';
		if ( $excerpt ) {
			$out .= '<span class="ff-sc-text">' . esc_html( wp_trim_words( $excerpt, 18 ) ) . '</span>';
		}
		$out .= '<span class="ff-sc-more">View details &rarr;</span></span></a>';
	}
	return $out . '</div></div>';
}

/**
 * Product spec table: every field in the ACF "Product Specifications" group, in ACF order,
 * using the ACF labels. Empty fields are skipped.
 */
function ff_specs_html( $post ) {
	if ( ! function_exists( 'acf_get_fields' ) ) {
		return '';
	}
	$rows = '';
	foreach ( (array) acf_get_fields( FF_SPEC_GROUP ) as $field ) {
		$value = get_post_meta( $post->ID, $field['name'], true );
		if ( ! is_string( $value ) || '' === trim( wp_strip_all_tags( $value ) ) ) {
			continue;
		}
		$rows .= '<div class="ff-spec"><dt>' . esc_html( $field['label'] ) . '</dt><dd>' . wp_kses_post( $value ) . '</dd></div>';
	}
	return $rows ? '<div class="ff-specs"><h2 class="ff-sc-title">Specifications</h2><dl class="ff-spec-grid">' . $rows . '</dl></div>' : '';
}

/**
 * Filterable technical-spec directory of every product, grouped by top-level range (Resources page).
 */
function ff_resources_html() {
	$ranges  = get_posts( array(
		'post_type'      => 'product',
		'post_parent'    => 0,
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	) );
	$tabs    = '<button type="button" class="is-active" data-ff-filter="all">All products</button>';
	$items   = '';
	$singles = array(); // top-level products without a range of their own
	foreach ( $ranges as $range ) {
		$descendants = ff_descendant_ids( $range->ID );
		if ( ! $descendants ) {
			$singles[] = $range;
			continue;
		}
		$tabs  .= '<button type="button" data-ff-filter="r' . $range->ID . '">' . esc_html( get_the_title( $range ) ) . '</button>';
		$leaves = get_posts( array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post__in'       => $descendants,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$items .= ff_resource_items( $leaves, 'r' . $range->ID, get_the_title( $range ) );
	}
	if ( $singles ) {
		$tabs  .= '<button type="button" data-ff-filter="other">Other products</button>';
		$items .= ff_resource_items( $singles, 'other', 'Other products' );
	}
	return '<div class="ff-res"><div class="ff-res-tabs" role="tablist">' . $tabs . '</div>'
		. '<input class="ff-res-search" type="search" placeholder="Search products, e.g. Modus, GRP, nosing…" aria-label="Search products">'
		. '<div class="ff-res-grid">' . $items . '</div><p class="ff-res-empty" hidden>No products match your search.</p></div>'
		. '<script>(function(){var r=document.currentScript.previousElementSibling,g="all",q="";function u(){var n=0;r.querySelectorAll(".ff-res-item").forEach(function(i){var s=(g==="all"||i.dataset.ffGroup===g)&&i.textContent.toLowerCase().indexOf(q)>-1;i.hidden=!s;if(s)n++});r.querySelector(".ff-res-empty").hidden=n>0}r.querySelectorAll("[data-ff-filter]").forEach(function(b){b.addEventListener("click",function(){r.querySelectorAll("[data-ff-filter]").forEach(function(x){x.classList.remove("is-active")});b.classList.add("is-active");g=b.dataset.ffFilter;u()})});r.querySelector(".ff-res-search").addEventListener("input",function(e){q=e.target.value.toLowerCase().trim();u()})})();</script>';
}

function ff_resource_items( $products, $group, $group_label ) {
	$items = '';
	foreach ( $products as $p ) {
		if ( get_children( array( 'post_parent' => $p->ID, 'post_type' => 'product', 'numberposts' => 1 ) ) ) {
			continue; // skip sub-ranges; list actual products only
		}
		$ref    = wp_strip_all_tags( str_replace( array( '<br />', '<br>' ), ' · ', (string) get_post_meta( $p->ID, 'ff_reference', true ) ) );
		$items .= '<a class="ff-res-item" data-ff-group="' . esc_attr( $group ) . '" href="' . esc_url( get_permalink( $p ) ) . '">'
			. '<span class="ff-res-range">' . esc_html( $group_label ) . '</span>'
			. '<span class="ff-res-name">' . esc_html( get_the_title( $p ) ) . '</span>'
			. ( $ref ? '<span class="ff-res-ref">' . esc_html( trim( $ref, ' ·' ) ) . '</span>' : '' )
			. '<span class="ff-res-more">View technical specs &rarr;</span></a>';
	}
	return $items;
}

function ff_descendant_ids( $parent_id ) {
	$ids = array();
	foreach ( get_posts( array( 'post_type' => 'product', 'post_parent' => $parent_id, 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $child_id ) {
		$ids[] = $child_id;
		$ids   = array_merge( $ids, ff_descendant_ids( $child_id ) );
	}
	return $ids;
}

/**
 * [ff_breadcrumbs] — ancestor trail for hierarchical posts (used by the single template's hero).
 */
add_shortcode( 'ff_breadcrumbs', function () {
	$post = get_post();
	if ( ! $post ) {
		return '';
	}
	$hubs  = array( 'product' => '/products/', 'services' => '/services/', 'solutions' => '/solutions/', 'our_work' => '/recent-work/' );
	$names = array( 'product' => 'Products', 'services' => 'Services', 'solutions' => 'Solutions', 'our_work' => 'Recent Work' );
	$items = array();
	if ( isset( $hubs[ $post->post_type ] ) ) {
		$items[] = '<a href="' . esc_url( home_url( $hubs[ $post->post_type ] ) ) . '">' . esc_html( $names[ $post->post_type ] ) . '</a>';
	}
	foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
		$items[] = '<a href="' . esc_url( get_permalink( $ancestor ) ) . '">' . esc_html( get_the_title( $ancestor ) ) . '</a>';
	}
	return $items ? '<nav class="ff-crumbs" aria-label="Breadcrumb">' . implode( '<span>/</span>', $items ) . '</nav>' : '';
} );

// Styles for the rendered blocks, matching the Elementor kit tokens.
add_action( 'wp_head', function () {
	?>
	<style id="ff-shortcodes">
	.ff-crumbs{display:flex;flex-wrap:wrap;gap:8px;font:600 13px/1.4 'DM Sans',sans-serif;letter-spacing:.06em;text-transform:uppercase}
	.ff-crumbs a,.elementor .ff-crumbs a{color:#5CC8B0!important;text-decoration:none!important;transition:color .2s}
	.ff-crumbs a:hover,.elementor .ff-crumbs a:hover{color:#fff!important}
	.ff-crumbs span{color:rgba(255,255,255,.35)}
	.ff-sc{margin:56px 0 0}.ff-sc-title{font:800 30px/1.2 'Plus Jakarta Sans',sans-serif;letter-spacing:-.02em;color:#012043;margin:0 0 24px}
	.ff-sc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
	@media(max-width:1024px){.ff-sc-grid{grid-template-columns:repeat(2,1fr);gap:20px}}
	@media(max-width:767px){.ff-sc-grid{grid-template-columns:1fr;gap:16px}.ff-sc{margin-top:40px}.ff-sc-title{font-size:24px;margin-bottom:18px}}
	.ff-sc-card{display:flex;flex-direction:column;background:#fff;border:1px solid #DCE3E9;border-radius:12px;overflow:hidden;text-decoration:none!important;transition:transform .25s,box-shadow .25s,border-color .25s}
	.ff-sc-card:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(1,32,67,.12);border-color:#B8C7D4}
	.ff-sc-img{display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover;background:#F2F5F7;margin:0!important;border-radius:0!important}
	.ff-sc-body{display:flex;flex-direction:column;gap:8px;padding:22px 22px 26px;flex-grow:1}
	.ff-sc-name{font:700 19px/1.3 'Plus Jakarta Sans',sans-serif;color:#012043}
	.ff-sc-text{font:400 15px/1.6 'DM Sans',sans-serif;color:#55667A}
	.ff-sc-more{margin-top:auto;padding-top:6px;font:700 13px/1 'DM Sans',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:#0C6A66}
	.ff-specs{margin:48px 0 0}
	.ff-spec-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:0 32px;margin:0;border-top:1px solid #DCE3E9}
	@media(max-width:767px){.ff-specs{margin-top:36px}.ff-spec-grid{grid-template-columns:1fr}.ff-spec{padding:18px 0}}
	.ff-spec{padding:22px 0;border-bottom:1px solid #DCE3E9;overflow-wrap:break-word}
	.ff-spec dt{font:700 12px/1.4 'DM Sans',sans-serif;letter-spacing:.14em;text-transform:uppercase;color:#0C6A66;margin:0 0 8px}
	.ff-spec dd{margin:0;font:400 16px/1.7 'DM Sans',sans-serif;color:#2B3A4D}
	.ff-spec dd a{color:#0C6A66}
	.ff-project-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0;margin:0 0 40px;border:1px solid #DCE3E9;border-radius:14px;overflow:hidden;background:#F2F5F7}
	.ff-project-meta div{padding:20px 24px;border-right:1px solid #DCE3E9;border-bottom:1px solid #DCE3E9;margin-bottom:-1px}
	.ff-project-meta dt{font:700 12px/1.4 'DM Sans',sans-serif;letter-spacing:.14em;text-transform:uppercase;color:#0C6A66;margin:0 0 6px}
	.ff-project-meta dd{margin:0;font:600 16px/1.5 'DM Sans',sans-serif;color:#012043}
	@media(max-width:767px){.ff-project-meta{grid-template-columns:1fr}.ff-project-meta div{border-right:0;padding:16px 20px}}
	.ff-gallery{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:32px 0 40px}
	@media(max-width:767px){.ff-gallery{grid-template-columns:repeat(2,1fr);gap:10px;margin:24px 0 32px}}
	.ff-gallery figure{margin:0}
	.ff-gallery img{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:12px!important;margin:0!important}
	.ff-link{margin:0 0 10px!important}.ff-link a{font:700 14px/1 'DM Sans',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:#0C6A66;text-decoration:none!important}
	.ff-caption{font-size:13px!important;color:#55667A}
	.ff-res-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 20px}
	.ff-res-tabs button{font:600 14px/1 'DM Sans',sans-serif;padding:12px 16px;border-radius:999px;border:1px solid #DCE3E9;background:#fff;color:#012043;cursor:pointer}
	.ff-res-tabs button:hover,.ff-res-tabs button:focus-visible{background:#0C6A66!important;border-color:#0C6A66!important;color:#fff!important;outline:none}
	.ff-res-tabs button.is-active{background:#012043!important;border-color:#012043!important;color:#fff!important}
	.ff-res-tabs button.is-active:hover{background:#0C6A66!important;border-color:#0C6A66!important}
	@media(max-width:767px){.ff-res-tabs{flex-wrap:nowrap;overflow-x:auto;margin:0 -20px 16px;padding:0 20px 4px;scrollbar-width:none}.ff-res-tabs::-webkit-scrollbar{display:none}.ff-res-tabs button{flex:0 0 auto;padding:11px 14px;font-size:13px}}
	.ff-res-search{width:100%;font:400 16px/1.4 'DM Sans',sans-serif;padding:16px 18px;border:1px solid #DCE3E9;border-radius:8px;background:#fff;margin:0 0 28px}
	.ff-res-search:focus{outline:none;border-color:#0C6A66}
	.ff-res-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
	@media(max-width:1024px){.ff-res-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:767px){.ff-res-grid{grid-template-columns:1fr}}
	.ff-res-item{display:flex;flex-direction:column;gap:6px;padding:20px 22px;background:#fff;border:1px solid #DCE3E9;border-radius:12px;text-decoration:none;transition:border-color .2s,box-shadow .2s}
	.ff-res-item[hidden]{display:none}.ff-res-item:hover{border-color:#0C6A66;box-shadow:0 12px 28px rgba(1,32,67,.08)}
	.ff-res-range{font:700 11px/1.4 'DM Sans',sans-serif;letter-spacing:.14em;text-transform:uppercase;color:#0C6A66}
	.ff-res-name{font:700 18px/1.3 'Plus Jakarta Sans',sans-serif;color:#012043}
	.ff-res-ref{font:400 14px/1.5 'DM Sans',sans-serif;color:#55667A}
	.ff-res-more{margin-top:auto;padding-top:6px;font:700 12px/1 'DM Sans',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:#0C6A66}
	.ff-res-empty{font:400 16px 'DM Sans',sans-serif;color:#55667A}
	</style>
	<?php
} );
