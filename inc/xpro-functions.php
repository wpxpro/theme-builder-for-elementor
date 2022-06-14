<?php
/**
 * Xpro Theme Builder Function
 *
 * @package  xpro-theme-builder
 */

/**
 * Checks if Header is enabled from Xpro_Theme_Builder.
 *
 * @return bool True if header is enabled. False if header is not enabled
 * @since 1.0.0
 */
function xpro_theme_builder_header_enabled() {
	$header_id = Xpro_Theme_Builder_Main::get_settings( 'type_header', '' );
	$status    = false;

	if ( '' !== $header_id ) {
		$status = true;
	}

	return apply_filters( 'xpro_theme_builder_header_enabled', $status );
}

/**
 * Checks if Footer is enabled from Xpro_Theme_Builder.
 *
 * @return bool True if header is enabled. False if header is not enabled.
 * @since 1.0.0
 */
function xpro_theme_builder_footer_enabled() {
	$footer_id = Xpro_Theme_Builder_Main::get_settings( 'type_footer', '' );
	$status    = false;

	if ( '' !== $footer_id ) {
		$status = true;
	}

	return apply_filters( 'xpro_theme_builder_footer_enabled', $status );
}

/**
 * Get Xpro_Theme_Builder Header ID
 *
 * @return (String|boolean) header id if it is set else returns false.
 * @since 1.0.0
 */
function get_xpro_theme_builder_header_id() {
	$header_id = Xpro_Theme_Builder_Main::get_settings( 'type_header', '' );

	if ( '' === $header_id ) {
		$header_id = false;
	}

	return apply_filters( 'get_xpro_theme_builder_header_id', $header_id );
}

/**
 * Get Xpro_Theme_Builder Footer ID
 *
 * @return (String|boolean) header id if it is set else returns false.
 * @since 1.0.0
 */
function get_xpro_theme_builder_footer_id() {
	$footer_id = Xpro_Theme_Builder_Main::get_settings( 'type_footer', '' );

	if ( '' === $footer_id ) {
		$footer_id = false;
	}

	return apply_filters( 'get_xpro_theme_builder_footer_id', $footer_id );
}

/**
 * Display header markup.
 *
 * @since 1.0.0
 */
function xpro_theme_builder_render_header() {

	if ( false === apply_filters( 'enable_xpro_theme_builder_render_header', true ) ) {
		return;
	}

	$sticky = get_post_meta( get_xpro_theme_builder_header_id(), 'xpro_theme_builder_sticky' );
	$class  = ( 'enable' === $sticky[0] ) ? ' xtb-header-sticky' : '';

	?>
	<header class="xpro-theme-builder-header<?php echo esc_attr( $class ); ?>" itemscope="itemscope" itemtype="https://schema.org/WPHeader">
		<p class="main-title xpro-hidden" itemprop="headline"><a href="<?php echo esc_url( bloginfo( 'url' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php esc_html( bloginfo( 'name' ) ); ?></a></p>
		<nav class="xpro-theme-builder-header-nav">
			<?php Xpro_Theme_Builder_Main::get_header_content(); ?>
		</nav>
	</header>

	<?php

}

/**
 * Display footer markup.
 *
 * @since 1.0.0
 */
function xpro_theme_builder_render_footer() {

	if ( false === apply_filters( 'enable_xpro_theme_builder_render_footer', true ) ) {
		return;
	}

	?>
	<footer itemtype="https://schema.org/WPFooter" itemscope="itemscope" id="xpro-theme-builder-footer"
			role="contentinfo">
		<?php Xpro_Theme_Builder_Main::get_footer_content(); ?>
	</footer>
	<?php

}


/**
 * Get Xpro_Theme_Builder Before Footer ID
 *
 * @return String|boolean before footer id if it is set else returns false.
 * @since 1.0.0
 */
function xpro_theme_builder_get_singular_id() {

	$singular_id = Xpro_Theme_Builder_Main::get_settings( 'type_singular', '' );

	if ( '' === $singular_id ) {
		$singular_id = false;
	}

	return apply_filters( 'get_xpro_theme_builder_singular_id', $singular_id );
}

/**
 * Checks if Before Footer is enabled from Xpro_Theme_Builder.
 *
 * @return bool True if before footer is enabled. False if before footer is not enabled.
 * @since 1.0.0
 */
function xpro_theme_builder_is_singular_enabled() {

	$singular_id = Xpro_Theme_Builder_Main::get_settings( 'type_singular', '' );
	$status      = false;

	if ( '' !== $singular_id ) {
		$status = true;
	}

	return apply_filters( 'xpro_theme_builder_singular_enabled', $status );
}

/**
 * Display before footer markup.
 *
 * @since 1.0.0
 */
function xpro_theme_builder_render_singular() {

	if ( false === apply_filters( 'enable_xpro_theme_builder_render_singular', true ) ) {
		return;
	}
	?>
	<div class="xpro-theme-builder-singular-wrapper">
		<?php Xpro_Theme_Builder_Main::get_singular_content(); ?>
	</div>
	<?php

}

/**
 * Get Xpro_Theme_Builder Before Footer ID
 *
 * @return String|boolean before footer id if it is set else returns false.
 * @since 1.0.0
 */
function xpro_theme_builder_get_archive_id() {

	$archive_id = Xpro_Theme_Builder_Main::get_settings( 'type_archive', '' );

	if ( '' === $archive_id ) {
		$archive_id = false;
	}

	return apply_filters( 'get_xpro_theme_builder_archive_id', $archive_id );
}

/**
 * Checks if Before Footer is enabled from Xpro_Theme_Builder.
 *
 * @return bool True if before footer is enabled. False if before footer is not enabled.
 * @since 1.0.0
 */
function xpro_theme_builder_is_archive_enabled() {

	$archive_id = Xpro_Theme_Builder_Main::get_settings( 'type_archive', '' );
	$status     = false;

	if ( '' !== $archive_id ) {
		$status = true;
	}

	return apply_filters( 'xpro_theme_builder_archive_enabled', $status );
}

/**
 * Display before footer markup.
 *
 * @since 1.0.0
 */
function xpro_theme_builder_render_archive() {

	if ( false === apply_filters( 'enable_xpro_theme_builder_render_archive', true ) ) {
		return;
	}
	?>
	<div class="xpro-theme-builder-archive-wrapper">
		<?php Xpro_Theme_Builder_Main::get_archive_content(); ?>
	</div>
	<?php

}
