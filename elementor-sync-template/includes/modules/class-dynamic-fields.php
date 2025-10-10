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
		add_action( 'elementor/element/common/section_effects/after_section_end', [ $this, 'add_dynamic_field_controls' ], 10, 1 );
	}

	/**
	 * Aggiunge una nuova sezione di controlli a ogni elemento di Elementor
	 *
	 * @since 1.1.0
	 * @since 1.2.1 fix: post_id non trovato su nuovo template quindi non caricava i controlli
	 * @access public
	 * @param \Elementor\Element_Base $element L'elemento che viene modificato.
	 */
	public function add_dynamic_field_controls( $element ): void {

		console_log('=== Funzione dichiarata === ');


		// Esce se l'oggetto non è un'istanza di Element_Base (es. preferenze dell'editor).
		if ( ! $element instanceof \Elementor\Element_Base ) {
			return;
		}

		console_log('=== Istanza di Element_Base === ');


		// Ottiene l'ID del post corrente dall'editor di Elementor.
		$post_id = \Elementor\Plugin::$instance->editor->get_post_id();

		// Se non è nel contesto di un editor di post, esce.
		// if ( ! $post_id ) {
		// 	return;
		// }

		console_log('Post ID: ' . $post_id);


		// Inizia una nuova sezione di controlli.
		$element->start_controls_section(
			'_est_dynamic_field_section',
			[
				'label' => '<i class="eicon-sync"></i> ' . __( 'Sync Template Key', 'elementor-sync-template' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		// Aggiunge il campo di testo per la chiave.
		$element->add_control(
			'_est_dynamic_field_key',
			[
				'label'       => __( 'Dynamic Field Key', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Enter a unique key (e.g., "hero_title") to make this element customizable in the Sync Template widget.', 'elementor-sync-template' ),
				'dynamic'     => [ 'active' => false ], // La chiave stessa non deve essere dinamica.
			]
		);

		// Termina la sezione di controlli.
		$element->end_controls_section();
	}
}