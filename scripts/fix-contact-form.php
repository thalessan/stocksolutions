<?php
/**
 * Corrige o formulario de contato (post 141):
 *  - traduz labels/notices/botao para pt-BR
 *  - habilita acao de email para contato@stocksolutions.com.br
 * Idempotente: pode rodar mais de uma vez sem efeitos colaterais.
 */
$ID      = 141;
$content = get_post_field( 'post_content', $ID );
$pairs   = [
	'label="First Name:"'                                                  => 'label="Nome:"',
	'label="Surname:"'                                                     => 'label="Sobrenome:"',
	'label="Your Email:"'                                                  => 'label="Seu e-mail:"',
	'label="Your Message:"'                                                => 'label="Sua mensagem:"',
	'label="Telephone:"'                                                   => 'label="Telefone:"',
	'name="telephone" required="no" /]'                                    => 'name="telephone" required="no" placeholder="(11) 91234-5678" /]',
	'success="Thank you for your message. It has been sent."'              => 'success="Obrigado! Sua mensagem foi enviada."',
	'error="There was an error trying to send your message. Please try again later."' => 'error="Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde."',
	'Submit Your Quote Request'                                            => 'Enviar Solicitação',
];
foreach ( $pairs as $from => $to ) {
	$n       = substr_count( $content, $from );
	$content = str_replace( $from, $to, $content );
	echo "replace [{$from}] => {$n}" . PHP_EOL;
}
$res = wp_update_post( wp_slash( [ 'ID' => $ID, 'post_content' => $content ] ), true );
echo 'update-post: ' . ( is_wp_error( $res ) ? $res->get_error_message() : 'ok' ) . PHP_EOL;

$meta = get_post_meta( $ID, '_fusion', true );
if ( ! is_array( $meta ) ) {
	$meta = [];
}
$meta['form_actions']   = [ 'database', 'email' ];
$meta['email']          = 'contato@stocksolutions.com.br';
$meta['email_from']     = 'StockSolutions';
$meta['email_subject']  = 'Contato pelo site - StockSolutions';
$meta['email_reply_to'] = '[your_email]';
update_post_meta( $ID, '_fusion', $meta );

echo 'form-actions: ' . implode( ',', $meta['form_actions'] ) . PHP_EOL;
echo 'email-to: ' . $meta['email'] . PHP_EOL;
echo 'FIX-FORM-DONE' . PHP_EOL;
