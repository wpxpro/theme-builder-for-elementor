<?php

/**
 * REST API methods to retrieve data for WordPress rules.
 *
 * @since 1.0.0
 */

use Xpro_Theme_Builder\Lib\Xpro_Target_Rules_Fields;

defined( 'ABSPATH' ) || exit;

final class Xpro_Theme_Builder_Rest_Api {

	/**
	 * REST API namespace
	 *
	 * @since 1.0.0
	 * @var string $namespace
	 */

	protected static $namespace = 'wp/v2/xpro-themer';

	public static function init() {
		add_action( 'rest_api_init', __CLASS__ . '::register_routes' );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public static function register_routes() {

		register_rest_route(
			self::$namespace,
			'/get-settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => __CLASS__ . '::get_settings',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

		register_rest_route(
			self::$namespace,
			'/update-settings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => __CLASS__ . '::update_settings',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

		register_rest_route(
			self::$namespace,
			'/create-post',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => __CLASS__ . '::create_post',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

		register_rest_route(
			self::$namespace,
			'/get-posts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => __CLASS__ . '::get_posts',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

		register_rest_route(
			self::$namespace,
			'/delete-post/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => __CLASS__ . '::delete_post',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

		register_rest_route(
			self::$namespace,
			'/untrash-post/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => __CLASS__ . '::untrash',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

		register_rest_route(
			self::$namespace,
			'/get-post/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => __CLASS__ . '::get_single_post',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

		register_rest_route(
			self::$namespace,
			'/update-post/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => __CLASS__ . '::update_post',
				'permission_callback' => __CLASS__ . '::check_permission',
			)
		);

	}

	public static function get_settings( $request ) {

		$data = get_option( 'xpro_themer_frontend_settings' );
		return $data;

	}

	public static function update_settings( $request ) {

		$status   = sanitize_text_field( $request->get_param( 'status' ) );
		$expanded = sanitize_text_field( $request->get_param( 'expanded' ) );
		$size     = sanitize_text_field( $request->get_param( 'size' ) );
		$tab      = sanitize_text_field( $request->get_param( 'tab' ) );
		$layout   = sanitize_text_field( $request->get_param( 'layout' ) );

		if ( ! $status && ! $expanded && ! $size && ! $tab ) {
			return new WP_Error( 'invalid_data', 'Please define a valid post settings.' );
		}

		$themer_settings = array(
			'public'   => false,
			'status'   => $status,
			'expanded' => $expanded,
			'tab'      => $tab,
			'size'     => $size,
			'layout'   => $layout,
		);

		update_option( 'xpro_themer_frontend_settings', $themer_settings );

		$data = 'Setting Saved';

		$response = new WP_REST_Response( $themer_settings, 200 );

		return $response;

	}

	public static function create_post( $request ) {

		$title = sanitize_text_field( $request->get_param( 'title' ) );
		$type  = sanitize_text_field( $request->get_param( 'type' ) );

		if ( 'header' === $type ) {
			$type = 'type_header';
		} elseif ( 'footer' === $type ) {
			$type = 'type_footer';
		} elseif ( 'singular' === $type ) {
			$type = 'type_singular';
		} elseif ( 'archive' === $type ) {
			$type = 'type_archive';
		} else {
			$type = '';
		}

		$data = array();

		$new_post = array(
			'post_title'  => $title,
			'post_status' => 'draft',
			'post_type'   => 'xpro-themer',
			'post_author' => 1,
			'post_date'   => gmdate( 'Y-m-d H:i:s' ),
		);

		$post = wp_insert_post( $new_post );

		update_post_meta( $post, 'xpro_theme_builder_template_type', $type );

		$data['message'] = __( 'Post Added', 'xpro-theme-builder' );

		$response = new WP_REST_Response( $data, 200 );

		return $response;

	}

	public static function get_posts( $request ) {

		$posts_data = array();

		//Params
		$type   = sanitize_text_field( $request->get_param( 'type' ) );
		$status = sanitize_text_field( $request->get_param( 'status' ) );
		$sort   = sanitize_text_field( $request->get_param( 'sort' ) );
		$order  = sanitize_text_field( $request->get_param( 'order' ) );
		$search = sanitize_text_field( $request->get_param( 'search' ) );

		if ( 'default' === $status ) {
			$status = array( 'publish', 'draft', 'private', 'future', 'pending', 'protected' );
		} elseif ( 'schedule' === $status ) {
			$status = 'future';
		}

		if ( 'ascending' === $order ) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		$posts = get_posts(
			array(
				'posts_per_page' => - 1,
				'orderby'        => $sort,
				'order'          => $order,
				'post_type'      => 'xpro-themer',
				'post_status'    => $status,
				's'              => $search,
			)
		);

		if ( 'all-layout' !== $type ) {
			$type = 'type_' . $type;
		}

		foreach ( $posts as $post ) {
			$id = $post->ID;
			if ( $type && 'all-layout' !== $type && get_post_meta( $post->ID, 'xpro_theme_builder_template_type', true ) === $type ) {
				$posts_data[] = (object) array(
					'id'         => $id,
					'title'      => $post->post_title,
					'date'       => $post->post_modified,
					'type'       => get_post_meta( $post->ID, 'xpro_theme_builder_template_type', true ),
					'location'   => get_post_meta( $post->ID, 'xpro_theme_builder_target_include_locations', true ),
					'author'     => get_the_author_meta( 'display_name', $post->post_author ),
					'author_url' => get_author_posts_url( get_the_author_meta( 'ID', $post->post_author ) ),
					'status'     => $post->post_status,
					'link'       => str_replace( array( '&#038;', '&amp;' ), '&', $post->guid ),
				);
			}

			if ( 'all-layout' === $type ) {
				$posts_data[] = (object) array(
					'id'         => $id,
					'title'      => $post->post_title,
					'date'       => $post->post_modified,
					'type'       => get_post_meta( $post->ID, 'xpro_theme_builder_template_type', true ),
					'location'   => get_post_meta( $post->ID, 'xpro_theme_builder_target_include_locations', true ),
					'author'     => get_the_author_meta( 'display_name', $post->post_author ),
					'author_url' => get_author_posts_url( get_the_author_meta( 'ID', $post->post_author ) ),
					'status'     => $post->post_status,
					'link'       => str_replace( array( '&#038;', '&amp;' ), '&', $post->guid ),
				);
			}
		}

		return $posts_data;

	}

	public static function delete_post( $request ) {

		if ( ! get_post( intval( $request['id'] ) ) ) {
			return new WP_Error( 'invalid_id', 'Please define a valid post ID.' );
		}

		$id        = intval( $request['id'] );
		$permanent = sanitize_text_field( $request->get_param( 'permanent' ) );

		$data = array();

		if ( 'true' === $permanent ) {
			wp_delete_post( $id, true );
			$data['message'] = __( 'Delete Permanent', 'xpro-theme-builder' );

		} else {
			wp_trash_post( $id );
			$data['message'] = __( 'Move to trash', 'xpro-theme-builder' );
		}

		$response = new WP_REST_Response( $data, 200 );

		return $response;

	}

	public static function untrash( $request ) {

		if ( ! get_post( intval( $request['id'] ) ) ) {
			return new WP_Error( 'invalid_id', 'Please define a valid post ID.' );
		}

		$id = intval( $request['id'] );

		$data = array();

		wp_untrash_post( $id );

		$data['message'] = __( 'Untrashed Post', 'xpro-theme-builder' );

		$response = new WP_REST_Response( $data, 200 );

		return $response;

	}

	public static function get_single_post( $request ) {

		$posts_data = array();

		if ( ! get_post( intval( $request['id'] ) ) ) {
			return new WP_Error( 'invalid_id', 'Please define a valid post ID.' );
		}

		$id = intval( $request['id'] );

		$args = array(
			'p'           => $id,
			'post_type'   => 'xpro-themer',
			'post_status' => array( 'publish', 'draft', 'private', 'future', 'pending', 'protected' ),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {

			$query->the_post();
			$post = get_post( $id );

			$posts_data[] = (object) array(
				'id'             => $id,
				'title'          => $post->post_title,
				'date'           => $post->post_modified,
				'status'         => $post->post_status,
				'link'           => str_replace( array( '&#038;', '&amp;' ), '&', $post->guid ),
				'type'           => get_post_meta( $post->ID, 'xpro_theme_builder_template_type', true ),
				'sticky'         => get_post_meta( $post->ID, 'xpro_theme_builder_sticky', true ),
				'location'       => get_post_meta( $post->ID, 'xpro_theme_builder_target_include_locations', true ),
				'exclude'        => get_post_meta( $post->ID, 'xpro_theme_builder_target_exclude_locations', true ),
				'user_role'      => get_post_meta( $post->ID, 'xpro_theme_builder_target_user_roles', true ),
				'author'         => get_the_author_meta( 'display_name', $post->post_author ),
				'all_locations'  => Xpro_Target_Rules_Fields::get_location_selections(),
				'specific_posts' => self::specific_posts(),
				'elementor'      => defined( 'ELEMENTOR_VERSION' ) && is_callable( 'Elementor\Plugin::instance' ),
				'beaver'         => class_exists( 'FLBuilder' ),
				'woocommerce'    => class_exists( 'woocommerce' ),
			);

		}

		return $posts_data;

	}

	public static function specific_posts() {

		$search_string = '';
		$data          = array();
		$result        = array();

		$args = array(
			'public'   => true,
			'_builtin' => false,
		);

		$output     = 'names'; // names or objects, note names is the default.
		$operator   = 'and'; // also supports 'or'.
		$post_types = get_post_types( $args, $output, $operator );

		//Exclude EHF templates.
		unset( $post_types['xpro-themer'] );
		unset( $post_types['elementor_library'] );
		unset( $post_types['xpro_content'] );

		$post_types['Posts'] = 'post';
		$post_types['Pages'] = 'page';

		foreach ( $post_types as $key => $post_type ) {
			$data  = array();
			$query = new \WP_Query(
				array(
					's'              => $search_string,
					'post_type'      => $post_type,
					'posts_per_page' => - 1,
				)
			);

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$title  = get_the_title();
					$title .= ( 0 !== $query->post->post_parent ) ? ' (' . get_the_title( $query->post->post_parent ) . ')' : '';
					$id     = get_the_id();
					$data[] = array(
						'id'   => 'post-' . $id,
						'text' => $title,
					);
				}
			}

			if ( is_array( $data ) && ! empty( $data ) ) {
				$result[] = array(
					'text'     => $key,
					'children' => $data,
				);
			}
		}

		wp_reset_postdata();

		$args = array(
			'public' => true,
		);

		$output     = 'objects'; // names or objects, note names is the default.
		$operator   = 'and'; // also supports 'or'.
		$taxonomies = get_taxonomies( $args, $output, $operator );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				$taxonomy->name,
				array(
					'orderby'    => 'count',
					'hide_empty' => 0,
					'name__like' => $search_string,
				)
			);

			$data = array();

			$label = ucwords( $taxonomy->label );

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_taxonomy_name = ucfirst( str_replace( '_', ' ', $taxonomy->name ) );

					$data[] = array(
						'id'   => 'tax-' . $term->term_id,
						'text' => $term->name . ' archive page',
					);

					$data[] = array(
						'id'   => 'tax-' . $term->term_id . '-single-' . $taxonomy->name,
						'text' => 'All singulars from ' . $term->name,
					);
				}
			}

			if ( is_array( $data ) && ! empty( $data ) ) {
				$result[] = array(
					'text'     => $label,
					'children' => $data,
				);
			}
		}

		return $result;

	}

	public static function update_post( $request ) {

		$posts_data = array();

		if ( ! get_post( intval( $request['id'] ) ) ) {
			return new WP_Error( 'invalid_id', 'Please define a valid post ID.' );
		}

		$id = intval( $request['id'] );

		$title            = sanitize_text_field( $request->get_param( 'title' ) );
		$type             = sanitize_text_field( $request->get_param( 'type' ) );
		$sticky           = sanitize_text_field( $request->get_param( 'sticky' ) );
		$status           = sanitize_text_field( $request->get_param( 'status' ) );
		$rule             = sanitize_text_field( $request->get_param( 'rule' ) );
		$specific         = sanitize_text_field( $request->get_param( 'specific' ) );
		$exclude_rule     = sanitize_text_field( $request->get_param( 'excludeRule' ) );
		$exclude_specific = sanitize_text_field( $request->get_param( 'excludeSpecific' ) );
		$role             = sanitize_text_field( $request->get_param( 'role' ) );

		$post_update = array(
			'ID'          => $id,
			'post_title'  => $title,
			'post_status' => $status,
		);

		wp_update_post( $post_update );

		$location = array();

		$location['rule'] = $rule ? explode( ',', $rule ) : array( '' );

		$location['specific'] = $specific ? explode( ',', $specific ) : array( '' );

		$exclude = array();

		$exclude['rule'] = ! empty( $exclude_rule ) ? explode( ',', $exclude_rule ) : array( '' );

		$exclude['specific'] = ! empty( $exclude_specific ) ? explode( ',', $exclude_specific ) : array( '' );

		$user_role = ! empty( $role ) ? explode( ',', $role ) : array( '' );

		update_post_meta( $id, 'xpro_theme_builder_template_type', $type );
		update_post_meta( $id, 'xpro_theme_builder_sticky', $sticky );
		update_post_meta( $id, 'xpro_theme_builder_target_include_locations', $location );
		update_post_meta( $id, 'xpro_theme_builder_target_exclude_locations', $exclude );
		update_post_meta( $id, 'xpro_theme_builder_target_user_roles', $user_role );

		$data = __( 'Post Updated', 'xpro-theme-builder' );

		$response = new WP_REST_Response( $data, 200 );

		return $response;

	}

	/**
	 * Checks permission.
	 *
	 * @return boolean
	 */
	public static function check_permission() {
		return current_user_can( 'edit_posts' );
	}
}

Xpro_Theme_Builder_Rest_Api::init();
