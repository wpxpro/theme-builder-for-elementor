<?php
/**
 * Astra_Theme_Compatibility setup
 *
 * @package xpro-theme-builder
 */

/**
 * Xpro theme compatibility.
 */
class Astra_Theme_Compatibility {

	/**
	 * Instance of Astra_Theme_Compatibility.
	 *
	 * @var Astra_Theme_Compatibility
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Astra_Theme_Compatibility();

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

			remove_action( 'astra_header', 'astra_header_markup' );

			// Remove the new header builder action.
			if ( class_exists( 'Astra_Builder_Helper' ) && Astra_Builder_Helper::$is_header_footer_builder_active ) {
				remove_action(
					'astra_header',
					array(
						Astra_Builder_Header::get_instance(),
						'prepare_header_builder_markup',
					)
				);
			}

			add_action( 'astra_header', 'xpro_theme_builder_render_header' );
		}

		if ( xpro_theme_builder_footer_enabled() ) {

			remove_action( 'astra_footer', 'astra_footer_markup' );

			// Remove the new footer builder action.
			if ( class_exists( 'Astra_Builder_Helper' ) && Astra_Builder_Helper::$is_header_footer_builder_active ) {
				remove_action( 'astra_footer', array( Astra_Builder_Footer::get_instance(), 'footer_markup' ) );
			}

			add_action( 'astra_footer', 'xpro_theme_builder_render_footer' );
		}

		if ( xpro_theme_builder_is_singular_enabled() ) {

			$this->override_with_post_meta( xpro_theme_builder_get_singular_id() );

			add_filter( 'single_template', array( $this, 'empty_template' ) );
			add_filter( '404_template', array( $this, 'empty_template' ) );
			add_filter( 'frontpage_template', array( $this, 'empty_template' ) );

			if ( defined( 'WOOCOMMERCE_VERSION' ) && ( is_product() || is_cart() || is_checkout() || is_account_page() ) ) {
				add_action( 'template_redirect', array( $this, 'woo_template' ), 999 );
				add_action( 'template_include', array( $this, 'woo_template' ), 999 );
			}
		}

		if ( xpro_theme_builder_is_archive_enabled() ) {

			$this->override_with_post_meta( xpro_theme_builder_get_archive_id() );

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

	/**
	 * Override sidebar, title etc with post meta
	 *
	 * @param integer $post_id Post ID.
	 *
	 * @return void
	 */
	public function override_with_post_meta( $post_id = 0 ) {
		// Override! Page Title.
		$title = get_post_meta( $post_id, 'site-post-title', true );
		if ( 'disabled' === $title ) {

			// Archive page.
			add_filter( 'astra_the_title_enabled', '__return_false', 99 );

			// Single page.
			add_filter( 'astra_the_title_enabled', '__return_false' );
			remove_action( 'astra_archive_header', 'astra_archive_page_info' );
		}

		// Override! Sidebar.
		$sidebar = get_post_meta( $post_id, 'site-sidebar-layout', true );
		if ( '' === $sidebar ) {
			$sidebar = 'default';
		}

		if ( 'default' !== $sidebar ) {
			add_filter(
				'astra_page_layout',
				function ( $page_layout ) use ( $sidebar ) {
					return $sidebar;
				}
			);
		}

		// Override! Content Layout.
		$content_layout = get_post_meta( $post_id, 'site-content-layout', true );
		if ( '' === $content_layout ) {
			$content_layout = 'default';
		}

		if ( 'default' !== $content_layout ) {
			add_filter(
				'astra_get_content_layout',
				function ( $layout ) use ( $content_layout ) {
					return $content_layout;
				}
			);
		}

		// Override! Footer Bar.
		$footer_layout = get_post_meta( $post_id, 'footer-sml-layout', true );
		if ( '' === $footer_layout ) {
			$footer_layout = 'default';
		}

		if ( 'disabled' === $footer_layout ) {
			add_filter(
				'astra_footer_sml_layout',
				function ( $is_footer ) {
					return 'disabled';
				}
			);
		}

		// Override! Footer Widgets.
		$footer_widgets = get_post_meta( $post_id, 'footer-adv-display', true );
		if ( '' === $footer_widgets ) {
			$footer_widgets = 'default';
		}

		if ( 'disabled' === $footer_widgets ) {
			add_filter(
				'astra_advanced_footer_disable',
				function () {
					return true;
				}
			);
		}

		// Override! Header.
		$main_header_display = get_post_meta( $post_id, 'ast-main-header-display', true );
		if ( '' === $main_header_display ) {
			$main_header_display = 'default';
		}

		if ( 'disabled' === $main_header_display ) {
			remove_action( 'astra_masthead', 'astra_masthead_primary_template' );
			add_filter(
				'astra_main_header_display',
				function ( $display_header ) {
					return 'disabled';
				}
			);
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

Astra_Theme_Compatibility::instance();
