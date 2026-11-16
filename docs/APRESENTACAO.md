# Roteiro da demonstração ao vivo (computador + celular)

Site: **https://fiadofacil.free.je**

## Antes da apresentação (no dia)
- [ ] Abrir o site no **computador** e no **celular** e confirmar que está no ar (a InfinityFree às vezes fica lenta; abra uns minutos antes).
- [ ] Testar o login de admin, de atendente e de um cliente.
- [ ] Deixar já criado um **acesso de cliente aprovado** (ex.: para João Silva) e um **pendente** para aprovar ao vivo.
- [ ] No celular, usar **dados móveis** como plano B caso o Wi-Fi da sala falhe.
- [ ] Para mostrar o celular no projetor: espelhar a tela (Android: "Transmitir tela"; iPhone: "Espelhar tela") ou abrir o site no computador com **F12 → modo dispositivo** (Ctrl+Shift+M).
- [ ] **Plano C:** ter o XAMPP com o projeto rodando em `localhost` e prints/vídeo curto da demo gravados.

> Se tiverem trocado as senhas de exemplo no site (o recomendado), usem as novas. Não mostrem senhas reais no projetor.

## Demo (cerca de 3 minutos)
**No computador, como Administrador**
1. Login → **Painel** (totais a receber, clientes devendo e inadimplentes).
2. **Clientes** → buscar "Carlos" → abrir o extrato: aparece como **inadimplente** (compra de 45 dias).
3. **Registrar compra** para Ana Lima de R$ 250 → o sistema **bloqueia por limite**. Lançar R$ 50 → aceito.
4. **Registrar pagamento** → "Quitar dívida inteira".
5. **Relatório de dívidas** → filtrar "inadimplentes" → mostrar a versão de impressão.
6. **Ranking** de confiabilidade.
7. **Aprovar acessos** → aprovar a solicitação pendente.

**No celular**
8. Abrir o site: mostrar o **menu responsivo** (botão ☰).
9. Login como **Atendente** → lançar uma compra rápida no balcão. Mostrar que o atendente **não** vê "Resumo" nem "Usuários".
10. Sair → login como **Cliente** → **Minha dívida**: o cliente vê só os dados dele.

**Fechamento**
11. Voltar ao computador e atualizar o extrato: a compra feita no celular já aparece. *"Os dois aparelhos usam o mesmo banco, em tempo real."*

## Contas de exemplo
| Perfil | Usuário | Senha |
|---|---|---|
| Administrador | `admin` | `admin123` |
| Atendente | `usuario` | `user123` |
