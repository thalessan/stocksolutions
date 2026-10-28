# Conteúdo do banco de dados — site Stock Solutions

Fonte: dump do MySQL de produção (`docker/db/01-dump.sql`, gerado de
`stcksolut2.mysql.uhserver.com` / db `stcksolut2`, 17 tabelas com prefixo `wp_stck`),
carregado no ambiente local Docker (`docker compose up -d` → http://localhost:8080).

Observação importante: **o conteúdo parece um template/demo do Avada** (portfólio com
"Carpentry/Plumbing/Decorating/Maintenance", menus "Featured Item 1–3", formulários
"Request A Quote" do demo) — não material real da empresa. considerável atenção antes
de lançar novas telas em cima desse conteúdo.

## Tema / scene
- Tema ativo: `Avada.7.11.7/Avada` (theme + child `stylesheet` igual, sem child)
- `blogname`: StockSolutions; registro de usuários desativado.
- Site: https://stocksolutions.com.br (produção); `WP_HOME/WP_SITEURL` parametrizados no `wp-config.php`.

## Páginas (`wp_stckposts`, post_type `page`)
| ID | Título | slug | status |
|----|--------|------|--------|
| 683 | StockSolutions Home | stocksolutions-home-2 | publish (43 KB de conteúdo Fusion) |
| 1797 | Draft Página | __trashed | trash |

## Fusion Builder — Template Builder (Avada)
- `fusion_tb_layout` (6): Custom Blog Post (229), Custom 404 (336), Homepage (689),
  Services (1403), Post Archives (1442), Search Results (1685).
- `fusion_tb_section` (11): Custom Footer Design (60), Home Global Header (1547, 29 KB),
  Custom Blog Content (239), Custom 404 Content (340), Home Header (690),
  Single Post Header (1341, 28 KB), Services Content (1404), Post Archives Content (1443),
  Search Content (1686), "StockSolutions" (1757), HEAD vertical "cabeçalho" (1780).
- `fusion_element` (5): Blog (474), Services (1383), Services 2/3/4 (1068/1260/1385).

## Portfólio (`avada_portfolio`, 4)
Carpentry (714) · Decorating (1384) · Plumbing (738) · Maintenance (741).

## Formulários (`fusion_forms`, 3)
Request A Quote (141) · Contact Us / Quote (573, 17 KB) · Callback (1548).
Sem envios: `fusion_form_submissions` = 0. Sem comentários (`comments` = 0).

Menus (`nav_menu_item`, 19 publicados): itens do demo (Home, Projects, Our Solutions,
About Us, News, Get In Touch, Solutions, Sub Item, Featured Item 1–3, Products, Company,
Resources, Maintenance, Plumbing, Decorating, Carpentry) + 1 draft.

## Revisões / outros
- 43 revisões (`post_type=revision`), 15 imagens, 1 `wp_navigation`.
- Único usuário WP:
  `roim@i` (`roimi`) — <ro.imai@gmail.com>. Sem extrair senha.
- `awb_critical_css`, `links`, `terms`, `usermeta`, `termmeta` presentes, uso mínimo.

## Ambiente local
- `docker-compose.yml`: MySQL 5.7 (init via dump), php-apache 8.1 servindo `web/`,
  phpMyAdmin na porta 8081.
- `web/wp-config.php` lê `WP_DB_HOST/NAME/USER/PASSWORD`, `WP_HOME`, `WP_SITEURL` do
  ambiente; fallback = credenciais de produção (⚠ senha de produção commitada
  historicamente no repo: considerar mover para `.env` fora do git).
- URLs de produção no DB foram substituídas por `http://localhost:8080`
  (via `wp search-replace`), só no ambiente local.
