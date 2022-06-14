<?php
/**
 * Generatepress Compatibility.
 *
 * @package xpro-theme-builder
 */

/**
 * Generate Press compatibility.
 *
 * @since 1.0.0
 */
class Xpro_GeneratePress_Compatibility {

	/**
	 * Instance of Xpro_GeneratePress_Compatibility
	 *
	 * @var Xpro_GeneratePress_Compatibility
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Xpro_GeneratePress_Compatibility();

			add_action( 'wp', array( self::$instance, 'hooks' ) );
		}

		return self::$instance;
	}

	/**
	 * Run all the Actions / Filters.
	 */
	public function hooks() {

		add_filter( 'single_template', array( $this, 'blank_template' ) );

		if ( xpro_theme_builder_header_enabled() ) {
			remove_action( 'generate_header', 'generate_construct_header' );
			add_action( 'generate_header', 'xpro_theme_builder_render_header' );
		}

		if ( xpro_theme_builder_footer_enabled() ) {
			remove_action( 'generate_footer', 'generate_construct_footer_widgets', 5 );
			remove_action( 'generate_footer', 'generate_construct_footer' );
			add_action( 'generate_footer', 'xpro_theme_builder_render_footer' );
		}

		if ( xpro_theme_builder_is_singular_enabled() ) {

			update_post_meta( xpro_theme_builder_get_singular_id(), '_generate-sidebar-layout-meta', 'no-sidebar' );
			update_post_meta( xpro_theme_builder_get_singular_id(), '_generate-full-width-content', 'true' );
			update_post_meta( xpro_theme_builder_get_singular_id(), '_generate-disable-headline', 'true' );

			add_filter( 'generate_page_class', array( $this, 'generate_do_page_container_classes' ) );
			add_filter( 'single_template', array( $this, 'empty_template' ) );
			add_filter( '404_template', array( $this, 'empty_template' ) );
			add_filter( 'frontpage_template', array( $this, 'empty_template' ) );

			if ( defined( 'WOOCOMMERCE_VERSION' ) && ( is_product() || is_cart() || is_checkout() || is_account_page() ) ) {
				add_action( 'template_redirect', array( $this, 'woo_template' ), 999 );
				add_action( 'template_include', array( $this, 'woo_template' ), 999 );
			}
		}

		if ( xpro_theme_builder_is_archive_enabled() ) {

			update_post_meta( xpro_theme_builder_get_archive_id(), '_generate-sidebar-layout-meta', 'no-sidebar' );
			update_post_meta( xpro_theme_builder_get_archive_id(), '_generate-full-width-content', 'true' );
			update_post_meta( xpro_theme_builder_get_archive_id(), '_generate-disable-headline', 'true' );

			add_filter( 'generate_page_class', array( $this, 'generate_do_page_container_classes' ), 20 );
			add_filter( 'search_template', array( $this, 'empty_template' ) );
			add_filter( 'date_template', array( $this, 'empty_template' ) );
			add_filter( 'author_template', array( $this, 'empty_template' ) );
			add_filter( 'archive_template', array( $this, 'empty_template' ) );
			add_filter( 'category_template', array( $this, 'empty_template' ) );
			add_filter( 'tag_template', array( $this, 'empty_template' ) );
			add_filter( 'home_template', array( $this, 'empty_template' ) );

			if ( defined( 'WOOCOMMERCE_VERSION' ) && is_shop() || ( is_tax( 'product_cat' ) && is_product_category() ) || ( is_tax( 'product_tag' ) && is_product_tag() ) ) {
				add_action( 'template_redirect', array( $this, 'woo_template' ), 999 );
				add_action( 'template_include', array( $this, 'woo_template' ), 999 );
			}
		}

	}

	public function generate_do_page_container_classes( $classes ) {
		$classes   = array();
		$classes[] = 'xpro-theme-builder-wrapper';

		return $classes;
	}

	public function blank_template( $template ) {

		global $post;

		if ( 'xpro-themer' === $post->post_type ) {
			if ( file_exists( XPRO_THEME_BUILDER_DIR . 'inc/templates/blank.php' ) ) {
				return XPRO_THEME_BUILDER_DIR . 'inc/templates/blank.php';
			}
		}

		return $template;
	}

	public function empty_template( $template ) {

		if ( file_exists( XPRO_THEME_BUILDER_DIR . 'inc/templates/empty.php' ) ) {
			return XPRO_THEME_BUILDER_DIR . 'inc/templates/empty.php';
		}

		return $template;
	}

	public function woo_template( $template ) {
		if ( file_exists( XPRO_THEME_BUILDER_DIR . 'inc/templates/woo.php' ) ) {
			return XPRO_THEME_BUILDER_DIR . 'inc/templates/woo.php';
		}

		return $template;

	}
}

Xpro_GeneratePress_Compatibility::instance();
