<?php
/**
 * Xpro_Theme_Compatibility setup
 *
 * @package xpro-theme-builder
 */

/**
 * Xpro theme compatibility.
 */
class Xpro_Theme_Compatibility {

	/**
	 * Instance of Xpro_Theme_Compatibility.
	 *
	 * @var Xpro_Theme_Compatibility
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Xpro_Theme_Compatibility();

			add_action( 'wp', array( self::$instance, 'hooks' ) );
		}

		return self::$instance;
	}

	/**
	 * Run all the Actions / Filters.
	 */
	public function hooks() {

		add_filter( 'single_template', array( $this, 'blank_template' ) );

		$header_meta = xpro_get_meta( 'xpro-main-header-display' );
		$footer_meta = xpro_get_meta( 'xpro-footer-layout' );

		if ( xpro_theme_builder_header_enabled() && 'disabled' !== $header_meta ) {
			remove_action( 'xpro_header', 'xpro_construct_header' );
			add_action( 'xpro_header', 'xpro_theme_builder_render_header' );
		}

		if ( xpro_theme_builder_footer_enabled() && 'disabled' !== $footer_meta ) {
			remove_action( 'xpro_footer', 'xpro_construct_footer' );
			add_action( 'xpro_footer', 'xpro_theme_builder_render_footer' );
		}

		if ( xpro_theme_builder_is_singular_enabled() ) {
			remove_action( 'xpro_content_before', 'xpro_construct_content_before' );
			remove_action( 'xpro_content_after', 'xpro_construct_content_after' );
			remove_action( 'xpro_title_wrapper', 'xpro_construct_title_wrapper' );
			remove_action( 'xpro_content_loop', 'xpro_construct_content_loop' );
			add_filter( 'page_template', array( $this, 'empty_template' ) );
			add_filter( 'single_template', array( $this, 'empty_template' ) );
			add_filter( '404_template', array( $this, 'empty_template' ) );
			add_filter( 'frontpage_template', array( $this, 'empty_template' ) );

			if ( defined( 'WOOCOMMERCE_VERSION' ) && ( is_product() || is_cart() || is_checkout() || is_account_page() ) ) {
				add_action( 'template_redirect', array( $this, 'woo_template' ), 999 );
				add_action( 'template_include', array( $this, 'woo_template' ), 999 );
			}
		}

		if ( xpro_theme_builder_is_archive_enabled() ) {

			remove_action( 'xpro_content_before', 'xpro_construct_content_before' );
			remove_action( 'xpro_content_after', 'xpro_construct_content_after' );
			remove_action( 'xpro_title_wrapper', 'xpro_construct_title_wrapper' );
			remove_action( 'xpro_content_loop', 'xpro_construct_content_loop' );
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

Xpro_Theme_Compatibility::instance();
