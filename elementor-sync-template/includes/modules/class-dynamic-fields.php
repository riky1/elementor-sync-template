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
	 * @access public
	 */
	public function __construct() {

		// Aggiunge il campo per la chiave dinamica a tutti gli elementi.
		// Questo hook viene eseguito dopo la sezione degli effetti comuni.
		// In questo modo, il campo sarà visibile in tutte le sezioni degli elementi
		add_action( 'elementor/element/common/section_effects/after_section_end', [ $this, 'add_dynamic_field_controls' ], 10, 2 );
	}

	/**
	 * Aggiunge una nuova sezione di controlli a ogni elemento di Elementor
	 * quando si sta modificando un post di tipo 'es_template'.
	 *
	 * @since 1.1.0
	 * @since 1.2.1 fix: post_id non trovato su nuovo template quindi non caricava i controlli
	 * @since 1.3.0 convertito a repeater per campi più versatili.
	 * @since 1.4.1 fix: la sezione veniva aggiunta anche in contesti non validi (es: modifica pagina)
	 * @access public
	 * @param \Elementor\Element_Base $element L'elemento che viene modificato.
	 */
	public function add_dynamic_field_controls( $element, $args ): void {

		console_log('=== Funzione dichiarata === ');

		// Esce se l'oggetto non è un'istanza di Element_Base (es. preferenze dell'editor).
		if ( ! $element instanceof \Elementor\Element_Base ) {
			console_log('Esce perché non è Element_Base');
			return;
		}

		$post_id = \Elementor\Plugin::$instance->editor->get_post_id();
		console_log('Post ID from get_post_id(): ' . $post_id);

		$post_type = get_post_type( $post_id );
		console_log('Post Type from get_post_type(): ' . $post_type);

		if ( $post_type != 'es_template' ) {
			console_log('Esce perché non è es_template');
			return;
		}

		console_log('Aggiunge i controlli per i campi dinamici');

		// Inizia una nuova sezione di controlli.
		$element->start_controls_section(
			'_est_dynamic_fields_section',
			[
				'label' => __( 'Sync Template Field', 'elementor-sync-template' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		// Aggiunge il controllo Repeater.
		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'key',
			[
				'label'       => __( 'Field Key', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Unique identifier (e.g., "hero_title"). No spaces or special characters.', 'elementor-sync-template' ),
				'default'     => '',
				'dynamic'     => [ 'active' => false ], // La chiave non deve essere dinamica.
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
					'image'    => __( 'Image', 'elementor-sync-template' ),
					'url'      => __( 'URL', 'elementor-sync-template' ),
				],
				'default' => 'text',
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'       => __( 'Description', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'label_block' => true,
				'description' => __( 'Instructions for the user filling out this field.', 'elementor-sync-template' ),
			]
		);

		$element->add_control(
			'_est_dynamic_fields_repeater',
			[
				'label'       => __( 'Dynamic Fields', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || key || "New Field" }}}',
				'prevent_empty' => false,
			]
		);

		// Termina la sezione di controlli.
		$element->end_controls_section();

	}
}