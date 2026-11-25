<?php
/**
 * Plugin Name: StockSolutions - Envio de e-mail
 * Description: Corrige o transporte de e-mail do site (remetente, envelope sender e Message-ID) e usa SMTP autenticado quando as constantes STCK_SMTP_* estiverem definidas no wp-config.php.
 * Version: 1.0.0
 *
 * @package StockSolutions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Domínio usado no remetente e no Message-ID.
 *
 * O host da UOL se identifica como "b:u:<conta>:1", o que gera
 * "Message-ID: <@localhost.localdomain>" e faz o Gmail tratar a mensagem como
 * suspeita. Usar o domínio do site mantém o cabeçalho válido.
 */
function stck_mail_domain() {
	$host = wp_parse_url( network_home_url(), PHP_URL_HOST );

	return preg_replace( '#^www\.#', '', (string) $host );
}

/**
 * Endereço de envio padrão.
 */
function stck_mail_from() {
	/**
	 * Permite trocar o remetente sem editar o plugin.
	 *
	 * @param string $email Endereço de e-mail.
	 */
	return apply_filters( 'stck_mail_from', 'contato@' . stck_mail_domain() );
}

add_filter(
	'wp_mail_from',
	function ( $email ) {
		$custom = stck_mail_from();

		return is_email( $custom ) ? $custom : $email;
	},
	99
);

add_filter(
	'wp_mail_from_name',
	function ( $name ) {
		return 'StockSolutions';
	},
	99
);

/**
 * Configura o PHPMailer.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Instância do PHPMailer.
 */
add_action(
	'phpmailer_init',
	function ( $phpmailer ) {
		$from = stck_mail_from();

		// Message-ID válido mesmo com hostname inválido na hospedagem.
		if ( empty( $phpmailer->Hostname ) || false !== strpos( (string) $phpmailer->Hostname, ':' ) ) {
			$phpmailer->Hostname = stck_mail_domain();
		}

		// A hospedagem gera IDs sem parte local ("<@dominio>"): força um válido.
		if ( empty( $phpmailer->MessageID ) || 0 === strpos( (string) $phpmailer->MessageID, '<@' ) ) {
			$phpmailer->MessageID = sprintf(
				'<%s@%s>',
				bin2hex( random_bytes( 16 ) ),
				stck_mail_domain()
			);
		}

		if ( is_email( $from ) ) {
			$phpmailer->setFrom( $from, 'StockSolutions', false );

			// Envelope sender: evita "Return-Path" inválido (usuário@host-quebrado).
			$phpmailer->Sender = $from;
		}

		// SMTP autenticado, quando configurado no wp-config.php.
		if ( ! defined( 'STCK_SMTP_HOST' ) || ! STCK_SMTP_HOST ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host = STCK_SMTP_HOST;
		$phpmailer->Port = defined( 'STCK_SMTP_PORT' ) ? (int) STCK_SMTP_PORT : 587;
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = defined( 'STCK_SMTP_USER' ) ? STCK_SMTP_USER : '';
		$phpmailer->Password = defined( 'STCK_SMTP_PASS' ) ? STCK_SMTP_PASS : '';
		$phpmailer->SMTPSecure = defined( 'STCK_SMTP_SECURE' ) ? STCK_SMTP_SECURE : '';
		$phpmailer->SMTPAutoTLS = false;
		$phpmailer->Timeout = 20;
	},
	99
);
