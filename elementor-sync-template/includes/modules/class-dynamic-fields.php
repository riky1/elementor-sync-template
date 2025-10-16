<?php

namespace Elementor_Sync_Template\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Funzione temporanea di debug per loggare i dati nella console degli errori di PHP.
 *
 * @param mixed $data I dati da loggare.
 */
function console_log($data) { 
	error_log(print_r($data, true)); 
}

/**
 * Class Dynamic_Fields
 *
 * Aggiunge i controlli per la mappatura dei campi dinamici a tutti gli elementi di Elementor.
 *
 * @since 1.1.0
 * @access public
 */
class Dynamic_Fields {

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 * @since 1.2.1 fix: la classe veniva caricata per ogni componente di Elementor
	 * @since 1.6.1 spostato controlli nel tab Advanced e aggiunto hook anche per i widget container
	 * @access public
	 */
	public function __construct() {

		// Aggiunge il campo per la chiave dinamica agli elementi Common e Container.
		// Questo hook viene eseguito prima della sezione style (x i common) e layout (x i container).
		add_action( 'elementor/element/common/_section_style/before_section_start', [ $this, 'add_dynamic_field_controls' ], 10, 2 );
		add_action( 'elementor/element/container/section_layout/before_section_start', [ $this, 'add_dynamic_field_controls' ], 10, 2 );
	}

	/**
	 * Aggiunge una nuova sezione di controlli a ogni elemento di Elementor
	 * quando si sta modificando un post di tipo 'es_template'.
	 *
	 * @since 1.1.0
	 * @since 1.2.1 fix: post_id non trovato su nuovo template quindi non caricava i controlli
	 * @since 1.3.0 convertito a repeater per campi più versatili.
	 * @since 1.4.1 fix: la sezione veniva aggiunta anche in contesti non validi (es: modifica pagina)
	 * @since 1.5.6 edit controls
	 * @since 1.6.1 add wysiwyg control + nascosto button 'Aggiungi elemento' dal repeater
	 * @access public
	 * @param \Elementor\Element_Base $element L'elemento che viene modificato.
	 */
	public function add_dynamic_field_controls( $element, $args ): void {

		console_log('=== Funzione dichiarata === ');

		// Esce se l'oggetto non è un'istanza di Element_Base (es. preferenze dell'editor).
		if ( ! $element instanceof \Elementor\Element_Base ) {
			return;
		}

		$post_id = \Elementor\Plugin::$instance->editor->get_post_id();
		console_log('Post ID from get_post_id(): ' . $post_id);

		$post_type = get_post_type( $post_id );
		console_log('Post Type from get_post_type(): ' . $post_type);

		// Esce se il post type non è 'es_template'.
		if ( $post_type != 'es_template' ) {
			console_log('Esce perché non è es_template');

			return;
		}

		$element->start_controls_section(
			'_est_dynamic_fields_section',
			[
				'label' => __( 'Sync Template Field', 'elementor-sync-template' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'key',
			[
				'label'       => __( 'Field Key', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Unique identifier (e.g., "hero_title"). No spaces or special characters.', 'elementor-sync-template' ),
				'default'     => '',
				'dynamic'     => [ 'active' => false ], // La chiave non deve essere dinamica.,
				'separator' => 'after',
			]
		);

		$repeater->add_control(
			'label',
			[
				'label'       => __( 'Field Label', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'User-friendly name for this field.', 'elementor-sync-template' ),
				'default'     => '',
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'type',
			[
				'label'   => __( 'Field Type', 'elementor-sync-template' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'text'     => __( 'Text', 'elementor-sync-template' ),
					'textarea' => __( 'Textarea', 'elementor-sync-template' ),
					'wysiwyg' =>  __( 'Wysiwyg', 'elementor-sync-template' ),
					'image'    => __( 'Image', 'elementor-sync-template' ),
					'url'      => __( 'URL', 'elementor-sync-template' ),
				],
				'default' => 'text',
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'       => __( 'Description', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'label_block' => true,
				'description' => __( 'Instructions for the user filling out this field.', 'elementor-sync-template' ),
				'separator' => 'before',
			]
		);

		$element->add_control(
			'_est_dynamic_fields_repeater',
			[
				'label'       => __( 'Dynamic Fields', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || key || "Add dynamic field" }}}',
				'prevent_empty' => false,
				'classes' => 'est-single-item-repeater',
				'default' => [
					[
						'key'   => '',
						'label' => '',
						'type'  => 'text',
						'description' => '',
					],
				],
			]
		);

		$element->end_controls_section();

	}
}