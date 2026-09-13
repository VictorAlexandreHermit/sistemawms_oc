# DOCUMENTO DE ESPECIFICAÇÃO FUNCIONAL (FSD)

---

## 1. Visão Geral

O **WMS Agiliza (WMS Leve & Visual)** é um sistema de gerenciamento de armazém (*Warehouse Management System*) projetado para controlar, otimizar e monitorar o fluxo operacional de galpões e pátios logísticos de Pequenas e Médias Empresas (PMEs).

O objetivo central do sistema é garantir alta acuracidade de estoque (próxima a 100%), visibilidade em tempo real do tempo de atendimento (*lead time*) em cada fase logística e medição contínua da satisfação do cliente final pós-entrega através do indicador OTIF (*On-Time In-Full*).

O WMS Agiliza cobre toda a jornada operacional do galpão:

1. **Recebimento (Inbound):** Importação de arquivos XML de Notas Fiscais Eletrônicas (NF-e) e conferência cega via bipagem de código de barras USB (com opção de digitação manual).


2. **Endereçamento e Guarda (Putaway):** Alocação física estruturada por Rua, Prédio e Nível (Ex: R01-P02-N03).


3. **Operação e Kanban (Outbound):** Painel visual de tarefas operacionais ordenadas automaticamente por prioridade de Curva ABC (produtos de maior giro/valor no topo).


4. **Separação (Picking) e Embalagem (Packing):** Validação obrigatória item a item via leitura de código de barras USB antes da liberação para expedição.


5. **Gestão de Avarias e Quarentena:** Isolamento imediato de mercadorias danificadas em um endereço virtual de saldo bloqueado ("Quarentena").


6. **Avaliação OTIF e Pós-Venda:** Disparo automático de links temporários de avaliação de entregas para o cliente final via mensagens (WhatsApp ou E-mail) assim que o pedido é marcado como "Entregue".


7. **Auditoria e Ajuste de Estoque:** Registros inalteráveis por operadores para acertos manuais de saldo com justificativa padronizada.


8. **Dashboard Executivo:** Visualização consolidada de 9 indicadores estratégicos para o gestor de logística.



O sistema opera em estações fixas no galpão com computadores de mesa acoplados a leitores de código de barras USB, além de oferecer uma interface web externa ultraleve e responsiva para acesso do cliente final via dispositivos móveis.

---

## 2. Documentos do Projeto para Implementação

Para a implementação completa do sistema, a IA codificadora deverá utilizar exclusivamente os seguintes documentos de referência:

* `docs/FSD.md` (Este documento, que consolida todas as especificações funcionais, regras de negócio, arquitetura e modelo de dados);
* `docs/DESIGN.md` (Documento com as diretrizes visuais, tokens de estilo, paleta de cores e padrões de interface).

O `docs/FSD.md` é autossuficiente e consolida rigorosamente todas as definições técnicas e operacionais necessárias para a construção do sistema.

---

## 3. Stack Definida

A aplicação será desenvolvida utilizando as seguintes tecnologias e convenções técnicas:

* **Linguagem de Back-end:** PHP (versão 8.x nativa);


* **Banco de Dados:** MySQL (versão 8.0+);


* **Tecnologias de Front-end:** HTML5, CSS3, JavaScript puro (ES6+, sem frameworks complexos);


* **Framework de Estilização:** Bootstrap (versão 5.x armazenada e servida **localmente** no projeto, sem dependência de CDN externa);


* **Diretrizes Visuais:** Tema *Precision Logistics*:


* Paleta de cores baseada em *Deep Slate* (`#0F172A`) para a barra lateral e navegação principal;


* Superfície neutra em cinza frio (`#F8FAFC`) para cartões e tabelas (`#FFFFFF` com borda de 1px em `#E2E8F0`);


* Tipografia **Inter** com suporte obrigatório a *tabular figures* (`tnum`) para alinhamento vertical exato de códigos SKU e quantidades;




* **Padrão Arquitetural:** Organização inspirada em **MVC (Model-View-Controller)** para separação estrita de responsabilidades;


* **Restrições Técnicas:**
* Uso obrigatório de bibliotecas e recursos locais (scripts, CSS e fontes hospedados no próprio servidor);


* Ausência de dependência de gerenciadores de pacotes externos em tempo de execução no servidor final.





---

## 4. Ambientes do Projeto

O ciclo de vida de desenvolvimento e hospedagem do projeto contempla os seguintes ambientes:

1. **Ambiente de Desenvolvimento Local:**
* Servidor local utilizando **XAMPP** com PHP e MySQL;


* Configuração de servidor web Apache com reescrita de URLs e proteção de pastas internas.




2. **Ambiente de Testes / Homologação:**
* Não haverá ambiente online obrigatório de testes na primeira versão (V1);


* Todas as validações funcionais, de banco de dados e de segurança deverão ser executadas localmente no ambiente XAMPP antes de qualquer publicação.




3. **Ambiente de Produção:**
* Hospedagem web com suporte a PHP e MySQL na plataforma **InfinityFree**;


* Publicação via upload seguro de arquivos e execução controlada de migrations de banco de dados.





---

## 5. Arquitetura do Sistema

### 5.1 Organização Geral e Padrão MVC

O sistema adota uma estrutura em camadas inspirada no padrão **MVC (Model-View-Controller)**:

* **Model:** Camada responsável pelas regras de negócio, validações de saldo, consultas ao banco de dados MySQL, cálculos de prioridade da Curva ABC e controle de transações.


* **View:** Camada responsável pela renderização da interface web responsiva utilizando Bootstrap local e estilo *Precision Logistics*. Recebe os dados processados e exibe elementos visuais, formulários e tabelas.


* **Controller:** Camada responsável pelo recebimento de requisições HTTP, interpretação das ações do usuário, invocação dos métodos do Model correspondente e seleção da View a ser apresentada.



### 5.2 Estrutura do Diretório do Projeto

A referência principal de diretório no repositório do projeto é denominada `[Diretório do Projeto - Repositório]`. O projeto deve funcionar de forma independente sem assumir que ocupa sozinho a raiz pública do servidor web (como `htdocs`, `www` ou `public_html`), permitindo a alocação em subpastas em ambientes locais ou de hospedagem (ex: `htdocs/wms-agiliza/` ou `www/wms-agiliza/`).

```text
[Diretório do Projeto - Repositório]
├── index.php                 # Arquivo de entrada único da aplicação (Front Controller)
├── .htaccess                 # Regras de reescrita Apache e bloqueio de pastas internas
├── config/                   # Configurações globais e de banco de dados (PHP em código)
│   ├── config.php            # Credenciais do banco, chaves e parâmetros globais
│   └── database.php          # Conexão PDO segura com MySQL
├── app/                      # Código-fonte principal da aplicação
│   ├── controllers/          # Controllers (Auth, Recebimento, Guarda, Kanban, etc.)
│   ├── models/               # Models (Usuario, Produto, Endereco, Estoque, Pedido, etc.)
│   ├── views/                # Views em PHP/HTML divididas por módulos
│   │   ├── auth/             # Login e troca de senha
│   │   ├── recebimento/      # Importação XML e Conferência Cega
│   │   ├── guarda/           # Instruções de Putaway
│   │   ├── kanban/           # Quadro operacional de tarefas
│   │   ├── picking/          # Conferência de separação e embalagem
│   │   ├── avarias/          # Quarentena e fotos
│   │   ├── otif/             # Formulário externo do cliente final
│   │   ├── auditoria/        # Ajustes e histórico de estoque
│   │   ├── dashboard/        # Indicadores executivos
│   │   └── templates/        # Header, Footer, Sidebar (Precision Logistics)
│   └── helpers/              # Funções auxiliares (Sessão, Sanitização, Upload, APIs)
├── database/                 # Arquivos relacionados ao banco de dados
│   └── migrations/           # Scripts versionados de criação e atualização de tabelas
├── assets/                   # Arquivos públicos e recursos estáticos
│   ├── css/                  # Bootstrap local e estilos customizados (Precision Logistics)
│   ├── js/                   # Scripts JavaScript puros (bipagem, Kanban, interações)
│   └── fonts/                # Arquivos da tipografia Inter
├── uploads/                  # Pasta privada de armazenamento de arquivos anexados
│   ├── avarias/              # Imagens de avarias registradas internamente
│   └── otif/                 # Imagens anexadas pelos clientes no pós-venda
└── logs/                     # Armazenamento seguro de logs de contingência em arquivo
    ├── error.log             # Log de falhas técnicas do sistema
    └── security.log          # Log de eventos de segurança e acessos negados

```

### 5.3 Proteção de Pastas e Arquivos Internos

1. **Arquivo de Entrada (`index.php`):** Atua como *Front Controller*. Todas as requisições web devem ser direcionadas obrigatoriamente para este arquivo, que trata o roteamento, inicializa a sessão e carrega o *Controller* correto.
2. **Proteção por `.htaccess`:** A pasta raiz do projeto deve conter um arquivo `.htaccess` impedindo a listagem de diretórios (`Options -Indexes`) e bloqueando o acesso público direto a pastas sensíveis (`config/`, `app/`, `database/`, `uploads/`, `logs/`).
3. **Validação Interna de Código:** Todos os arquivos incluídos dentro de `app/`, `config/` e `database/` devem conter uma verificação de constante de controle no topo do script para impedir a execução direta via navegação por URL:
```php
<?php
if (!defined('WMS_EXEC')) {
    http_response_code(403);
    die('Acesso direto não permitido.');
}

```



---

## 6. Escopo Funcional da Primeira Versão

### Módulo 1: Autenticação e Sessão

* **Objetivo:** Garantir a identificação individual de operadores e gestores no ambiente interno, além de fornecer acesso restrito por token temporário para o cliente final.


* **Ações Permitidas:** Login por matrícula e senha, logout seguro e validação de token externo em link temporário.



### Módulo 2: Recebimento e Entrada (Inbound)

* **Objetivo:** Processar o recebimento de cargas a partir da importação de XML da NF-e e validar as quantidades físicas recebidas através de conferência cega por bipagem USB ou digitação manual.


* **Ações Permitidas:** Upload de XML de NF-e, execução de conferência cega, registro de divergências para a fila de aprovação do Gestor e autorização de entrada de estoque.



### Módulo 3: Endereçamento e Guarda (Putaway)

* **Objetivo:** Organizar e direcionar o armazenamento físico das mercadorias conferidas na estrutura de endereços do galpão (Rua - Prédio - Nível).


* **Ações Permitidas:** Cadastro e consulta de endereços físicos, geração automática de instruções de guarda e confirmação de alocação física por bipagem USB/manual.



### Módulo 4: Operação e Kanban (Outbound)

* **Objetivo:** Fornecer um painel visual de gerenciamento das tarefas do galpão organizadas nas colunas *Recebido* ➔ *A Armazenar* ➔ *A Separar* ➔ *A Expedir*, com ordenação por Curva ABC e controle visual de SLA/lead time.


* **Ações Permitidas:** Visualização dos cards de tarefas, movimentação entre colunas, priorização automática de Curva A no topo e alteração visual de borda conforme o limite de tempo estipulado.



### Módulo 5: Separação (Picking), Embalagem (Packing) e Expedição

* **Objetivo:** Garantir a conferência física total de cada item do pedido antes de autorizar a saída e embalagem do material.


* **Ações Permitidas:** Leitura obrigatória código a código via USB (ou manual), validação de 100% dos itens da ordem, liberação de expedição e alteração do status para "Entregue".



### Módulo 6: Quarentena e Avarias

* **Objetivo:** Isolar produtos danificados identificados no recebimento ou na separação, impedindo sua venda ou saída indevida.


* **Ações Permitidas:** Registro de avarias com quantidade e fotos opcionais, transferência automática para o endereço virtual bloqueado "Quarentena" e histórico de danos.



### Módulo 7: Avaliação OTIF e Pós-Venda

* **Objetivo:** Medir o indicador de entregas perfeitas (*On-Time In-Full*) coletando o feedback do cliente final através de um formulário externo simplificado.


* **Ações Permitidas:** Disparo automático de link de avaliação via API externa (WhatsApp ou E-mail) ao marcar o pedido como "Entregue", resposta de questionário de 3 perguntas (Prazo, Avaria, Conformidade) com anexo de até 2 fotos pelo cliente final, e expiração automática do link após 10 dias úteis.



### Módulo 8: Auditoria e Ajuste de Estoque

* **Objetivo:** Manter o histórico inalterável de acertos manuais de quantidade no estoque executados pela operação.


* **Ações Permitidas:** Realização de ajustes manuais com justificativa padronizada obrigatória e consulta/gestão exclusiva de histórico pelo Gestor.



### Módulo 9: Dashboard Executivo

* **Objetivo:** Apresentar graficamente ao Gestor os 9 indicadores vitais da operação de logística.


* **Ações Permitidas:** Leitura e filtragem dos indicadores operacionais de galpão e pós-venda.



---

## 7. Fora de Escopo

Os seguintes recursos estão ativamente desconsiderados e **não devem ser desenvolvidos** na V1:

* Módulo fiscal, faturamento, emissão de NF-e, apuração de impostos e financeiro (DRE, contas a pagar/receber);


* Integração com hardwares industriais (leitores RFID, esteiras automatizadas, balanças integradas ou WCS);


* Módulo interno de criação, estilização ou impressão de etiquetas de código de barras;


* Algoritmo tridimensional de otimização de rota física de separação (*picking path*);


* Agendamento automatizado de inventário cíclico programado por amostragem;


* Alertas automáticos de estoque mínimo, ponto de ressuprimento ou validade de produto;


* Algoritmos de previsão de demanda ou compras baseados em inteligência artificial;


* Exportações de relatórios em arquivos CSV, Excel ou PDF.



---

## 8. Perfis de Usuário e Permissões

O sistema possui 3 perfis bem definidos:

1. **Operador:** Profissional de galpão responsável pelas tarefas executivas de entrada, conferência, guarda, separação, embalagem, avarias e ajustes.


2. **Gestor:** Supervisor responsável pela administração operacional, aprovação de exceções, parametrização de tempo, auditoria e análise de indicadores.


3. **Cliente Final:** Usuário externo com acesso restrito via link/token à página de avaliação OTIF do seu pedido.



### Matriz de Permissões de Acesso (RBAC)

| Módulo / Funcionalidade | Operador | Gestor | Cliente Final |
| --- | --- | --- | --- |
| Login por Matrícula e Senha | Permite | Permite | Bloqueado |
| Importação de XML de NF-e | Permite | Permite | Bloqueado |
| Conferência Cega no Recebimento | Permite | Permite | Bloqueado |
| Aprovação de Divergências no Recebimento | Bloqueado | Permite | Bloqueado |
| Cadastro e Consulta de Endereços Físicos | Permite | Permite | Bloqueado |
| Visualização e Movimentação no Kanban | Permite | Permite | Bloqueado |
| Reconfiguração dos Limites de SLA do Kanban | Bloqueado | Permite | Bloqueado |
| Validação de Picking e Packing por Bipagem | Permite | Permite | Bloqueado |
| Registro Interno de Avarias e Fotos | Permite | Permite | Bloqueado |
| Ajuste Manual de Estoque com Justificativa | Permite | Permite | Bloqueado |
| Gestão e Edição do Log de Auditoria | Bloqueado | Permite | Bloqueado |
| Visualização do Dashboard Executivo (9 Indicadores) | Bloqueado | Permite | Bloqueado |
| Acesso ao Formulário de Avaliação OTIF (via Token) | Bloqueado | Bloqueado | Permite |

---

## 9. Recursos Estruturais do Sistema

### 9.1 Autenticação e Sessão

* Acesso interno baseado em matrícula de usuário e senha tratada via HASH seguro (`password_hash` com algoritmos `PASSWORD_BCRYPT` ou `PASSWORD_ARGON2I`).
* Controle de sessão no PHP com tempo de inatividade limite de 8 horas e destruição automática de dados no logout.
* Acesso do Cliente Final protegido por token alfanumérico único gravado no pedido e enviado via URL.

### 9.2 Soft Delete (Exclusão Lógica)

* Aplicação de exclusão lógica nos cadastros de Produtos, Endereços e Usuários através da coluna `deleted_at`.
* Registros excluídos são ocultados de pesquisas operacionais e seleções do sistema, preservando o histórico e a integridade relacional.

### 9.3 Logs de Erro com Contingência em Arquivo

* O sistema deve capturar exceções não tratadas e falhas do PHP/MySQL.
* Erros são gravados primariamente na tabela `logs_erro` no banco de dados.
* **Estratégia de Contingência:** Se a conexão com o MySQL falhar ou o banco estiver inacessível, o sistema deve gravar os detalhes do erro em um arquivo físico `logs/error.log`, fora do acesso público, contendo data/hora, mensagem técnica, arquivo e linha da ocorrência.
* O usuário final visualiza apenas uma mensagem amigável e segura ("*Ocorreu um erro temporário no processamento. A equipe técnica foi notificada.*").

### 9.4 Logs de Segurança

* Gravação obrigatória na tabela `logs_seguranca` para eventos sensíveis: falhas de login por senha incorreta, tentativas de acesso a páginas de perfil não autorizado e tentativas de edição do log de auditoria por perfil Operador.

### 9.5 Configurações Globais de SLA

* O tempo limite das etapas do Kanban (padrão inicial de 120 minutos por coluna) é armazenado na tabela `configuracoes_sla` e editável via interface exclusiva do Gestor.

### 9.6 Uploads e Anexos

* Suporte ao envio de até 2 fotos no formulário externo OTIF do Cliente Final e foto opcional no registro interno de Avarias.
* Validação rigorosa do tipo real do arquivo (MIME-type restrito a `image/jpeg` e `image/png`), limitação de tamanho (máximo 5MB por foto) e renomeação para HASH MD5/SHA256 único antes do salvamento em pasta protegida.

### 9.7 APIs e Integrações Externas

* Cliente HTTP interno acionado automaticamente na transição do status do pedido para "Entregue".
* Disparo de mensagens contendo o link com token OTIF via requisição POST para serviços externos configurados (Evolution API para WhatsApp ou Resend/SendGrid para E-mails).

---

## 10. Entidades do Sistema

### 1. `usuarios`

* **Finalidade:** Armazena as contas e credenciais dos operadores e gestores internos.


* **Atributos Principais:** Matrícula/login, senha criptografada, nome completo, perfil (OPERADOR, GESTOR).


* **Recursos:** Usa Soft Delete (`deleted_at`) e Auditoria de criação/edição (`created_at`, `created_by`, `updated_at`, `updated_by`).

### 2. `produtos`

* **Finalidade:** Catálogo dos produtos manipulados no galpão.


* **Atributos Principais:** Código SKU, código de barras EAN, descrição, unidade de medida, classificação na Curva ABC (A, B, C).


* **Recursos:** Usa Soft Delete (`deleted_at`) e Auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).

### 3. `enderecos`

* **Finalidade:** Mapeamento físico hierárquico das posições de estoque no galpão.


* **Atributos Principais:** Rua, Prédio, Nível (ex: R01-P02-N03), capacidade máxima de volumes.


* **Recursos:** Usa Soft Delete (`deleted_at`).

### 4. `estoque_saldos`

* **Finalidade:** Registra as quantidades disponíveis de cada produto em cada endereço físico.


* **Atributos Principais:** ID do Produto, ID do Endereço, Quantidade Física, Lote, Data de Validade, Status do Saldo (DISPONIVEL, QUARENTENA).


* **Recursos:** Atualização em tempo real via transações controladas.

### 5. `pedidos`

* **Finalidade:** Representa as ordens e cargas movimentadas no fluxo do galpão do recebimento à entrega.


* **Atributos Principais:** Chave da NF-e, Número da Nota, Nome do Cliente, Telefone/E-mail do Cliente, Status no Kanban (RECEBIDO, A_ARMAZENAR, A_SEPARAR, A_EXPEDIR, ENTREGUE), Prioridade ABC, Token OTIF, Timestamps de início e fim em cada etapa do Kanban.


* **Recursos:** Auditoria e vinculação com histórico de lead time.

### 6. `pedido_itens`

* **Finalidade:** Itens contidos em cada pedido.


* **Atributos Principais:** ID do Pedido, ID do Produto, Quantidade Esperada (XML), Quantidade Conferida (Entrada), Quantidade Bipada (Picking).



### 7. `divergencias_recebimento`

* **Finalidade:** Fila de exceções no recebimento para aprovação do Gestor.


* **Atributos Principais:** ID do Pedido, ID do Produto, Quantidade XML, Quantidade Física Bipada, Motivo da Divergência, Status da Aprovação (PENDENTE, APROVADO, REJEITADO), ID do Gestor Tratador.



### 8. `avarias`

* **Finalidade:** Registro documentado de mercadorias danificadas.


* **Atributos Principais:** ID do Produto, ID do Endereço de Origem, Quantidade Avariada, Foto Comprovatória (caminho), Motivo, ID do Operador.



### 9. `logs_auditoria_estoque`

* **Finalidade:** Registro permanente de acertos manuais de saldo executados.


* **Atributos Principais:** ID do Produto, ID do Endereço, Quantidade Anterior, Quantidade Nova, Código do Motivo, ID do Operador Responsável, Timestamp.


* **Recursos:** Permissão exclusiva de consulta e gestão atribuída ao perfil Gestor.



### 10. `pesquisas_otif`

* **Finalidade:** Respostas de avaliação enviadas pelos clientes finais no pós-venda.


* **Atributos Principais:** ID do Pedido, Token de Acesso, Resposta Prazo (Sim/Não), Resposta Avaria (Sim/Não), Resposta Conformidade (Sim/Não), Caminho Foto 1, Caminho Foto 2, Observações, Data de Expiração do Link, Timestamp de Resposta.



---

## 11. Modelo de Dados Proposto e Migrations

### 11.1 Especificação de Tabelas e Campos (MySQL)

```sql
-- Tabela de Usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(50) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    nome_completo VARCHAR(100) NOT NULL,
    perfil ENUM('OPERADOR', 'GESTOR') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_matricula (matricula),
    INDEX idx_perfil (perfil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    codigo_barras VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255) NOT NULL,
    unidade_medida VARCHAR(10) NOT NULL DEFAULT 'UN',
    curva_abc ENUM('A', 'B', 'C') NOT NULL DEFAULT 'C',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_sku (sku),
    INDEX idx_codigo_barras (codigo_barras),
    INDEX idx_curva_abc (curva_abc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Endereços Físicos
CREATE TABLE enderecos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rua VARCHAR(10) NOT NULL,
    predio VARCHAR(10) NOT NULL,
    nivel VARCHAR(10) NOT NULL,
    capacidade_maxima INT NOT NULL DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_endereco_fisico (rua, predio, nivel),
    INDEX idx_rua_predio_nivel (rua, predio, nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Saldos de Estoque
CREATE TABLE estoque_saldos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    endereco_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    lote VARCHAR(50) NULL,
    data_validade DATE NULL,
    status_saldo ENUM('DISPONIVEL', 'QUARENTENA') NOT NULL DEFAULT 'DISPONIVEL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
    CONSTRAINT chk_quantidade_positiva CHECK (quantidade >= 0),
    INDEX idx_produto_endereco (produto_id, endereco_id),
    INDEX idx_status_saldo (status_saldo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Pedidos / Cargas
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_nota_xml VARCHAR(50) NOT NULL,
    chave_nfe VARCHAR(44) NOT NULL UNIQUE,
    cliente_nome VARCHAR(100) NOT NULL,
    cliente_contato VARCHAR(100) NOT NULL,
    status_kanban ENUM('RECEBIDO', 'A_ARMAZENAR', 'A_SEPARAR', 'A_EXPEDIR', 'ENTREGUE') NOT NULL DEFAULT 'RECEBIDO',
    prioridade_abc ENUM('A', 'B', 'C') NOT NULL DEFAULT 'C',
    token_otif VARCHAR(64) NOT NULL UNIQUE,
    ts_recebido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ts_a_armazenar TIMESTAMP NULL,
    ts_a_separar TIMESTAMP NULL,
    ts_a_expedir TIMESTAMP NULL,
    ts_entregue TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_kanban (status_kanban),
    INDEX idx_prioridade_abc (prioridade_abc),
    INDEX idx_token_otif (token_otif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Itens do Pedido
CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade_esperada INT NOT NULL,
    quantidade_conferida INT NOT NULL DEFAULT 0,
    quantidade_bipada_picking INT NOT NULL DEFAULT 0,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    INDEX idx_pedido_produto (pedido_id, produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Divergências no Recebimento
CREATE TABLE divergencias_recebimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    qtd_xml INT NOT NULL,
    qtd_fisica INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    status_aprovação ENUM('PENDENTE', 'APROVADO', 'REJEITADO') NOT NULL DEFAULT 'PENDENTE',
    tratado_por INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (tratado_por) REFERENCES usuarios(id),
    INDEX idx_status_aprovacao (status_aprovação)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Registro de Avarias
CREATE TABLE avarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    endereco_id INT NOT NULL,
    pedido_id INT NULL,
    quantidade INT NOT NULL,
    foto_url VARCHAR(255) NULL,
    motivo VARCHAR(255) NOT NULL,
    operador_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Log de Auditoria de Estoque
CREATE TABLE logs_auditoria_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    endereco_id INT NOT NULL,
    quantidade_anterior INT NOT NULL,
    quantidade_nova INT NOT NULL,
    motivo_codigo VARCHAR(50) NOT NULL,
    operador_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id),
    INDEX idx_auditoria_produto (produto_id),
    INDEX idx_auditoria_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Pesquisas OTIF (Cliente Final)
CREATE TABLE pesquisas_otif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL UNIQUE,
    token_acesso VARCHAR(64) NOT NULL UNIQUE,
    prazo_cumprido ENUM('SIM', 'NAO') NULL,
    sem_avaria ENUM('SIM', 'NAO') NULL,
    conformidade_itens ENUM('SIM', 'NAO') NULL,
    foto_1_url VARCHAR(255) NULL,
    foto_2_url VARCHAR(255) NULL,
    observacoes TEXT NULL,
    data_expiracao DATETIME NOT NULL,
    respondido_em DATETIME NULL,
    status_envio ENUM('PENDENTE', 'ENVIADO', 'FALHA') DEFAULT 'PENDENTE',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    INDEX idx_token (token_acesso),
    INDEX idx_data_expiracao (data_expiracao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Configuração de SLA por Etapa do Kanban
CREATE TABLE configuracoes_sla (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etapa_kanban ENUM('RECEBIDO', 'A_ARMAZENAR', 'A_SEPARAR', 'A_EXPEDIR') NOT NULL UNIQUE,
    tempo_limite_minutos INT NOT NULL DEFAULT 120,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Logs de Erro
CREATE TABLE logs_erro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensagem TEXT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    linha INT NOT NULL,
    trace TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Logs de Segurança
CREATE TABLE logs_seguranca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento VARCHAR(100) NOT NULL,
    ip_origem VARCHAR(45) NOT NULL,
    usuario_id INT NULL,
    detalhes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Controle de Migrations
CREATE TABLE schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

```

### 11.2 Arquitetura de Migrations

* **Localização dos Arquivos:** Todos os scripts de migration devem ser armazenados no diretório interno `database/migrations/` usando a nomenclatura ordenada `YYYY_MM_DD_HHMMSS_descricao.sql` (ex: `2026_08_25_000001_create_inicial_schema.sql`).
* **Mecanismo de Execução e Prevenção de Duplicidade:**
* A execução de migrations é controlada pela verificação da tabela `schema_migrations`.
* Um script runner em código PHP (executável via CLI `php database/migrate.php` ou acessível em rota interna protegida exclusiva do Gestor) lê os arquivos da pasta `database/migrations/`.
* Se o nome do arquivo de migration já estiver registrado na tabela `schema_migrations`, o arquivo é ignorado. Caso contrário, a migration é executada dentro de uma transação SQL e registrada imediatamente.


* **Bloqueio do Navegador:** O diretório `database/migrations/` é protegido por `.htaccess` e não pode ser acessado via requisição direta no navegador.

---

## 12. Módulos e Telas

A interface do sistema é desenvolvida seguindo o guia visual *Precision Logistics* do `docs/DESIGN.md`, utilizando fundo cinza neutro (`#F8FAFC`), barra lateral fixa em *Deep Slate* (`#0F172A`), tipografia Inter com suporte a dígitos tabulares (`tnum`) e raio de borda suave de 4px (0.25rem).

### Tela 1: Login de Usuários Internos (`/login`)

* **Objetivo:** Autenticação por matrícula e senha.


* **Layout:** Card centralizado de superfície branca sobre fundo cinza neutro.


* **Campos:** Matrícula (texto), Senha (password).
* **Ação:** Botão "Entrar na Operação" (Estilo Primary `#0F172A`).


* **Estados:** Mensagem de erro para credenciais inválidas ou conta desativada.

### Tela 2: Recebimento e Conferência Cega (`/recebimento`)

* **Objetivo:** Processar o XML da NF-e e executar a leitura de entrada.


* **Elementos:**
* Zona de Upload para o arquivo XML da NF-e;
* Painel de Conferência Cega (exibe SKU, código de barras e descrição do item; o campo "Quantidade Esperada" permanece totalmente oculto);


* Campo de entrada focalizado para leitura via leitor USB com opção de digitação manual do código de barras;


* Tabela de leitura dinâmica que incrementa as unidades bipadas em tempo real;


* Botão "Finalizar Conferência".


* **Estados:** Se houver divergência ao finalizar, gera registro e exibe aviso de que os itens divergentes foram enviados para aprovação do Gestor.



### Tela 3: Fila de Exceções do Recebimento (`/recebimento/excecoes`)

* **Objetivo:** Exclusivo do Gestor para autorização ou rejeição de divergências.


* **Elementos:** Tabela contendo Número da Nota, Produto, Quantidade XML, Quantidade Bipada e motivo da divergência.
* **Ações:** Botões "Aprovar Entrada" e "Rejeitar Entrada".

### Tela 4: Instruções de Guarda / Putaway (`/guarda`)

* **Objetivo:** Direcionamento de produtos conferidos para o endereço físico.


* **Elementos:**
* Lista de cargas no status *A Armazenar*;
* Instrução de destino gerada pelo sistema (ex: "*Transportar Produto SKU-102 para R01-P02-N03*");


* Campo para bipagem de confirmação do produto e bipagem/digitação do código do endereço de destino;


* Botão "Confirmar Alocação Física".



### Tela 5: Quadro Kanban Operacional (`/kanban`)

* **Objetivo:** Gerenciamento visual do fluxo de saída e tarefas do galpão.


* **Layout:** Painel com 4 colunas horizontais fluida: *Recebido* ➔ *A Armazenar* ➔ *A Separar* ➔ *A Expedir*.


* **Comportamento do Card:**
* Os cards dentro da coluna *A Separar* são ordenados automaticamente colocando os pedidos de **Curva A no topo**;


* Cada card possui uma tag de identificação visual do nível da Curva ABC;


* Exibição de cronômetro com tempo decorrido no estágio;


* **Alerta Visual de SLA:** A borda do card altera para amarelo quando atinge 80% do tempo limite configurado e para vermelho quando o limite é atingido/superado (ex: 2 horas).





### Tela 6: Estação de Picking e Packing (`/separacao/conferir`)

* **Objetivo:** Validação individual obrigatoria de itens antes da expedição.


* **Elementos:**
* Identificação do pedido e endereço físico onde os itens devem ser coletados;
* Campo de leitura USB para bipagem do código de barras item a item;


* Contador regressivo de validação (100% dos itens devem ser bipados);


* Botão "Concluir Embalagem e Liberar Expedição" (bloqueado até atingir a bipagem total).





### Tela 7: Registro e Consulta de Quarentena (`/avarias`)

* **Objetivo:** Lançamento interno de danos e visualização do saldo retido.


* **Elementos:** Formulário para seleção do produto, endereço atual, quantidade avariada, campo para upload opcional de foto e botão "Transferir para Quarentena".



### Tela 8: Formulário Externo de Avaliação OTIF (`/otif/avaliar?token=XYZ`)

* **Objetivo:** Portal web ultraleve para o Cliente Final.


* **Layout:** Interface limpa sem navegação interna ou menu do WMS.


* **Elementos:**
* Pergunta 1: "O pedido foi entregue dentro do prazo combinado?" (Botões Sim / Não);


* Pergunta 2: "A embalagem ou os produtos possuíam avarias?" (Botões Sim / Não);


* Pergunta 3: "Os produtos e as quantidades vieram corretos?" (Botões Sim / Não);


* Campo de upload para até 2 fotos (ativo se houver avaria/divergência);


* Campo de texto livre para observações;


* Botão "Enviar Avaliação".




* **Estados:** Exibe mensagem de erro se o token estiver expirado (mais de 10 dias úteis) ou se a avaliação já tiver sido enviada.



### Tela 9: Auditoria de Estoque (`/auditoria`)

* **Objetivo:** Formulário de acerto manual e visualização de log.


* **Elementos:**
* Formulário de ajuste: Seleção de produto, endereço, nova quantidade e seleção **obrigatória** de justificativa padronizada em dropdown (ex: *Danos no manuseio*, *Erro de contagem anterior*, *Item avariado*);


* Tabela de histórico (visível/gerenciável apenas pelo Gestor) com data/hora, operador, quantidade anterior, quantidade nova e justificativa.





### Tela 10: Dashboard Executivo do Gestor (`/dashboard`)

* **Objetivo:** Monitoramento central dos 9 indicadores logísticos.


* **Elementos:**
1. *Taxa de Ocupação do Estoque* (Card com % de endereços ocupados);


2. *Taxa de OTIF Acumulada do Mês* (Card com % de entregas perfeitas);


3. *Gargalos de Lead Time por Etapa* (Gráfico visual indicando tempo médio por coluna do Kanban);


4. *Histórico de Avarias por Fornecedor* (Tabela/Gráfico de incidência de danos);


5. *Taxa de Acuracidade de Estoque* (% de precisão das conferências);


6. *Lista de Pedidos Atrasados* (Tabela de ordens com SLA estourado);


7. *Gráfico de Curva ABC* (Distribuição do inventário em A, B e C);


8. *Consulta de Endereçamento de Cargas* (Busca rápida por posição física);


9. *Total de Produtos Movimentados no Dia* (Volume total bipado nas últimas 24h).





---

## 13. Fluxos Funcionais

### Fluxo 1: Recebimento de Mercadoria via XML e Conferência Cega

1. O Operador acessa a área `/recebimento` e seleciona o arquivo XML da NF-e enviada pelo fornecedor.


2. O sistema processa o XML, registra o pedido com status `RECEBIDO` e abre a interface de Conferência Cega (sem exibir as quantidades esperadas).


3. O Operador bipa o código de barras de cada item físico no leitor USB (ou digita o código).


4. O sistema valida as leituras e incrementa a quantidade conferida na sessão.


5. O Operador clica em "Finalizar Conferência".


6. O sistema compara a contagem física com os dados do XML:


* **Se as quantidades baterem 100%:** O sistema dá entrada nos produtos, avança o status do pedido para `A_ARMAZENAR` no Kanban e gera a instrução de guarda.


* **Se houver divergência:** O sistema armazena a quantidade física correta no estoque, direciona o saldo divergente para a tabela de exceções e envia um alerta para aprovação do Gestor.





### Fluxo 2: Guardar Produto no Estoque (Putaway)

1. O Operador acessa `/guarda` ou clica na tarefa na coluna *A Armazenar* do Kanban.


2. O sistema indica o produto e o endereço físico de destino (Rua-Prédio-Nível).


3. O Operador transporta os volumes e, no local físico, bipa o código de barras do produto e o código do endereço impresso na estrutura.


4. O sistema valida a associação e confirma a guarda.


5. O saldo de estoque é atualizado para a condição `DISPONIVEL` naquele endereço físico e o card avança para a coluna *A Separar*.



### Fluxo 3: Separação (Picking), Embalagem (Packing) e Expedição

1. O Operador acessa o Kanban e seleciona a coluna *A Separar*, que exibe as tarefas com prioridade de Curva A no topo.


2. O Operador inicia o processo de picking e dirige-se ao endereço indicado.


3. Na estação de embalagem (`/separacao/conferir`), o Operador bipa obrigatoriamente código a código no leitor USB cada item coletado.


4. O sistema valida item a item. Quando 100% dos produtos do pedido são validados por bipagem, o botão de expedição é liberado.


5. O Operador confirma a embalagem e altera o status para `A_EXPEDIR`.


6. Ao despachar a carga, o Operador confirma a expedição e o sistema altera o status do pedido para `ENTREGUE`.


7. A mudança de status aciona automaticamente a API de mensagens enviando o link de avaliação OTIF para o cliente final.



### Fluxo 4: Avaliação OTIF pelo Cliente Final

1. O Cliente Final recebe o link com token via WhatsApp/E-mail.


2. O Cliente acessa a página externa `/otif/avaliar?token=XYZ` no seu celular.


3. O sistema valida se o token é válido e se está dentro do prazo limite de 10 dias úteis.


4. O Cliente responde às 3 perguntas objetivas (Prazo, Avaria, Conformidade).


5. Se indicar avaria ou inconformidade, o sistema permite anexar até 2 fotos e escrever observações.


6. O Cliente clica em "Enviar Avaliação".


7. O sistema calcula a nota no indicador OTIF e, caso a avaliação seja negativa, gera um alerta crítico no Dashboard do Gestor.



### Fluxo 5: Ajuste Manual de Estoque com Log de Auditoria

1. O Operador identifica uma divergência física e acessa a tela `/auditoria`.


2. O Operador seleciona o produto, o endereço e digita a nova quantidade física real.


3. O sistema exige a seleção obrigatória de um motivo padronizado na lista.


4. O Operador confirma o ajuste.


5. O sistema atualiza o saldo na tabela `estoque_saldos` imediatamente.


6. O sistema grava um registro inalterável na tabela `logs_auditoria_estoque` contendo usuário, data/hora, quantidade anterior, quantidade nova e motivo.


7. O histórico fica disponível exclusivamente para consulta e gestão do Gestor.



---

## 14. Validações e Regras de Negócio

* **RN-01 (Obrigatoriedade do XML de Entrada):** Todo recebimento de mercadoria deve ser iniciado exclusivamente a partir do upload de um arquivo XML de NF-e válido.


* **RN-02 (Conferência Cega):** As telas de conferência de entrada não devem exibir para o operador as quantidades esperadas constantes no XML.


* **RN-03 (Aprovação de Divergências):** Contagens físicas divergentes do XML exigem a aprovação manual do Gestor na fila de exceções para consolidação do estoque.


* **RN-04 (Padrão de Endereçamento):** Todo saldo de produto armazenado deve estar vinculado a um endereço no formato obrigatório "Rua - Prédio - Nível".


* **RN-05 (Bloqueio de Quarentena):** Mercadorias marcadas como avariadas são transferidas para o status `QUARENTENA` e não podem ser alocadas ou separadas em pedidos de saída.


* **RN-06 (Auditoria Restrita):** Ajustes manuais de saldo atualizam o estoque imediatamente, mas exigem justificativa padrão e criam logs inalteráveis que só podem ser geridos pelo Gestor.


* **RN-07 (Priorização ABC no Kanban):** Pedidos com itens de Curva A devem ser posiciados automaticamente no topo da coluna de separação.


* **RN-08 (SLA do Kanban):** O limite genérico padrão por etapa é de 120 minutos (reconfigurável pelo Gestor). Bordas de cards alteram para amarelo ao atingir 80% do tempo e vermelho ao estourar o SLA.


* **RN-09 (Validação Obrigatória de Outbound):** A expedição de um pedido exige a bipagem e conferência de 100% dos seus itens na fase de picking/packing.


* **RN-10 (Gatilho de Mensagens OTIF):** A alteração do status para `ENTREGUE` dispara automaticamente o envio do link de avaliação pós-venda via API externa.


* **RN-11 (Validade do Link OTIF):** O token de avaliação expira após 10 dias úteis a contar da data de envio. Links expirados exibem mensagem de bloqueio.


* **RN-12 (Alerta Crítico no Dashboard):** Avaliações OTIF negativas (atrasos ou avarias) geram alertas prioritários visíveis no painel do Gestor.



---

## 15. Autenticação e Sessão

* **Mecanismo:** Autenticação por formulário enviando matrícula e senha via POST sobre HTTP/HTTPS.
* **Criptografia:** Comparação de senhas usando `password_verify()` contra hashes criados com `password_hash()`.
* **Gerenciamento de Sessão:** Uso do `session_start()` nativo do PHP com inclusão do ID do usuário, nome e perfil na superglobal `$_SESSION`.
* **Segurança de Sessão:** Regenarção do ID de sessão (`session_regenerate_id(true)`) a cada login bem-sucedido. Expiração automática após 8 horas de inatividade.
* **Acesso do Cliente Final:** Autenticação sem formulário via parâmetro `GET token` sanitizado e validado contra a tabela `pesquisas_otif`.

---

## 16. Controle de Acesso

* **Proteção em Camada Controller:** Todos os Controllers do sistema (com exceção do `OtifController` e do `AuthController`) devem invocar a checagem de autorização antes de executar qualquer ação:
```php
// Exemplo de controle de acesso
AuthHelper::requireLogin(); // Redireciona para /login se não houver sessão ativa
AuthHelper::requirePerfil('GESTOR'); // Dispara erro 403 se o usuário não for Gestor

```


* **Página de Acesso Negado:** Tentativas de acesso a rotas não autorizadas renderizam uma View com status HTTP 403 e registram a ocorrência na tabela `logs_seguranca`.

---

## 17. Auditoria e Histórico

* **Registros Auditados:** Todas as alterações manuais de saldo em `estoque_saldos`.
* **Campos Armazenados:** ID do produto, ID do endereço, quantidade anterior, nova quantidade, motivo selecionado, ID do operador e data/hora.
* **Inviolabilidade:** A interface do Operador não possui rotas ou botões para alterar ou deletar logs de auditoria. O Gestor possui visualização exclusiva do histórico e pode marcar registros excluídos apenas via exclusão lógica em tabela protegida.

---

## 18. Soft Delete e Exclusões

* **Aplicação:** Tabelas `produtos`, `enderecos`, `usuarios` e `logs_auditoria_estoque` possuem a coluna `deleted_at TIMESTAMP NULL`.
* **Regra de Consulta:** Todas as cláusulas `SELECT` operacionais do sistema devem incluir obrigatoriamente a condição `WHERE deleted_at IS NULL`.
* **Restauração:** Somente o perfil Gestor tem acesso à tela de gerenciamento para visualizar itens excluídos e executar a restauração (definindo `deleted_at = NULL`).

---

## 19. Logs

### 19.1 Log de Erros e Contingência

* **Gravador Primário:** Exceções do PHP e erros de execução de queries SQL gravam registro na tabela `logs_erro`.
* **Mecanismo de Contingência em Arquivo:**
```php
public static function registrarErro(\Throwable $e) {
    try {
        // Tenta gravar no banco de dados MySQL
        DbLog::write($e->getMessage(), $e->getFile(), $e->getLine());
    } catch (\Exception $dbException) {
        // Fallback: Grava em arquivo de texto fora do diretório público se o banco falhar
        $logPath = __DIR__ . '/../../logs/error.log';
        $entry = sprintf("[%s] ERRO: %s em %s:%d\n", date('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine());
        file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
    }
}

```



### 19.2 Log de Segurança

* **Eventos Gravados:** Tentativas de login com matrícula inexistente ou senha incorreta, acesso negado por perfil e tentativas de burlar formulários sem token CSRF.
* **Informações Armazenadas:** Nome do evento, IP de origem do cliente (`$_SERVER['REMOTE_ADDR']`), ID do usuário (se autenticado), e detalhes do cabeçalho HTTP.

---

## 20. Configurações Globais

* **Arquivo de Configuração Técnica em Código:** `config/config.php` (não é utilizado arquivo `.env`).
```php
<?php
defined('WMS_EXEC') or die('Acesso direto proibido.');

return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'wms_agiliza',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'name' => 'WMS Agiliza',
        'base_url' => 'http://localhost/wms-agiliza',
        'timezone' => 'America/Sao_Paulo'
    ],
    'otif' => [
        'expiracao_dias_uteis' => 10,
        'upload_max_bytes' => 5242880 // 5MB
    ]
];

```



---

## 21. Uploads, Anexos e Arquivos

* **Anexos Permitidos:**
* Portal OTIF (Cliente Final): até 2 fotos por avaliação.
* Módulo de Avarias (Interno): 1 foto opcional por lançamento.


* **Regras e Validações:**
* Tamanho máximo por foto: 5MB;
* Extensões e tipos MIME permitidos: `.jpg`, `.jpeg`, `.png` (`image/jpeg`, `image/png`);
* Renomeação do arquivo: Gera HASH MD5 do conteúdo + timestamp antes de salvar no disco (ex: `a8f5f167f44f4964e6c998dee827110c.png`);
* Local de armazenamento: `uploads/otif/` e `uploads/avarias/`;
* Proteção: A pasta `uploads/` possui um arquivo `.htaccess` impedindo a execução de scripts PHP (`php_flag engine off` e `SetHandler default-handler`).



---

## 22. Relatórios, Consultas e Exportações

* **Consultas em Tela:** Todas as consultas e os 9 indicadores do Dashboard Executivo são exibidos dinamicamente na interface web do Gestor.
* **Filtros Disponíveis:** Por período de datas (início/fim), por código SKU do produto, por endereço físico (Rua/Prédio/Nível) e por status do pedido.
* **Exportações:** O sistema **não inclui** geradores de arquivos CSV, Excel ou PDF na versão V1.



---

## 23. APIs e Integrações Externas

* **Objetivo:** Notificação automatizada ao cliente final contendo o link de avaliação do pedido.


* **Disparo:** Acionado via rotina assíncrona/curll no momento em que o status do pedido muda para `ENTREGUE` no Controller.


* **Estrutura da Requisição Externa (Exemplo Evolution API / Resend):**
```php
$payload = [
    'number' => $pedido->cliente_contato,
    'text' => "Olá {$pedido->cliente_nome}! Seu pedido {$pedido->numero_nota_xml} foi entregue. Avalie nossa entrega em: " . BASE_URL . "/otif/avaliar?token=" . $pedido->token_otif
];

```


* **Tratamento de Falhas:** Caso a API externa retorne erro ou timeout, o status de envio na tabela `pesquisas_otif` é alterado para `FALHA` para posterior reprocessamento manual pelo Gestor, sem travar a operação do galpão.

---

## 24. Segurança Funcional

* **Proteção CSRF:** Todos os formulários internos `POST` devem conter um campo oculto com token CSRF gerado por sessão (`$_SESSION['csrf_token']`).
* **Proteção contra SQL Injection:** Uso exclusivo de declarações preparadas (*Prepared Statements*) com a biblioteca PDO do PHP em todos os Models.
* **Proteção contra XSS:** Todas as saídas de dados exibidas no HTML do navegador devem ser tratadas com `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
* **Nomes de Arquivos:** Sanitização estrita em upload de imagens e importação do XML para remover caracteres especiais ou caminhos relativos (`../`).

---

## 25. Organização Sugerida da Implementação

A IA codificadora deve executar a implementação da aplicação em etapas sequenciais e testáveis a partir do `[Diretório do Projeto - Repositório]`:

1. **Preparação da Raiz e Pastas:** Criar a estrutura do `[Diretório do Projeto - Repositório]` com as pastas `config/`, `app/`, `database/`, `assets/`, `uploads/` e `logs/`.
2. **Proteção e Roteamento:** Criar o arquivo `.htaccess` na raiz e a estrutura básica do `index.php`.
3. **Arquivo de Configuração:** Criar o arquivo `config/config.php` sem uso de `.env` e com permissões protegidas.
4. **Conexão com Banco:** Criar `config/database.php` com o manipulador de conexões PDO.
5. **Estrutura de Migrations:** Criar a tabela `schema_migrations` e o executor PHP em `database/migrate.php`.
6. **Migrations do Banco:** Escrever as migrations de criação das 13 tabelas principais do banco de dados na pasta `database/migrations/` e executá-las.
7. **Helpers Base:** Implementar funções de sessão, sanitização, CSRF e gravação de logs (erro e segurança).
8. **Módulo de Autenticação:** Implementar `UsuarioModel`, `AuthController` e a View de login `/login`.
9. **Layout e Template Base:** Estruturar a barra lateral fixa em *Deep Slate* (`#0F172A`) e a área de conteúdo usando o tema *Precision Logistics*.


10. **Cadastros Básicos:** Implementar os Models e Controllers de Produtos e Endereços.
11. **Módulo de Recebimento:** Criar o leitor de XML de NF-e e a interface de Conferência Cega por leitor USB/manual.
12. **Aprovação de Exceções:** Desenvolver a fila de aprovação de divergências do Gestor.
13. **Módulo de Guarda (Putaway):** Criar as instruções de alocação física por produto e endereço.
14. **Módulo Kanban:** Desenvolver o painel visual com 4 colunas, ordenação por Curva ABC e alteração de cores de borda por SLA (Amarelo/Vermelho).
15. **Módulo de Picking e Packing:** Implementar a estação de separação e conferência por bipagem USB item a item.
16. **Módulo de Quarentena e Avarias:** Implementar o formulário de avarias com transferência automática de saldo para o endereço de Quarentena.
17. **Módulo de Auditoria:** Implementar a alteração manual de estoque com seleção obrigatória de justificativa e gravação no log inalterável.
18. **Gatilho de Expedição e APIs:** Desenvolver a integração do cliente HTTP para envio automatizado da mensagem com token OTIF.
19. **Portal OTIF (Cliente Final):** Criar a interface externa ultraleve de avaliação com upload de até 2 fotos e expiração em 10 dias úteis.
20. **Dashboard Executivo:** Implementar os 9 indicadores visuais no painel do Gestor.
21. **Log de Contingência:** Testar e validar a gravação do log em arquivo `logs/error.log` simular a queda de conexão com o MySQL.
22. **Revisão de Segurança:** Validar proteções CSRF, XSS, validações de MIME-type em uploads e controle de acesso RBAC.
23. **Validação Geral:** Testar todos os fluxos operacionais completos localmente no XAMPP.
24. **Ajuste para Deploy:** Documentar o script de migração e publicação para o ambiente final InfinityFree.

---

## 26. Critérios de Aceitação Técnica e Funcional

A implementação será considerada concluída quando atender integralmente aos seguintes pontos:

* [ ] Estrutura organizada a partir do `[Diretório do Projeto - Repositório]`, funcionando em subpasta sem depender de nomes fixos como `htdocs` ou `www`;
* [ ] Arquivo de configuração `config/config.php` em código PHP criado e protegido, sem qualquer uso de arquivo `.env`;
* [ ] Pastas internas (`config/`, `app/`, `database/`, `uploads/`, `logs/`) protegidas contra acesso direto por URL;
* [ ] Migrations criadas e executadas de forma controlada através da tabela `schema_migrations`, impedindo reexecução duplicada;
* [ ] Login por matrícula e senha funcionando para os perfis Operador e Gestor com controle de sessão e logout;
* [ ] Recebimento importando XML de NF-e e exibindo a tela de Conferência Cega sem expor as quantidades esperadas para o operador;
* [ ] Leitura de código de barras funcionando perfeitamente via leitor USB e permitindo entrada por digitação manual;
* [ ] Endereçamento físico registrando e validando obrigatoriamente a posição na estrutura "Rua - Prédio - Nível";
* [ ] Quadro Kanban apresentando as 4 colunas, com priorização de Curva A no topo e bordas de card alterando a cor conforme o tempo decorrido;
* [ ] Validação de Picking/Packing exigindo 100% de bipagem dos itens antes de liberar a expedição do pedido;
* [ ] Itens avariados sendo transferidos automaticamente para o saldo bloqueado no endereço virtual "Quarentena";
* [ ] Disparo automático de link com token OTIF acionado quando o pedido avança para o status "Entregue";
* [ ] Formulário externo OTIF funcionando em celulares sem exigir login, aceitando até 2 fotos e bloqueando tokens com mais de 10 dias úteis;
* [ ] Ajustes manuais de estoque exigindo motivo padronizado e gerando logs inalteráveis na tabela de auditoria;
* [ ] Dashboard exibindo corretamente os 9 indicadores do Gestor;
* [ ] Soft Delete implementado via `deleted_at` ocultando registros sem apagar histórico do banco;
* [ ] Mecanismo de contingência de log de erro em arquivo texto `logs/error.log` funcionando caso o banco MySQL fique indisponível;
* [ ] Interface aderente ao estilo *Precision Logistics* do `docs/DESIGN.md` com Bootstrap local e tipografia Inter.

---

## 27. Pontos Pendentes e Decisões Futuras

Não foram identificadas pendências para iniciar a codificação com base neste FSD.

---

## 28. Conclusão

Este Documento de Especificação Funcional (FSD) está completo, consolidado e pronto para orientar de forma autônoma a IA codificadora no desenvolvimento do sistema **WMS Agiliza**.

Para o início da codificação do projeto, os únicos documentos que devem ser fornecidos à IA codificadora são:

* `docs/FSD.md` (Este documento);
* `docs/DESIGN.md` (Documento de diretrizes de interface e estilo).