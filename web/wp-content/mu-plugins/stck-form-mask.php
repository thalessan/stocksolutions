<?php
/**
 * Plugin Name: StockSolutions - Máscara de telefone (Avada Forms)
 * Description: Formata os campos de telefone dos formulários Avada no padrão brasileiro: (XX) XXXX-XXXX para fixo e (XX) XXXXX-XXXX para celular.
 * Version: 1.0.0
 *
 * @package StockSolutions
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$file = __DIR__ . '/stck-form-mask.js';

		if ( ! file_exists( $file ) ) {
			return;
		}

		wp_enqueue_script(
			'stck-form-mask',
			content_url( 'mu-plugins/stck-form-mask.js' ),
			[],
			(string) filemtime( $file ),
			true
		);
	}
);
