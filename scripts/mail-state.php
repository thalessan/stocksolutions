<?php
/**
 * Confirma o estado final do envio de e-mail (temporário).
 */
echo 'SMTP_HOST: ' . ( defined( 'STCK_SMTP_HOST' ) ? STCK_SMTP_HOST : '(nao definido)' ) . PHP_EOL;
echo 'SMTP_PORT: ' . ( defined( 'STCK_SMTP_PORT' ) ? STCK_SMTP_PORT : '(nao definido)' ) . PHP_EOL;
echo 'SMTP_USER: ' . ( defined( 'STCK_SMTP_USER' ) ? STCK_SMTP_USER : '(nao definido)' ) . PHP_EOL;
echo 'SMTP_SECURE: ' . ( defined( 'STCK_SMTP_SECURE' ) ? STCK_SMTP_SECURE : '(nao definido)' ) . PHP_EOL;
echo 'SMTP_PASS_DEFINIDA: ' . ( defined( 'STCK_SMTP_PASS' ) && STCK_SMTP_PASS ? 'sim' : 'nao' ) . PHP_EOL;
echo 'MAIL_FROM: ' . ( function_exists( 'stck_mail_from' ) ? stck_mail_from() : '(mu-plugin ausente)' ) . PHP_EOL;

$id   = 141;
$meta = get_post_meta( $id, '_fusion', true );
echo 'FORM_ACTIONS: ' . implode( ',', (array) $meta['form_actions'] ) . PHP_EOL;
echo 'FORM_EMAIL_TO: ' . ( isset( $meta['email'] ) ? $meta['email'] : '(vazio)' ) . PHP_EOL;
echo 'FORM_REPLY_TO: ' . ( isset( $meta['email_reply_to'] ) ? $meta['email_reply_to'] : '(vazio)' ) . PHP_EOL;
echo 'PLACEHOLDER_TELEFONE: ' . ( false !== strpos( get_post_field( 'post_content', $id ), 'placeholder="(11) 91234-5678"' ) ? 'AINDA PRESENTE' : 'removido' ) . PHP_EOL;

echo 'STATE-END' . PHP_EOL;
