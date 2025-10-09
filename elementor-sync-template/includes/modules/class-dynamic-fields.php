<?php

namespace Elementor_Sync_Template\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
	 * @access public
	 */
	public function __construct() {

		// Aggiunge il campo per la chiave dinamica a tutti gli elementi.
		// Questo hook generico permette di intercettare tutte le sezioni.
		\add_action( 'elementor/element/after_section_end', [ $this, 'add_dynamic_field_controls' ], 10, 2 );

	}

	/**
	 * Il controllo aggiunge il controllo '_est_dynamic_field_key' all'elemento nel CPT specifico.
	 * Le chiavi sono salvate nelle impostazioni dell'elemento nel template post meta o nel markup JSON dell'elementor
	 *
	 * Controlla ogni sezione e, se è la prima della tab "Contenuto",
	 * aggiunge la sezione personalizzata subito dopo.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param \Elementor\Element_Base $element L'elemento che viene modificato.
	 * @param string $section_id L'ID della sezione corrente.
	 */
	public function add_dynamic_field_controls( $element, string $section_id ): void {

		// Ottiene l'ID del post corrente dall'editor di Elementor.
		$post_id = \Elementor\Plugin::$instance->editor->get_post_id();

		// Se non è nel contesto di un editor di post, esce.
		if ( ! $post_id ) {
			return;
		}

		// Controlla se il tipo di post corrente è il CPT. Se non lo è, esce.
		if ( \Elementor_Sync_Template\Cpt\EST_CPT::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		// Previene l'esecuzione su elementi non standard come le preferenze dell'editor.
		if ( ! $element instanceof \Elementor\Element_Base ) {
			return;
		}

		// Controlla se è già stata aggiunta la sezione per questo elemento.
		$controls = $element->get_controls();

		if ( isset( $controls['_est_dynamic_field_key'] ) ) {
			return;
		}

		// Ottiene i dettagli della sezione corrente.
		$current_section = $element->get_controls( $section_id );

		// Se la sezione non ha una tab (raro) o non è nella tab "Contenuto", esce.
		if ( empty( $current_section['tab'] ) || \Elementor\Controls_Manager::TAB_CONTENT !== $current_section['tab'] ) {
			return;
		}

		// Inizia una nuova sezione di controlli.
		$element->start_controls_section(
			'_est_dynamic_field_section',
			[
				'label' => '<i class="eicon-sync"></i> ' . __( 'Sync Template Field', 'elementor-sync-template' ),
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
			]
		);

		// Termina la sezione di controlli.
		$element->end_controls_section();
	}
}