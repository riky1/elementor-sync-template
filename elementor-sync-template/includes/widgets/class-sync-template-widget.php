<?php

namespace Elementor_Sync_Template\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sync Template Widget
 *
 * Widget Elementor per visualizzare un 'ES Template' e popolarne i campi dinamici.
 *
 * @since 1.4.0
 */
class Sync_Template_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @since 1.4.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'sync-template';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.4.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return __( 'Sync Template', 'elementor-sync-template' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.4.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'eicon-sync';
	}

	/**
	 * Get widget categories.
	 *
	 * @since 1.4.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories(): array {
		return [ 'general' ]; // è possibile creare una categoria personalizzata.
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.4.0
	 * @access public
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return [ 'sync', 'template', 'reusable', 'dynamic', 'est' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @since 1.4.0
   * @since 1.4.2 Aggiunto repeater per i campi dinamici.
	 * @access protected
	 */
	protected function _register_controls(): void {
		$this->start_controls_section(
			'section_template',
			[
				'label' => __( 'Template', 'elementor-sync-template' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'template_id',
			[
				'label'   => __( 'Choose Template', 'elementor-sync-template' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $this->get_template_options(),
				'default' => '',
				'description' => __( 'Select the Sync Template you want to display.', 'elementor-sync-template' ),
			]
		);

		$this->end_controls_section();

		// Sezione per inserire i valori dei campi dinamici.
		$this->start_controls_section(
			'section_dynamic_overrides',
			[
				'label'     => __( 'Dynamic Overrides', 'elementor-sync-template' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => [
					'template_id!' => '', // Mostra questa sezione solo se è stato scelto un template.
				],
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'override_key',
			[
				'label'       => __( 'Field Key', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => __( 'Enter the exact key of the field to override (e.g., "hero_title").', 'elementor-sync-template' ),
			]
		);

		$repeater->add_control(
			'override_value',
			[
				'label' => __( 'Field Value', 'elementor-sync-template' ),
				'type'  => \Elementor\Controls_Manager::WYSIWYG, // WYSIWYG è molto versatile.
			]
		);

		$this->add_control(
			'dynamic_overrides',
			[
				'label'         => __( 'Field Overrides', 'elementor-sync-template' ),
				'type'          => \Elementor\Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'title_field'   => '{{{ override_key }}}',
				'prevent_empty' => false,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 *
	 * @since 1.4.0
	 * @access protected
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$template_id = $settings['template_id'];

		if ( empty( $template_id ) ) {
			echo '<div class="elementor-alert elementor-alert-warning">' . esc_html__( 'Please select a template.', 'elementor-sync-template' ) . '</div>';
			return;
		}

		// Qui andrà la logica per renderizzare il template con i valori dinamici.
		echo '<div>Rendering Template ID: ' . esc_html( $template_id ) . '</div>';
	}

	/**
	 * Helper function to get available templates.
	 *
	 * @since 1.4.0
	 * @access private
	 * @return array
	 */
	private function get_template_options(): array {
		$query = new \WP_Query( [
			'post_type'      => \Elementor_Sync_Template\Cpt\EST_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );

		return wp_list_pluck( $query->posts, 'post_title', 'ID' );
	}
}