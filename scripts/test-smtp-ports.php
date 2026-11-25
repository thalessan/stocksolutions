<?php
/**
 * Testa conectividade de saída para relays SMTP (temporário).
 */
$targets = [
	'smtp-relay.brevo.com:587',
	'smtp-relay.brevo.com:2525',
	'smtp-relay.brevo.com:465',
	'smtp.sendgrid.net:587',
	'smtp.sendgrid.net:2525',
	'api.brevo.com:443',
];

foreach ( $targets as $target ) {
	list( $host, $port ) = explode( ':', $target );
	$start = microtime( true );
	$fp    = @fsockopen( $host, (int) $port, $errno, $errstr, 8 );
	$ms    = round( ( microtime( true ) - $start ) * 1000 );

	if ( $fp ) {
		stream_set_timeout( $fp, 5 );
		$banner = fgets( $fp, 200 );
		fclose( $fp );
		echo sprintf( '%-32s OK %5dms  %s', $target, $ms, trim( (string) $banner ) ) . PHP_EOL;
	} else {
		echo sprintf( '%-32s FALHOU %5dms  %s (%s)', $target, $ms, $errstr, $errno ) . PHP_EOL;
	}
}

echo 'PORTS-END' . PHP_EOL;
