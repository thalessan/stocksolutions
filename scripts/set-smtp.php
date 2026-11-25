<?php
/**
 * Injeta (ou atualiza) as constantes de SMTP no wp-config.php.
 *
 * Uso (CLI, fora do WordPress):
 *   php set-smtp.php <wp-config.php> <host> <port> <user> <pass> [secure]
 *
 * - idempotente: substitui o bloco entre os marcadores
 * - faz backup .bak-<data> antes de escrever
 * - valida a sintaxe (php -l) antes de substituir o arquivo real
 * - `secure` vazio = sem TLS; 'tls' = STARTTLS
 */

$path = isset( $argv[1] ) ? $argv[1] : '';

if ( ! $path || ! is_file( $path ) ) {
	fwrite( STDERR, "wp-config nao encontrado: {$path}\n" );
	exit( 2 );
}

$host   = isset( $argv[2] ) ? $argv[2] : '';
$port   = isset( $argv[3] ) ? (int) $argv[3] : 587;
$user   = isset( $argv[4] ) ? $argv[4] : '';
$pass   = isset( $argv[5] ) ? $argv[5] : '';
$secure = isset( $argv[6] ) ? trim( $argv[6] ) : '';

if ( ! $host || ! $user || ! $pass ) {
	fwrite( STDERR, "faltam host/user/pass\n" );
	exit( 2 );
}

$original = file_get_contents( $path );

if ( false === $original ) {
	fwrite( STDERR, "nao foi possivel ler {$path}\n" );
	exit( 2 );
}

$begin = '// BEGIN StockSolutions SMTP (gerado por scripts/set-smtp.php)';
$end   = '// END StockSolutions SMTP';

// Remove bloco anterior, se existir.
$pattern = '/' . preg_quote( $begin, '/' ) . '.*?' . preg_quote( $end, '/' ) . "\n?/s";
$stripped = preg_replace( $pattern, '', $original );

if ( null === $stripped ) {
	fwrite( STDERR, "falha ao limpar bloco anterior\n" );
	exit( 2 );
}

$quote = static function ( $value ) {
	return "'" . str_replace( [ '\\', "'" ], [ '\\\\', "\\'" ], $value ) . "'";
};

$block = $begin . "\n"
	. "define( 'STCK_SMTP_HOST', " . $quote( $host ) . " );\n"
	. "define( 'STCK_SMTP_PORT', " . (int) $port . " );\n"
	. "define( 'STCK_SMTP_USER', " . $quote( $user ) . " );\n"
	. "define( 'STCK_SMTP_PASS', " . $quote( $pass ) . " );\n"
	. "define( 'STCK_SMTP_SECURE', " . $quote( $secure ) . " );\n"
	. $end . "\n";

// Insere antes do encerramento do wp-config.
$anchor = "/* That's all, stop editing!";

if ( false !== strpos( $stripped, $anchor ) ) {
	$updated = str_replace( $anchor, $block . "\n" . $anchor, $stripped );
} else {
	$anchor  = "require_once ABSPATH . 'wp-settings.php';";
	$updated = str_replace( $anchor, $block . "\n" . $anchor, $stripped );
}

if ( $updated === $stripped ) {
	fwrite( STDERR, "ancora de insercao nao encontrada no wp-config\n" );
	exit( 2 );
}

// Valida a sintaxe em um arquivo temporario antes de tocar no original.
$tmp = $path . '.new-' . getmypid();
file_put_contents( $tmp, $updated );

$output = [];
$status = 0;
exec( 'php -l ' . escapeshellarg( $tmp ) . ' 2>&1', $output, $status );

if ( 0 !== $status ) {
	unlink( $tmp );
	fwrite( STDERR, "sintaxe invalida, nada foi alterado:\n" . implode( "\n", $output ) . "\n" );
	exit( 1 );
}

$backup = $path . '.bak-' . date( 'Ymd-His' );
copy( $path, $backup );

if ( ! rename( $tmp, $path ) ) {
	fwrite( STDERR, "falha ao substituir {$path}\n" );
	exit( 1 );
}

echo 'backup: ' . $backup . PHP_EOL;
echo 'host: ' . $host . ':' . $port . PHP_EOL;
echo 'user: ' . $user . PHP_EOL;
echo 'secure: ' . ( '' === $secure ? '(nenhum)' : $secure ) . PHP_EOL;
echo 'SMTP-CONFIG-END' . PHP_EOL;
