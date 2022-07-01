<?php
/**
 * Plugin Name: Xpro Elementor Theme Builder
 * Plugin URI:  https://elementor.wpxpro.com/theme-builder/
 * Description: Free Theme Builder for Elementor with 50+ widgets. Now create theme parts like header, footer, singular, archive, woocommerce stores & more.
 * Author:      Xpro
 * Author URI:  https://www.wpxpro.com/
 * Version:     1.0.4
 * Developer:   Farman Akram
 * Text Domain: xpro-theme-builder
 * Elementor tested up to: 3.6.0
 * Elementor Pro tested up to: 3.6.0
 *
 * @package xpro-theme-builder
 */

define( 'XPRO_THEME_BUILDER_VER', '1.0.4' );
define( 'XPRO_THEME_BUILDER_FILE', __FILE__ );
define( 'XPRO_THEME_BUILDER_BASE', plugin_basename( __FILE__ ) );
define( 'XPRO_THEME_BUILDER_DIR', plugin_dir_path( XPRO_THEME_BUILDER_FILE ) );
define( 'XPRO_THEME_BUILDER_URL', plugins_url( '/', __FILE__ ) );


final class Xpro_Theme_Builder {

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.0.0
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.0';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		// Load translation
		add_action( 'init', array( $this, 'i18n' ) );

		// Init Plugin
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		//Fires when Xpro Theme Builder was fully loaded
		do_action( 'xpro_theme_builder_loaded' );

	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 * Fired by `init` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'xpro-theme-builder' );
	}

	/**
	 * Initialize the plugin
	 *
	 * Validates that Elementor is already loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed include the plugin class.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init() {

		// Check if Xpro Elementor Addons installed and activated
		if ( ! did_action( 'xpro_elementor_addons_loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_xpro_elementor_addons' ) );
			return;
		}

		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}

		// Once we get here, We have passed all validation checks so we can safely include our plugin
		require_once plugin_dir_path( __FILE__ ) . 'plugin.php';

	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_missing_xpro_elementor_addons() {

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$screen = get_current_screen();

		if ( 'plugins' === $screen->base ) {
			if ( file_exists( WP_PLUGIN_DIR . '/xpro-elementor-addons/xpro-elementor-addons.php' ) ) {
				$message = sprintf(
				/* translators: 1: Plugin name 2: URL */
					esc_html__( '"%1$s" requires "%2$s" to be activated. %3$s', 'xpro-theme-builder' ),
					'<strong>' . esc_html__( 'Xpro Elementor Theme Builder', 'xpro-theme-builder' ) . '</strong>',
					'<strong>' . esc_html__( 'Xpro Elementor Addons', 'xpro-theme-builder' ) . '</strong>',
					'<p><a href="' . wp_nonce_url( 'plugins.php?action=activate&plugin=xpro-elementor-addons/xpro-elementor-addons.php', 'activate-plugin_xpro-elementor-addons/xpro-elementor-addons.php' ) . '" class="button-primary">' . esc_html__( 'Activate', 'xpro-theme-builder' ) . '</a></p>'
				);
			} else {
				$message = sprintf(
				/* translators: 1: Plugin name 2: URL */
					esc_html__( '"%1$s" requires "%2$s" to be installed. %3$s', 'xpro-theme-builder' ),
					'<strong>' . esc_html__( 'Xpro Elementor Theme Builder', 'xpro-theme-builder' ) . '</strong>',
					'<strong>' . esc_html__( 'Xpro Elementor Addons', 'xpro-theme-builder' ) . '</strong>',
					'<p><a href="' . wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=xpro-elementor-addons' ), 'install-plugin_xpro-elementor-addons' ) . '" class="button-primary">' . esc_html__( 'Install', 'xpro-theme-builder' ) . '</a></p>'
				);
			}

			printf( '<div class="notice notice-warning"><p>%1$s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'xpro-theme-builder' ),
			'<strong>' . esc_html__( 'Xpro Elementor Theme Builder', 'xpro-theme-builder' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'xpro-theme-builder' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}

// Instantiate Xpro_Theme_Builder.
new Xpro_Theme_Builder();
