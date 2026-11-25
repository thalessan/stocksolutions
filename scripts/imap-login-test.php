<?php
/**
 * Testa login IMAP (temporário, sem credenciais no repo).
 *
 * Uso: wp eval-file imap-login-test.php <host> <porta> <usuario> <senha>
 */
$host = isset( $args[0] ) ? $args[0] : 'imap.uhserver.com';
$port = isset( $args[1] ) ? (int) $args[1] : 993;
$user = isset( $args[2] ) ? $args[2] : '';
$pass = isset( $args[3] ) ? $args[3] : '';

if ( ! $user ) {
	echo 'falta usuario' . PHP_EOL;
	echo 'IMAP-END' . PHP_EOL;
	return;
}

$ctx = stream_context_create(
	[ 'ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ] ]
);

$fp = @stream_socket_client( 'ssl://' . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx );

if ( ! $fp ) {
	echo "TCP falhou: {$errstr} ({$errno})" . PHP_EOL;
	echo 'IMAP-END' . PHP_EOL;
	return;
}

stream_set_timeout( $fp, 20 );

$read = static function ( $fp ) {
	$out = '';
	while ( false !== ( $line = fgets( $fp, 2048 ) ) ) {
		$out .= $line;
		if ( false !== strpos( $line, 'OK' ) || false !== strpos( $line, 'NO' ) || false !== strpos( $line, 'BAD' ) ) {
			break;
		}
	}
	return trim( $out );
};

echo '< ' . $read( $fp ) . PHP_EOL;

fwrite( $fp, 'a1 LOGIN "' . $user . '" "' . $pass . "\"\r\n" );
$login = $read( $fp );

echo '< ' . $login . PHP_EOL;
echo 'RESULTADO_IMAP: ' . ( 0 === strpos( $login, 'a1 OK' ) ? 'OK' : 'FALHOU' ) . PHP_EOL;

fwrite( $fp, "a2 LOGOUT\r\n" );
$read( $fp );
fclose( $fp );

echo 'IMAP-END' . PHP_EOL;
