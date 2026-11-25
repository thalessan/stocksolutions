<?php
/**
 * Lista as submissões do formulário (temporário, diagnóstico).
 */
global $wpdb;
$subs = $wpdb->get_results( "SELECT id, form_id, time FROM {$wpdb->prefix}fusion_form_submissions ORDER BY id DESC LIMIT 5", ARRAY_A );
echo 'SUBMISSIONS: ' . wp_json_encode( $subs ) . PHP_EOL;
$entries = $wpdb->get_results( "SELECT form_id, field_id, value FROM {$wpdb->prefix}fusion_form_entries ORDER BY id DESC LIMIT 8", ARRAY_A );
echo 'ENTRIES: ' . wp_json_encode( $entries ) . PHP_EOL;
echo 'SUBCHECK-END' . PHP_EOL;
