# Referência da API do FiadoFácil

API REST **somente leitura** que entrega os dados de **uma loja** em JSON, para outro programa usar (um app, uma planilha, outro sistema).

- **Endereço:** `https://fiadofacil.free.je/api.php`
- **Método:** só `GET`
- **Formato:** JSON (UTF-8)

> **Atenção:** a hospedagem gratuita (InfinityFree) tem uma proteção contra robôs que bloqueia chamadas feitas fora do navegador. A API funciona completa no XAMPP e em hospedagens pagas.

## Autenticação

1. O administrador da loja abre **Minha loja → API da loja → Ligar a API (gerar chave)**.
2. A chave aparece **uma vez só**, no formato `ff_` seguido de 48 letras e números. Copie e guarde.
3. Envie a chave em todas as chamadas, no cabeçalho **`X-Api-Key`**.

A loja é identificada pela chave: cada chave só enxerga os dados da própria loja. No banco fica só o **hash SHA-256** da chave. Gerar uma chave nova cancela a anterior, e **Desligar a API** cancela a chave na hora.

```bash
curl -H "X-Api-Key: ff_SUA_CHAVE" "https://fiadofacil.free.je/api.php?recurso=resumo"
```

## Recursos

### `GET api.php?recurso=resumo`
Totais da loja.

```json
{
    "loja": { "nome": "Mercearia do Zé", "endereco": "/loja/mercearia-do-ze" },
    "clientes": 3,
    "clientes_devendo": 2,
    "inadimplentes": 1,
    "total_a_receber": 114.5,
    "gerado_em": "2026-11-20T07:15:33-03:00"
}
```

### `GET api.php?recurso=clientes`
Todos os clientes da loja, de quem deve mais para quem deve menos.

```json
{
    "loja": { "nome": "Mercearia do Zé", "endereco": "/loja/mercearia-do-ze" },
    "total": 3,
    "clientes": [
        {
            "id": 5,
            "nome": "João Pereira",
            "telefone": "(62) 98111-0001",
            "limite_credito": 400.0,
            "divida": 68.5,
            "situacao": "devendo",
            "dias_devendo": 13
        }
    ]
}
```

`situacao` é `em_dia`, `devendo` ou `inadimplente` (compra mais antiga não paga com mais de 30 dias).

### `GET api.php?recurso=cliente&id=5`
Dados e extrato de um cliente (até as 100 compras e os 100 pagamentos mais recentes).

```json
{
    "loja": { "nome": "Mercearia do Zé", "endereco": "/loja/mercearia-do-ze" },
    "cliente": {
        "id": 5,
        "nome": "João Pereira",
        "telefone": "(62) 98111-0001",
        "limite_credito": 400.0,
        "divida": 68.5,
        "limite_disponivel": 331.5,
        "cliente_desde": "2026-11-19 10:00:00"
    },
    "compras": [
        { "data": "2026-11-15 10:12:00", "descricao": "Verduras e legumes", "valor": 23.5 }
    ],
    "pagamentos": [
        { "data": "2026-11-13 09:40:00", "valor": 40.0 }
    ]
}
```

## Códigos de resposta

| Código | Quando | Exemplo de resposta |
|---|---|---|
| 200 | Deu certo | os dados pedidos |
| 400 | Recurso desconhecido | `{"erro": "Recurso desconhecido. Use resumo, clientes ou cliente&id=N."}` |
| 401 | Sem chave, chave em formato errado, inválida ou cancelada | `{"erro": "Chave inválida ou cancelada."}` |
| 403 | A loja está bloqueada | `{"erro": "Loja bloqueada."}` |
| 404 | O cliente não existe **nesta loja** | `{"erro": "Cliente não encontrado nesta loja."}` |
| 405 | Método diferente de GET | `{"erro": "Use o método GET. A API é somente leitura."}` |

Um cliente de outra loja recebe **404**, como se não existisse: a API não revela nem que o número existe.

## Boas práticas para quem usa
- Guarde a chave fora do código (variável de ambiente ou arquivo de configuração).
- Nunca coloque a chave no JavaScript de uma página pública nem na URL.
- Se a chave vazar, gere uma nova em **Minha loja**: a antiga para de funcionar na hora.
