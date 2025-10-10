<?php

namespace Elementor_Sync_Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin class.
 *
 * The main class that initiates and runs the addon.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Addon Version
	 *
	 * @since 1.0.0
	 * @var string The addon version.
	 */
	const VERSION = '1.3.0';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.0.0
	 * @var string Minimum Elementor version required to run the addon.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.20.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.0.0
	 * @var string Minimum PHP version required to run the addon.
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * Istanza unica della classe.
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 * @var \Elementor_Sync_Template\Plugin The single instance of the class.
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
	 * @return \Elementor_Sync_Template\Plugin An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * Constructor
	 *
	 * Perform some compatibility checks to make sure basic requirements are meet.
	 * If all compatibility checks pass, initialize the functionality.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		// Carica i file necessari per i controlli iniziali.
		require_once EST_PLUGIN_PATH . 'includes/class-compatibility-manager.php';

		$compatibility_manager = new Compatibility_Manager();

    // Se l'ambiente è compatibile, inizializza il plugin.
		if ( $compatibility_manager->is_compatible() ) {
			add_action( 'elementor/init', [ $this, 'init' ] );
		}

	}

	/**
	 * Initialize
	 *
	 * Load the addons functionality only after Elementor is initialized.
	 * Fired by `elementor/init` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init(): void {

    // Carica i file necessari
		$this->includes();

    // Inizializza il Custom Post Type
		$this->init_classes();

	}

  /**
   * Include Files
   * Carica i file necessari per il funzionamento del plugin.
   * 
   * @since 1.0.0 Aggiunto il Custom Post Type.
   * @since 1.1.0 Aggiunto modulo campi dinamici.
   * @since 1.2.0 Aggiunto endpoint REST per le chiavi dei template.
   * @access private
   */
  private function includes() {

    // include il custom post type
    require_once EST_PLUGIN_PATH . 'includes/cpt/class-est-cpt.php';

    // include la classe per i campi dinamici
    require_once EST_PLUGIN_PATH . 'includes/modules/class-dynamic-fields.php';

    // include la classe per l'endpoint REST
    require_once EST_PLUGIN_PATH . 'includes/rest-api/class-template-keys-controller.php';

  }

  /**
	 * Inizializza le classi.
	 *
	 * Crea le istanze delle classi.
	 *
	 * @since 1.0.0 Aggiunto il Custom Post Type.
   * @since 1.1.0 Aggiunto il modulo campi dinamici.
	 * @access private
	 */
	private function init_classes(): void {

    // Inizializza il Custom Post Type
		\Elementor_Sync_Template\Cpt\EST_CPT::instance();

    // Inizializza il modulo per i campi dinamici
    new \Elementor_Sync_Template\Modules\Dynamic_Fields();

	}

}

/**
 * Funzione eseguita all'attivazione del plugin.
 * Registra il Custom Post Type e fa il flush delle rewrite rules.
 * 
 * @since 1.1.0
 * @access public
 */
function est_plugin_activation() {

	// Si assicura che il CPT sia registrato prima di fare il flush.
	require_once EST_PLUGIN_PATH . 'includes/cpt/class-est-cpt.php';
	\Elementor_Sync_Template\Cpt\EST_CPT::instance()->register_post_type();
	\flush_rewrite_rules();

}

/**
 * Funzione eseguita alla disattivazione del plugin.
 * Fa il flush delle rewrite rules.
 * 
 * @since 1.1.0
 * @access public
 */
function est_plugin_deactivation() {

	\flush_rewrite_rules();

}

// Activation/deactivation hooks are registered from the main plugin file.