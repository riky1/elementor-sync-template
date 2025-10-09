<?php

namespace Elementor_Sync_Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Compatibility_Manager.
 *
 * Gestisce i controlli di compatibilità del plugin con l'ambiente WordPress,
 * inclusi PHP, Elementor e altre dipendenze.
 *
 * @since 1.0.0
 */
class Compatibility_Manager {

	/**
	 * Controlla se l'ambiente soddisfa i requisiti del plugin.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool True se compatibile, altrimenti false.
	 */
	public function is_compatible(): bool {
		// Controlla se Elementor è installato e attivo.
		if ( ! \did_action( 'elementor/loaded' ) ) {
			\add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return false;
		}

		// Controlla la versione minima di Elementor.
		if ( ! \version_compare( \defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '0.0.0', Plugin::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			\add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
			return false;
		}

		// Controlla la versione minima di PHP.
		if ( \version_compare( PHP_VERSION, Plugin::MINIMUM_PHP_VERSION, '<' ) ) {
			\add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
			return false;
		}

		return true;
	}

	/**
	 * Avviso amministrativo per la mancanza di Elementor.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_missing_main_plugin(): void {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			\esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'elementor-sync-template' ),
			'<strong>' . \esc_html__( 'Elementor Sync Template', 'elementor-sync-template' ) . '</strong>',
			'<strong>' . \esc_html__( 'Elementor', 'elementor-sync-template' ) . '</strong>'
		);

		echo sprintf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', \wp_kses( $message, [ 'strong' => [] ] ) );
	}

	/**
	 * Avviso amministrativo per la versione minima di Elementor non soddisfatta.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_elementor_version(): void {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			\esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-sync-template' ),
			'<strong>' . \esc_html__( 'Elementor Sync Template', 'elementor-sync-template' ) . '</strong>',
			'<strong>' . \esc_html__( 'Elementor', 'elementor-sync-template' ) . '</strong>',
			Plugin::MINIMUM_ELEMENTOR_VERSION
		);

		echo sprintf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', \wp_kses( $message, [ 'strong' => [] ] ) );
	}

	/**
	 * Avviso amministrativo per la versione minima di PHP non soddisfatta.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_php_version(): void {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			\esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-sync-template' ),
			'<strong>' . \esc_html__( 'Elementor Sync Template', 'elementor-sync-template' ) . '</strong>',
			'<strong>' . \esc_html__( 'PHP', 'elementor-sync-template' ) . '</strong>',
			Plugin::MINIMUM_PHP_VERSION
		);

		echo sprintf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', \wp_kses( $message, [ 'strong' => [] ] ) );
	}
}