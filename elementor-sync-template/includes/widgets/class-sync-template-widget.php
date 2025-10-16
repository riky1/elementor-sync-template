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
	 * @since 1.5.0 change name
	 * @access public
	 * @return array Widget script dependencies.
	 */
	public function get_script_depends(): array {
		return [ 'est-editor' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @since 1.4.0
   * @since 1.4.2 Aggiunto repeater per i campi dinamici.
   * @since 1.5.0 Logica di popolamento delegata a JS.
	 * @since 1.5.5 Modifiche ai controlli
	 * @since 1.6.0 Aggiunto controlli condizionali
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
				'label'   => __( 'Field Key', 'elementor-sync-template' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'classes' => 'est-control-disabled',
				'default' => '',
			]
		);

		// Campo di tipo (nascosto ma utile per condizionare)
		$repeater->add_control(
			'override_type',
			[
				'label'   => __( 'Type', 'elementor-sync-template' ),
				'type'    => \Elementor\Controls_Manager::HIDDEN,
				'default' => 'text',
			]
		);

		// TEXT
		$repeater->add_control(
			'override_value_text',
			[
				'label'       => __( 'Value', 'elementor-sync-template' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'condition'   => [ 'override_type' => 'text' ],
			]
		);

		// TEXTAREA
		$repeater->add_control(
			'override_value_textarea',
			[
				'label'      => __( 'Value', 'elementor-sync-template' ),
				'type'       => \Elementor\Controls_Manager::TEXTAREA,
				'condition'  => [ 'override_type' => 'textarea' ],
			]
		);

		// IMAGE
		$repeater->add_control(
			'override_value_image',
			[
				'label'      => __( 'Image', 'elementor-sync-template' ),
				'type'       => \Elementor\Controls_Manager::MEDIA,
				'condition'  => [ 'override_type' => 'image' ],
			]
		);

		// URL
		$repeater->add_control(
			'override_value_url',
			[
				'label'      => __( 'Link', 'elementor-sync-template' ),
				'type'       => \Elementor\Controls_Manager::URL,
				'condition'  => [ 'override_type' => 'url' ],
			]
		);

		$this->add_control(
			'dynamic_overrides',
			[
				'label'         => __( 'Fields', 'elementor-sync-template' ),
				'type'          => \Elementor\Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'title_field'   => '{{{ _override_label || override_key || "Field" }}}',
				'classes'     => 'est-dynamic-overrides-fields',
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
	 * @since 1.6.0 Aggiunto controlli condizinali
	 * @access protected
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
    $template_id = $settings['template_id'] ?? '';
    $overrides = $settings['dynamic_overrides'] ?? [];

    if ( empty( $template_id ) ) {

			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-warning">' . esc_html__( 'Please select a template.', 'elementor-sync-template' ) . '</div>';
			}

			return;
    }

    // 1. Prepara la mappa delle sostituzioni (multi-type)
    $this->overrides_map = [];

    foreach ( $overrides as $item ) {
			$key   = $item['override_key'] ?? '';
			$type  = $item['override_type'] ?? 'text';
			$value = '';

			if ( ! $key ) continue;

			switch ( $type ) {
				case 'textarea':
					$value = $item['override_value_textarea'] ?? '';
					break;

				case 'image':
					// Nel caso dell'immagine Elementor salva un array (id, url)
					$image_data = $item['override_value_image'] ?? null;
					if ( is_array( $image_data ) && ! empty( $image_data['url'] ) ) {
						$value = $image_data['url'];
					}
					break;

				case 'url':
					// Anche il campo URL di Elementor è un array
					$url_data = $item['override_value_url'] ?? null;
					if ( is_array( $url_data ) && ! empty( $url_data['url'] ) ) {
						$value = $url_data['url'];
					}
					break;

				default:
					$value = $item['override_value_text'] ?? '';
					break;
			}

			if ( $value !== '' ) {
				$this->overrides_map[ $key ] = $value;
			}
    }

    // 2. Aggiunge il filtro se ci sono override
    if ( ! empty( $this->overrides_map ) ) {
			add_filter( 'elementor/frontend/widget/before_render', [ $this, 'apply_dynamic_overrides' ], 10, 1 );
    }

    // 3. Renderizza il template selezionato
    echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id );

    // 4. Rimuove il filtro per non interferire con altri widget
    if ( ! empty( $this->overrides_map ) ) {
			remove_filter( 'elementor/frontend/widget/before_render', [ $this, 'apply_dynamic_overrides' ], 10 );
    }

    // 5. Pulisce la mappa
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
	 * @since 1.6.0 Aggiunto controlli condizionali
	 * @access public
	 * @param \Elementor\Widget_Base $widget_instance L'istanza del widget che sta per essere renderizzato.
	 * @return \Elementor\Widget_Base L'istanza del widget, potenzialmente modificata.
	 */
	public function apply_dynamic_overrides( \Elementor\Widget_Base $widget_instance ): \Elementor\Widget_Base {
		$widget_settings = $widget_instance->get_settings();

    // Se il widget non ha campi dinamici definiti, esce.
    if ( empty( $widget_settings['_est_dynamic_fields_repeater'] ) ) {
			return $widget_instance;
    }

    $fields_to_override = $widget_settings['_est_dynamic_fields_repeater'];

    foreach ( $fields_to_override as $field ) {
			$key  = $field['key'] ?? '';
			$type = $field['type'] ?? 'text';

			if ( empty( $key ) || ! isset( $this->overrides_map[ $key ] ) ) {
				continue;
			}

			$value        = $this->overrides_map[ $key ];
			$widget_name  = $widget_instance->get_name();
			$setting_name = '';

			/**
			 * Mappatura base dei widget Elementor più comuni.
			 * Può essere ampliata se necessario.
			 */
			switch ( $type ) {
				case 'text':
				case 'textarea':
					if ( 'heading' === $widget_name ) {
						$setting_name = 'title';
					} elseif ( 'text-editor' === $widget_name ) {
						$setting_name = 'editor';
					} elseif ( 'button' === $widget_name ) {
						$setting_name = 'text';
					}
					break;

				case 'image':
					if ( 'image' === $widget_name ) {
						$setting_name = 'image';
						// Elementor si aspetta un array con 'url' (e opzionalmente 'id')
						$value = [ 'url' => esc_url( $value ) ];
					}
					break;

				case 'url':
					// Imposta il link (molti widget lo usano)
					$setting_name = 'link';
					// Elementor si aspetta un array con chiave 'url'
					$value = [ 'url' => esc_url( $value ) ];
					break;
			}

			// Applica l’override solo se abbiamo trovato un’impostazione valida
			if ( ! empty( $setting_name ) ) {
				$widget_instance->set_settings( $setting_name, $value );
			}
    }

    return $widget_instance;
	}
}