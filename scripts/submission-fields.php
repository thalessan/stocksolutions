<?php
/**
 * Campos de submissões específicas (temporário).
 * Uso: wp eval-file submission-fields.php 6 7
 */
global $wpdb;

$ids = array_map( 'absint', $args );

if ( ! $ids ) {
	$ids = [ 6, 7 ];
}

foreach ( $ids as $sid ) {
	$fields = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT field_id, value FROM {$wpdb->prefix}fusion_form_entries WHERE submission_id = %d ORDER BY field_id",
			$sid
		),
		ARRAY_A
	);

	echo 'SUB ' . $sid . ': ' . wp_json_encode( $fields ) . PHP_EOL;
}

echo 'FIELDS-END' . PHP_EOL;
