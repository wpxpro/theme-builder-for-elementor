<?php

use Elementor\Core\Files\CSS\Post;
use Elementor\Frontend;
use Elementor\Post_CSS_File;
use ElementorPro\Plugin;
use Xpro_Theme_Builder\Lib\Xpro_Target_Rules_Fields;
use XproElementorAddons\Inc\Xpro_Elementor_Module_List;
use XproElementorAddons\Libs\Xpro_Elementor_Dashboard;

/**
 * Class Xpro_Theme_Builder_Main
 */
class Xpro_Theme_Builder_Main {

	/**
	 * Instance of Elementor Frontend class.
	 *
	 * @var Frontend()
	 */
	private static $elementor_instance;
	/**
	 * Instance of Xpro_Theme_Builder_Admin
	 *
	 * @var Xpro_Theme_Builder_Main
	 */
	private static $_instance = null;
	/**
	 * Current theme template
	 *
	 * @var String
	 */
	public $template;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->template = get_template();

		self::$elementor_instance = ( defined( 'ELEMENTOR_VERSION' ) && is_callable( 'Elementor\Plugin::instance' ) ) ? Elementor\Plugin::instance() : '';

		if ( self::$elementor_instance ) {

			$this->includes();
			$this->load_textdomain();

			if ( 'xpro' === $this->template ) {
				require XPRO_THEME_BUILDER_DIR . 'themes/xpro/class-xpro-compatibility.php';
			} elseif ( 'astra' === $this->template ) {
				require XPRO_THEME_BUILDER_DIR . 'themes/astra/class-astra-compatibility.php';
			} elseif ( 'hello-elementor' === $this->template ) {
				require XPRO_THEME_BUILDER_DIR . 'themes/default/class-default-compatibility.php';
			} elseif ( 'generatepress' === $this->template ) {
				require XPRO_THEME_BUILDER_DIR . 'themes/generatepress/class-xpro-generatepress-compatibility.php';
			} else {
				add_action( 'admin_notices', array( $this, 'theme_not_compatible_notice' ) );
				require XPRO_THEME_BUILDER_DIR . 'themes/default/class-default-compatibility.php';
			}

			// Scripts and styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			add_filter( 'body_class', array( $this, 'body_class' ) );

			//Plugin Row Meta & Link
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			add_filter( 'plugin_action_links_' . XPRO_THEME_BUILDER_BASE, array( $this, 'plugin_action_links' ) );

			add_shortcode( 'xpro_theme_builder_template', array( $this, 'render_template' ) );

		}
	}

	/**
	 * Loads the globally required files for the plugin.
	 */
	public function includes() {

		require_once XPRO_THEME_BUILDER_DIR . 'admin/class-xpro-admin.php';
		require_once XPRO_THEME_BUILDER_DIR . 'admin/class-xpro-rest-api.php';

		require_once XPRO_THEME_BUILDER_DIR . 'inc/xpro-functions.php';

		// Load Target rules.
		require_once XPRO_THEME_BUILDER_DIR . 'lib/target-rule/class-xpro-target-rules-fields.php';
	}

	/**
	 * Loads textdomain for the plugin.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'xpro-theme-builder' );
	}

	/**
	 * Instance of Xpro_Theme_Builder_Main
	 *
	 * @return Xpro_Theme_Builder_Main Instance of Xpro_Theme_Builder_Main
	 */
	public static function instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Prints the Header content.
	 */
	public static function get_header_content() {
		if ( self::$elementor_instance ) {
			echo self::$elementor_instance->frontend->get_builder_content_for_display( get_xpro_theme_builder_header_id() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Prints the Footer content.
	 */
	public static function get_footer_content() {
		if ( self::$elementor_instance ) {
			echo self::$elementor_instance->frontend->get_builder_content_for_display( get_xpro_theme_builder_footer_id() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Prints the Before Footer content.
	 */
	public static function get_singular_content() {
		if ( self::$elementor_instance ) {
			echo self::$elementor_instance->frontend->get_builder_content_for_display( xpro_theme_builder_get_singular_id() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Prints the Before Footer content.
	 */
	public static function get_archive_content() {
		if ( self::$elementor_instance ) {
			echo self::$elementor_instance->frontend->get_builder_content_for_display( xpro_theme_builder_get_archive_id() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Get option for the plugin settings
	 *
	 * @param mixed $setting Option name.
	 * @param mixed $default Default value to be received if the option value is not stored in the option.
	 *
	 * @return mixed.
	 */
	public static function get_settings( $setting = '', $default = '' ) {
		if ( 'type_header' === $setting || 'type_footer' === $setting || 'type_singular' === $setting || 'type_archive' === $setting ) {
			$templates = self::get_template_id( $setting );

			$template = ! is_array( $templates ) ? $templates : $templates[0];

			$template = apply_filters( "xpro_theme_builder_get_settings_{$setting}", $template );

			return $template;
		}
	}

	/**
	 * Get header or footer template id based on the meta query.
	 *
	 * @param String $type Type of the template header/footer.
	 *
	 * @return Mixed       Returns the header or footer template id if found, else returns string ''.
	 */
	public static function get_template_id( $type ) {

		$option = array(
			'location'  => 'xpro_theme_builder_target_include_locations',
			'exclusion' => 'xpro_theme_builder_target_exclude_locations',
			'users'     => 'xpro_theme_builder_target_user_roles',
		);

		$xpro_theme_builder_templates = Xpro_Target_Rules_Fields::get_instance()->get_posts_by_conditions( 'xpro-themer', $option );

		foreach ( $xpro_theme_builder_templates as $template ) {
			if ( get_post_meta( absint( $template['id'] ), 'xpro_theme_builder_template_type', true ) === $type ) {
				return $template['id'];
			}
		}

		return '';
	}

	public function theme_not_compatible_notice() {

		$screen = get_current_screen();

		if ( 'plugins' === $screen->base || 'themes' === $screen->base || ( 'xpro-themer' === $screen->post_type && 'edit' === $screen->base ) ) {

			$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
				esc_html__( '"%1$s" is not fully compatible with current theme. Please install and activate %2$s theme.', 'xpro-theme-builder' ),
				'<strong>' . esc_html__( 'Xpro Theme Builder', 'xpro-theme-builder' ) . '</strong>',
				'<strong><a href="' . admin_url( '/theme-install.php?search=xpro' ) . '">' . esc_html__( 'Xpro', 'xpro-theme-builder' ) . '</a></strong>'
			);
			printf( '<div class="notice notice-warning"><p>%1$s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}

	}

	/**
	 * Enqueue styles and scripts.
	 */
	public function enqueue_scripts() {

		$all_modules    = Xpro_Elementor_Module_List::instance()->get_list();
		$active_modules = Xpro_Elementor_Dashboard::instance()->utils->get_option( 'xpro_elementor_module_list', array_keys( $all_modules ) );

		wp_enqueue_style( 'xpro-theme-builder', XPRO_THEME_BUILDER_URL . 'assets/css/xpro-theme-builder.css', null, XPRO_THEME_BUILDER_VER );
		wp_enqueue_script( 'xpro-theme-builder', XPRO_THEME_BUILDER_URL . 'assets/js/xpro-theme-builder.js', array( 'jquery' ), XPRO_THEME_BUILDER_VER, true );

		wp_script_add_data( 'xpro-theme-builder', 'async', true );

		//Frontend Panel
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) && in_array( 'theme-builder', $active_modules, true ) && ! ( self::$elementor_instance && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) {

			global $user_ID;

			wp_enqueue_style( 'xpro-theme-builder-frontend', XPRO_THEME_BUILDER_URL . 'admin/assets/css/xpro-frontend.css', array(), XPRO_THEME_BUILDER_VER );
			wp_enqueue_script( 'xpro-theme-builder-frontend', XPRO_THEME_BUILDER_URL . 'admin/assets/js/xpro-frontend.js', array(), XPRO_THEME_BUILDER_VER, true );
			wp_enqueue_script( 'xpro-theme-builder-frontend-chunk', XPRO_THEME_BUILDER_URL . 'admin/assets/js/xpro-chunk.js', array(), XPRO_THEME_BUILDER_VER, true );
			wp_enqueue_script( 'xpro-theme-builder-frontend-main', XPRO_THEME_BUILDER_URL . 'admin/assets/js/xpro-main.js', array(), XPRO_THEME_BUILDER_VER, true );
			wp_localize_script(
				'xpro-theme-builder-frontend',
				'XproThemeBuilderApi',
				array(
					'ApiUrl'   => get_rest_url(),
					'siteUrl'  => get_site_url(),
					'adminUrl' => get_admin_url(),
					'user'     => $user_ID,
					'nonce'    => wp_create_nonce( 'wp_rest' ),
				)
			);
		}

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$elementor = \Elementor\Plugin::instance();
			$elementor->frontend->enqueue_styles();
		}

		if ( class_exists( '\ElementorPro\Plugin' ) ) {
			$elementor_pro = Plugin::instance();
			$elementor_pro->enqueue_styles();
		}

		if ( self::$elementor_instance && xpro_theme_builder_header_enabled() ) {
			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				$css_file = new Post( get_xpro_theme_builder_header_id() );
			} elseif ( class_exists( '\Elementor\Post_CSS_File' ) ) {
				$css_file = new Post_CSS_File( get_xpro_theme_builder_header_id() );
			}

			$css_file->enqueue();
		}

		if ( self::$elementor_instance && xpro_theme_builder_footer_enabled() ) {
			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				$css_file = new Post( get_xpro_theme_builder_footer_id() );
			} elseif ( class_exists( '\Elementor\Post_CSS_File' ) ) {
				$css_file = new Post_CSS_File( get_xpro_theme_builder_footer_id() );
			}

			$css_file->enqueue();
		}

		if ( self::$elementor_instance && xpro_theme_builder_is_singular_enabled() ) {
			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				$css_file = new Post( xpro_theme_builder_get_singular_id() );
			} elseif ( class_exists( '\Elementor\Post_CSS_File' ) ) {
				$css_file = new Post_CSS_File( xpro_theme_builder_get_singular_id() );
			}
			$css_file->enqueue();
		}

		if ( self::$elementor_instance && xpro_theme_builder_is_archive_enabled() ) {
			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				$css_file = new Post( xpro_theme_builder_get_archive_id() );
			} elseif ( class_exists( '\Elementor\Post_CSS_File' ) ) {
				$css_file = new Post_CSS_File( xpro_theme_builder_get_archive_id() );
			}
			$css_file->enqueue();
		}

	}

	/**
	 * Load admin styles on header footer elementor edit screen.
	 */
	public function enqueue_admin_scripts() {

		global $pagenow;
		$screen = get_current_screen();

		wp_enqueue_style( 'xpro-theme-builder-admin', XPRO_THEME_BUILDER_URL . 'admin/assets/css/xpro-admin.css', array(), XPRO_THEME_BUILDER_VER );

		if ( ( 'xpro-themer' === $screen->id && ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) ) || ( 'edit.php' === $pagenow && 'edit-xpro-themer' === $screen->id ) ) {

			wp_enqueue_script( 'xpro-theme-builder-admin', XPRO_THEME_BUILDER_URL . 'admin/assets/js/xpro-admin.js', array( 'jquery' ), XPRO_THEME_BUILDER_VER, true );

		}
	}

	/**
	 * Adds classes to the body tag conditionally.
	 *
	 * @param Array $classes array with class names for the body tag.
	 *
	 * @return Array          array with class names for the body tag.
	 */
	public function body_class( $classes ) {

		$classes[] = 'xpro-theme-builder-template';

		return $classes;
	}

	/**
	 * Callback to shortcode.
	 *
	 * @param array $atts attributes for shortcode.
	 */
	public function render_template( $atts ) {

		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			$atts,
			'xpro_theme_builder_template'
		);

		$id = ! empty( $atts['id'] ) ? apply_filters( 'xpro_theme_builder_render_template_id', intval( $atts['id'] ) ) : '';

		if ( empty( $id ) ) {
			return '';
		}

		if ( self::$elementor_instance ) {
			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				$css_file = new Post( $id );
			} elseif ( class_exists( '\Elementor\Post_CSS_File' ) ) {
				// Load elementor styles.
				$css_file = new Post_CSS_File( $id );
			}
			$css_file->enqueue();
		}

		if ( self::$elementor_instance ) {
			return self::$elementor_instance->frontend->get_builder_content_for_display( $id );
		}

	}

	/**
	 * Plugin row meta.
	 *
	 * Adds row meta links to the plugin list table
	 *
	 * Fired by `plugin_row_meta` filter.
	 *
	 * @param array $plugin_meta An array of the plugin's metadata, including
	 *                            the version, author, author URI, and plugin URI.
	 * @param string $plugin_file Path to the plugin file, relative to the plugins
	 *                            directory.
	 *
	 * @return array An array of plugin row meta links.
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( XPRO_THEME_BUILDER_BASE === $plugin_file ) {
			$row_meta    = array( 'docs' => '<a href="https://elementor.wpxpro.com/docs/" aria-label="' . esc_attr( esc_html__( 'View Documentation', 'xpro-theme-builder' ) ) . '" target="_blank">' . esc_html__( 'Documentation', 'xpro-theme-builder' ) . '</a>' );
			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}

	/**
	 * Plugin action links.
	 *
	 * Adds action links to the plugin list table
	 *
	 * Fired by `plugin_action_links` filter.
	 *
	 * @param array $links An array of plugin action links.
	 *
	 * @return array An array of plugin action links.
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'edit.php?post_type=xpro-themer' ), esc_html__( 'Settings', 'xpro-theme-builder' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

}

// Instantiate Xpro_Theme_Builder_Main Class
Xpro_Theme_Builder_Main::instance();
