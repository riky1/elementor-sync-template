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
	 * Constructor.
	 *
	 * @since 1.4.4
	 * @access public
	 */
	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);

		wp_register_script( 'est-script-handle', EST_PLUGIN_URL . 'assets/js/est-editor.js', [ 'elementor-frontend' ], '1.0.0', true );
   }

	/**
	 * Mappa temporanea per le sostituzioni dei campi.
	 *
	 * @since 1.4.3
	 * @var array
	 */
	private $overrides_map = [];

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
	 * Get widget script dependencies.
	 *
	 * @since 1.4.4
	 * @access public
	 * @return array Widget script dependencies.
	 */
	public function get_script_depends() {
    return [ 'est-script-handle' ];
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
	 * @since 1.4.3 Aggiunta logica di rendering con override dinamici.
	 * @access protected
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$template_id = $settings['template_id'];
		$overrides = $settings['dynamic_overrides'] ?? [];

		if ( empty( $template_id ) ) {

			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-warning">' . esc_html__( 'Please select a template.', 'elementor-sync-template' ) . '</div>';
			}

			return;
      
		}

		// 1. Prepara la mappa delle sostituzioni per una ricerca veloce.
		$this->overrides_map = wp_list_pluck( $overrides, 'override_value', 'override_key' );

		// 2. Aggiunge un filtro che si attiverà per ogni widget renderizzato all'interno del template.
		if ( ! empty( $this->overrides_map ) ) {
			add_filter( 'elementor/frontend/widget/before_render', [ $this, 'apply_dynamic_overrides' ], 10, 1 );
		}

		// Renderizza il contenuto del template di Elementor.
		// La funzione get_builder_content_for_display si occupa di renderizzare l'HTML.
		// Durante la sua esecuzione, il filtro 'before_render' verrà chiamato.
		echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id );

		// 3. Rimuove il filtro per non interferire con il resto della pagina.
		if ( ! empty( $this->overrides_map ) ) {
			remove_filter( 'elementor/frontend/widget/before_render', [ $this, 'apply_dynamic_overrides' ], 10 );
		}

		// 4. Pulisce la mappa.
		$this->overrides_map = [];
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

	/**
	 * Applica le sostituzioni dinamiche a un widget prima che venga renderizzato.
	 *
	 * Questa funzione viene chiamata dal filtro 'elementor/frontend/widget/before_render'.
	 *
	 * @since 1.4.3
	 * @access public
	 * @param \Elementor\Widget_Base $widget_instance L'istanza del widget che sta per essere renderizzato.
	 * @return \Elementor\Widget_Base L'istanza del widget, potenzialmente modificata.
	 */
	public function apply_dynamic_overrides( \Elementor\Widget_Base $widget_instance ): \Elementor\Widget_Base {
		$widget_settings = $widget_instance->get_settings();

		// Controlla se questo widget ha dei campi dinamici definiti.
		if ( empty( $widget_settings['_est_dynamic_fields_repeater'] ) ) {
			return $widget_instance;
		}

		$fields_to_override = $widget_settings['_est_dynamic_fields_repeater'];

		foreach ( $fields_to_override as $field ) {
			$key = $field['key'] ?? '';

			// Se la chiave di questo campo è presente...
			if ( ! empty( $key ) && isset( $this->overrides_map[ $key ] ) ) {

				$value = $this->overrides_map[ $key ];
				$type = $field['type'] ?? 'text';

				// Determina quale impostazione del widget deve essere modificata,
				// considerando sia il tipo di campo che il tipo di widget.
				$setting_to_change = '';
				$widget_name = $widget_instance->get_name();

				if ( 'text' === $type || 'textarea' === $type ) {

					if ( 'heading' === $widget_name ) {
						$setting_to_change = 'title';
					} elseif ( 'text-editor' === $widget_name ) {
						$setting_to_change = 'editor';
					}
					// Aggiungere qui altri 'elseif' per altri widget di testo (es. 'button' -> 'text')

				} elseif ( 'image' === $type ) {
					if ( 'image' === $widget_name ) {
						$setting_to_change = 'image';
					}

				} elseif ( 'url' === $type ) {
					// L'impostazione 'link' è comune a molti widget (titolo, pulsante, immagine).
					$setting_to_change = 'link';
				}

				if ( ! empty( $setting_to_change ) ) {
					$widget_instance->set_settings( $setting_to_change, $value );
				}
			}
		}
		return $widget_instance;
	}
}