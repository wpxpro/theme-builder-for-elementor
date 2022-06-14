<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	if ( xpro_theme_builder_is_singular_enabled() ) {
		xpro_theme_builder_render_singular();
	}

	if ( xpro_theme_builder_is_archive_enabled() ) {
		xpro_theme_builder_render_archive();
	}
}


get_footer();
