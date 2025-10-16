<?php

namespace Elementor_Sync_Template\Rest_API;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template Keys REST Controller
 *
 * Espone un endpoint REST per recuperare le chiavi dinamiche presenti in un template Elementor.
 * Route: GET /est/v1/templates/<id>/keys
 * 
 * @since 1.2.0
 * @access public
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
 */
class Template_Keys_Controller {

 	/**
 	 * REST namespace and version.
 	 */
 	const NAMESPACE = 'est/v1';

 	/**
 	 * Register routes.
   *
   * @since 1.2.0
 	 * @access public
 	 * @static
 	 */
 	public static function register_routes(): void {
 		register_rest_route(
 			self::NAMESPACE,
 			'/templates/(?P<id>\d+)/keys',
 			[
 				'methods' => WP_REST_Server::READABLE,
 				'callback' => [ __CLASS__, 'get_template_keys' ],
 				'permission_callback' => [ __CLASS__, 'permissions_check' ],
 			]
 		);
 	}

 	/**
 	 * Permission check: ensure the template exists and the current user can edit it (or is allowed to view in editor).
 	 *
   * @since 1.2.0
 	 * @access public
 	 * @static
 	 * @param WP_REST_Request $request
 	 * @return bool
 	 */
 	public static function permissions_check( WP_REST_Request $request ): bool {

 		$id = (int) $request->get_param( 'id' );
 		if ( ! $id ) return false;

 		$post = get_post( $id );
 		if ( ! $post ) return false;

 		// Ensure it's the right post type (our templates CPT).
 		if ( \Elementor_Sync_Template\Cpt\EST_CPT::POST_TYPE !== get_post_type( $post ) ) {
 			return false;
 		}

 		// Require capability to edit the post (restrict to editors/owners) — this is safe for editor-only data.
 		return current_user_can( 'edit_post', $id );
 	}

 	/**
 	 * Callback to get template keys.
 	 *
   * @since 1.2.0
	 * @since 1.3.0 add repeater support
 	 * @access public
 	 * @static
 	 * @param WP_REST_Request $request
 	 * @return WP_REST_Response
 	 */
 	public static function get_template_keys( WP_REST_Request $request ): WP_REST_Response {
 		$id = (int) $request->get_param( 'id' );

 		$keys = self::extract_fields_from_template( $id );

 		return new WP_REST_Response( [ 'keys' => $keys ], 200 );
 	}

 	/**
 	 * Extract dynamic keys from the Elementor template data.
 	 *
   * @since 1.2.0
	 * @since 1.3.0 add repeater support
	 * @since 1.7.0 change from key to _id 
 	 * @access private
 	 * @static
 	 * @param int $template_id
 	 * @return array
 	 */
 	private static function extract_fields_from_template( int $template_id ): array {
 		$fields = [];

 		// Try common storage locations.
 		$data = get_post_meta( $template_id, '_elementor_data', true );

 		if ( empty( $data ) ) {
 			$raw = get_post_field( 'post_content', $template_id );

 			if ( $raw ) {
 				$json = json_decode( $raw, true );

 				if ( is_array( $json ) ) {
 					$data = $json;
 				}
 			}
 		}

 		if ( is_string( $data ) ) {
 			$decoded = json_decode( $data, true );

 			if ( is_array( $decoded ) ) {
 				$data = $decoded;
 			}
 		}

 		if ( empty( $data ) || ! is_array( $data ) ) {
 			return [];
 		}

 		// Use Elementor iterator if available.
 		if ( ! empty( \Elementor\Plugin::$instance->db ) && method_exists( \Elementor\Plugin::$instance->db, 'iterate_data' ) ) {

 			\Elementor\Plugin::$instance->db->iterate_data( $data, function( $element ) use ( & $fields ) {
 				$settings = $element['settings'] ?? [];

 				if ( ! empty( $settings['_est_dynamic_fields_repeater'] ) && is_array( $settings['_est_dynamic_fields_repeater'] ) ) {

 					foreach ( $settings['_est_dynamic_fields_repeater'] as $field ) {
						if ( ! empty( $field['_id'] ) ) {
							$fields[ $field['_id'] ] = $field; // Usa _id come chiave unica.
						}
					}

 				}
 				return $element;
 			} );

 		} else {

 			// Simple fallback.
 			$iterator = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $data ) );

			foreach ( $iterator as $key => $value ) {
				if ( '_est_dynamic_fields_repeater' === $key && is_array( $value ) ) {

					foreach ( $value as $field ) {
						// usa _id, ma lascia compatibilità temporanea con key
						$field_id = $field['_id'] ?? ( $field['key'] ?? null );
						if ( ! empty( $field_id ) ) {
							$fields[ $field_id ] = $field;
						}
					}

				}
			}
 		}

 		$final_fields = array_values( $fields );

		// Ordina per label o _id
		// usort(
		// 	$final_fields,
		// 	function ( $a, $b ) {
		// 		$a_label = $a['label'] ?? $a['_id'] ?? '';
		// 		$b_label = $b['label'] ?? $b['_id'] ?? '';
		// 		return strcmp( $a_label, $b_label );
		// 	}
		// );

		return $final_fields;
 	}

}

// Self-register route when this file is loaded.
add_action( 'rest_api_init', [ '\Elementor_Sync_Template\Rest_API\Template_Keys_Controller', 'register_routes' ] );
