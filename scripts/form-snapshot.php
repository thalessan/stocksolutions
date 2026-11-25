<?php
/**
 * Impressão digital do formulário (conteúdo + meta), para comparar local x PRD.
 */
$id      = 141;
$content = get_post_field( 'post_content', $id );
$meta    = get_post_meta( $id, '_fusion', true );
echo 'MD5: ' . md5( $content ) . PHP_EOL;
echo 'LEN: ' . strlen( $content ) . PHP_EOL;
echo 'META: ' . wp_json_encode( $meta ) . PHP_EOL;
echo 'SNAPSHOT-END' . PHP_EOL;
