# Pitch: FiadoFácil

Roteiro para uma apresentação de **8 a 10 minutos**: 13 slides, cerca de 30 a 40 segundos cada, mais a demonstração ao vivo (o roteiro dela está em [APRESENTACAO.md](APRESENTACAO.md)).
Os diagramas para os slides estão em [`docs/img/`](img/).

> **Antes de apresentar:** abra cada link da seção [Fontes](#fontes) e confira o número e a data. Os dados de inadimplência da Serasa saem todo mês; use o mais recente.

---

## Slide 1: Abertura (gancho)
**"Anota aí que eu pago sexta."**
Quem nunca ouviu isso numa mercearia, padaria ou açougue de bairro? O fiado é tradição no comércio brasileiro, e quase sempre é controlado num **caderninho de papel**.

*Fala:* "Sou Matheus Wilton, do curso de GTI da FPM, e criei o FiadoFácil."

## Slide 2: O problema
O caderninho:
- **some, rasga, molha** e não tem cópia;
- não soma sozinho: o dono **não sabe quanto tem para receber**;
- não avisa quem está atrasado nem tem **limite**: o cliente compra mais do que consegue pagar;
- gera **briga**: "eu não comprei isso", e não há histórico para mostrar;
- só fica com quem está no balcão; o funcionário do outro turno não sabe de nada.

## Slide 3: Os números (por que escolhi esse tema)
| Dado | Fonte |
|---|---|
| **84,2 milhões** de brasileiros inadimplentes em set/2026, **51,1% da população adulta**, recorde da série iniciada em 2016 | Serasa, Mapa da Inadimplência |
| Cerca de **4 em cada 10 MEIs vendem fiado** (≈42%) | Sebrae, pesquisa Educação Financeira do MEI (citada pelo Banco Central) |
| O MEI ainda **registra os custos em papel** | Sebrae, perfil da gestão financeira do MEI |
| **61%** dos donos de pequenos negócios pagam contas da empresa com a conta pessoal (2025) | Sebrae, Hábitos Financeiros dos Pequenos Negócios |
| **68%** dos empreendedores não acompanham o fluxo de caixa regularmente | Sebrae-MT |
| MEIs têm a maior mortalidade: **29% fecham em até 5 anos**, e o Sebrae liga isso à capacidade de gestão | Sebrae, Sobrevivência de Empresas (2020) |

*Fala:* "Metade dos adultos já está com o nome sujo, e o pequeno comerciante continua vendendo a prazo sem controle nenhum. Cada real que não volta faz falta no caixa de quem tem margem pequena."

## Slide 4: Público
- Mercearias, padarias, açougues, lanchonetes, salões e pequenos comércios de bairro.
- Dono que **não tem dinheiro para um ERP** e não quer aprender um sistema complicado.
- Usa o **celular** no balcão.

## Slide 5: A solução, o FiadoFácil
Sistema web gratuito, que roda no **navegador do computador e do celular**, sem instalar nada.
1. **Limite de crédito:** a compra é **recusada** se passar do limite do cliente.
2. **Dívida automática:** compras menos pagamentos, sempre certa. Pagamento parcial ou total, nunca maior que a dívida.
3. **Situação automática:** em dia, devendo ou **inadimplente** (mais de 30 dias).
4. **Cobranças:** quem vence nos próximos 3 dias e quem já está atrasado, com a mensagem pronta para o WhatsApp.
5. **Relatórios:** dívidas por período (com versão para imprimir), ranking de confiabilidade e resumo financeiro.
6. **Conta do cliente pelo CPF:** o cliente vê o fiado de **todas as lojas** onde compra, numa conta só.
7. **Perfis:** dono da loja, atendente, cliente e o dono da plataforma, cada um só com o que precisa.
8. **Computador e celular:** menu simples, modo noturno e instalável na tela inicial do celular.

## Slide 6: Uma plataforma, várias lojas
- Cada comércio tem **endereço, login, equipe e foto próprios** (ex.: `/loja/padaria-pao-quente`).
- **Isolamento de dados:** uma loja nunca vê clientes, compras ou funcionários de outra. Toda consulta filtra pela loja da sessão, e cada ação confere se o registro é da loja (proteção contra IDOR).
- **Conta única do cliente:** pelo CPF, ele vê as dívidas de todas as lojas que confirmaram que é ele.
- **Painel do dono da plataforma:** cria lojas, bloqueia e entra em qualquer uma como administrador.

*Fala:* "O mesmo sistema pode atender várias lojas ao mesmo tempo, cada uma com os seus dados separados, como um SaaS. As três lojas que vou mostrar são de demonstração."

## Slide 7: Integrações
| Integração | O que faz |
|---|---|
| **Pix** | QR Code no padrão do Banco Central (BR Code), com o valor da dívida já preenchido |
| **WhatsApp** | Abre a conversa com a mensagem de cobrança pronta: valor, prazo e chave Pix |
| **ViaCEP** | Digitou o CEP, o endereço do cliente se preenche sozinho |
| **API própria** | Outro programa (um app, uma planilha) lê os dados da loja com uma chave de acesso |

*Fala:* "O Pix cai direto na conta da loja, sem intermediário. A cobrança pelo WhatsApp abre pronta, mas só sai quando o comerciante aperta enviar."

## Slide 8: Demonstração ao vivo
Siga o roteiro em [APRESENTACAO.md](APRESENTACAO.md). Mostre no **computador** e no **celular**.

## Slide 9: Como funciona por dentro
- **Tecnologias:** PHP 8, MySQL, HTML, CSS e JavaScript (com GSAP nas animações); hospedagem gratuita na InfinityFree. Mostrar `img/fluxo-compra.png`.
- **A dívida é calculada** (compras − pagamentos) e nunca digitada à mão, por isso não fica errada.
- **Transação com trava:** duas compras ao mesmo tempo para o mesmo cliente não conseguem estourar o limite.

## Slide 10: Banco de dados
- **7 tabelas:** lojas (`comercios`), clientes, compras, pagamentos, usuários, contas dos clientes por CPF e os vínculos entre conta e loja. Mostrar `img/banco-der.png`.
- O banco **se atualiza sozinho** a cada versão do sistema (migrações): num banco vazio, ele cria as tabelas e as lojas de exemplo.

## Slide 11: Qualidade e segurança
- **Fluxo de empresa:** branch `teste` → testes automáticos e análise de segurança → site de teste → Pull Request → site oficial.
- **Testes automáticos:** um navegador de verdade (Playwright) testa o limite, os pagamentos, a conta por CPF, a API e o isolamento entre lojas, num banco novo a cada execução.
- **Site de teste:** fiadofacilteste.free.je, com banco próprio.
- **Snyk Code:** analisa o código a cada envio. Achou 2 problemas de gravidade média (um redirecionamento aberto e um valor do banco usado num comando SQL sem filtro), corrigidos; hoje, zero.
- **Segurança:** senhas com bcrypt, prepared statements contra SQL Injection, proteção contra XSS e CSRF, limite de tentativas de login e HTTPS.

*Fala:* "Nenhuma mudança chega ao site oficial sem passar pelos testes e pela análise de segurança."

## Slide 12: Soluções existentes × FiadoFácil
| | Caderninho | Planilha | ERP pago | **FiadoFácil** |
|---|---|---|---|---|
| Custo | grátis | grátis | mensalidade | **grátis** |
| Funciona no celular | não | ruim | às vezes | **sim** |
| Bloqueia compra acima do limite | não | não | sim | **sim** |
| Mostra quem está atrasado | não | manual | sim | **sim, automático** |
| Cobrança e Pix | não | não | às vezes | **WhatsApp e QR Code** |
| Cliente consulta a dívida | não | não | raramente | **sim, todas as lojas** |
| Fácil de aprender | sim | médio | difícil | **sim** |

Também existem apps de "caderneta digital" no celular. O FiadoFácil se diferencia por ter **várias lojas na mesma plataforma**, **funcionários com permissões diferentes**, **conta única do cliente** e **código aberto**.

## Slide 13: Próximos passos e encerramento
- **Acesso por link no WhatsApp:** o cliente entra na hora, sem esperar a loja aprovar.
- **Pix confirmado automaticamente:** o banco avisa o pagamento e a dívida baixa sozinha.
- **Gráficos** de evolução das vendas a prazo e dos recebimentos.
- **Cadastro da loja pelo próprio comerciante**, num plano pago.

*Fala final:* "O FiadoFácil troca o caderninho por um controle simples, seguro e gratuito, que cabe no bolso do comerciante. Obrigado! O site está no ar em **fiadofacil.free.je** e o código está no GitHub."

---

## Fontes
1. Serasa: Mapa da Inadimplência e matérias sobre o recorde de 2026
   - https://www.serasa.com.br/imprensa/inadimplencia-ultrapassa-806-milhoes-virada-ano-dividas-impedem-sonhos-serasa/
   - https://diariodocomercio.com.br/economia/inadimplencia-atinge-metade-adultos/
   - https://timesbrasil.com.br/brasil/serasa-inadimplencia-bate-recorde-e-atinge-842-milhoes-de-brasileiros/
   - https://monitormercantil.com.br/brasil-fechou-ano-com-50-da-populacao-adulta-inadimplente/ (dez/2025: 81,2 mi, 49,7%)
2. Banco Central: artigo "Educação financeira de MEIs" (cita a pesquisa do Sebrae sobre venda fiado)
   - https://www.bcb.gov.br/Nor/relcidfin/docs/art9_educacao_finanaceira_MEIs.pdf
3. Sebrae: perfil da gestão financeira do MEI (papel e fiado)
   - https://www.coad.com.br/home/noticias-detalhe/86417/pesquisa-inedita-mostra-o-perfil-da-gestao-financeira-do-mei
4. Agência Sebrae: 61% fazem pagamentos da empresa com a conta pessoal
   - https://agenciasebrae.com.br/cultura-empreendedora/61-dos-empreendedores-brasileiros-fazem-pagamentos-da-empresa-com-a-conta-pessoal/
5. Sebrae-MT: controle de caixa (68%)
   - https://www.omie.com.br/blog/pmes-controle-de-caixa-pesquisa-sebrae/
6. Sebrae: sobrevivência de empresas, mortalidade de MEIs (29% em 5 anos)
   - https://www.jb.com.br/economia/2021/06/1031115-sebrae-pequenos-negocios-tem-maior-taxa-de-mortalidade.html
7. CNDL/SPC Brasil: crediário entre os principais responsáveis pela negativação
   - https://blogs.correiobraziliense.com.br/servidor/crediario-e-cartao-de-credito-sao-os-principais-viloes-da-inadimplencia-no-pais-apontam-cndl-spc-brasil/
8. Portais de dados para conferir números atualizados: https://datasebrae.com.br, https://bibliotecas.sebrae.com.br e o site da Serasa (Mapa da Inadimplência)
