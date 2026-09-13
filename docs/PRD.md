# DOCUMENTO DE REQUISITOS DO PRODUTO (PRD)

## 1. Visão Geral do Produto

* **Nome Provisório:** WMS Agiliza (WMS Leve & Visual).


* **Descrição Resumida:** O sistema é um WMS (*Warehouse Management System* ou Sistema de Gerenciamento de Armazém) prático, visual e focado no controle operacional de galpões e pátios. Ele gerencia todo o fluxo de mercadorias, do recebimento via importação de XML da Nota Fiscal Eletrônica (NF-e) até a expedição, acompanhando o tempo de cada etapa (*lead time*) e coletando o nível de satisfação das entregas (*OTIF - On-Time In-Full*) diretamente com o cliente final.


* **Público Principal:** Operadores de pátio/galpão, conferentes, estoquistas e gestores de logística de Pequenas e Médias Empresas (PMEs).


* **Principal Benefício Esperado:** Aumentar a acuracidade de estoque para níveis próximos de 100%, eliminar erros de contagem e digitação manual, visibilizar gargalos operacionais em tempo real e fornecer métricas claras sobre a eficiência da entrega no pós-venda.


* **Contexto Geral de Uso:** O sistema será utilizado no dia a dia da operação logística em estações de trabalho fixas (computadores de mesa acoplados a leitores de código de barras USB) no galpão, além de disponibilizar um portal leve acessado via dispositivo móvel pelo cliente final.



---

## 2. Problema que o Sistema Resolve

Nas Pequenas e Médias Empresas (PMEs), o controle de pátio e estoque frequentemente depende de planilhas manuais, anotações em papel e memória dos funcionários. Esse cenário gera diversos gargalos operacionais:

* **Erros Manuais e Incompatibilidade de Estoque:** A digitação manual de itens no recebimento causa erros de quantidade e descrição, gerando divergências entre o estoque físico real e os registros do sistema.


* **Perda de Localização Física:** Os produtos são armazenados sem um padrão de endereçamento rigoroso, fazendo com que operadores percam tempo procurando mercadorias no galpão durante a separação dos pedidos.


* **Gargalos Operacionais Ocultos (*Lead Time* Cego):** Os gestores não sabem quanto tempo uma carga leva para ser conferida, guardada, separada e despachada, tornando impossível identificar onde o processo está travado.


* **Falta de Rastreabilidade de Avarias:** Produtos chegam danificados de fornecedores ou sofrem avarias no manuseio interno e acabam misturados aos itens bons, sendo enviados erroneamente para os clientes.


* **Desconexão com a Satisfação do Cliente (Pós-Venda):** A empresa não possui dados concretos sobre se as entregas chegaram no prazo, sem avarias e com as quantidades corretas (*OTIF*).



---

## 3. Objetivos do Sistema

### Objetivo Principal

Controlar e otimizar o fluxo operacional logístico de ponta a ponta (recebimento, armazenamento, separação, embalagem e expedição), garantindo alta acuracidade de estoque, visibilidade do *lead time* das etapas e medição contínua da satisfação do cliente final (OTIF) sem burocracias fiscais ou financeiras.

### Objetivos Específicos

* Automatizar a entrada de mercadorias via importação do XML da NF-e e validação por conferência cega.


* Estruturar o armazenamento físico por endereçamento hierárquico (Rua, Prédio e Nível).


* Organizar as tarefas do galpão em um painel Kanban com priorização automática por Curva ABC (produtos de maior valor/giro no topo).


* Monitorar o tempo gasto em cada etapa do fluxo (*lead time*), sinalizando atrasos e gargalos visualmente.


* Garantir a conferência obrigatória por leitura de código de barras USB durante a separação (*picking*) e embalagem (*packing*).


* Isolar automaticamente produtos avariados em um saldo bloqueado de "Quarentena".


* Enviar questionários automáticos pós-entrega via WhatsApp ou E-mail para mensurar o indicador OTIF.


* Exibir indicadores operacionais atualizados em um dashboard central focado na tomada de decisão do Gestor.



---

## 4. Personas e Perfis de Usuário

| Perfil | Descrição simples | Principais ações no sistema | Permissões básicas |
| --- | --- | --- | --- |
| **Operador** | Conferente, estoquista ou operador de pátio que atua fisicamente no galpão.

 | Importar XML da NF-e, realizar conferência cega via leitor USB, endereçar produtos guardados, movimentar cards no Kanban, efetuar bipagem na separação/embalagem, registrar avarias e realizar ajustes manuais pontuais de estoque.

 | Acesso às telas operacionais (Recebimento, Endereçamento, Kanban e Separação); permissão para registrar ajustes com justificativa padrão. Não pode aprovar divergências de recebimento nem alterar logs de auditoria.

 |
| **Gestor** | Gerente ou supervisor responsável pelo desempenho e controle da logística.

 | Acompanhar o Dashboard executivo, aprovar/liberar divergências no recebimento, visualizar históricos de avarias, reconfigurar limites de tempo (*SLA*) das etapas e auditar/editar logs de alterações manuais de estoque.

 | Acesso irrestrito a todas as áreas, relatórios, configurações de sistema, parametrização de SLAs, gestão de logs de auditoria e liberação de pendências/exceções.

 |
| **Cliente Final** | Destinatário da carga (usuário externo ao sistema).

 | Acessar o formulário web leve (via link ou QR Code recebido) e responder à pesquisa de avaliação da entrega.

 | Acesso restrito exclusivamente à página externa de formulário OTIF vinculada ao seu pedido específico.

 |

---

## 5. Escopo da Primeira Versão (MVP)

### Área Funcional 1: Recebimento e Entrada (Inbound)

* **Importação de XML da NF-e:** Permite carregar o arquivo XML da Nota Fiscal emitida pelo fornecedor para cadastrar ou atualizar automaticamente os dados dos produtos, quantidades, fornecedores e volumes no sistema.


* **Conferência Cega por Bipagem:** O sistema oculta a quantidade esperada e exige que o operador realize a leitura de cada código de barras via leitor USB (ou digitação manual). O sistema compara a contagem física com o XML importado.


* **Tratamento de Divergências de Entrada:** Caso haja escassez, excesso ou avarias na entrada, o sistema dá entrada no saldo correto e envia os itens divergentes para uma fila de exceção, exigindo aprovação manual do Gestor.



### Área Funcional 2: Endereçamento e Guarda (Putaway)

* **Gestão de Estrutura Física:** Permite cadastrar e consultar a localização exata no galpão seguindo a estrutura "Rua - Prédio - Nível" (Ex: R01-P02-N03).


* **Fila de Guarda:** Após a conferência de entrada, o sistema gera automaticamente uma instrução de guarda indicando o produto e o endereço físico de destino.



### Área Funcional 3: Operação e Kanban (Outbound)

* **Quadro Kanban de Tarefas Operacionais:** Painel com as colunas de fluxo: *Recebido* ➔ *A Armazenar* ➔ *A Separar* ➔ *A Expedir*.


* **Priorização Automática por Curva ABC:** Pedidos que contêm produtos de Curva A (alta prioridade e alto giro) são organizados automaticamente no topo da coluna de separação.


* **Alertas Visuais de Lead Time (SLA):** As bordas dos cards no Kanban mudam de cor com base no tempo decorrido na etapa (amarelo para atenção, vermelho para tempo limite excedido).


* **Conferência em Picking e Packing:** Para liberar um pedido para expedição, o operador deve bipar obrigatoriamente código a código (USB ou digitação manual), garantindo que nenhum item vá trocado ou em quantidade incorreta.



### Área Funcional 4: Gestão de Avarias e Quarentena

* **Isolamento de Produtos Danificados:** Permite registrar avarias identificadas durante o recebimento ou a separação.


* **Endereço Virtual de Quarentena:** O sistema transfere a quantidade avariada automaticamente para um saldo bloqueado no endereço virtual "Quarentena", impedindo que esses itens fiquem disponíveis para venda ou separação.



### Área Funcional 5: Avaliação OTIF e Pós-Venda

* **Disparo Automático de Pesquisa:** Assim que o status do pedido é alterado para "Entregue", o sistema dispara automaticamente uma mensagem (WhatsApp/E-mail via API integrada de terceiros) contendo o link de avaliação.


* **Portal de Feedback do Cliente:** Interface externa leve com 3 perguntas objetivas (Prazo, Avaria, Conformidade), opção de anexar até 2 fotos e campo de observações em texto.


* **Expiração de Link:** O link de avaliação do cliente expira automaticamente após 10 dias úteis.


* **Alertas de Ocorrência Crítica:** Avaliações com respostas negativas (avaria ou atraso) geram alertas de prioridade no painel do Gestor.



### Área Funcional 6: Auditoria e Ajustes de Estoque

* **Ajuste Manual de Saldo:** O Operador pode realizar acertos rápidos de quantidade diretamente no endereço físico, selecionando obrigatoriamente um motivo pré-cadastrado em uma lista padrão.


* **Log Permanente de Auditoria:** O ajuste altera o saldo imediatamente e grava um histórico inalterável para o operador, onde apenas o Gestor possui acesso e permissão para gerenciar ou editar o registro.



### Área Funcional 7: Dashboard e Indicadores

* **Painel do Gestor:** Exibe 9 indicadores vitais: Ocupação do estoque, Taxa OTIF acumulada do mês, Gargalos de Lead Time por etapa do Kanban, Histórico de Avarias por fornecedor, Taxa de acuracidade de estoque, Lista de pedidos atrasados, Gráfico de Curva ABC, Endereçamento de cargas e Total de produtos movimentados no dia.



---

## 6. Funcionalidades Fora de Escopo

As seguintes funcionalidades foram ativamente desconsideradas para esta primeira versão, visando manter o MVP viável, enxuto e focado na operação logística de galpão:

* **Módulo Fiscal e Financeiro Completo:** Emissão de NF-e, faturamento, contas a pagar/receber, conciliação bancária e apuração de impostos. (Razão: Aumentaria drasticamente a complexidade do sistema, fugindo do propósito de um WMS operacional focado em PMEs).


* **Integrações com Hardwares Avançados e Automação Industrial:** Leitura de etiquetas RFID, comunicação com esteiras rolantes automatizadas, balanças industriais integradas ou sistemas robóticos (WCS). (Razão: Custo elevado e fora da realidade de PMEs em fase de modernização).


* **Gerador e Impressor Interno de Etiquetas:** Criação de layout e impressão direta de código de barras no sistema. (Razão: Utilização pontual de etiquetas prontas do próprio fornecedor ou do ERP parceiro).


* **Algoritmo de Roteamento de Separação (*Picking Path*):** Cálculo dinâmico da menor rota física a ser percorrida pelo operador dentro do galpão. (Razão: O tamanho dos galpões de PMEs não justifica a complexidade de um algoritmo de roteamento tridimensional no MVP).


* **Módulo de Inventário Cíclico Programado:** Agendamento automático de contagens periódicas rotativas por amostragem de estoque. (Razão: O ajuste manual imediato com log atende às necessidades iniciais de acuracidade).


* **Alertas Automáticos de Estoque Mínimo e Validade:** Disparo de avisos de necessidade de compra/ressuprimento ou produtos vencendo. (Razão: Responsabilidade delegada ao ERP conectado ou a versões futuras).


* **Previsão de Demanda por Inteligência Artificial:** Algoritmos preditivos de compra baseados em sazonalidade histórica. (Razão: Recurso avançado fora do escopo funcional básico).



---

## 7. Regras de Negócio

### Regras de Recebimento e Entrada

* **RN-01 (Obrigatoriedade de XML):** Todo recebimento de mercadoria deve ser iniciado a partir da importação de um arquivo XML de NF-e válido.


* **RN-02 (Conferência Cega):** Na tela do conferente, as quantidades esperadas do XML não devem ser exibidas antes ou durante a bipagem.


* **RN-03 (Aprovação de Divergências):** Se a quantidade bipada for diferente do XML ou contiver avarias, o sistema armazena a quantidade física correta, move a diferença para uma "Fila de Exceções" e exige a aprovação manual do perfil Gestor.



### Regras de Endereçamento e Estoque

* **RN-04 (Padrão de Endereço Físico):** Todo produto guardado no estoque deve estar obrigatoriamente associado a um endereço físico válido no formato "Rua - Prédio - Nível" (Ex: R01-P02-N03).


* **RN-05 (Bloqueio de Quarentena):** Qualquer item marcado como avariado deve ter seu saldo transferido imediatamente para o endereço virtual de "Quarentena". Produtos na Quarentena não podem ser alocados em pedidos de saída nem ser separados.


* **RN-06 (Ajuste Manual e Auditoria):** Ajustes manuais de quantidade no endereço podem ser feitos pelo perfil Operador, mas exigem a seleção de um motivo em uma lista predefinida. A alteração atualiza o saldo na hora e gera um log inalterável pelo Operador, que só pode ser gerido ou alterado pelo Gestor.



### Regras do Kanban e Priorização

* **RN-07 (Priorização ABC):** Pedidos contendo produtos classificados como Curva A devem ser posicionados automaticamente no topo da fila de separação.


* **RN-08 (Tempo Limite por Etapa / SLA):** O tempo limite padrão genérico para a permanência de um pedido em cada etapa do Kanban é de 2 horas. Quando o tempo atinge 80% do limite, a borda do card fica amarela (atenção); se ultrapassar as 2 horas, a borda fica vermelha (atraso). O perfil Gestor pode reconfigurar o tempo de SLA do sistema.


* **RN-09 (Validação de Picking/Packing):** Um pedido só pode avançar da fase de separação/embalagem para expedição se 100% dos seus itens forem validados via bipagem individual de código de barras.



### Regras de Pós-Venda e OTIF

* **RN-10 (Gatilho de Disparo OTIF):** A mudança do status do pedido para "Entregue" dispara automaticamente uma requisição para serviço externo de mensagens (WhatsApp ou E-mail) enviando o link de avaliação ao cliente final.


* **RN-11 (Validade do Link OTIF):** O link do formulário de avaliação do cliente final permanece ativo por exatamente 10 dias úteis a contar do envio. Após esse prazo, o link deve exibir uma mensagem de expiração e impedir o envio de respostas.


* **RN-12 (Ocorrência Crítica no Dashboard):** Qualquer formulário OTIF respondido indicando descumprimento de prazo ou avaria no produto gera um aviso de alta prioridade no dashboard do Gestor e contabiliza negativamente no indicador OTIF do mês.



---

## 8. Informações que o Sistema Precisa Controlar

| Informação | Para que serve no sistema | Observações importantes |
| --- | --- | --- |
| **Produtos** | Armazenar o catálogo de itens movimentados no galpão.

 | Descrição, código de barras, código SKU, unidade de medida e classificação da Curva ABC (A, B ou C).

 |
| **Endereços Físicos** | Mapear as posições físicas do galpão.

 | Identificação formada obrigatoriamente por Rua, Prédio e Nível.

 |
| **Saldos de Estoque** | Registrar a quantidade de cada produto em cada endereço.

 | Vincula produto, endereço físico, quantidade física, lote, data de validade e status do saldo (Disponível vs. Quarentena).

 |
| **Pedidos / Cargas** | Controlar o fluxo operacional no Kanban da entrada à expedição.

 | Dados da NF-e/XML, nome do cliente, status atual, prioridade ABC, timestamps de entrada/saída em cada etapa e histórico de conferência.

 |
| **Registros de Avaria** | Documentar produtos danificados identificados na operação.

 | Quantidade avariada, fotos anexadas, etapa de identificação e fornecedor/transportadora responsável.

 |
| **Logs de Auditoria** | Registrar todas as alterações manuais feitas no estoque.

 | Data/hora, usuário responsável, produto, endereço, quantidade ajustada, motivo padronizado e controle de edição exclusivo do Gestor.

 |
| **Pesquisas OTIF** | Armazenar o feedback enviado pelos clientes finais no pós-venda.

 | Status do prazo (Sim/Não), presença de avarias (Sim/Não), conformidade dos itens (Sim/Não), até 2 fotos anexadas, observações em texto e data de expiração do link.

 |
| **Parâmetros de SLA** | Armazenar as configurações de tempo das etapas operacionais.

 | Define os limites em minutos/horas para cada coluna do Kanban (padrão inicial de 2 horas).

 |

---

## 9. Fluxos Principais de Uso

### Fluxo 1: Recebimento de Mercadoria via XML e Conferência Cega

1. O Operador acessa a área de Recebimento de Mercadorias no sistema.


2. O Operador realiza o upload do arquivo XML da NF-e enviada pelo fornecedor.


3. O sistema processa o XML, registra os dados da carga e abre a tela de Conferência Cega (sem exibir as quantidades esperadas).


4. O Operador bipa o código de barras de cada item físico recebido utilizando o leitor USB (ou digita o código manualmente).


5. O sistema valida as leituras contra os dados ocultos do XML.


6. O Operador confirma o encerramento da conferência.


7. O sistema dá entrada nos produtos conferidos corretamente, gera o card do pedido no Kanban e direciona eventuais divergências para a fila de aprovação do Gestor.



### Fluxo 2: Guardar Produto no Estoque (Putaway)

1. O Operador acessa a coluna "A Armazenar" no quadro Kanban.


2. O Operador seleciona o card da carga recebida e visualiza a instrução de guarda gerada pelo sistema.


3. O Operador transporta a carga até o local físico no galpão.


4. O Operador bipa (ou digita) o código de barras do produto e o código do endereço físico (Rua/Prédio/Nível) para confirmar a alocação.


5. O sistema valida a associação entre o produto e o endereço físico.


6. O Operador confirma a conclusão da guarda.


7. O sistema atualiza a posição do estoque para "Disponível" naquele endereço e move o card no Kanban.



### Fluxo 3: Separação (Picking), Embalagem (Packing) e Expedição

1. O Operador acessa a coluna "A Separar" no quadro Kanban e visualiza os pedidos ordenados com os itens de Curva A no topo.


2. O Operador seleciona o pedido de maior prioridade e dirige-se aos endereços indicados no sistema.


3. O Operador coleta os itens e realiza a bipagem obrigatória item a item no leitor USB.


4. O sistema valida se todos os itens conferem exatamente com a ordem do pedido.


5. O Operador confirma a embalagem e avança o card para "A Expedir".


6. O Operador confirma o despacho da carga com a transportadora.


7. O sistema altera o status do pedido para "Entregue" (ou despachado) e dispara a automação da pesquisa OTIF.



### Fluxo 4: Avaliação OTIF pelo Cliente Final

1. O Cliente Final recebe uma mensagem automática no WhatsApp ou E-mail contendo o link da avaliação pós-entrega.


2. O Cliente acessa o link através do seu dispositivo móvel.


3. O sistema valida o token do link e abre a interface simplificada de avaliação.


4. O Cliente responde às 3 perguntas objetivas (Sim/Não) referentes a Prazo, Avaria e Quantidade Correta.


5. Caso reporte avaria, o Cliente anexa até 2 fotos e digita um comentário opcional.


6. O Cliente clica em "Enviar Avaliação".


7. O sistema armazena a resposta, calcula o indicador OTIF no Dashboard e gera um alerta para o Gestor caso haja insatisfação.



### Fluxo 5: Ajuste Manual de Estoque com Log de Auditoria

1. O Operador identifica uma divergência física e acessa a tela de consulta de endereço.


2. O Operador solicita a alteração manual da quantidade de um produto.


3. O sistema solicita a seleção obrigatória de um motivo na lista padrão (ex: Quebra, Perda, Erro de Contagem).


4. O Operador confirma a nova quantidade e justifica a ação.


5. O sistema valida a entrada de dados e atualiza o saldo do estoque imediatamente.


6. O sistema grava um registro inalterável no Log de Auditoria contendo usuário, data, motivo e alteração realizada.


7. O sistema exibe a atualização e disponibiliza o histórico exclusivamente para consulta e gestão do perfil Gestor.



---

## 10. Histórias de Usuário

* **Como Conferente (Operador),** eu quero importar o XML da NF-e no recebimento **para** cadastrar e conferir os produtos recebidos rapidamente sem precisar digitar manualmente item por item.


* **Como Operador de Pátio,** eu quero realizar a conferência cega via leitor USB **para** garantir que os produtos recebidos batem com a nota fiscal e evitar erros de entrada no estoque.


* **Como Estoquista (Operador),** eu quero vincular um produto a um endereço físico (Rua/Prédio/Nível) **para** saber a localização exata da mercadoria no galpão no momento da separação.


* **Como Operador de Separação,** eu quero visualizar as tarefas em um quadro Kanban ordenado por Curva ABC **para** separar primeiro os pedidos contendo produtos mais valiosos ou de maior giro.


* **Como Operador de Embalagem,** eu quero bipar todos os produtos antes de fechar a caixa **para** garantir que nenhum pedido seja enviado ao cliente com itens faltantes ou trocados.


* **Como Operador,** eu quero registrar itens avariados enviando-os para a Quarentena **para** impedir que mercadorias danificadas sejam enviadas aos clientes por engano.


* **Como Gestor de Logística,** eu quero visualizar as bordas dos cards no Kanban mudando de cor conforme o *lead time* **para** identificar imediatamente em qual etapa a operação está sofrendo atrasos.


* **Como Gestor de Logística,** eu quero acessar um log permanente de ajustes manuais de estoque **para** auditar alterações feitas pelos operadores e manter o controle sobre perdas.


* **Como Cliente Final,** eu quero receber um link simples no WhatsApp/E-mail após a entrega **para** avaliar a qualidade do serviço e reportar eventuais avarias enviando fotos de forma rápida.


* **Como Gestor de Logística,** eu quero consultar o indicador de OTIF acumulado no Dashboard **para** medir a eficiência das entregas e tomar decisões de melhoria no pós-venda.



---

## 11. Critérios de Aceitação

### Recebimento e Conferência Cega

* [ ] O sistema importa com sucesso o arquivo XML da NF-e e extrai os dados do fornecedor, produtos, lote e quantidades.


* [ ] A tela de conferência cega não exibe a quantidade prevista no XML para o Operador durante o processo de bipagem.


* [ ] O sistema aceita a leitura do código de barras por meio do leitor USB e também disponibiliza um campo funcional para digitação manual do código.


* [ ] Se houver divergência entre a contagem física e o XML, o sistema trava a entrada total e envia a pendência para a fila de exceção do Gestor.



### Endereçamento e Kanban

* [ ] Todo produto guardado exige a confirmação de um endereço válido no padrão "Rua - Prédio - Nível".


* [ ] O quadro Kanban organiza automaticamente as colunas (*Recebido*, *A Armazenar*, *A Separar*, *A Expedir*) e coloca os pedidos com produtos de Curva A no topo da fila.


* [ ] O card exibe alerta visual amarelo caso o tempo limite configurado (ex: 2h) atinja 80% do prazo, e borda vermelha quando o prazo for ultrapassado.



### Separação, Embalagem e Quarentena

* [ ] O sistema impede o avanço de um pedido para a etapa de expedição caso a bipagem item a item do *picking/packing* não esteja 100% concluída.


* [ ] Produtos marcados como avariados são movidos imediatamente para o endereço virtual de "Quarentena" e o seu saldo é bloqueado para novas separações.



### Avaliação OTIF e Pós-Venda

* [ ] A alteração do status do pedido para "Entregue" aciona a requisição de envio do link via API de mensagem para o cliente.


* [ ] O formulário externo OTIF abre em dispositivos móveis, contém 3 perguntas objetivas de Sim/Não e permite anexar até 2 fotos.


* [ ] O link de avaliação expira e fica inacessível exatamente após 10 dias úteis da sua geração.



### Auditoria e Dashboard

* [ ] O ajuste manual de saldo exige obrigatoriamente a seleção de um motivo padronizado em uma lista.


* [ ] O log de auditoria é gerado instantaneamente e não permite edições ou exclusões pelo perfil Operador.


* [ ] O Dashboard exibe corretamente os 9 indicadores operacionais parametrizados para o Gestor.



---

## 12. Consultas, Relatórios e Indicadores

### Painel Principal do Gestor (Dashboard Operacional)

O sistema disponibiliza um painel visual consolidado acessível ao perfil Gestor, contendo as seguintes consultas e gráficos:

1. **Taxa de Ocupação do Estoque:** Percentual de posições/endereços físicos ocupados em relação à capacidade total cadastrada no galpão.


2. **Taxa de OTIF Acumulada do Mês:** Porcentagem de entregas realizadas no prazo, sem avarias e em conformidade completa.


3. **Gargalos de Lead Time por Etapa:** Indicador gráfico do tempo médio que os pedidos permanecem em cada coluna do Kanban.


4. **Histórico de Avarias por Fornecedor:** Relatório comparativo exibindo quais fornecedores/transportadoras concentram o maior número de mercadorias danificadas na entrada.


5. **Taxa de Acuracidade de Estoque:** Percentual de precisão entre o saldo do sistema e as conferências físicas realizadas.


6. **Lista de Pedidos Atrasados:** Tabela de consulta rápida listando pedidos que estouraram o SLA da etapa no Kanban.


7. **Gráfico de Curva ABC:** Visualização da distribuição do inventário entre as categorias A (alta prioridade/giro), B (média) e C (baixa).


8. **Endereçamento de Cargas:** Consulta rápida para localizar em qual Rua, Prédio e Nível está posicionado determinado lote ou produto.


9. **Total de Produtos Movimentados no Dia:** Volume total de itens bipados e processados nas etapas de recebimento e expedição nas últimas 24 horas.



### Filtros e Buscas Requeridas

* Busca por código de barras, SKU ou descrição do produto.


* Filtro por intervalo de datas (dia, semana, mês).


* Filtro de pedidos por status no Kanban e por nível de prioridade ABC.


* Filtro do histórico de avarias por fornecedor ou data de recebimento.



---

## 13. Permissões e Segurança Funcional

| Perfil | Pode fazer | Não pode fazer | Observações |
| --- | --- | --- | --- |
| **Operador** | Importar XML de NF-e; realizar conferência cega por bipagem USB ou digitação manual; consultar endereços físicos; realizar guarda de itens; movimentar cards do Kanban; efetuar bipagem em picking e packing; registrar avarias; realizar ajustes manuais de estoque selecionando motivo padronizado.

 | Não pode aprovar divergências de recebimento entre físico e XML; não pode reconfigurar SLAs do Kanban; não pode alterar nem excluir registros do Log de Auditoria; não pode acessar o Dashboard executivo.

 | Perfil voltado estritamente à execução operacional de pátio e galpão.

 |
| **Gestor** | Acessar todas as telas e funcionalidades do sistema; aprovar e liberar divergências de recebimento na fila de exceções; reconfigurar tempos de SLA das etapas do Kanban; visualizar e gerenciar o Log de Auditoria de estoque; visualizar o Dashboard de indicadores; visualizar histórico de avarias e relatórios OTIF.

 | N/A (Possui acesso funcional completo ao sistema).

 | Responsável pela gestão estratégica, parametrização e auditoria.

 |
| **Cliente Final** | Acessar a página externa do formulário OTIF; responder às 3 perguntas da avaliação; anexar até 2 fotos; digitar observações no campo de texto.

 | Não pode acessar nenhuma área interna, tela operacional, cadastro ou relatório do WMS.

 | Usuário externo com acesso temporário limitado pelo token do link de avaliação.

 |

---

## 14. Limitações da Primeira Versão

Para assegurar a viabilidade e a entrega rápida no modelo de Produto Mínimo Viável (MVP), foram estabelecidas as seguintes limitações:

* **Interface Web Responsiva sem Aplicativo Nativo:** A operação será realizada via navegadores web em computadores instalados no galpão acoplados a leitores USB, sem aplicativo mobile instalado (iOS/Android) para a equipe interna.


* **Sem Módulo Fiscal e Emissão de Notas:** O sistema não realiza cálculo de tributos nem emissão de NF-e, dependendo da importação do XML emitido por terceiros/ERPs.


* **Sem Automação por RFID ou Coleta Avançada:** A identificação de produtos é realizada exclusivamente por códigos de barras tradicionais via leitor USB ou entrada manual.


* **Parametrização Genérica de SLA:** O tempo limite padrão das etapas do Kanban inicia configurado em 2 horas de forma genérica para todos os pedidos, podendo ser ajustado globalmente pelo Gestor.


* **Disparo de Mensagens via Serviços de Terceiros:** A entrega de links OTIF depende do funcionamento de serviços integrados de API de mensagens externas (como Evolution API para WhatsApp e Resend/SendGrid para E-mails).



---

## 15. Pontos Pendentes Antes do FSD

Não foram identificadas dúvidas funcionais pendentes para a criação do FSD.

---

## 16. Resumo Final do PRD

### O que será construído

Um sistema web WMS (*Warehouse Management System*) leve, prático e visual para gestão operacional de estoque e pátio em PMEs. O sistema cobre o ciclo operacional do recebimento de mercadorias via XML da NF-e até a expedição e o pós-venda, incluindo conferência cega por leitor USB, endereçamento físico (Rua/Prédio/Nível), Kanban com Curva ABC, controle de *lead time*, quarentena de avarias e coleta de satisfação OTIF.

### Quem usará

* **Operadores:** Executam a conferência, guarda, separação, embalagem e movimentações físicas no galpão.


* **Gestor:** Monitora o Dashboard de indicadores, aprova pendências de recebimento, reconfigura SLAs e audita o estoque.


* **Cliente Final:** Responde ao formulário externo de avaliação da entrega (OTIF).



### O que fica fora do MVP

Módulos fiscais/financeiros, integração com hardwares industriais (RFID, esteiras, robôs), impressor de etiquetas interno, otimização de rotas de separação (*picking path*), inventário cíclico programado e inteligência artificial para previsão de demanda.

### Status do Projeto

O projeto está com **100% dos seus requisitos funcionais mapeados e alinhados**, sem pendências operacionais abertas, estando **totalmente pronto para avançar para a fase de elaboração do FSD (Documento de Especificação Funcional)**.