<?php
/**
 * Remove uma submissão de teste do formulário.
 *
 * Uso: wp eval-file clean-test-submission.php <submission_id>
 */
global $wpdb;

$sid = isset( $args[0] ) ? absint( $args[0] ) : 0;

if ( ! $sid ) {
	echo 'informe o submission_id: wp eval-file clean-test-submission.php <id>' . PHP_EOL;
	echo 'CLEAN-END' . PHP_EOL;
	return;
}

$entries = $wpdb->delete( $wpdb->prefix . 'fusion_form_entries', [ 'submission_id' => $sid ] );
$subs    = $wpdb->delete( $wpdb->prefix . 'fusion_form_submissions', [ 'id' => $sid ] );

echo "entries removidas: {$entries}" . PHP_EOL;
echo "submissions removidas: {$subs}" . PHP_EOL;
echo 'CLEAN-END' . PHP_EOL;
