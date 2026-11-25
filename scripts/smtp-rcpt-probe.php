<?php
/**
 * Sonda RCPT no SMTP autenticado: aceita só endereços existentes?
 *
 * Uso: wp eval-file smtp-rcpt-probe.php <usuario> <senha> <destino1> [destino2...]
 */
$user = isset( $args[0] ) ? $args[0] : '';
$pass = isset( $args[1] ) ? $args[1] : '';
$to   = array_slice( $args, 2 );

if ( ! $user || ! $to ) {
	echo 'uso: <usuario> <senha> <destino...>' . PHP_EOL;
	echo 'RCPT-END' . PHP_EOL;
	return;
}

$ctx = stream_context_create(
	[ 'ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ] ]
);

$fp = @stream_socket_client( 'ssl://smtps.uhserver.com:465', $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx );

if ( ! $fp ) {
	echo "TCP falhou: {$errstr} ({$errno})" . PHP_EOL;
	echo 'RCPT-END' . PHP_EOL;
	return;
}

stream_set_timeout( $fp, 20 );

$read = static function ( $fp ) {
	$out = '';
	while ( false !== ( $line = fgets( $fp, 2048 ) ) ) {
		$out .= $line;
		if ( isset( $line[3] ) && ' ' === $line[3] ) {
			break;
		}
	}
	return trim( $out );
};

$send = static function ( $fp, $cmd ) use ( $read ) {
	fwrite( $fp, $cmd . "\r\n" );
	return $read( $fp );
};

$read( $fp );
$send( $fp, 'EHLO stocksolutions.com.br' );
$send( $fp, 'AUTH LOGIN' );
$send( $fp, base64_encode( $user ) );
$auth = $send( $fp, base64_encode( $pass ) );

if ( 0 !== strpos( $auth, '235' ) ) {
	echo 'AUTH FALHOU: ' . $auth . PHP_EOL;
	echo 'RCPT-END' . PHP_EOL;
	return;
}

$send( $fp, 'MAIL FROM:<' . $user . '>' );

foreach ( $to as $addr ) {
	$resp = $send( $fp, 'RCPT TO:<' . $addr . '>' );
	echo sprintf( '%-45s %s', $addr, $resp ) . PHP_EOL;
}

$send( $fp, 'RSET' );
$send( $fp, 'QUIT' );
fclose( $fp );

echo 'RCPT-END' . PHP_EOL;
