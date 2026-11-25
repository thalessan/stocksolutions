<?php
/**
 * Envia e confirma qual transporte o PHPMailer usou (temporário).
 *
 * Uso: wp eval-file verify-mail.php destino@exemplo.com [assunto]
 */
$to      = isset( $args[0] ) ? $args[0] : '';
$subject = isset( $args[1] ) ? $args[1] : 'Teste de envio StockSolutions';

if ( ! is_email( $to ) ) {
	echo 'destino invalido' . PHP_EOL;
	echo 'VERIFY-END' . PHP_EOL;
	return;
}

$info = [];

add_action(
	'phpmailer_init',
	function ( $phpmailer ) use ( &$info ) {
		$info['Mailer']      = $phpmailer->Mailer;
		$info['Host']        = $phpmailer->Host;
		$info['Port']        = $phpmailer->Port;
		$info['SMTPSecure']  = $phpmailer->SMTPSecure;
		$info['SMTPAuth']    = $phpmailer->SMTPAuth ? 'sim' : 'nao';
		$info['Username']    = $phpmailer->Username;
		$info['From']        = $phpmailer->From;
		$info['Sender']      = $phpmailer->Sender;
	},
	999
);

$err = null;
add_action(
	'wp_mail_failed',
	function ( $e ) use ( &$err ) {
		$err = $e->get_error_message();
	}
);

$sent = wp_mail(
	$to,
	$subject,
	"Teste de envio do site StockSolutions.\nData: " . date( 'c' ) . "\nTransporte: SMTP autenticado.\n"
);

echo 'TO: ' . $to . PHP_EOL;
echo 'SENT: ' . var_export( $sent, true ) . PHP_EOL;
echo 'ERROR: ' . var_export( $err, true ) . PHP_EOL;

foreach ( [ 'Mailer', 'Host', 'Port', 'SMTPSecure', 'SMTPAuth', 'Username', 'From', 'Sender' ] as $key ) {
	echo $key . ': ' . ( isset( $info[ $key ] ) ? $info[ $key ] : '(nao capturado)' ) . PHP_EOL;
}

echo 'VERIFY-END' . PHP_EOL;
