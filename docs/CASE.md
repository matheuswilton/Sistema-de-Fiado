# Case: Mercearia de bairro troca o caderninho pelo FiadoFácil

> Case **ilustrativo**: o comércio e os números são um cenário de exemplo, montado com os dados de exemplo do sistema, para mostrar como o FiadoFácil resolve o problema. Se você testou com um comércio real, troque por esses dados.

## 1. Contexto
Uma mercearia de bairro em Goiânia/GO, com o dono e uma atendente, vende fiado para cerca de 40 clientes fixos. O controle é feito num caderno no balcão: nome do cliente, data, o que levou e o valor.

## 2. Problemas encontrados
| Problema | Consequência |
|---|---|
| O total de cada cliente era somado à mão | Erros de conta e tempo perdido no fim do mês |
| Não havia limite de crédito | Clientes acumulavam dívidas que não conseguiam pagar |
| Ninguém sabia quem estava atrasado | A cobrança era feita "de cabeça", e muitas dívidas antigas eram esquecidas |
| O caderno ficava só no balcão | A atendente do outro turno não sabia quanto cada um devia |
| O cliente não tinha como conferir | Discussões do tipo "eu não comprei isso" |
| Caderno sem cópia | Se perder ou molhar, perde-se todo o dinheiro a receber |

## 3. Requisitos levantados
**Funcionais**
- RF01: Cadastrar, editar, buscar e excluir clientes, com limite de crédito.
- RF02: Registrar compras no fiado, impedindo que a dívida passe do limite.
- RF03: Registrar pagamentos parciais ou totais, impedindo pagar mais do que a dívida.
- RF04: Mostrar o extrato de cada cliente (compras, pagamentos, dívida e limite disponível).
- RF05: Classificar o cliente como em dia, devendo ou inadimplente (mais de 30 dias).
- RF06: Gerar relatório de dívidas por período e situação, com impressão.
- RF07: Ranking de confiabilidade dos clientes.
- RF08: Dois perfis de funcionário: administrador e atendente.
- RF09: O cliente pode consultar somente a própria dívida, com uma conta única pelo CPF que mostra todas as lojas onde ele compra (cada loja confirma que é ele).
- RF10: Várias lojas na mesma plataforma, com endereço, equipe e dados separados, e um painel para o dono da plataforma.
- RF11: Lista de cobranças (quem vence nos próximos 3 dias e quem está atrasado), com a mensagem pronta para o WhatsApp.
- RF12: Pagamento pelo Pix com QR Code, com o valor da dívida já preenchido.
- RF13: Endereço preenchido pelo CEP e uma API para outros programas lerem os dados da loja.

**Não funcionais**
- RNF01: Funcionar no navegador do computador e do celular (layout responsivo).
- RNF02: Custo zero de hospedagem.
- RNF03: Senhas guardadas com hash; proteção contra SQL Injection e CSRF.
- RNF04: Interface simples, em português, para quem não tem prática com sistemas.
- RNF05: Toda mudança é testada automaticamente e analisada pelo Snyk antes de chegar ao site oficial, passando por um site de teste.

## 4. A solução aplicada
1. O dono (administrador) cadastrou os clientes e definiu um limite para cada um, de R$ 200 a R$ 500.
2. A atendente passou a lançar cada compra pelo celular no balcão. Quando um cliente tenta passar do limite, o sistema bloqueia e mostra quanto ainda está disponível.
3. Toda semana o dono abre o **Relatório de dívidas** filtrando "inadimplentes" e sabe exatamente quem cobrar.
4. Os clientes criaram a conta pelo CPF e conferem a própria dívida pelo celular, e as discussões no balcão acabaram.
5. Na tela **Cobranças**, o dono vê quem vence nos próximos dias e manda a cobrança pelo WhatsApp com um clique. O cliente paga pelo **Pix** lendo o QR Code, e a loja registra o pagamento depois de conferir no banco.

## 5. Exemplo com os dados do sistema
| Cliente | Limite | Compras | Pagamentos | Dívida | Situação |
|---|---|---|---|---|---|
| João Silva | R$ 500 | R$ 60,00 (há 10 e 5 dias) | — | R$ 60,00 | Devendo |
| Maria Souza | R$ 300 | R$ 20,00 (há 3 dias) | R$ 10,00 | R$ 10,00 | Devendo |
| Carlos Pereira | R$ 400 | R$ 180,00 (há 45 dias) | R$ 50,00 | R$ 130,00 | **Inadimplente** |
| Ana Lima | R$ 200 | R$ 60,00 (há 20 dias) | R$ 60,00 | R$ 0,00 | Em dia |

Sem o sistema, a compra de Carlos, de 45 dias atrás, poderia ter sido esquecida no caderno. Com o FiadoFácil, ele aparece em vermelho como inadimplente e no fim do ranking.

**Teste do limite:** se a atendente tentar lançar mais R$ 250 para Ana Lima (limite de R$ 200), o sistema recusa: *"Limite de crédito excedido. Limite: R$ 200,00 | Dívida atual: R$ 0,00 | Disponível: R$ 200,00"*.

## 6. Resultados esperados
- Total a receber **sempre atualizado**, sem fazer contas.
- **Menos inadimplência**, graças ao limite por cliente e à lista de atrasados.
- **Menos conflito** com clientes, que conferem o próprio extrato.
- **Nenhum dado perdido**: tudo fica no banco de dados on-line, acessível de qualquer aparelho.
- **Custo zero**: hospedagem gratuita e nenhuma instalação.

## 7. Várias lojas na mesma plataforma
O FiadoFácil foi feito para atender várias lojas ao mesmo tempo. Cada comércio tem o seu endereço (por exemplo `/loja/mercearia-do-ze`), o seu login, os seus dados e o seu visual, e nenhuma loja enxerga os dados das outras. O dono da plataforma acompanha todas pelo painel: cria lojas, bloqueia e entra em qualquer uma como administrador.

Lojas de exemplo para a demonstração: Mercearia do Zé, Padaria Pão Quente e Açougue Bom Corte.

## 8. Integrações
| Integração | Para que serve no dia a dia |
|---|---|
| **Pix** (padrão do Banco Central) | O cliente paga lendo o QR Code, com o valor já preenchido; o dinheiro cai direto na conta da loja |
| **WhatsApp** | A cobrança abre pronta, com valor, prazo e chave Pix; a loja só aperta enviar |
| **ViaCEP** | O endereço do cliente se preenche pelo CEP no cadastro |
| **API própria** | Outro programa lê os dados da loja com uma chave de acesso (detalhes em [API.md](API.md)) |

## 9. Qualidade e segurança
- Toda mudança vai primeiro para um **site de teste**, com banco próprio, e só chega ao site oficial depois de aprovada num Pull Request.
- **Testes automáticos** num navegador de verdade conferem as regras de negócio, a conta por CPF, a API e o isolamento entre as lojas a cada mudança.
- O **Snyk Code** analisa o código a cada envio. Na primeira análise achou 2 problemas de gravidade média, que foram corrigidos; hoje não há nenhum.

## 10. Diagramas relacionados
Veja [DIAGRAMAS.md](DIAGRAMAS.md): banco de dados (DER/UML), casos de uso, fluxo geral, fluxo da compra e do pagamento, estados do cliente e arquitetura.
