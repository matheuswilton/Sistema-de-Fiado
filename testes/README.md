# Testes automáticos do FiadoFácil

Os testes rodam sozinhos no GitHub Actions (arquivo `.github/workflows/testes.yml`):
- a cada envio para a branch **`teste`**;
- em todo **Pull Request** para a `main`;
- antes de cada **publicação**: se algum teste falhar, o site não é atualizado.

Eles usam um **banco só de testes**, criado do zero a cada execução. As contas de teste recebem uma senha própria (variável `SENHA_TESTE`), então nenhuma senha real do site fica no código.

## O que é testado

| Arquivo | O que confere |
|---|---|
| `unidade.php` | Funções: validação de CPF e telefone, formato de dinheiro, código Pix (BR Code e CRC16), chave Pix e link do WhatsApp. |
| `regras.js` | Limite de crédito, valor zero, pagamento maior que a dívida, cliente sem dívida, exclusão bloqueada com dívida, filtro de inadimplentes, permissões do atendente, "Qual é a sua loja?", endereço `/loja` com maiúsculas e página 404. |
| `conta_cpf.js` | Conta única do cliente: criar, telefone que confere, confirmação pela loja, dívidas de várias lojas, Pix, CPF trocado, celular e sair. |
| `api.js` | API da loja: chave, recursos, códigos 200/400/401/404/405, isolamento entre lojas e chave cancelada. |
| `isolamento.js` | Nenhuma loja vê, altera ou apaga dados de outra, pela URL ou pelo formulário; painel da plataforma; bloqueio de loja. |

## Rodar no computador

Precisa de PHP 8, MySQL/MariaDB e Node.js.

```bash
# 1) banco de testes (separado do banco de uso)
mysql -uroot -e "CREATE DATABASE fiado_teste CHARACTER SET utf8mb4"
mysql -uroot fiado_teste < fiadofacil/sql/banco.sql
# 2) aponte o fiadofacil/config.local.php para o banco fiado_teste e prepare
php testes/preparar.php
# 3) ligue o site de testes
php -S 127.0.0.1:8080 -t fiadofacil testes/servidor.php
# 4) em outro terminal
cd testes && npm install && npx playwright install chromium
MYSQL_CMD="mysql -uroot fiado_teste" bash rodar.sh
```

> Atenção: o `preparar.php` troca a senha de **todas** as contas do banco configurado. Use sempre um banco só de testes.
