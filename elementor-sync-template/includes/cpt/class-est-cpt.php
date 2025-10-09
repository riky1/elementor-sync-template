<?php

namespace Elementor_Sync_Template\Cpt;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class EST_CPT
 *
 * Gestisce la registrazione del Custom Post Type per i template sincronizzati.
 *
 * @since 1.0.0
 */
class EST_CPT {

	/**
	 * Istanza unica della classe.
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 * @var \Elementor_Sync_Template\Cpt\EST_CPT The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Assicura che venga caricata una sola istanza della classe (singleton).
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return \Elementor_Sync_Template\Cpt\EST_CPT An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * Nome del Custom Post Type.
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	const POST_TYPE = 'es_template';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'register_post_type' ] );

		// Imposta il template "Canvas" come predefinito per i nuovi post del CPT e lo carica.
		add_filter( 'template_include', [ $this, 'set_default_template' ] );

	}

	/**
	 * Registra il Custom Post Type.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_post_type(): void {

		$labels = [
			'name'                  => esc_html_x( 'ES Template', 'Post Type General Name', 'elementor-sync-template' ),
			'singular_name'         => esc_html_x( 'ES Template', 'Post Type Singular Name', 'elementor-sync-template' ),
			'menu_name'             => esc_html__( 'ES Templates', 'elementor-sync-template' ),
			'name_admin_bar'        => esc_html__( 'ES Template', 'elementor-sync-template' ),
			'archives'              => esc_html__( 'Template Archives', 'elementor-sync-template' ),
			'attributes'            => esc_html__( 'Template Attributes', 'elementor-sync-template' ),
			'parent_item_colon'     => esc_html__( 'Parent Template:', 'elementor-sync-template' ),
			'all_items'             => esc_html__( 'All Templates', 'elementor-sync-template' ),
			'add_new_item'          => esc_html__( 'Add New Template', 'elementor-sync-template' ),
			'add_new'               => esc_html__( 'Add New', 'elementor-sync-template' ),
			'new_item'              => esc_html__( 'New Template', 'elementor-sync-template' ),
			'edit_item'             => esc_html__( 'Edit Template', 'elementor-sync-template' ),
			'update_item'           => esc_html__( 'Update Template', 'elementor-sync-template' ),
			'view_item'             => esc_html__( 'View Template', 'elementor-sync-template' ),
			'view_items'            => esc_html__( 'View Templates', 'elementor-sync-template' ),
			'search_items'          => esc_html__( 'Search Template', 'elementor-sync-template' ),
			'not_found'             => esc_html__( 'Not found', 'elementor-sync-template' ),
			'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'elementor-sync-template' ),
			'featured_image'        => esc_html__( 'Featured Image', 'elementor-sync-template' ),
			'set_featured_image'    => esc_html__( 'Set featured image', 'elementor-sync-template' ),
			'remove_featured_image' => esc_html__( 'Remove featured image', 'elementor-sync-template' ),
			'use_featured_image'    => esc_html__( 'Use as featured image', 'elementor-sync-template' ),
			'insert_into_item'      => esc_html__( 'Insert into template', 'elementor-sync-template' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this template', 'elementor-sync-template' ),
			'items_list'            => esc_html__( 'Templates list', 'elementor-sync-template' ),
			'items_list_navigation' => esc_html__( 'Templates list navigation', 'elementor-sync-template' ),
			'filter_items_list'     => esc_html__( 'Filter templates list', 'elementor-sync-template' ),
		];

		$args = [
			'label'               => esc_html__( 'ES Template', 'elementor-sync-template' ),
			'description'         => esc_html__( 'Reusable and synchronized templates for Elementor.', 'elementor-sync-template' ),
			'labels'              => $labels,
			'supports'            => [ 'title', 'elementor' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 58,
			'menu_icon'           => 'dashicons-randomize',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'rewrite'             => [ 'slug' => 'es-template' ],
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		];

		register_post_type( self::POST_TYPE, $args );

	}

	/**
	 * Imposta e carica il template di default per i post del tipo 'es_template'.
	 *
	 * Questo metodo assicura che ogni nuovo "Sync Template" creato
	 * utilizzi di default il template Canvas. Inoltre, si assicura che il template
	 * corretto venga caricato quando si visualizza un post di questo tipo.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $template Il percorso del template di default corrente.
	 * @return string Il percorso del nuovo template di default.
	 */
	public function set_default_template( $template ) {

		if ( is_singular( self::POST_TYPE ) && is_user_logged_in() && current_user_can('edit_posts') ) {
			
			$empty_template = EST_PLUGIN_PATH . 'templates/template-canvas.php';

			if ( file_exists( $empty_template ) ) {
					return $empty_template;
			}

		}

		return $template;
		
	}
}
