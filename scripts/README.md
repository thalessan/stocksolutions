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

# impressão digital do post 141 (md5 do conteúdo + meta + nonce) para comparar local x PRD
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/form-snapshot.php SNAPSHOT-END

# remover uma submissão de teste (os argumentos extras chegam em $args)
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/clean-test-submission.php CLEAN-END <submission_id>
```

Observação: `wp db query` **não funciona** no PRD (wp-cli procura o binário
`mariadb`, que não existe na conta); por isso as consultas usam `$wpdb` via
`wp eval-file`.

## E-mail (mu-plugin `stck-mail.php`)

O transporte nativo da UOL **não entrega e-mail para fora**:

| Evidência | Detalhe |
|---|---|
| `sendmail_path` | `/usr/local/sbin/wrapper-mail -t -i` |
| wrapper | script Python que envia para `127.0.0.1:25` e engole o erro (`/tmp/sendmail.log` só registra "Sending") |
| `127.0.0.1:25` | **proxy** `Python SMTP proxy version 0.2` — responde `250 Ok` para tudo, sem MTA real |
| porta 25 de saída | **bloqueada** (Gmail e MX da UOL dão timeout) |
| relay autenticado UOL | `smtp.uhserver.com:587` recusa o IP da hospedagem: `554 5.7.1 Client host rejected: Access denied` |
| cabeçalhos | hostname `b:u:<conta>:1` gerava `Message-ID: <@localhost.localdomain>` |

Conclusão: é preciso **SMTP autenticado** (porta 587, que funciona). O mu-plugin
corrige From/envelope/Message-ID e ativa SMTP quando as constantes existem.

DKIM do domínio já está configurado na UOL: `pro._domainkey` (CNAME para
`pro._domainkey.uhserver.com`) e SPF `include:spf.whservidor.com ?all`.

### Opção A — SMTP da própria UOL (EM USO)

Configurado e validado em 2026-11-25:

| Item | Valor |
|---|---|
| host | `smtps.uhserver.com` |
| porta / segurança | `465` / SSL |
| usuário | `site@stocksolutions.com.br` |
| remetente | `site@stocksolutions.com.br` (mesmo usuário autenticado) |
| Reply-To | e-mail do visitante (`[your_email]`) |
| Destinatário do formulário | `contato@stocksolutions.com.br` (alias que redireciona para outra caixa) |

Resultado no mail-tester: **10/10** (texto) e **9,7/10** (formulário HTML),
com `SPF pass` (`200.147.x`, via `include:spf.whservidor.com`) e
`DKIM pass` (`s=pro`, chave de 1024 bits da UOL). Entrega confirmada pelo
cliente em 2026-11-25.

```bash
# conferir o estado atual (host/porta/usuário/remetente/destinatário do form)
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/mail-state.php STATE-END
```

Comandos:

```bash
# aplicar / atualizar
PRD_RUN=php PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/set-smtp.php SMTP-CONFIG-END \
  stocksolutions.com.br/web/wp-config.php smtps.uhserver.com 465 site@stocksolutions.com.br '<senha>' ssl

# conferir qual transporte o PHPMailer usou + disparar teste
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/verify-mail.php VERIFY-END destino@exemplo.com 'Assunto'
```

Notas: a caixa `site@` ficou inativa por um período — enquanto isso o AUTH
retornava `535` e o IMAP `[AUTHENTICATIONFAILED]`. `contato@stocksolutions.com.br`
**não é uma caixa**: é um alias que redireciona para outra caixa, por isso nunca
autenticou (e não deve autenticar). O SMTP aceita o destinatário (`250 2.1.5 Ok`)
e, em caso de falha no destino, o bounce volta para `site@` (monitorado: zero
bounces após os testes).

### Autenticação de DNS (estado em 2026-11-25)

| Registro | Estado |
|---|---|
| SPF | ✅ `v=spf1 include:spf.whservidor.com ?all` → `pass` (relay `200.147.x`) |
| DKIM | ✅ `pro._domainkey` (CNAME → UOL), `s=pro`, `pass` |
| DMARC | ✅ `_dmarc` TXT `v=DMARC1; p=reject` (relatórios `rua` removidos) → `dmarc=pass` |
| PTR do relay | ⚠️ `a2-smithers*.uhserver.com` ≠ HELO `smtp.uhserver.com` (infra da UOL, impacto baixo) |
| Chave DKIM | ⚠️ 1024 bits (a UOL pode emitir 2048 no painel) |

Laudo após o DMARC: **9,8/10** (o desconto é por o teste técnico ser só texto;
o e-mail real do formulário é HTML).

**Política final adotada em 2026-11-26:** `p=reject` sem `rua`, ou seja,
relatórios agregados desativados. SPF e DKIM continuam passando (o `From` é a
caixa autenticada `site@`), então o envio do formulário não é afetado.

Rollback, se algum remetente legítimo quebrar: editar o TXT `_dmarc` para
`v=DMARC1; p=none` (ou `p=quarantine`) — vale após o TTL (1800s).

> Cuidado: dois registros TXT em `_dmarc` invalidam a política. Verifique com
> `dig TXT _dmarc.stocksolutions.com.br +short` (deve retornar uma linha).

### Opção B — relay transacional (Brevo)

```bash
PRD_RUN=php PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/set-smtp.php SMTP-CONFIG-END \
  stocksolutions.com.br/web/wp-config.php smtp-relay.brevo.com 587 '<login>' '<smtp-key>' tls
```

Exige autenticar o domínio no Brevo (registros DNS no painel da UOL).

Outros comandos de diagnóstico:

```bash
# conectividade de saída (587/465/443 liberados, 2525 e 25 bloqueados)
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/test-smtp-ports.php PORTS-END
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/test-port25.php PORT25-END

# teste de wp_mail (retorna bool(true)/erro)
PRD_SSH_PASS=... expect scripts/prd-run-php.expect scripts/mail-test.php MAIL-TEST-END
```


## Máscara de telefone

O Avada Forms não tem máscara nativa (só o atributo `pattern`, que não formata).
A máscara é feita por um mu-plugin, que fica no repo e não depende de opção no
banco:

- `web/wp-content/mu-plugins/stck-form-mask.php` — enfileira o JS no front-end.
- `web/wp-content/mu-plugins/stck-form-mask.js` — formata `input[type="tel"]`
  dentro de `.fusion-form` enquanto o usuário digita:
  - 10 dígitos → `(XX) XXXX-XXXX` (fixo)
  - 11 dígitos → `(XX) XXXXX-XXXX` (celular)
  - aceita colagem com `+55` e descarta o que não for dígito;
  - preserva a posição do cursor; backspace sobre `-`/espaço apaga o dígito anterior.

Teste da lógica (usa o arquivo real, sem dependências):

```bash
node scripts/test-form-mask.js
```

Deploy:

```bash
PRD_SSH_PASS=... expect scripts/prd-put-file.expect \
  web/wp-content/mu-plugins/stck-form-mask.php \
  stocksolutions.com.br/web/wp-content/mu-plugins/stck-form-mask.php \
  web/wp-content/mu-plugins/stck-form-mask.js \
  stocksolutions.com.br/web/wp-content/mu-plugins/stck-form-mask.js
```

## `prd-run-php.expect`

Runner genérico: envia um arquivo PHP por base64, executa com
`php wp-cli.phar eval-file` e apaga o arquivo remoto. Credenciais vêm de
variáveis de ambiente (`PRD_SSH_PASS` obrigatória; `PRD_SSH_HOST`,
`PRD_SSH_USER`, `PRD_WP_PATH` opcionais). Repete a conexão até 4 vezes porque
o gateway SSH da UOL derruba tentativas em sequência.
