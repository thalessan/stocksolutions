<?php
/**
 * Testa conexão + AUTH em um servidor SMTP (temporário, sem credenciais no repo).
 *
 * Uso: wp eval-file smtp-auth-test.php <host> <porta> <usuario> <senha> [ssl|tls|]
 */
$host   = isset( $args[0] ) ? $args[0] : '';
$port   = isset( $args[1] ) ? (int) $args[1] : 465;
$user   = isset( $args[2] ) ? $args[2] : '';
$pass   = isset( $args[3] ) ? $args[3] : '';
$secure = isset( $args[4] ) ? trim( $args[4] ) : 'ssl';

if ( ! $host || ! $user ) {
	echo 'faltam host/usuario' . PHP_EOL;
	echo 'AUTH-END' . PHP_EOL;
	return;
}

$remote = 'ssl' === $secure ? 'ssl://' . $host : $host;
$ctx    = stream_context_create(
	[
		'ssl' => [
			'verify_peer'       => false,
			'verify_peer_name'  => false,
			'allow_self_signed' => true,
		],
	]
);

$start = microtime( true );
$fp    = @stream_socket_client( $remote . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx );
$ms    = round( ( microtime( true ) - $start ) * 1000 );

if ( ! $fp ) {
	echo "TCP falhou em {$host}:{$port} ({$errstr} / {$errno}) em {$ms}ms" . PHP_EOL;
	echo 'AUTH-END' . PHP_EOL;
	return;
}

echo "TCP OK {$host}:{$port} em {$ms}ms" . PHP_EOL;
echo 'ARG_USUARIO: ' . $user . PHP_EOL;
echo 'ARG_SENHA_MD5: ' . md5( $pass ) . ' (len ' . strlen( $pass ) . ')' . PHP_EOL;
stream_set_timeout( $fp, 15 );

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

$talk = static function ( $fp, $cmd, $hide = false ) use ( $read ) {
	fwrite( $fp, $cmd . "\r\n" );
	$resp = $read( $fp );
	echo '> ' . ( $hide ? '(oculto)' : str_replace( "\r\n", ' | ', $cmd ) ) . PHP_EOL;
	echo '< ' . str_replace( "\r\n", ' | ', $resp ) . PHP_EOL;
	return $resp;
};

echo '< ' . $read( $fp ) . PHP_EOL;
$ehlo = $talk( $fp, 'EHLO stocksolutions.com.br' );
$talk( $fp, 'AUTH LOGIN' );
$talk( $fp, base64_encode( $user ), true );
$auth = $talk( $fp, base64_encode( $pass ), true );

echo 'RESULTADO_AUTH: ' . ( 0 === strpos( $auth, '235' ) ? 'OK' : 'FALHOU' ) . PHP_EOL;
echo 'STARTTLS_OFERECIDO: ' . ( false !== stripos( $ehlo, 'STARTTLS' ) ? 'sim' : 'nao' ) . PHP_EOL;

$talk( $fp, 'QUIT' );
fclose( $fp );

echo 'AUTH-END' . PHP_EOL;
