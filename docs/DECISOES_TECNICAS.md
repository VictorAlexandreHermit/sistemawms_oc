# DECISÕES TÉCNICAS DO PROJETO

## 1. Documentos recebidos

* **PRD.md:** Recebido e analisado com sucesso.


* **DESIGN.md:** Recebido e analisado com sucesso.


* **Observações relevantes:** O `PRD.md` define de forma clara o escopo funcional do sistema WMS. O `DESIGN.md` fornece as diretrizes visuais (estilo *Precision Logistics*, paleta baseada em *Deep Slate* `#0F172A`, tipografia Inter com suporte a *tabular figures* e layout de *Fixed Sidebar / Fluid Content*) que servirão de base para a construção da interface no FSD.



## 2. Identificação do sistema

* **Nome do sistema:** WMS Agiliza (WMS Leve & Visual).


* **Objetivo principal:** Controlar e otimizar o fluxo operacional logístico de ponta a ponta (recebimento, armazenamento, separação, embalagem e expedição), garantindo alta acuracidade de estoque, visibilidade do *lead time* e medição contínua da satisfação do cliente final (OTIF).


* **Público usuário:** Operadores de pátio/galpão, conferentes, estoquistas e gestores de logística de Pequenas e Médias Empresas (PMEs), além do Cliente Final (acesso externo via token).


* **Contexto de uso:** Estações de trabalho fixas no galpão com computadores de mesa acoplados a leitores de código de barras USB (com opção de digitação manual) e portal web leve e responsivo acessado via dispositivo móvel pelo cliente final.


* **Resumo funcional:** Cobre a jornada operacional desde o recebimento via importação de XML de NF-e, conferência cega por bipagem USB, endereçamento físico (Rua-Prédio-Nível), gestão visual por Kanban com priorização automática por Curva ABC, controle de *lead time*, quarentena de avarias e coleta de satisfação pós-venda (OTIF) com auditoria de estoque.



## 3. Decisões técnicas confirmadas

* **Stack:** PHP, HTML, CSS, JavaScript puro, MySQL, Bootstrap local e organização inspirada em MVC.
* **Ambientes:** Desenvolvimento local em XAMPP; homologação/testes não obrigatórios na V1 (validação local antes do deploy); produção hospedada na InfinityFree.
* **Arquitetura:** Separação estrutural em camadas inspirada no padrão Model-View-Controller (MVC).
* **Autenticação de Usuários Internos:** Nome de usuário (login/matrícula) e senha para os perfis de Operador e Gestor.
* **Acesso Externo (Cliente Final):** Acesso sem login/senha por meio de token único e temporário vinculado ao pedido no portal OTIF.


* **Usuários e Permissões:** Perfis bem demarcados de Operador (execução operacional, recebimento, guarda, picking/packing, registro de avaria e ajustes com justificativa) e Gestor (acesso total, liberação de exceções, reconfiguração de SLAs, auditoria de estoque e visualização de dashboards).


* **Auditoria:** Registro inalterável pelo Operador gravando data/hora, usuário, produto, endereço, quantidade ajustada e motivo padronizado, sob controle e gestão do Gestor.


* **Soft Delete:** Padrão de exclusão lógica para cadastros e registros principais (produtos, endereços, usuários) mantendo o histórico de integridade operacional.
* **Logs:** Gravação de logs de erros em banco de dados com contingência local em arquivo seguro fora da pasta pública; mensagens genéricas para o usuário na interface.
* **Configurações Globais:** Limite de SLA de etapas no Kanban (padrão 2 horas por coluna) reconfigurável via interface de administração do Gestor.


* **Uploads e Arquivos:** Anexo de até 2 fotos por envio no portal de avaliação OTIF e anexo opcional de fotos no registro interno de avarias.


* **Exportações / Impressão:** Não haverá gerador ou impressor de etiquetas interno na V1.


* **APIs e Integrações Externas:** Disparo automatizado de pesquisas OTIF via integração com APIs externas de mensagens de mercado (Evolution API para WhatsApp ou Resend/SendGrid para E-mails transacionais) acionadas pela alteração de status para "Entregue".


* **Segurança:** Bloqueio de expiração de link OTIF após 10 dias úteis; telas operacionais com exibição oculta das quantidades na conferência cega do XML.


* **Desempenho:** Necessidade de índices no MySQL em buscas frequentes como código de barras, SKU, status do Kanban e timestamps de movimentação.
* **Fora de escopo técnico:** Módulo fiscal/financeiro, leitor RFID/hardware de automação, gerador/impressor interno de etiquetas, algoritmo tridimensional de rota de separação (*picking path*), inventário cíclico programado e inteligência artificial para previsão de demanda.



## 4. Decisões adotadas por padrão

* **Stack:** PHP, HTML, CSS, JavaScript puro, MySQL, Bootstrap local e organização em MVC.
* **Ambiente local:** XAMPP com PHP e MySQL locais.
* **Ambiente de testes/homologação:** Ausência de ambiente obrigatório de testes na primeira versão; testes e validações conduzidos localmente no XAMPP antes da publicação.
* **Soft Delete:** Exclusão lógica aplicada aos cadastros principais do banco de dados para evitar perda irrecuperável de movimentações de estoque.
* **Auditoria básica:** Preservação de campos `created_at`, `created_by`, `updated_at` e `updated_by` nos registros principais do sistema.
* **Tratamento de Logs de Erro:** Mensagens amigáveis e seguras em tela com salvamento em banco e contingência em arquivo texto local fora do diretório público.

## 5. Stack e ambientes

* **Linguagem de programação:** PHP.
* **Banco de dados:** MySQL.
* **Tecnologias de interface:** HTML, CSS, JavaScript puro e Bootstrap (armazenado localmente, sem dependência de CDN externa).
* **Ambiente local:** XAMPP rodando PHP e MySQL locais.
* **Ambiente de testes / homologação:** Não haverá ambiente obrigatório na V1; a aplicação será totalmente validada em ambiente local (XAMPP).
* **Ambiente de produção:** Hospedagem com PHP e MySQL na InfinityFree.
* **Observações sobre deploy:** O processo e script de deploy serão detalhados em uma etapa própria do fluxo no FSD.

## 6. Arquitetura obrigatória

O sistema seguirá obrigatoriamente a organização arquitetural inspirada em **MVC (Model-View-Controller)**.

* **Model:** Encapsula as regras de negócio, persistência de saldo de estoque, atualização do Kanban e comunicação com o banco MySQL.
* **View:** Interfaces web responsivas utilizando Bootstrap local e estilo *Precision Logistics*, preparadas para estações no galpão acopladas a leitores USB.


* **Controller:** Processa as ações do usuário (ex: validação de bipagem, movimentação de card, aceite de XML), invoca a regra no Model e direciona a resposta para a View correspondente.



## 7. Recursos estruturais definidos

* **Autenticação:** Nome de usuário (login/matrícula) e senha para perfis internos; acesso via token seguro em URL para o Cliente Final.


* **RBAC (Controle de Acesso Baseado em Perfis):** Diferenciação estrita entre os perfis Operador, Gestor e Cliente Final.


* **Auditoria:** Log inalterável gravando ajustes manuais de estoque (usuário, data/hora, quantidade anterior/atual, motivo) sob gestão do perfil Gestor.


* **Soft Delete:** Exclusão lógica via campo `deleted_at` para registros do sistema.
* **Log de Erros:** Gravação de falhas em tabela de banco de dados com mecanismo de salvamento em arquivo de texto em caso de desconexão do MySQL.
* **Log de Segurança:** Registro de falhas de autenticação e tentativas de acesso negado a funções do Gestor.
* **Configurações Globais:** Parametrização dos SLAs (limite de tempo em horas por etapa do Kanban).


* **Uploads e Anexos:** Suporte a upload de até 2 imagens no formulário público OTIF e anexo opcional de fotos no registro interno de avarias.


* **Exportações:** Não aplicável para a versão V1.


* **APIs / Integrações:** Integração de saída para serviços externos de mensagem (Evolution API para WhatsApp e Resend/SendGrid para E-mails).



## 8. Perfis e permissões em nível alto

* **Operador:** Importa XML, executa conferência cega via bipagem USB/manual, realiza alocação/guarda, movimenta tarefas no Kanban, bipa itens na separação/embalagem, registra avarias e faz ajustes manuais com justificativa padronizada. Não pode aprovar divergências, alterar SLAs nem gerenciar o log de auditoria.


* **Gestor:** Acesso completo a todas as funcionalidades. Pode aprovar exceções/divergências no recebimento, alterar SLAs do Kanban, auditar e gerenciar logs de estoque e visualizar os 9 indicadores do Dashboard executivo.


* **Cliente Final:** Acesso exclusivamente externo via link com token, limitado a responder ao questionário OTIF com anexo de até 2 fotos e comentários.



## 9. Entidades prováveis em nível alto

O FSD considerará as seguintes entidades conceituais principais:

* **Usuários / Perfis:** Credenciais de operadores/gestores (login/matrícula e senha).
* **Produtos:** Catálogo de itens contendo código SKU, descrição, código de barras, unidade e curva ABC.


* **Endereços Físicos:** Mapeamento hierárquico das posições do estoque (Rua, Prédio, Nível).


* **Estoque / Saldos:** Vinculação de produtos aos endereços físicos, indicando lote, validade, quantidades e status do saldo (Disponível vs. Quarentena).


* **Cargas / Pedidos:** Registros das notas/pedidos movimentados no Kanban, armazenando status atual, prioridade ABC e os *timestamps* de entrada e saída em cada etapa.


* **Avarias:** Registros de mercadorias danificadas com quantidade, fotos e vínculo ao fornecedor/transportadora.


* **Logs de Auditoria:** Histórico inalterável de ajustes manuais de saldo.


* **Pesquisas OTIF / Feedbacks:** Registros de respostas de satisfação dos clientes, links, tokens e imagens anexadas.



## 10. Módulos, telas e fluxos esperados em nível alto

* **Módulo de Autenticação:** Login por matrícula/senha e controle de sessão.
* **Módulo de Recebimento (Inbound):** Importação do XML de NF-e, tela de conferência cega por bipagem USB/manual e fila de aprovação de divergências do Gestor.


* **Módulo de Endereçamento (Putaway):** Cadastro de Ruas/Prédios/Níveis e instruções de direcionamento de guarda de produtos.


* **Módulo Kanban Operacional (Outbound):** Painel visual (*Recebido* ➔ *A Armazenar* ➔ *A Separar* ➔ *A Expedir*) com ordenação automática por Curva ABC e alteração visual de borda conforme o *lead time*.


* **Módulo de Picking e Packing:** Interface de validação obrigatória por bipagem item a item para liberação de expedição.


* **Módulo de Quarentena e Avarias:** Isolação automática de itens danificados e bloqueio de saldo.


* **Módulo OTIF e Pós-Venda:** Disparo automático de links via API e interface web externa leve para avaliação do cliente final.


* **Módulo de Auditoria e Ajuste de Estoque:** Ajustes manuais por justificativa e histórico gravado.


* **Módulo Dashboard Executivo:** Visualização dos 9 indicadores principais do Gestor.


## 11. Alertas para relatórios, consultas, exportações e desempenho

* **Relatórios e Indicadores:** O FSD detalhará as consultas para os 9 indicadores do Dashboard (Ocupação do estoque, Taxa OTIF, Gargalos de Lead Time, Histórico de Avarias por fornecedor, Acuracidade de estoque, Pedidos atrasados, Curva ABC, Endereçamento de cargas e Produtos movimentados no dia).


* **Alertas de Desempenho no MySQL:** O FSD avaliará a criação de índices para consultas frequentes, considerando:
* Busca por código de barras e SKU de produtos.


* Filtro de pedidos por status no Kanban e prioridade ABC.


* Leitura de saldo por endereço físico (Rua, Prédio, Nível).


* Consultas por intervalo de datas e ordenação de *timestamps* de *lead time*.



## 12. Alertas para uploads, anexos e arquivos

* **Portal OTIF:** Upload público pelo cliente final de até 2 imagens (formatos padrão como JPG/PNG com restrição de tamanho máximo a ser fixada no FSD).


* **Módulo de Avarias:** Upload interno de fotos comprovatórias de danos na entrada ou separação.


* **Cuidados de Segurança:** O FSD especificará a validação de extensão/MIME-type, renomeação de arquivos via HASH único e armazenamento em pasta protegida contra execução de scripts PHP no servidor.

## 13. Alertas para logs, auditoria e segurança

* **Auditoria de Estoque:** Gravação imediata do ID do usuário, data/hora, quantidade anterior, quantidade nova e motivo selecionado a cada ajuste.


* **Log de Erros e Contingência:** O FSD deverá definir que qualquer exceção do sistema gravará detalhes em tabela MySQL e, caso a conexão com o banco caia, gravará as informações em um arquivo `.log` armazenado fora do diretório público do Apache/Nginx.
* **Eventos de Segurança:** Registro de falhas no login de usuários e tentativas de edição do histórico de auditoria por contas que não sejam do perfil Gestor.


## 14. Itens que não devem ser inventados

Não deverão ser incluídos no FSD os seguintes recursos por estarem fora do escopo do MVP:

* Módulo fiscal e de faturamento (emissão de notas, cálculo de impostos).


* Integração com coletores de dados dedicados, RFID, esteiras ou robôs (WCS).


* Módulo interno de geração e impressão de etiquetas.


* Algoritmos tridimensionais de otimização de rotas de separação (*picking path*).


* Agendamento de inventários cíclicos programados.


* Alertas automáticos de estoque mínimo, ponto de compra ou validade de produto.


* Previsões de demanda utilizando inteligência artificial.


## 15. Pendências não bloqueantes

Não foram identificadas pendências não bloqueantes para a criação do FSD.

## 16. Pronto para o FSD

As decisões técnicas do projeto estão **100% consolidadas e prontas** para a criação do FSD.

O próximo passo do fluxo será a elaboração do documento:
`docs/FSD.md`

O `FSD.md` deverá ser gerado com base no alinhamento rigoroso entre:

* `PRD.md`
* `DECISOES_TECNICAS.md`
* `DESIGN.md`