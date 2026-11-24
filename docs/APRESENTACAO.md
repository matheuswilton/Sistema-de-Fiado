# Roteiro da demonstração ao vivo (computador + celular)

Site: **https://fiadofacil.free.je** · Tempo total: cerca de 5 minutos

## Antes da apresentação (no dia)
- [ ] Abrir o site no **computador** e no **celular** uns minutos antes: a hospedagem gratuita pode demorar no primeiro acesso.
- [ ] Testar o login do administrador da Mercearia do Zé, de um atendente e do cliente por CPF.
- [ ] Deixar o **app do banco** aberto no celular, para ler o QR Code do Pix (só ler, sem pagar).
- [ ] No celular, usar **dados móveis** como plano B caso o Wi-Fi da sala falhe.
- [ ] Para mostrar o celular no projetor: espelhar a tela (Android: "Transmitir tela"; iPhone: "Espelhar tela") ou abrir o site no computador com **F12 → modo dispositivo** (Ctrl+Shift+M).
- [ ] **Plano C:** ter o XAMPP com o projeto rodando em `localhost` e um vídeo curto da demonstração gravado.

> As senhas das contas de exemplo não ficam neste arquivo público: estão guardadas com o Matheus. Nunca mostre uma senha real no projetor.

## 1. Página inicial e entrada (cerca de 1 minuto)
1. Abra **fiadofacil.free.je** e role a página devagar: a cena do caderninho que vira o FiadoFácil no celular **acompanha a rolagem**. *"Descendo, a história avança; subindo, ela volta."*
2. Clique em **Entrar**: o sistema pergunta **"Qual é a sua loja?"**. Digite "mercearia" (sem acento funciona) e mostre o login da loja, com a **foto da Mercearia do Zé** no fundo.

## 2. No computador, como administrador da Mercearia (cerca de 2 minutos)
1. **Início:** total a receber, clientes devendo, inadimplentes e o aviso **"Hora de cobrar"**. Mostre o **menu enxuto**: "Nova venda", 4 itens principais e 2 secundários.
2. **Clientes** → abra **Lúcia Santos**: ela aparece como **inadimplente** (dívida com mais de 30 dias).
3. **Cobrar no WhatsApp:** clique no botão e mostre a mensagem pronta, com o valor, o prazo e a chave Pix. Feche sem enviar. *"Nada sai sem o comerciante apertar enviar."*
4. **Nova venda → Registrar compra** para **João Pereira** com um valor acima do disponível (por exemplo, R$ 400): o sistema **recusa** e mostra o limite, a dívida e o disponível. Depois lance um valor pequeno: aceito, com a janela de confirmação.
5. **Relatórios:** mostre as abas **Relatório, Ranking e Resumo**. No relatório, filtre "inadimplente" e mostre a versão para imprimir.
6. **Cadastrar cliente:** digite um CEP de Goiânia e mostre o endereço se preenchendo sozinho (**ViaCEP**). Cancele sem salvar.

## 3. No celular (cerca de 1 minuto)
1. Mostre o **menu do celular** (botão "Menu") e o botão **"Nova venda"**.
2. Saia e clique em **"Sou cliente"**: entre com o **CPF do cliente de exemplo**. A tela **"Minhas dívidas"** mostra a **Mercearia e a Padaria juntas**, com o total e o limite de cada loja.
3. Abra **"Pagar com Pix"** e leia o QR Code com o app do banco: o valor já vem preenchido. *"O Pix cai direto na conta da loja."*
4. Volte ao computador e atualize o extrato: a compra lançada aparece. *"Os dois aparelhos usam o mesmo banco, em tempo real."*

## 4. Várias lojas (cerca de 1 minuto)
1. Abra `fiadofacil.free.je/loja/padaria-pao-quente` numa aba anônima: outra foto, outra cor de destaque e **outros clientes**.
2. Mostre que a Mercearia não vê nada da Padaria: *"Toda consulta filtra pela loja de quem está logado."*
3. Entre no `painel.php` como dono da plataforma: todas as lojas, com os totais, o botão para bloquear e a entrada em qualquer uma como administrador.

## Se perguntarem
- **"E a API?"** A hospedagem gratuita bloqueia chamadas que não vêm do navegador, então a API não roda ao vivo no site. Mostre a tela **Loja e equipe → Minha loja** (onde a chave é gerada) e o exemplo de resposta em [API.md](API.md).
- **"Como você garante que nada quebra?"** Toda mudança vai primeiro para o site de teste, passa pelos testes automáticos e pela análise de segurança do Snyk, e só chega ao site oficial por um Pull Request.
