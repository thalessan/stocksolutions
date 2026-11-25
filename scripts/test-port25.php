<?php
/**
 * Testa saída na porta 25 (entrega direta) a partir da hospedagem.
 */
$targets = [
	'gmail-smtp-in.l.google.com:25',
	'mx.uhserver.com:25',
	'127.0.0.1:25',
];

foreach ( $targets as $target ) {
	list( $host, $port ) = explode( ':', $target );

	$ips = @gethostbynamel( $host );

	if ( ! $ips ) {
		echo sprintf( '%-34s DNS nao resolveu', $target ) . PHP_EOL;
		continue;
	}

	$ip    = $ips[0];
	$start = microtime( true );
	$fp    = @fsockopen( $ip, (int) $port, $errno, $errstr, 8 );
	$ms    = round( ( microtime( true ) - $start ) * 1000 );

	if ( $fp ) {
		stream_set_timeout( $fp, 5 );
		$banner = fgets( $fp, 200 );
		fclose( $fp );
		echo sprintf( '%-34s OK  (%s) %5dms  %s', $target, $ip, $ms, trim( (string) $banner ) ) . PHP_EOL;
	} else {
		echo sprintf( '%-34s FALHOU (%s) %5dms  %s (%d)', $target, $ip, $ms, $errstr, $errno ) . PHP_EOL;
	}
}

echo 'PORT25-END' . PHP_EOL;
