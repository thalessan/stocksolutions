# scripts

Scripts operacionais usados para corrigir/diagnosticar o site em produção
(UOL Host, hospedagem compartilhada: sem root, sem `mariadb` client).

## Formulário de contato (Avada Forms)

O formulário fica no post `141` (post type `fusion_form`), referenciado pela
home (`[fusion_form form_post_id="141"]`). O conteúdo e as configurações vivem
no banco, não em arquivos do tema.

Dois problemas corrigidos em 2026-11-25:

1. **Rótulos em inglês** — o conteúdo do post 141 veio do demo import do Avada
   (`First Name:`, `Surname:`, `Your Email:`, `Telephone:`, `Your Message:`,
   `Submit Your Quote Request`). Agora estão em pt-BR.
2. **E-mail não era enviado** — a meta `_fusion` do post tinha
   `form_actions: ["database"]`, ou seja, a submissão só era gravada nas tabelas
   `*_fusion_form_submissions`/`*_fusion_form_entries`. A ação `email` foi
   habilitada com destinatário `contato@stocksolutions.com.br`, assunto
   `Contato pelo site - StockSolutions` e `Reply-To` dinâmico (`[your_email]`).

### `fix-contact-form.php`

Idempotente: traduz os rótulos/avisos/botão e garante as ações
`database,email` + destinatário. Rodar local e depois no PRD:

```bash
# local (Docker)
docker run --rm -v "$PWD":/repo -v "$PWD/web":/var/www/html --user root \
  --network thalessan-stocksolutions_default \
  -e WP_DB_HOST=db -e WP_DB_NAME=stcksolut2 -e WP_DB_USER=wplocal -e WP_DB_PASSWORD=wplocal \
  -e WP_HOME=http://localhost:8080 -e WP_SITEURL=http://localhost:8080 \
  wordpress:cli wp eval-file /repo/scripts/fix-contact-form.php --path=/var/www/html --allow-root

# PRD
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/fix-contact-form.php FIX-FORM-DONE
```

### Diagnóstico

```bash
# submissões gravadas (últimas 5) + campos
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/check-submissions.php SUBCHECK-END

# teste de wp_mail (retorna bool(true)/erro)
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/mail-test.php MAIL-TEST-END
```

Observação: `wp db query` **não funciona** no PRD (wp-cli procura o binário
`mariadb`, que não existe na conta); por isso as consultas usam `$wpdb` via
`wp eval-file`.

## `prd-run-php.expect`

Runner genérico: envia um arquivo PHP por base64, executa com
`php wp-cli.phar eval-file` e apaga o arquivo remoto. Credenciais vêm de
variáveis de ambiente (`PRD_SSH_PASS` obrigatória; `PRD_SSH_HOST`,
`PRD_SSH_USER`, `PRD_WP_PATH` opcionais). Repete a conexão até 4 vezes porque
o gateway SSH da UOL derruba tentativas em sequência.
