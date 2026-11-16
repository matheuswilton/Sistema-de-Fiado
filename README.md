# FiadoFácil — Sistema de Fiado
Sistema web em PHP e MySQL para controlar compras no fiado, pagamentos e dívidas de clientes de um comércio.

🔗 **Site no ar:** https://fiadofacil.free.je

Projeto acadêmico de Matheus Wilton, Luiz Guilherme e Henrique Nascimento (GTI — FPM, Goiânia/GO).
## Documentação do Projeto Integrador
| Documento | Conteúdo |
|---|---|
| [Diagramas](docs/DIAGRAMAS.md) | Banco de dados (DER e diagrama de classes UML), casos de uso, fluxo do sistema, fluxo de compra e pagamento, estados do cliente, sequência e arquitetura |
| [Case](docs/CASE.md) | Estudo de caso: problema, requisitos, solução aplicada e resultados |
| [Pitch](docs/PITCH.md) | Roteiro da apresentação, dados que justificam o projeto, comparação com outras soluções e fontes |
| [Demonstração ao vivo](docs/APRESENTACAO.md) | Checklist e roteiro para mostrar o sistema no computador e no celular |
| [Imagens dos diagramas](docs/img/) | PNGs prontos para colocar nos slides |

![Diagrama do banco de dados](docs/img/banco-der.png)
## Funcionalidades
- Login com dois perfis de funcionário: **Administrador** e **Usuário comum**
- Cadastro, edição, busca (por nome ou telefone) e exclusão de clientes (exclusão só do administrador)
- Extrato de cada cliente com dívida, limite disponível, compras e pagamentos
- Registro de compras no fiado com validação do limite de crédito (transação com trava, sem estourar o limite)
- Registro de pagamentos, com bloqueio de pagamento maior que a dívida e botão "Quitar dívida inteira"
- Relatório de dívidas com filtros por período e situação (dá para imprimir)
- Ranking de confiabilidade dos clientes
- Resumo financeiro e maiores devedores (somente administrador)
- Gerenciar funcionários: criar, bloquear/desbloquear e redefinir senha (somente administrador)
- Todo usuário troca a própria senha em "Minha senha"
- **Acesso do cliente (somente leitura):** o cliente solicita um usuário pela tela de login, o administrador aprova escolhendo o cliente, e o cliente vê apenas a própria dívida
## Instalação no XAMPP (computador local)
1. Copie a pasta `fiadofacil` para `C:\xampp\htdocs\fiadofacil` (o `login.php` deve ficar direto dentro dela).
2. No XAMPP, ligue o **Apache** e o **MySQL**.
3. Em `http://localhost/phpmyadmin` crie um banco chamado `sistema_fiado` (utf8mb4), selecione-o, vá em **Importar** e envie `fiadofacil/sql/banco.sql`.
4. Copie `config.local.example.php` para `config.local.php` (dentro de `fiadofacil`) e confira os dados (padrão do XAMPP: host `localhost`, usuário `root`, senha vazia, banco `sistema_fiado`).
5. Acesse `http://localhost/fiadofacil/login.php`.
## Instalação na InfinityFree
1. No painel, crie o banco MySQL e anote host, usuário, senha e nome do banco.
2. No phpMyAdmin da hospedagem, selecione o banco, vá em **Importar** e envie `sql/banco.sql` (pode importar de novo sem duplicar dados).
3. Envie o conteúdo da pasta `fiadofacil` para `htdocs` (sem deixar uma subpasta extra). **Não precisa enviar a pasta `sql`.**
4. Edite o `config.local.php` com os dados do banco **direto no servidor** (ou preencha antes de enviar).
5. Abra o endereço do site. O `index.php` encaminha para o login.
## Segurança (leia)
- A senha do banco **nunca** fica no código: ela fica só no `config.local.php`, que está no `.gitignore`. **Se você enviar arquivos pelo botão "Upload files" do GitHub, ele ignora o `.gitignore`: não envie o `config.local.php`.**
- Se alguma senha de banco já foi publicada no GitHub, troque-a no painel da hospedagem. Apagar do arquivo não adianta, porque o histórico do Git guarda a versão antiga.
- As contas de exemplo são públicas. Troque as senhas no primeiro acesso (menu **Minha senha**). O sistema avisa no início enquanto a senha de exemplo estiver em uso.
- Senhas novas precisam de no mínimo 8 caracteres.
## Contas de exemplo
| Perfil | Usuário | Senha |
|---|---|---|
| Administrador | `admin` | `admin123` |
| Usuário comum | `usuario` | `user123` |
Clientes de exemplo: João Silva `(62) 99999-9999`, Maria Souza `(62) 98888-8888`, Carlos Pereira `(62) 97777-7777`, Ana Lima `(62) 96666-6666`.
## Como usar o acesso do cliente
1. **Cliente:** na tela de login, clica em "Sou cliente e quero ver minha dívida", informa nome, telefone cadastrado na loja, usuário e senha.
2. **Administrador:** menu → "Aprovar acessos". O sistema já sugere o cliente pelo telefone; confira e clique em **Aprovar**.
3. **Cliente:** entra com o usuário criado e vê a página "Minha dívida".
4. **Administrador:** menu → "Acessos de clientes" para bloquear, desbloquear e trocar a senha.
## Regras de negócio
- Situação do cliente: **em dia** (sem dívida), **devendo** e **inadimplente** (a compra mais antiga ainda não paga tem mais de 30 dias). Os pagamentos quitam as compras mais antigas primeiro.
- Score do ranking: começa em 100, perde 2 pontos por dia acima de 7 em aberto, perde 10 se a dívida passa de 50% do limite, ganha 5 se já pagou alguma vez.
- Usuário comum cadastra clientes com limite padrão de R$ 500; só o administrador altera limites.
- Cliente com dívida em aberto não pode ser excluído. Excluir um cliente apaga o histórico e o acesso dele.
## Estrutura
```
fiadofacil/
├── *.php                    páginas do sistema
├── cabecalho.php / rodape.php  layout e menu comuns a todas as páginas
├── funcoes.php              funções compartilhadas (sessão, segurança, regras de compra/pagamento, situação dos clientes)
├── conexao.php              conecta no banco usando o config.local.php
├── config.local.example.php modelo do arquivo de configuração
├── css/estilo.css           visual
├── js/script.js             menu do celular, confirmações, máscara de telefone
└── sql/banco.sql            criação das tabelas e dados de exemplo
```
## Problemas comuns
- **404 ao abrir o login:** a pasta não está em `htdocs` ou está duplicada (`fiadofacil\fiadofacil`).
- **"Falta o arquivo config.local.php":** copie o `config.local.example.php` para `config.local.php` e preencha.
- **"Não foi possível conectar ao banco":** confira host, usuário, senha e nome do banco no `config.local.php`.
- **MySQL não liga no XAMPP:** outro MySQL do Windows pode estar usando a porta 3306; pare o serviço dele em `services.msc`.
