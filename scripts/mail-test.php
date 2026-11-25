<?php
/**
 * Teste tecnico de envio via wp_mail no PRD (temporario).
 */
$err = null;
add_action(
	'wp_mail_failed',
	function ( $e ) use ( &$err ) {
		$err = $e->get_error_message();
	}
);
$sent = wp_mail(
	'contato@stocksolutions.com.br',
	'[Teste] StockSolutions - verificacao de envio',
	"Teste tecnico de envio via wp_mail no site StockSolutions.\nSe esta mensagem chegou, o envio nativo esta funcionando.\n"
);
var_dump( $sent, $err );
echo 'MAIL-TEST-END' . PHP_EOL;
