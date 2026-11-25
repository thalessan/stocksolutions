<?php
/**
 * Lista as últimas mensagens de uma caixa via IMAP (temporário).
 * Usado para detectar bounces (MAILER-DAEMON) na caixa do remetente.
 *
 * Uso: wp eval-file imap-inbox.php <usuario> <senha> [quantidade]
 */
$user  = isset( $args[0] ) ? $args[0] : '';
$pass  = isset( $args[1] ) ? $args[1] : '';
$limit = isset( $args[2] ) ? absint( $args[2] ) : 8;

if ( ! $user ) {
	echo 'falta usuario' . PHP_EOL;
	echo 'INBOX-END' . PHP_EOL;
	return;
}

$ctx = stream_context_create(
	[ 'ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ] ]
);

$fp = @stream_socket_client( 'ssl://imap.uhserver.com:993', $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx );

if ( ! $fp ) {
	echo "TCP falhou: {$errstr} ({$errno})" . PHP_EOL;
	echo 'INBOX-END' . PHP_EOL;
	return;
}

stream_set_timeout( $fp, 25 );

$read = static function ( $fp, $tag = null ) {
	$out = '';
	while ( false !== ( $line = fgets( $fp, 8192 ) ) ) {
		$out .= $line;
		if ( null !== $tag && 0 === strpos( $line, $tag . ' ' ) ) {
			break;
		}
		if ( null === $tag && false !== strpos( $line, 'OK' ) && false !== strpos( $line, 'ready' ) ) {
			break;
		}
	}
	return $out;
};

$cmd = static function ( $fp, $tag, $command ) use ( $read ) {
	fwrite( $fp, $tag . ' ' . $command . "\r\n" );
	return $read( $fp, $tag );
};

$read( $fp );
$login = $cmd( $fp, 'b1', 'LOGIN "' . $user . '" "' . $pass . '"' );

if ( 0 !== strpos( $login, 'b1 OK' ) ) {
	echo 'LOGIN FALHOU: ' . trim( strtok( $login, "\n" ) ) . PHP_EOL;
	echo 'INBOX-END' . PHP_EOL;
	return;
}

$select = $cmd( $fp, 'b2', 'SELECT INBOX' );

if ( preg_match( '/\* (\d+) EXISTS/', $select, $m ) ) {
	$total = (int) $m[1];
} else {
	$total = 0;
}

echo 'TOTAL_MENSAGENS: ' . $total . PHP_EOL;

if ( $total > 0 ) {
	$from = max( 1, $total - $limit + 1 );
	$resp = $cmd( $fp, 'b3', 'FETCH ' . $from . ':* (FLAGS BODY.PEEK[HEADER.FIELDS (FROM SUBJECT DATE TO)])' );
	echo $resp . PHP_EOL;
}

$cmd( $fp, 'b4', 'LOGOUT' );
fclose( $fp );

echo 'INBOX-END' . PHP_EOL;
