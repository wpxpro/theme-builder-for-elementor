<?php
/**
 *
 * @package xpro-theme-builder
 */

use Xpro_Theme_Builder\Lib\Xpro_Target_Rules_Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Xpro_Theme_Builder_Admin setup
 *
 * @since 1.0.0
 */
class Xpro_Theme_Builder_Admin {

	/**
	 * Instance of Xpro_Theme_Builder_Admin
	 *
	 * @var Xpro_Theme_Builder_Admin
	 */
	private static $_instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'xpro_theme_builder_post_type' ) );
		add_action( 'init', array( $this, 'xpro_theme_builder_frontend_settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'xpro_theme_builder_register_metabox' ) );
		add_action( 'save_post', array( $this, 'xpro_theme_builder_save_meta' ) );
		add_action( 'template_redirect', array( $this, 'block_template_frontend' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_submenus' ), 99 );
		add_filter( 'manage_xpro-themer_posts_columns', array( $this, 'set_custom_columns' ) );
		add_action( 'manage_xpro-themer_posts_custom_column', array( $this, 'render_custom_column' ), 10, 2 );
		add_action( 'manage_xpro-themer_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'manage_xpro-themer_posts_columns', array( $this, 'column_headings' ) );
		add_action( 'admin_head', array( $this, 'correct_current_active_menu' ), 50 );

		if ( defined( 'ELEMENTOR_PRO_VERSION' ) && ELEMENTOR_PRO_VERSION > 2.8 ) {
			add_action( 'elementor/editor/footer', array( $this, 'register_xpro_theme_builder_epro_script' ), 99 );
		}

		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
		register_activation_hook( __FILE__, array( $this, 'flush_rewrites' ) );

	}

	/**
	 * Instance of Xpro_Theme_Builder_Admin
	 *
	 * @return Xpro_Theme_Builder_Admin Instance of Xpro_Theme_Builder_Admin
	 */
	public static function instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Script for Elementor Pro full site editing support.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function register_xpro_theme_builder_epro_script() {
		$ids_array = array(
			array(
				'id'    => get_xpro_theme_builder_header_id(),
				'value' => 'Header',
			),
			array(
				'id'    => get_xpro_theme_builder_footer_id(),
				'value' => 'Footer',
			),
			array(
				'id'    => xpro_theme_builder_get_singular_id(),
				'value' => 'Singular',
			),
			array(
				'id'    => xpro_theme_builder_get_archive_id(),
				'value' => 'Archive',
			),
		);
	}

	/**
	 * Adds or removes list table column headings.
	 *
	 * @param array $columns Array of columns.
	 *
	 * @return array
	 */
	public function column_headings( $columns ) {
		unset( $columns['date'] );

		$columns['xpro_theme_builder_display_rules'] = __( 'Display Rules', 'xpro-theme-builder' );
		$columns['date']                             = __( 'Date', 'xpro-theme-builder' );

		return $columns;
	}

	/**
	 * Adds the custom list table column content.
	 *
	 * @param array $column Name of column.
	 * @param int $post_id Post id.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function column_content( $column, $post_id ) {

		if ( 'xpro_theme_builder_display_rules' === $column ) {

			$locations = get_post_meta( $post_id, 'xpro_theme_builder_target_include_locations', true );
			if ( ! empty( $locations ) ) {
				echo '<div class="xpro-advanced-headers-location-wrap" style="margin-bottom: 5px;">';
				echo '<strong>Display: </strong>';
				$this->column_display_location_rules( $locations );
				echo '</div>';
			}

			$locations = get_post_meta( $post_id, 'xpro_theme_builder_target_exclude_locations', true );
			if ( ! empty( $locations ) ) {
				echo '<div class="xpro-advanced-headers-exclusion-wrap" style="margin-bottom: 5px;">';
				echo '<strong>Exclusion: </strong>';
				$this->column_display_location_rules( $locations );
				echo '</div>';
			}

			$users = get_post_meta( $post_id, 'xpro_theme_builder_target_user_roles', true );
			if ( isset( $users ) && is_array( $users ) ) {
				if ( isset( $users[0] ) && ! empty( $users[0] ) ) {
					$user_label = array();
					foreach ( $users as $user ) {
						$user_label[] = Xpro_Target_Rules_Fields::get_user_by_key( $user );
					}
					echo '<div class="xpro-advanced-headers-users-wrap">';
					echo '<strong>Users: </strong>';
					echo join( ', ', array_map( 'esc_html', $user_label ) );
					echo '</div>';
				}
			}
		}
	}

	/**
	 * Get Markup of Location rules for Display rule column.
	 *
	 * @param array $locations Array of locations.
	 *
	 * @return void
	 */
	public function column_display_location_rules( $locations ) {

		$location_label = array();
		$index          = array_search( 'specifics', $locations['rule'], true );
		if ( false !== $index && ! empty( $index ) ) {
			unset( $locations['rule'][ $index ] );
		}

		if ( isset( $locations['rule'] ) && is_array( $locations['rule'] ) ) {
			foreach ( $locations['rule'] as $location ) {
				$location_label[] = Xpro_Target_Rules_Fields::get_location_by_key( $location );
			}
		}
		if ( isset( $locations['specific'] ) && is_array( $locations['specific'] ) ) {
			foreach ( $locations['specific'] as $location ) {
				$location_label[] = Xpro_Target_Rules_Fields::get_location_by_key( $location );
			}
		}

		echo join( ', ', array_map( 'esc_html', $location_label ) );
	}

	public function flush_rewrites() {
		$this->xpro_theme_builder_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Register Post type for Xpro Theme Builder templates
	 */
	public function xpro_theme_builder_post_type() {
		$labels = array(
			'name'               => __( 'Layouts', 'xpro-theme-builder' ),
			'singular_name'      => __( 'Layout', 'xpro-theme-builder' ),
			'menu_name'          => __( 'Theme Builder', 'xpro-theme-builder' ),
			'name_admin_bar'     => __( 'Theme Builder', 'xpro-theme-builder' ),
			'add_new'            => __( 'Add New', 'xpro-theme-builder' ),
			'add_new_item'       => __( 'Add New Layout', 'xpro-theme-builder' ),
			'new_item'           => __( 'New Layout', 'xpro-theme-builder' ),
			'edit_item'          => __( 'Edit Layout', 'xpro-theme-builder' ),
			'view_item'          => __( 'View Layout', 'xpro-theme-builder' ),
			'all_items'          => __( 'All Layout', 'xpro-theme-builder' ),
			'search_items'       => __( 'Search Layouts', 'xpro-theme-builder' ),
			'parent_item_colon'  => __( 'Parent Layouts:', 'xpro-theme-builder' ),
			'not_found'          => __( 'No Layout found.', 'xpro-theme-builder' ),
			'not_found_in_trash' => __( 'No Layout found in Trash.', 'xpro-theme-builder' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'map_meta_cap'        => true,
			'capability_type'     => 'page',
			'hierarchical'        => false,
			'has_archive'         => true,
			'menu_icon'           => XPRO_THEME_BUILDER_URL . '/admin/assets/images/xpro-themer-icon.svg',
			'supports'            => array( 'title', 'thumbnail', 'elementor' ),
		);

		register_post_type( 'xpro-themer', $args );

	}

	public function register_settings_submenus() {

		// Add sub menu
		add_submenu_page(
			Xpro_Elementor_Addons::PAGE_SLUG,
			esc_html__( 'Theme Builder', 'xpro-theme-builder' ),
			esc_html__( 'Theme Builder', 'xpro-theme-builder' ),
			'manage_options',
			'edit.php?post_type=xpro-themer'
		);

	}

	public function correct_current_active_menu() {

		$screen = get_current_screen();

		if ( 'xpro-themer' === $screen->id ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$('#toplevel_page_xpro-elementor-addons').addClass('wp-has-current-submenu wp-menu-open menu-top menu-top-first').removeClass('wp-not-current-submenu');
					$('#toplevel_page_xpro-elementor-addons > a').addClass('wp-has-current-submenu').removeClass('wp-not-current-submenu');
					$("#toplevel_page_xpro-elementor-addons a[href*='edit.php?post_type=xpro-themer']").addClass('current');
				});
			</script>
			<?php
		}

	}

	/**
	 * Register settings.
	 */
	public function xpro_theme_builder_frontend_settings() {

		$themer_settings = array(
			'public'   => false,
			'status'   => false,
			'expanded' => false,
			'tab'      => 'all-layout',
			'size'     => 500,
			'layout'   => 'list',
		);

		add_option( 'xpro_themer_frontend_settings', $themer_settings );

	}

	/**
	 * Register meta box(es).
	 */
	public function xpro_theme_builder_register_metabox() {
		add_meta_box(
			'xpro-theme-builder-meta-box',
			__( 'Xpro Theme Builder Options', 'xpro-theme-builder' ),
			array(
				$this,
				'xpro_theme_builder_metabox_render',
			),
			'xpro-themer',
			'normal',
			'high'
		);
	}

	/**
	 * Render Meta field.
	 */
	public function xpro_theme_builder_metabox_render( $post ) {
		$values        = get_post_custom( $post->ID );
		$template_type = isset( $values['xpro_theme_builder_template_type'] ) ? esc_attr( $values['xpro_theme_builder_template_type'][0] ) : '';
		$sticky        = isset( $values['xpro_theme_builder_sticky'] ) ? esc_attr( $values['xpro_theme_builder_sticky'][0] ) : '';

		// We'll use this nonce field later on when saving.
		wp_nonce_field( 'xpro_theme_builder_meta_nounce', 'xpro_theme_builder_meta_nounce' );
		?>
		<table class="xpro-theme-builder-options-table widefat">
			<tbody>
			<tr class="xpro-theme-builder-options-row type-of-template">
				<td class="xpro-theme-builder-options-row-heading">
					<label for="xpro_theme_builder_template_type"><?php esc_html_e( 'Type of Template', 'xpro-theme-builder' ); ?></label>
				</td>
				<td class="xpro-theme-builder-options-row-content">
					<select name="xpro_theme_builder_template_type" id="xpro_theme_builder_template_type">
						<option value="" <?php selected( $template_type, '' ); ?>><?php esc_html_e( 'Select', 'xpro-theme-builder' ); ?></option>
						<optgroup label="Structure">
							<option value="type_header" <?php selected( $template_type, 'type_header' ); ?>><?php esc_html_e( 'Header', 'xpro-theme-builder' ); ?></option>
							<option value="type_footer" <?php selected( $template_type, 'type_footer' ); ?>><?php esc_html_e( 'Footer', 'xpro-theme-builder' ); ?></option>
						</optgroup>
						<optgroup label="Content">
							<option value="type_archive" <?php selected( $template_type, 'type_archive' ); ?>><?php esc_html_e( 'Archive', 'xpro-theme-builder' ); ?></option>
							<option value="type_singular" <?php selected( $template_type, 'type_singular' ); ?>><?php esc_html_e( 'Singular', 'xpro-theme-builder' ); ?></option>
							<option value="custom" <?php selected( $template_type, 'custom' ); ?>><?php esc_html_e( 'Shortcode', 'xpro-theme-builder' ); ?></option>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr class="xpro-theme-builder-options-row header-sticky">
				<td class="xpro-theme-builder-options-row-heading">
					<label for="xpro_theme_builder_sticky"><?php esc_html_e( 'Header Sticky', 'xpro-theme-builder' ); ?></label>
					<i class="xpro-theme-builder-options-row-heading-help dashicons dashicons-editor-help" title="<?php esc_html_e( 'Enable this in order to sticky header (xtb-appear).', 'xpro-theme-builder' ); ?>"></i>
				</td>
				<td class="xpro-theme-builder-options-row-content">
					<select name="xpro_theme_builder_sticky" id="xpro_theme_builder_sticky">
						<option value="" <?php selected( $sticky, '' ); ?>><?php esc_html_e( 'Disable', 'xpro-theme-builder' ); ?></option>
						<option value="enable" <?php selected( $sticky, 'enable' ); ?>><?php esc_html_e( 'Enable', 'xpro-theme-builder' ); ?></option>
					</select>
				</td>
			</tr>
			<?php $this->display_rules_tab(); ?>
			<tr class="xpro-theme-builder-options-row xpro-theme-builder-shortcode">
				<td class="xpro-theme-builder-options-row-heading">
					<label for="xpro_theme_builder_template_type"><?php esc_html_e( 'Shortcode', 'xpro-theme-builder' ); ?></label>
					<i class="xpro-theme-builder-options-row-heading-help dashicons dashicons-editor-help" title="<?php esc_html_e( 'Copy this shortcode and paste it into post.', 'xpro-theme-builder' ); ?>">
					</i>
				</td>
				<td class="xpro-theme-builder-options-row-content">
						<span class="xpro-theme-builder-shortcode-col-wrap">
							<input type="text" onfocus="this.select();" readonly="readonly" value="[xpro_theme_builder_template id='<?php echo esc_attr( $post->ID ); ?>']" class="xpro-theme-builder-large-text code">
						</span>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Markup for Display Rules Tabs.
	 *
	 * @since  1.0.0
	 */
	public function display_rules_tab() {
		// Load Target Rule assets.
		Xpro_Target_Rules_Fields::get_instance()->admin_styles();

		$include_locations = get_post_meta( get_the_id(), 'xpro_theme_builder_target_include_locations', true );
		$exclude_locations = get_post_meta( get_the_id(), 'xpro_theme_builder_target_exclude_locations', true );
		$users             = get_post_meta( get_the_id(), 'xpro_theme_builder_target_user_roles', true );
		?>
		<tr class="xpro-theme-builder-target-rules-row xpro-theme-builder-options-row">
			<td class="xpro-theme-builder-target-rules-row-heading xpro-theme-builder-options-row-heading">
				<label><?php esc_html_e( 'Display On', 'xpro-theme-builder' ); ?></label>
				<i class="xpro-theme-builder-target-rules-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__( 'Add locations for where this template should appear.', 'xpro-theme-builder' ); ?>"></i>
			</td>
			<td class="xpro-theme-builder-target-rules-row-content xpro-theme-builder-options-row-content">
				<?php
				Xpro_Target_Rules_Fields::target_rule_settings_field(
					'xpro-theme-builder-target-rules-location',
					array(
						'title'          => __( 'Display Rules', 'xpro-theme-builder' ),
						'value'          => '[{"type":"basic-global","specific":null}]',
						'tags'           => 'site,enable,target,pages',
						'rule_type'      => 'display',
						'add_rule_label' => __( 'Add Display Rule', 'xpro-theme-builder' ),
					),
					$include_locations
				);
				?>
			</td>
		</tr>
		<tr class="xpro-theme-builder-target-rules-row xpro-theme-builder-options-row">
			<td class="xpro-theme-builder-target-rules-row-heading xpro-theme-builder-options-row-heading">
				<label><?php esc_html_e( 'Do Not Display On', 'xpro-theme-builder' ); ?></label>
				<i class="xpro-theme-builder-target-rules-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__( 'Add locations for where this template should not appear.', 'xpro-theme-builder' ); ?>"></i>
			</td>
			<td class="xpro-theme-builder-target-rules-row-content xpro-theme-builder-options-row-content">
				<?php
				Xpro_Target_Rules_Fields::target_rule_settings_field(
					'xpro-theme-builder-target-rules-exclusion',
					array(
						'title'          => __( 'Exclude On', 'xpro-theme-builder' ),
						'value'          => '[]',
						'tags'           => 'site,enable,target,pages',
						'add_rule_label' => __( 'Add Exclusion Rule', 'xpro-theme-builder' ),
						'rule_type'      => 'exclude',
					),
					$exclude_locations
				);
				?>
			</td>
		</tr>
		<tr class="xpro-theme-builder-target-rules-row xpro-theme-builder-options-row">
			<td class="xpro-theme-builder-target-rules-row-heading xpro-theme-builder-options-row-heading">
				<label><?php esc_html_e( 'User Roles', 'xpro-theme-builder' ); ?></label>
				<i class="xpro-theme-builder-target-rules-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__( 'Display custom template based on user role.', 'xpro-theme-builder' ); ?>"></i>
			</td>
			<td class="xpro-theme-builder-target-rules-row-content xpro-theme-builder-options-row-content">
				<?php
				Xpro_Target_Rules_Fields::target_user_role_settings_field(
					'xpro-theme-builder-target-rules-users',
					array(
						'title'          => __( 'Users', 'xpro-theme-builder' ),
						'value'          => '[]',
						'tags'           => 'site,enable,target,pages',
						'add_rule_label' => __( 'Add User Rule', 'xpro-theme-builder' ),
					),
					$users
				);
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save meta field.
	 *
	 * @param POST $post_id Currennt post object which is being displayed.
	 *
	 * @return Void
	 */
	public function xpro_theme_builder_save_meta( $post_id ) {

		// Bail if we're doing an auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if our nonce isn't there, or we can't verify it, bail.
		if ( ! isset( $_POST['xpro_theme_builder_meta_nounce'] ) || ! wp_verify_nonce( $_POST['xpro_theme_builder_meta_nounce'], 'xpro_theme_builder_meta_nounce' ) ) {
			return;
		}

		// if our current user can't edit this post, bail.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$target_locations = Xpro_Target_Rules_Fields::get_format_rule_value( $_POST, 'xpro-theme-builder-target-rules-location' );
		$target_exclusion = Xpro_Target_Rules_Fields::get_format_rule_value( $_POST, 'xpro-theme-builder-target-rules-exclusion' );
		$target_users     = array();

		if ( isset( $_POST['xpro-theme-builder-target-rules-users'] ) ) {
			$target_users = array_map( 'sanitize_text_field', $_POST['xpro-theme-builder-target-rules-users'] );
		}

		update_post_meta( $post_id, 'xpro_theme_builder_target_include_locations', $target_locations );
		update_post_meta( $post_id, 'xpro_theme_builder_target_exclude_locations', $target_exclusion );
		update_post_meta( $post_id, 'xpro_theme_builder_target_user_roles', $target_users );

		if ( isset( $_POST['xpro_theme_builder_template_type'] ) ) {
			update_post_meta( $post_id, 'xpro_theme_builder_template_type', sanitize_text_field( $_POST['xpro_theme_builder_template_type'] ) );
		}

		if ( isset( $_POST['xpro_theme_builder_sticky'] ) ) {
			update_post_meta( $post_id, 'xpro_theme_builder_sticky', sanitize_text_field( $_POST['xpro_theme_builder_sticky'] ) );
		}
	}

	/**
	 * Don't display the elementor Xpro Theme Builder templates on the frontend for non edit_posts capable users.
	 *
	 * @since  1.0.0
	 */
	public function block_template_frontend() {
		if ( is_singular( 'xpro-themer' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_safe_redirect( site_url(), 301 );
			die;
		}
	}


	/**
	 * Set shortcode column for template list.
	 *
	 * @param array $columns template list columns.
	 */
	public function set_custom_columns( $columns ) {
		$date_column = $columns['date'];

		unset( $columns['date'] );
		$columns['type'] = __( 'Type', 'xpro-theme-builder' );
		$columns['date'] = $date_column;

		return $columns;
	}

	/**
	 * Display shortcode in template list column.
	 *
	 * @param array $column template list column.
	 * @param int $post_id post id.
	 */
	public function render_custom_column( $column, $post_id ) {

		$type = get_post_meta( $post_id, 'xpro_theme_builder_template_type', true );

		if ( 'type' === $column ) {
			ob_start();
			?>
			<span class="xpro-theme-builder-type-col-wrap">
					<?php echo esc_html( ucfirst( str_replace( 'type_', '', $type ) ) ); ?>
			</span>
			<?php
			ob_get_contents();
		}
	}

}

Xpro_Theme_Builder_Admin::instance();
