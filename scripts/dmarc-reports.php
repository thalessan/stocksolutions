<?php
/**
 * Lê os relatórios DMARC agregados (Google) na caixa do remetente e resume.
 * Uso: wp eval-file dmarc-reports.php <usuario> <senha> [quantidade]
 */
$user  = isset( $args[0] ) ? $args[0] : '';
$pass  = isset( $args[1] ) ? $args[1] : '';
$limit = isset( $args[2] ) ? absint( $args[2] ) : 5;

if ( ! $user ) {
	echo 'falta usuario' . PHP_EOL;
	echo 'DMARC-END' . PHP_EOL;
	return;
}

$mbox = @imap_open( '{imap.uhserver.com:993/imap/ssl}INBOX', $user, $pass, 0, 1 );

if ( ! $mbox ) {
	echo 'LOGIN FALHOU: ' . imap_last_error() . PHP_EOL;
	echo 'DMARC-END' . PHP_EOL;
	return;
}

$ids = imap_search( $mbox, 'FROM "dmarc-support"' );

if ( ! $ids ) {
	$ids = imap_search( $mbox, 'SUBJECT "Report domain"' );
}

if ( ! $ids ) {
	echo 'Nenhum relatorio DMARC encontrado no INBOX.' . PHP_EOL;
	imap_close( $mbox );
	echo 'DMARC-END' . PHP_EOL;
	return;
}

rsort( $ids );
$ids = array_slice( $ids, 0, $limit );

echo 'RELATORIOS_ENCONTRADOS: ' . count( $ids ) . PHP_EOL;

/**
 * Procura partes que parecem ser o XML.gz.
 */
function dmarc_find_xml_parts( $struct, $prefix = '' ) {
	$parts = [];

	$sub = strtoupper( (string) $struct->subtype );
	$name = '';

	if ( isset( $struct->dparameters ) ) {
		foreach ( $struct->dparameters as $param ) {
			if ( 'FILENAME' === strtoupper( $param->attribute ) ) {
				$name = $param->value;
			}
		}
	}

	$is_xml = false !== strpos( $sub, 'XML' ) || (bool) preg_match( '/\.xml(\.gz)?$/i', $name );

	if ( $is_xml && '' !== $prefix ) {
		$parts[] = [ 'part' => $prefix, 'encoding' => (int) $struct->encoding ];
	}

	if ( isset( $struct->parts ) && is_array( $struct->parts ) ) {
		foreach ( $struct->parts as $i => $part ) {
			$num  = '' === $prefix ? (string) ( $i + 1 ) : $prefix . '.' . ( $i + 1 );
			$sub2 = strtoupper( (string) $part->subtype );
			$nom  = '';

			if ( isset( $part->dparameters ) ) {
				foreach ( $part->dparameters as $param ) {
					if ( 'FILENAME' === strtoupper( $param->attribute ) ) {
						$nom = $param->value;
					}
				}
			}

			if ( false !== strpos( $sub2, 'XML' ) || (bool) preg_match( '/\.xml(\.gz)?$/i', $nom ) ) {
				$parts[] = [ 'part' => $num, 'encoding' => (int) $part->encoding ];
			} elseif ( isset( $part->parts ) && is_array( $part->parts ) ) {
				$parts = array_merge( $parts, dmarc_find_xml_parts( $part, $num ) );
			}
		}
	}

	return $parts;
}

$summary = [];
$reports = [];

foreach ( $ids as $uid ) {
	$header = imap_fetchheader( $mbox, $uid, FT_UID );
	$subject = '';

	if ( preg_match( '/^Subject:\s*(.+)$/mi', $header, $m ) ) {
		$subject = trim( $m[1] );
	}

	$struct = imap_fetchstructure( $mbox, $uid, FT_UID );
	$parts  = dmarc_find_xml_parts( $struct, '' );

	if ( ! $parts ) {
		echo 'SEM_ANEXO: ' . $subject . PHP_EOL;
		continue;
	}

	$raw = imap_fetchbody( $mbox, $uid, $parts[0]['part'], FT_UID );

	if ( 3 === $parts[0]['encoding'] ) {
		$raw = base64_decode( $raw );
	} elseif ( 4 === $parts[0]['encoding'] ) {
		$raw = quoted_printable_decode( $raw );
	}

	$xml = @gzdecode( $raw );

	if ( false === $xml ) {
		$xml = $raw; // alguns remetentes mandam XML puro.
	}

	$data = @simplexml_load_string( $xml );

	if ( ! $data ) {
		echo 'XML_INVALIDO: ' . $subject . PHP_EOL;
		continue;
	}

	$meta = $data->report_metadata;

	$reports[] = [
		'org'    => (string) $meta->org_name,
		'id'     => (string) $meta->report_id,
		'begin'  => date( 'Y-m-d H:i', (int) $meta->date_range->begin ),
		'end'    => date( 'Y-m-d H:i', (int) $meta->date_range->end ),
		'policy' => (string) $data->policy_published->p,
	];

	foreach ( $data->record as $record ) {
		$ip    = (string) $record->row->source_ip;
		$count = (int) $record->row->count;
		$spf   = (string) $record->row->policy_evaluated->spf;
		$dkim  = (string) $record->row->policy_evaluated->dkim;

		$dkim_domain = '';
		$dkim_result = '';
		$spf_domain  = '';

		foreach ( $record->auth_results->dkim as $d ) {
			$dkim_domain = (string) $d->domain;
			$dkim_result = (string) $d->result;
			break;
		}

		foreach ( $record->auth_results->spf as $s ) {
			$spf_domain = (string) $s->domain;
			break;
		}

		$key = sprintf(
			'%s | spf=%s (%s) | dkim=%s (%s) | disposition=%s',
			$ip,
			$spf,
			$spf_domain ? $spf_domain : '-',
			$dkim,
			$dkim_domain ? $dkim_domain : '-',
			(string) $record->row->policy_evaluated->disposition
		);

		if ( ! isset( $summary[ $key ] ) ) {
			$summary[ $key ] = 0;
		}

		$summary[ $key ] += $count;
	}
}

imap_close( $mbox );

echo PHP_EOL . '--- RELATORIOS ---' . PHP_EOL;

foreach ( $reports as $r ) {
	echo sprintf( '%s | %s..%s | policy=%s | id=%s', $r['org'], $r['begin'], $r['end'], $r['policy'], $r['id'] ) . PHP_EOL;
}

echo PHP_EOL . '--- MENSAGENS POR ORIGEM ---' . PHP_EOL;

arsort( $summary );

foreach ( $summary as $key => $count ) {
	echo sprintf( '%5d  %s', $count, $key ) . PHP_EOL;
}

echo PHP_EOL . 'TOTAL: ' . array_sum( $summary ) . ' mensagens' . PHP_EOL;
echo 'DMARC-END' . PHP_EOL;
