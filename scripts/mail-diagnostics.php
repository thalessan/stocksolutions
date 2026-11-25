<?php
/**
 * Diagnóstico do envio de e-mail no PRD (temporário).
 *
 * Mostra a configuração de mail() do PHP e dispara um teste para o Gmail.
 */
$failed = null;

add_action(
	'wp_mail_failed',
	function ( $error ) use ( &$failed ) {
		$failed = $error->get_error_message();
	}
);

echo 'SENDMAIL_PATH: ' . ini_get( 'sendmail_path' ) . PHP_EOL;
echo 'MAIL_FUNC: ' . ( function_exists( 'mail' ) ? 'yes' : 'no' ) . PHP_EOL;
echo 'DISABLED_FUNCTIONS: ' . ini_get( 'disable_functions' ) . PHP_EOL;
echo 'HOSTNAME: ' . gethostname() . PHP_EOL;
echo 'SERVER_ADDR: ' . ( isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : '' ) . PHP_EOL;
echo 'ADMIN_EMAIL: ' . get_option( 'admin_email' ) . PHP_EOL;

$ip = @file_get_contents( 'https://ifconfig.me/ip' );
echo 'PUBLIC_IP: ' . trim( (string) $ip ) . PHP_EOL;

$sent = wp_mail(
	'thalesgomessan@gmail.com',
	'[StockSolutions] Teste de entrega (Gmail)',
	"Teste de entrega disparado pelo site StockSolutions.\n"
	. 'Data: ' . date( 'c' ) . "\n"
	. 'Host: ' . gethostname() . "\n"
);

echo 'SENT: ' . var_export( $sent, true ) . PHP_EOL;
echo 'ERROR: ' . var_export( $failed, true ) . PHP_EOL;
echo 'DIAG-END' . PHP_EOL;
