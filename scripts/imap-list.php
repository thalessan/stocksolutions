<?php
/**
 * Lista pastas IMAP e as últimas mensagens (temporário).
 * Uso: wp eval-file imap-list.php <usuario> <senha> [quantidade]
 */
$user  = isset( $args[0] ) ? $args[0] : '';
$pass  = isset( $args[1] ) ? $args[1] : '';
$limit = isset( $args[2] ) ? absint( $args[2] ) : 10;

if ( ! $user ) {
	echo 'falta usuario' . PHP_EOL;
	echo 'LIST-END' . PHP_EOL;
	return;
}

$mbox = @imap_open( '{imap.uhserver.com:993/imap/ssl}INBOX', $user, $pass, 0, 1 );

if ( ! $mbox ) {
	echo 'LOGIN FALHOU: ' . imap_last_error() . PHP_EOL;
	echo 'LIST-END' . PHP_EOL;
	return;
}

echo '--- PASTAS ---' . PHP_EOL;

$boxes = imap_getmailboxes( $mbox, '{imap.uhserver.com:993/imap/ssl}', '*' );

foreach ( $boxes as $box ) {
	$name = str_replace( '{imap.uhserver.com:993/imap/ssl}', '', $box->name );
	$info = @imap_status( $mbox, $box->name, SA_MESSAGES | SA_UNSEEN );

	echo sprintf(
		'%-40s mensagens=%s nao_lidas=%s',
		$name,
		$info ? $info->messages : '?',
		$info ? $info->unseen : '?'
	) . PHP_EOL;
}

echo PHP_EOL . '--- ULTIMAS MENSAGENS (INBOX) ---' . PHP_EOL;

$total = imap_num_msg( $mbox );
echo 'TOTAL_INBOX: ' . $total . PHP_EOL;

if ( $total > 0 ) {
	for ( $i = $total; $i > max( 0, $total - $limit ); $i-- ) {
		$h = imap_headerinfo( $mbox, $i );

		if ( ! $h ) {
			continue;
		}

		$from = isset( $h->from[0] ) ? $h->from[0]->mailbox . '@' . ( isset( $h->from[0]->host ) ? $h->from[0]->host : '?' ) : '?';
		$subj = isset( $h->subject ) ? imap_utf8( $h->subject ) : '(sem assunto)';
		$date = isset( $h->date ) ? $h->date : '';

		echo sprintf( '#%d  %-42s  %s  | %s', $i, substr( $from, 0, 42 ), $subj, $date ) . PHP_EOL;
	}
}

imap_close( $mbox );

echo 'LIST-END' . PHP_EOL;
