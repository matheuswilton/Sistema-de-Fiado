# Pitch: FiadoFácil

Roteiro para uma apresentação de **5 a 7 minutos**: 9 slides, cerca de 40 segundos cada, mais a demonstração ao vivo.
Os diagramas para os slides estão em [`docs/img/`](img/).

> ⚠️ **Antes de apresentar:** abra cada link da seção [Fontes](#fontes) e confira o número e a data. Os dados de inadimplência da Serasa saem todo mês; use o mais recente.

---

## Slide 1: Abertura (gancho)
**"Anota aí que eu pago sexta."**
Quem nunca ouviu isso numa mercearia, padaria ou açougue de bairro? O fiado é tradição no comércio brasileiro, e quase sempre é controlado num **caderninho de papel**.

*Fala:* "Somos Matheus Wilton, Luiz Guilherme e Henrique Nascimento, do curso de GTI da FPM, e criamos o FiadoFácil."

## Slide 2: O problema
O caderninho:
- **some, rasga, molha** e não tem cópia;
- não soma sozinho: o dono **não sabe quanto tem para receber**;
- não avisa quem está atrasado nem tem **limite**: o cliente compra mais do que consegue pagar;
- gera **briga**: "eu não comprei isso", e não há histórico para mostrar;
- só fica com quem está no balcão; o funcionário do outro turno não sabe de nada.

## Slide 3: Os números (por que escolhemos esse tema)
| Dado | Fonte |
|---|---|
| **84,2 milhões** de brasileiros inadimplentes em set/2026, **51,1% da população adulta**, recorde da série iniciada em 2016 | Serasa, Mapa da Inadimplência |
| Cerca de **4 em cada 10 MEIs vendem fiado** (≈42%) | Sebrae, pesquisa Educação Financeira do MEI (citada pelo Banco Central) |
| O MEI ainda **registra os custos em papel** | Sebrae, perfil da gestão financeira do MEI |
| **61%** dos donos de pequenos negócios pagam contas da empresa com a conta pessoal (2025) | Sebrae, Hábitos Financeiros dos Pequenos Negócios |
| **68%** dos empreendedores não acompanham o fluxo de caixa regularmente | Sebrae-MT |
| MEIs têm a maior mortalidade: **29% fecham em até 5 anos**, e o Sebrae liga isso à capacidade de gestão | Sebrae, Sobrevivência de Empresas (2020) |

*Fala:* "Metade dos adultos já está com o nome sujo, e o pequeno comerciante continua vendendo a prazo sem controle nenhum. Cada real que não volta faz falta no caixa de quem tem margem pequena."

## Slide 4: Nosso público
- Mercearias, padarias, açougues, lanchonetes, salões e pequenos comércios de bairro.
- Dono que **não tem dinheiro para um ERP** e não quer aprender um sistema complicado.
- Usa o **celular** no balcão.

## Slide 5: A solução, o FiadoFácil
Sistema web gratuito, que roda no **navegador do computador e do celular**, sem instalar nada.
1. **Cadastro de clientes** com limite de crédito.
2. **Compra no fiado em segundos**: o sistema **bloqueia** a compra se passar do limite.
3. **Pagamento** parcial ou "quitar tudo"; não deixa pagar mais do que deve.
4. **Situação automática**: em dia, devendo ou **inadimplente** (mais de 30 dias).
5. **Relatórios**: quem deve, quanto e desde quando, com versão para imprimir.
6. **Ranking de confiabilidade**: em quem dá para confiar mais crédito.
7. **O cliente consulta a própria dívida** com login próprio, sem precisar ligar ou ir à loja. Acabam as discussões.
8. **Perfis**: administrador (dono) e atendente; o atendente não muda limites nem exclui clientes.

## Slide 6: Demonstração ao vivo
Ver o roteiro em [APRESENTACAO.md](APRESENTACAO.md). Mostre no **computador** e no **celular**.

## Slide 7: Como funciona por dentro
- **Tecnologias:** PHP 8, MySQL, HTML, CSS e JavaScript; hospedagem gratuita na InfinityFree.
- **Banco:** 4 tabelas (clientes, usuários, compras, pagamentos). Mostrar `img/banco-der.png`.
- **A dívida é calculada** (compras − pagamentos) e nunca digitada à mão, por isso não fica errada.
- **Segurança:** senhas com hash (bcrypt), proteção contra SQL Injection (prepared statements) e contra CSRF, sessão que cai na hora se o usuário for bloqueado, e transação com trava para duas compras simultâneas não estourarem o limite.

## Slide 8: Soluções existentes × FiadoFácil
| | Caderninho | Planilha | ERP pago | **FiadoFácil** |
|---|---|---|---|---|
| Custo | grátis | grátis | mensalidade | **grátis** |
| Funciona no celular | — | ruim | às vezes | **sim** |
| Bloqueia compra acima do limite | não | não | sim | **sim** |
| Mostra quem está atrasado | não | manual | sim | **sim, automático** |
| Cliente consulta a dívida | não | não | raramente | **sim** |
| Fácil de aprender | sim | médio | difícil | **sim** |

Também existem apps de "caderneta digital" no celular. O FiadoFácil se diferencia por ter **vários funcionários com permissões diferentes**, **acesso do cliente** e **código aberto**.

## Slide 9: Próximos passos e encerramento
- Lembrete de cobrança pelo **WhatsApp**.
- Pagamento por **Pix** com QR Code.
- Gráficos de evolução das vendas a prazo.
- Versão instalável no celular (PWA).

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
