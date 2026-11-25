<?php
/**
 * Reverte o formulário de contato (post 141) ao estado anterior ao fix:
 *  - rótulos/avisos/botão de volta para inglês
 *  - form_actions apenas ["database"] (sem envio de e-mail)
 *
 * Use somente se precisar desfazer a correção em produção.
 */
$ID      = 141;
$content = get_post_field( 'post_content', $ID );
$pairs   = [
	'label="Nome:"'            => 'label="First Name:"',
	'label="Sobrenome:"'       => 'label="Surname:"',
	'label="Seu e-mail:"'      => 'label="Your Email:"',
	'label="Telefone:"'        => 'label="Telephone:"',
	'label="Sua mensagem:"'    => 'label="Your Message:"',
	'success="Obrigado! Sua mensagem foi enviada."' => 'success="Thank you for your message. It has been sent."',
	'error="Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde."' => 'error="There was an error trying to send your message. Please try again later."',
	'Enviar Solicitação'       => 'Submit Your Quote Request',
];
foreach ( $pairs as $from => $to ) {
	$n       = substr_count( $content, $from );
	$content = str_replace( $from, $to, $content );
	echo "revert [{$from}] => {$n}" . PHP_EOL;
}
$res = wp_update_post( wp_slash( [ 'ID' => $ID, 'post_content' => $content ] ), true );
echo 'update-post: ' . ( is_wp_error( $res ) ? $res->get_error_message() : 'ok' ) . PHP_EOL;

$meta = get_post_meta( $ID, '_fusion', true );
if ( ! is_array( $meta ) ) {
	$meta = [];
}
$meta['form_actions'] = [ 'database' ];
foreach ( [ 'email', 'email_from', 'email_subject', 'email_reply_to' ] as $key ) {
	unset( $meta[ $key ] );
}
update_post_meta( $ID, '_fusion', $meta );
echo 'form-actions: ' . implode( ',', $meta['form_actions'] ) . PHP_EOL;
echo 'ROLLBACK-FORM-DONE' . PHP_EOL;
