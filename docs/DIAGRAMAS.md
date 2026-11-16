# Diagramas do FiadoFácil

Os diagramas estão em [Mermaid](https://mermaid.js.org/), que o GitHub desenha automaticamente ao abrir este arquivo.
Para exportar como imagem (PNG/SVG) para slides: cole o bloco em https://mermaid.live e clique em **Actions → PNG/SVG**.

---

## 1. Diagrama do banco de dados (UML / DER)

Modelo entidade-relacionamento das 4 tabelas do arquivo [`fiadofacil/sql/banco.sql`](../fiadofacil/sql/banco.sql).

```mermaid
erDiagram
    CLIENTES ||--o{ COMPRAS : "faz"
    CLIENTES ||--o{ PAGAMENTOS : "realiza"
    CLIENTES |o--o{ USUARIOS : "tem acesso (perfil cliente)"

    CLIENTES {
        INT id PK "AUTO_INCREMENT"
        VARCHAR nome "(100) NOT NULL"
        VARCHAR telefone "(20)"
        VARCHAR endereco "(200)"
        DECIMAL limite_credito "(10,2) DEFAULT 500.00"
        DATETIME criado_em "DEFAULT CURRENT_TIMESTAMP"
    }
    USUARIOS {
        INT id PK "AUTO_INCREMENT"
        VARCHAR nome "(100) NOT NULL"
        VARCHAR usuario UK "(50) NOT NULL UNIQUE"
        VARCHAR senha "(255) hash bcrypt"
        ENUM nivel "admin | comum | cliente"
        ENUM status "pendente | aprovado | recusado | bloqueado"
        INT cliente_id FK "NULL, ON DELETE CASCADE"
        VARCHAR telefone_solicitado "(20) NULL"
        DATETIME criado_em
    }
    COMPRAS {
        INT id PK "AUTO_INCREMENT"
        INT cliente_id FK "NOT NULL, ON DELETE CASCADE"
        VARCHAR descricao "(200)"
        DECIMAL valor "(10,2) NOT NULL"
        DATETIME data_compra "DEFAULT CURRENT_TIMESTAMP"
    }
    PAGAMENTOS {
        INT id PK "AUTO_INCREMENT"
        INT cliente_id FK "NOT NULL, ON DELETE CASCADE"
        DECIMAL valor "(10,2) NOT NULL"
        DATETIME data_pagamento "DEFAULT CURRENT_TIMESTAMP"
    }
```

**Cardinalidades**
- Um **cliente** tem 0..N **compras** e 0..N **pagamentos**. Cada compra/pagamento pertence a exatamente 1 cliente.
- Um **usuário** com `nivel = 'cliente'` aponta para 1 cliente (`cliente_id`). Funcionários (`admin`/`comum`) têm `cliente_id` nulo.
- Ao excluir um cliente, as compras, os pagamentos e o acesso dele são apagados (`ON DELETE CASCADE`).

**Por que não existe coluna "dívida"?** A dívida é sempre **calculada**: `SUM(compras.valor) − SUM(pagamentos.valor)`. Assim ela nunca fica desatualizada ou inconsistente.

### Diagrama de classes UML (mesmo modelo, notação UML)

```mermaid
classDiagram
    direction LR
    class Cliente {
        +int id
        +string nome
        +string telefone
        +string endereco
        +decimal limite_credito
        +datetime criado_em
        +saldo() decimal
        +situacao() string
    }
    class Usuario {
        +int id
        +string nome
        +string usuario
        -string senha_hash
        +enum nivel
        +enum status
        +int cliente_id
        +string telefone_solicitado
    }
    class Compra {
        +int id
        +int cliente_id
        +string descricao
        +decimal valor
        +datetime data_compra
    }
    class Pagamento {
        +int id
        +int cliente_id
        +decimal valor
        +datetime data_pagamento
    }
    Cliente "1" *-- "0..*" Compra : faz
    Cliente "1" *-- "0..*" Pagamento : realiza
    Cliente "0..1" <-- "0..*" Usuario : acessa
```

---

## 2. Casos de uso (quem faz o quê)

```mermaid
flowchart LR
    FUN([👤 Atendente])
    ADM([👤 Administrador])
    CLI([👤 Cliente])
    ADM -- "herda tudo do atendente" --> FUN

    subgraph S1 [Atendente e Administrador]
        UC2(Cadastrar e editar cliente)
        UC3(Buscar cliente)
        UC4(Registrar compra no fiado)
        UC5(Registrar pagamento)
        UC6(Ver extrato do cliente)
        UC7(Relatório de dívidas e ranking)
    end
    subgraph S2 [Somente Administrador]
        UC8(Alterar limite de crédito)
        UC9(Excluir cliente)
        UC10(Resumo financeiro)
        UC11(Gerenciar funcionários)
        UC12(Aprovar acesso de clientes)
    end
    subgraph S3 [Cliente]
        UC13(Solicitar acesso)
        UC14(Ver a própria dívida)
    end

    FUN --- UC2 & UC3 & UC4 & UC5 & UC6 & UC7
    ADM --- UC8 & UC9 & UC10 & UC11 & UC12
    CLI --- UC13 & UC14
```

> Todos os perfis fazem login e trocam a própria senha.

---

## 3. Fluxo geral do sistema (navegação)

```mermaid
flowchart TD
    A[Abrir o site] --> B[index.php] --> C{Já está logado?}
    C -- Não --> D[login.php]
    C -- Sim --> E{Qual perfil?}
    D --> D1{Usuário e senha corretos?}
    D1 -- Não --> D
    D1 -- Sim --> D2{Status aprovado?}
    D2 -- "pendente / recusado / bloqueado" --> D
    D2 -- Sim --> E
    D -. "Sou cliente" .-> S[solicitar_acesso.php] --> P[(status = pendente)]
    P --> AP[Admin: aprovar_acessos.php] --> D

    E -- cliente --> MD[minha_divida.php<br/>somente leitura]
    E -- "admin / comum" --> DB[dashboard.php]

    DB --> L[Listar / buscar clientes]
    DB --> CC[Cadastrar cliente]
    DB --> RC[Registrar compra]
    DB --> RP[Registrar pagamento]
    DB --> RL[Relatório de dívidas]
    DB --> RK[Ranking]
    L --> X[cliente.php<br/>extrato]
    X --> RC & RP & ED[Editar cliente]

    DB -. somente admin .-> RS[Resumo financeiro]
    DB -. somente admin .-> US[Gerenciar funcionários]
    DB -. somente admin .-> AC[Acessos de clientes]
```

---

## 4. Fluxo de uma compra no fiado (regra principal)

```mermaid
flowchart TD
    A[Atendente escolhe o cliente<br/>e digita o valor] --> B{Valor > 0?}
    B -- Não --> E1[❌ Erro: valor inválido]
    B -- Sim --> C[Inicia transação e trava<br/>a linha do cliente FOR UPDATE]
    C --> D[Calcula dívida atual<br/>compras − pagamentos]
    D --> F{dívida + valor ≤ limite?}
    F -- Não --> E2[❌ ROLLBACK<br/>Mostra limite, dívida e disponível]
    F -- Sim --> G[INSERT em compras]
    G --> H[✅ COMMIT<br/>Mostra a nova dívida]
```

## 5. Fluxo de um pagamento

```mermaid
flowchart TD
    A[Atendente informa o valor<br/>ou clica em Quitar dívida inteira] --> B{Valor > 0?}
    B -- Não --> E1[❌ Erro]
    B -- Sim --> C[Transação + trava do cliente]
    C --> D{Cliente tem dívida?}
    D -- Não --> E2[❌ Cliente sem dívida]
    D -- Sim --> F{valor ≤ dívida?}
    F -- Não --> E3[❌ Pagamento maior que a dívida]
    F -- Sim --> G[INSERT em pagamentos + COMMIT]
    G --> H{Sobrou dívida?}
    H -- Não --> I[✅ Dívida quitada!]
    H -- Sim --> J[✅ Mostra quanto ainda resta]
```

## 6. Situação do cliente (estados)

```mermaid
stateDiagram-v2
    [*] --> EmDia : cliente cadastrado
    EmDia --> Devendo : registra compra
    Devendo --> EmDia : paga tudo
    Devendo --> Inadimplente : compra mais antiga com mais de 30 dias
    Inadimplente --> Devendo : paga as compras antigas
    Inadimplente --> EmDia : paga tudo
```

## 7. Sequência: cliente solicita acesso e vê a dívida

```mermaid
sequenceDiagram
    actor C as Cliente
    participant S as Sistema (PHP)
    participant BD as MySQL
    actor A as Administrador
    C->>S: solicitar_acesso.php (nome, telefone, usuário, senha)
    S->>BD: INSERT usuarios (nivel=cliente, status=pendente)
    A->>S: aprovar_acessos.php
    S->>BD: busca cliente pelo telefone (sugestão)
    A->>S: Aprovar (escolhe o cliente)
    S->>BD: UPDATE usuarios SET status=aprovado, cliente_id=...
    C->>S: login.php
    S->>BD: password_verify + status
    S-->>C: minha_divida.php (só os dados dele)
```

## 8. Arquitetura

```mermaid
flowchart LR
    subgraph Dispositivos
        PC[💻 Navegador no computador]
        CEL[📱 Navegador no celular]
    end
    subgraph Hospedagem InfinityFree / XAMPP
        AP[Apache + PHP 8<br/>páginas .php, CSS, JS]
        DB[(MySQL<br/>sistema_fiado)]
    end
    PC -- HTTPS --> AP
    CEL -- HTTPS --> AP
    AP -- mysqli + prepared statements --> DB
```
