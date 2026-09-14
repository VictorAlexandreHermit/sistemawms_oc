<?php
/**
 * app/models/PedidoModel.php
 * Pedidos/cargas e itens, kanban, timestamps de lead time e token OTIF.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class PedidoModel
{
    public const STATUS_TRANSICOES = [
        // Recebimento (inbound): conferência -> guarda -> repouso (armazenado)
        'RECEBIDO'     => 'A_ARMAZENAR',
        'A_ARMAZENAR'  => 'ARMAZENADO',
        // Venda (outbound): picking -> packing -> expedição -> rota -> entrega
        'A_SEPARAR'    => 'A_EMBALAR',
        'A_EMBALAR'    => 'A_EXPEDIR',
        'A_EXPEDIR'    => 'EM_TRANSITO',
        'EM_TRANSITO'  => 'ENTREGUE',
    ];

    public const STATUS_PIPELINE = [
        'RECEBIDO', 'A_ARMAZENAR', 'ARMAZENADO', 'A_SEPARAR', 'A_EMBALAR', 'A_EXPEDIR', 'EM_TRANSITO',
    ];

    public static function rotuloStatus(string $status): string
    {
        return [
            'RECEBIDO'     => 'Recebido',
            'A_ARMAZENAR'  => 'A Armazenar',
            'ARMAZENADO'   => 'Armazenado',
            'A_SEPARAR'    => 'A Separar',
            'A_EMBALAR'    => 'A Embalar',
            'A_EXPEDIR'    => 'A Expedir',
            'EM_TRANSITO'  => 'Em trânsito',
            'ENTREGUE'     => 'Entregue',
        ][$status] ?? $status;
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    public static function buscarPorToken(string $token): ?array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE token_otif = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $reg = $stmt->fetch();
        return $reg ?: null;
    }

    /**
     * Cria o pedido + itens + registro OTIF a partir do XML importado.
     */
    public static function criarDoXml(array $xml): int
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            $chave = $xml['chave_nfe'] !== '' ? $xml['chave_nfe'] : SecurityHelper::tokenAleatorio(22);
            $token = SecurityHelper::tokenAleatorio(32);

            $sql = 'INSERT INTO pedidos (tipo, numero_nota_xml, chave_nfe, fornecedor_nome, cliente_nome, cliente_contato,
                                         status_kanban, prioridade_abc, token_otif)
                    VALUES ("RECEBIMENTO", :nota, :chave, :fornecedor, :cliente, :contato, :status, :prioridade, :token)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nota'      => mb_substr($xml['numero_nota'], 0, 50),
                ':chave'     => $chave,
                ':fornecedor'=> mb_substr($xml['fornecedor'] ?? '', 0, 150),
                ':cliente'   => mb_substr($xml['cliente_nome'] ?: 'SEM DESTINATÁRIO', 0, 100),
                ':contato'   => mb_substr($xml['cliente_contato'] ?? '', 0, 100),
                ':status'    => 'RECEBIDO',
                ':prioridade'=> 'C',
                ':token'     => $token,
            ]);
            $pedidoId = (int) $pdo->lastInsertId();

            foreach ($xml['itens'] as $itemXml) {
                $produto = ProdutoModel::obterOuCriar($itemXml);
                $ins = $pdo->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade_esperada, quantidade_conferida)
                                      VALUES (:pedido, :produto, :qtd, 0)');
                $ins->execute([
                    ':pedido' => $pedidoId,
                    ':produto'=> $produto['id'],
                    ':qtd'    => (int) round((float) $itemXml['quantidade']),
                ]);
            }

            self::definirPrioridadeAbc($pedidoId);

            $expiracao = DateHelper::adicionarDiasUteis(DateHelper::agora(), (int) APP_CONFIG['otif']['expiracao_dias_uteis']);
            $otif = $pdo->prepare('INSERT INTO pesquisas_otif (pedido_id, token_acesso, data_envio, data_expiracao)
                                   VALUES (:pedido, :token, NULL, :expiracao)');
            $otif->execute([
                ':pedido'  => $pedidoId,
                ':token'   => $token,
                ':expiracao' => $expiracao->format('Y-m-d H:i:s'),
            ]);

            $pdo->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            throw new RuntimeException('Não foi possível registrar a carga recebida.');
        }
    }

    /**
     * Cria um recebimento manual (sem XML) por código de barras.
     * A conferência é considerada 100% registrada na própria digitação,
     * então a carga é liberada direto para a Guarda (putaway).
     *
     * @param array  $itens     [ ['produto_id' => int, 'quantidade' => int], ... ]
     * @param string $fornecedor Emitente/fornecedor opcional da entrada.
     */
    public static function criarManual(array $itens, string $fornecedor = ''): int
    {
        if (empty($itens)) {
            throw new RuntimeException('Informe ao menos um item para a entrada manual.');
        }

        // Agrega linhas do mesmo produto (o picking bipa por produto)
        $agrupados = [];
        foreach ($itens as $item) {
            $produtoId = (int) $item['produto_id'];
            $agrupados[$produtoId] = ($agrupados[$produtoId] ?? 0) + max(1, (int) $item['quantidade']);
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            $chave = SecurityHelper::tokenAleatorio(22);
            $token = SecurityHelper::tokenAleatorio(32);
            $nota  = 'MAN-' . date('Ymd-His');

            $sql = 'INSERT INTO pedidos (tipo, numero_nota_xml, chave_nfe, fornecedor_nome, cliente_nome,
                                         status_kanban, prioridade_abc, token_otif)
                    VALUES ("RECEBIMENTO", :nota, :chave, :fornecedor, :cliente, :status, :prioridade, :token)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nota'       => mb_substr($nota, 0, 50),
                ':chave'      => $chave,
                ':fornecedor' => mb_substr(trim($fornecedor) ?: 'ENTRADA MANUAL', 0, 150),
                ':cliente'    => 'OPERAÇÃO INTERNA',
                ':status'     => 'RECEBIDO',
                ':prioridade' => 'C',
                ':token'      => $token,
            ]);
            $pedidoId = (int) $pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade_esperada, quantidade_conferida, status_conferencia)
                                  VALUES (:pedido, :produto, :qtd, :qtd, "CONFERIDO")');
            foreach ($agrupados as $produtoId => $qtd) {
                $ins->execute([
                    ':pedido'  => $pedidoId,
                    ':produto' => $produtoId,
                    ':qtd'     => $qtd,
                ]);
            }

            self::definirPrioridadeAbc($pedidoId);

            $expiracao = DateHelper::adicionarDiasUteis(DateHelper::agora(), (int) APP_CONFIG['otif']['expiracao_dias_uteis']);
            $otif = $pdo->prepare('INSERT INTO pesquisas_otif (pedido_id, token_acesso, data_envio, data_expiracao)
                                   VALUES (:pedido, :token, NULL, :expiracao)');
            $otif->execute([
                ':pedido'    => $pedidoId,
                ':token'     => $token,
                ':expiracao' => $expiracao->format('Y-m-d H:i:s'),
            ]);

            // Conferência 100% registrada: libera direto para a Guarda (putaway)
            self::alterarStatus($pedidoId, 'A_ARMAZENAR');

            $pdo->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            throw new RuntimeException('Não foi possível registrar a entrada manual.');
        }
    }

    public static function definirPrioridadeAbc(int $pedidoId): void
    {
        $pdo = Database::conexao();
        $sql = 'SELECT MIN(p.curva_abc) AS prioridade
                FROM pedido_itens pi
                INNER JOIN produtos p ON p.id = pi.produto_id
                WHERE pi.pedido_id = :pedido';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pedido' => $pedidoId]);
        $reg = $stmt->fetch();

        // MENOR letra no universo ABC significa maior prioridade (A < B < C)
        $prioridade = $reg && $reg['prioridade'] ? $reg['prioridade'] : 'C';
        $up = $pdo->prepare('UPDATE pedidos SET prioridade_abc = :pri WHERE id = :id');
        $up->execute([':pri' => $prioridade, ':id' => $pedidoId]);
    }

    public static function itens(int $pedidoId): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT pi.*, p.sku, p.codigo_barras, p.descricao, p.unidade_medida, p.curva_abc
                FROM pedido_itens pi
                INNER JOIN produtos p ON p.id = pi.produto_id
                WHERE pi.pedido_id = :pedido
                ORDER BY p.curva_abc, p.sku';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pedido' => $pedidoId]);
        return $stmt->fetchAll();
    }

    public static function listarKanban(): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM pedidos
                WHERE status_kanban <> "ENTREGUE"
                ORDER BY FIELD(status_kanban, "RECEBIDO", "A_ARMAZENAR", "ARMAZENADO", "A_SEPARAR", "A_EMBALAR", "A_EXPEDIR", "EM_TRANSITO"),
                         FIELD(prioridade_abc, "A", "B", "C"),
                         ts_recebido ASC';
        return $pdo->query($sql)->fetchAll();
    }

    public static function listarPorStatus(string $status): array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE status_kanban = :s
                               ORDER BY FIELD(prioridade_abc, "A", "B", "C"), ts_recebido ASC');
        $stmt->execute([':s' => $status]);
        return $stmt->fetchAll();
    }

    public static function entreguesRecentes(int $limite = 10): array
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE status_kanban = "ENTREGUE"
                               ORDER BY ts_entregue DESC LIMIT :limite');
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Avança o pedido para o próximo status do kanban, gravando o timestamp
     * da etapa de destino. Retorna o novo status ou null (sem mudança válida).
     */
    public static function avancar(int $pedidoId): ?string
    {
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null) {
            return null;
        }
        $novo = self::STATUS_TRANSICOES[$pedido['status_kanban']] ?? null;
        if ($novo === null) {
            return null;
        }
        self::alterarStatus($pedidoId, $novo);
        return $novo;
    }

    public static function alterarStatus(int $pedidoId, string $novoStatus): void
    {
        $pdo = Database::conexao();
        $coluna = 'ts_' . strtolower($novoStatus);
        $stmt = $pdo->prepare('UPDATE pedidos SET status_kanban = :status, ' . $coluna . ' = COALESCE(' . $coluna . ', NOW())
                               WHERE id = :id');
        $stmt->execute([':status' => $novoStatus, ':id' => $pedidoId]);
    }

    /**
     * Timestamp de início da etapa atual.
     */
    public static function tsInicioEtapa(array $pedido): ?string
    {
        return $pedido['ts_' . strtolower($pedido['status_kanban'])] ?? null;
    }

    public static function minutosNaEtapa(array $pedido): int
    {
        $inicio = self::tsInicioEtapa($pedido);
        if ($inicio === null) {
            return 0;
        }
        try {
            $inicioDt = new DateTime($inicio, new DateTimeZone(CFG_TIMEZONE));
        } catch (\Throwable $e) {
            $inicioDt = new DateTime($inicio);
        }
        $agora = new DateTime('now', new DateTimeZone(CFG_TIMEZONE));
        return DateHelper::minutosEntre($inicioDt, $agora);
    }

    /**
     * Classifica o estado de SLA do card (ok / atencao / atrasado).
     * Mercadorias ARMAZENADO ficam em repouso (sem cronômetro de sla).
     */
    public static function classificarSla(array $pedido): array
    {
        if (($pedido['status_kanban'] ?? '') === 'ARMAZENADO') {
            return ['classe' => 'ok', 'minutos' => 0, 'limite' => 0, 'porcentagem' => 0];
        }

        $limite = ConfigSlaModel::limiteDaEtapa($pedido['status_kanban']);
        $decorrido = self::minutosNaEtapa($pedido);
        $porcentagem = $limite > 0 ? ($decorrido / $limite) * 100 : 0;

        if ($decorrido >= $limite) {
            $classe = 'atrasado';
        } elseif ($porcentagem >= 80) {
            $classe = 'atencao';
        } else {
            $classe = 'ok';
        }

        return [
            'classe'       => $classe,
            'minutos'      => $decorrido,
            'limite'       => $limite,
            'porcentagem'  => round($porcentagem, 1),
        ];
    }

    /**
     * Registra a bipagem de conferência do Recebimento por PRODUTO:
     * 1 leitura de código de barras já confirma o item inteiro da nota
     * (a unidade de carga vem etiquetada com o código na embalagem/caixa),
     * eliminando a necessidade de bipar unidade por unidade.
     *
     * @return array ['ok' => bool, 'mensagem' => string, 'item' => ?array]
     */
    public static function conferirBipagem(int $pedidoId, string $codigo): array
    {
        $produto = ProdutoModel::buscarPorCodigoBarras($codigo);
        if ($produto === null) {
            return ['ok' => false, 'mensagem' => 'Código não cadastrado no catálogo de produtos.', 'item' => null];
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedido_itens WHERE pedido_id = :pedido AND produto_id = :produto LIMIT 1');
        $stmt->execute([':pedido' => $pedidoId, ':produto' => $produto['id']]);
        $item = $stmt->fetch();

        if ($item === false) {
            return ['ok' => false, 'mensagem' => 'Código bipado não pertence a esta carga (nota fiscal).', 'item' => null];
        }

        if ((int) $item['quantidade_conferida'] >= (int) $item['quantidade_esperada']) {
            return ['ok' => false, 'mensagem' => 'Este produto já foi conferido na carga.', 'item' => $item];
        }

        $up = $pdo->prepare('UPDATE pedido_itens
                             SET quantidade_conferida = quantidade_esperada,
                                 status_conferencia = "CONFERIDO"
                             WHERE id = :id');
        $up->execute([':id' => $item['id']]);

        $item['quantidade_conferida'] = (int) $item['quantidade_esperada'];
        $item['status_conferencia'] = 'CONFERIDO';
        return ['ok' => true, 'mensagem' => 'Produto conferido com 1 leitura (embalagem etiquetada).', 'item' => $item];
    }

    /**
     * Finaliza a conferência cega: compara com o XML, cria divergências e
     * avança o pedido para A_ARMAZENAR (gerando instrução de guarda).
     *
     * @return array Resumo da conferência
     */
    public static function finalizarConferencia(int $pedidoId): array
    {
        $pdo = Database::conexao();
        $itens = self::itens($pedidoId);

        $resumo = [
            'total_itens'    => count($itens),
            'itens_ok'       => 0,
            'divergencias'   => 0,
        ];

        foreach ($itens as $item) {
            $esperado = (int) $item['quantidade_esperada'];
            $conferido = (int) $item['quantidade_conferida'];

            if ($conferido === $esperado) {
                $up = $pdo->prepare("UPDATE pedido_itens SET status_conferencia = 'CONFERIDO' WHERE id = :id");
                $up->execute([':id' => $item['id']]);
                $resumo['itens_ok']++;
            } else {
                $up = $pdo->prepare("UPDATE pedido_itens SET status_conferencia = 'DIVERGENTE' WHERE id = :id");
                $up->execute([':id' => $item['id']]);
                DivergenciaModel::criar($pedidoId, (int) $item['produto_id'], $esperado, $conferido);
                $resumo['divergencias']++;
            }
        }

        // Avança para A_ARMAZENAR (gera instrução de guarda)
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido && $pedido['status_kanban'] === 'RECEBIDO') {
            self::alterarStatus($pedidoId, 'A_ARMAZENAR');
        }

        return $resumo;
    }

    public static function desfazerBipagem(int $pedidoId, int $produtoId): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedido_itens WHERE pedido_id = :pedido AND produto_id = :produto LIMIT 1');
        $stmt->execute([':pedido' => $pedidoId, ':produto' => $produtoId]);
        $item = $stmt->fetch();
        if ($item === false || (int) $item['quantidade_conferida'] <= 0) {
            return;
        }
        $up = $pdo->prepare('UPDATE pedido_itens
                             SET quantidade_conferida = 0,
                                 status_conferencia = "PENDENTE"
                             WHERE id = :id');
        $up->execute([':id' => $item['id']]);
    }

    /**
     * Itens de um pedido em A_ARMAZENAR que ainda possuem saldo a guardar.
     */
    public static function itensPendentesDeGuarda(int $pedidoId): array
    {
        $itens = self::itens($pedidoId);
        $pendentes = [];
        foreach ($itens as $item) {
            $aGuardar = (int) $item['quantidade_conferida'] - (int) $item['quantidade_guardada'];
            if ($aGuardar > 0) {
                $item['a_guardar'] = $aGuardar;
                $pendentes[] = $item;
            }
        }
        return $pendentes;
    }

    /**
     * Executa a guarda física de um item do pedido no endereço informado.
     *
     * @return array ['ok'=>bool, 'mensagem'=>string, 'concluido'=>bool]
     */
    public static function guardarItem(int $pedidoId, int $produtoId, int $enderecoId): array
    {
        $itens = self::itensPendentesDeGuarda($pedidoId);
        $item = null;
        foreach ($itens as $i) {
            if ((int) $i['produto_id'] === $produtoId) {
                $item = $i;
                break;
            }
        }
        if ($item === null) {
            return ['ok' => false, 'mensagem' => 'Produto não possui saldo pendente de guarda nesta carga.', 'concluido' => false];
        }

        $quantidade = (int) $item['a_guardar'];

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            EstoqueModel::entrada($produtoId, $enderecoId, $quantidade, null, null, 'DISPONIVEL');

            $up = $pdo->prepare('UPDATE pedido_itens SET quantidade_guardada = quantidade_guardada + :qtd WHERE id = :id');
            $up->execute([':qtd' => $quantidade, ':id' => $item['id']]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            return ['ok' => false, 'mensagem' => 'Não foi possível registrar a guarda.', 'concluido' => false];
        }

        // Se todos os itens estiverem guardados, a carga passa a repouso
        // (ARMAZENADO): o produto fica em estoque no endereço físico e só sai
        // quando um Pedido de Venda (aba Pedidos) o direciona ao Picking.
        $restantes = self::itensPendentesDeGuarda($pedidoId);
        $concluido = empty($restantes);
        if ($concluido) {
            self::alterarStatus($pedidoId, 'ARMAZENADO');
        }

        return [
            'ok'        => true,
            'mensagem'  => 'Guarda registrada para ' . (int) $quantidade . ' unidade(s).',
            'concluido' => $concluido,
        ];
    }

    /**
     * Registra a bipagem de picking/packing por PRODUTO: 1 leitura do código
     * de barras já confirma o item inteiro (a unidade de carga vem etiquetada
     * com o código na embalagem/caixa), eliminando a bipagem unidade a unidade.
     */
    public static function biparPicking(int $pedidoId, string $codigo): array
    {
        // Após o recebimento gerar o SKU, o processo inteiro pesquisa APENAS
        // pelo SKU (o código de barras fornecedor pode se repetir).
        $produto = ProdutoModel::buscarPorSku($codigo);
        if ($produto === null) {
            return ['ok' => false, 'mensagem' => 'Nenhum produto com este SKU. Digite apenas o SKU (ex.: SIS-002).', 'item' => null, 'completo' => false];
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedido_itens WHERE pedido_id = :pedido AND produto_id = :produto LIMIT 1');
        $stmt->execute([':pedido' => $pedidoId, ':produto' => $produto['id']]);
        $item = $stmt->fetch();

        if ($item === false) {
            return ['ok' => false, 'mensagem' => 'Código bipado não pertence a este pedido.', 'item' => null, 'completo' => false];
        }

        $esperado = (int) $item['quantidade_esperada'];
        $bipado   = (int) $item['quantidade_bipada_picking'];

        if ($bipado >= $esperado) {
            return ['ok' => false, 'mensagem' => 'Este produto já foi separado no pedido.', 'item' => $item, 'completo' => false];
        }

        $novo = $esperado;
        $up = $pdo->prepare('UPDATE pedido_itens
                             SET quantidade_bipada_picking = :qtd, status_conferencia = "PICKING_COMPLETO"
                             WHERE id = :id');
        $up->execute([':qtd' => $novo, ':id' => $item['id']]);

        $item['quantidade_bipada_picking'] = $novo;
        $item['status_conferencia'] = 'PICKING_COMPLETO';
        $completo = self::pickingCompleto($pedidoId);

        return ['ok' => true, 'mensagem' => 'Produto separado com 1 leitura (embalagem etiquetada).', 'item' => $item, 'completo' => $completo];
    }

    public static function pickingCompleto(int $pedidoId): bool
    {
        $itens = self::itens($pedidoId);
        foreach ($itens as $item) {
            if ((int) $item['quantidade_bipada_picking'] < (int) $item['quantidade_esperada']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Desfaz a bipagem de picking de um produto (volta o item a pendente).
     */
    public static function desfazerPicking(int $pedidoId, int $produtoId): void
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM pedido_itens WHERE pedido_id = :pedido AND produto_id = :produto LIMIT 1');
        $stmt->execute([':pedido' => $pedidoId, ':produto' => $produtoId]);
        $item = $stmt->fetch();
        if ($item === false || (int) $item['quantidade_bipada_picking'] <= 0) {
            return;
        }
        $up = $pdo->prepare('UPDATE pedido_itens
                             SET quantidade_bipada_picking = 0,
                                 status_conferencia = "PENDENTE"
                             WHERE id = :id');
        $up->execute([':id' => $item['id']]);
    }

    public static function progressoPicking(int $pedidoId): array
    {
        $itens = self::itens($pedidoId);
        $esperados = count($itens);
        $bipados   = 0;
        foreach ($itens as $item) {
            if ((int) $item['quantidade_bipada_picking'] >= (int) $item['quantidade_esperada']) {
                $bipados++;
            }
        }
        return [
            'esperados' => $esperados,
            'bipados'   => $bipados,
            'percentual'=> $esperados > 0 ? round(($bipados / $esperados) * 100, 1) : 0,
        ];
    }

    /**
     * Cria um PEDIDO DE VENDA simulado (aba Pedidos) que alimenta o Picking.
     * Só aceita produtos com saldo DISPONIVEL suficiente. O pedido nasce em
     * A_SEPARAR e só assim a mercadoria entra no fluxo de separação.
     *
     * @param array  $itens    [ ['produto_id' => int, 'quantidade' => int], ... ]
     * @param string $cliente  Nome do cliente destinatário do pedido.
     * @param string $contato  Contato do cliente (telefone/e-mail, opcional).
     *
     * @return int ID do pedido de venda criado.
     */
    public static function criarVenda(array $itens, string $cliente, string $contato = ''): int
    {
        $cliente = trim($cliente);
        if ($cliente === '') {
            throw new RuntimeException('Informe o nome do cliente do pedido.');
        }

        $agrupados = [];
        foreach ($itens as $item) {
            $produtoId = (int) $item['produto_id'];
            if ($produtoId <= 0) {
                continue;
            }
            $qtd = max(1, (int) $item['quantidade']);
            $agrupados[$produtoId] = ($agrupados[$produtoId] ?? 0) + $qtd;
        }

        if (empty($agrupados)) {
            throw new RuntimeException('Informe ao menos um item para o pedido.');
        }

        // Valida saldo disponível antes de abrir o pedido
        foreach ($agrupados as $produtoId => $qtd) {
            $saldo = EstoqueModel::saldoTotalDisponivel($produtoId);
            if ($saldo < $qtd) {
                $produto = ProdutoModel::buscarPorId($produtoId);
                $nome = $produto ? $produto['sku'] . ' - ' . $produto['descricao'] : ('#' . $produtoId);
                throw new RuntimeException(
                    'Estoque insuficiente para ' . SecurityHelper::e(mb_strimwidth($nome, 0, 60, '…')) .
                    ' (solicitado ' . $qtd . ', disponível ' . $saldo . ').'
                );
            }
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            $chave = SecurityHelper::tokenAleatorio(22);
            $token = SecurityHelper::tokenAleatorio(32);
            $nota  = 'PDV-' . date('Ymd-His');

            $sql = 'INSERT INTO pedidos (tipo, numero_nota_xml, chave_nfe, fornecedor_nome, cliente_nome,
                                         cliente_contato, status_kanban, prioridade_abc, token_otif, ts_a_separar)
                    VALUES ("VENDA", :nota, :chave, "PEDIDO INTERNO", :cliente, :contato,
                            "A_SEPARAR", "C", :token, NOW())';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nota'     => mb_substr($nota, 0, 50),
                ':chave'    => $chave,
                ':cliente'  => mb_substr($cliente, 0, 100),
                ':contato'  => mb_substr($contato, 0, 100),
                ':token'    => $token,
            ]);
            $pedidoId = (int) $pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT INTO pedido_itens
                                  (pedido_id, produto_id, quantidade_esperada, quantidade_conferida, status_conferencia)
                                  VALUES (:pedido, :produto, :qtd, 0, "PENDENTE")');
            foreach ($agrupados as $produtoId => $qtd) {
                $ins->execute([':pedido' => $pedidoId, ':produto' => $produtoId, ':qtd' => $qtd]);
            }

            self::definirPrioridadeAbc($pedidoId);

            $expiracao = DateHelper::adicionarDiasUteis(DateHelper::agora(), (int) APP_CONFIG['otif']['expiracao_dias_uteis']);
            $otif = $pdo->prepare('INSERT INTO pesquisas_otif (pedido_id, token_acesso, data_envio, data_expiracao)
                                   VALUES (:pedido, :token, NULL, :expiracao)');
            $otif->execute([
                ':pedido'    => $pedidoId,
                ':token'     => $token,
                ':expiracao' => $expiracao->format('Y-m-d H:i:s'),
            ]);

            $pdo->commit();
            return $pedidoId;
        } catch (RuntimeException $e) {
            $pdo->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            throw new RuntimeException('Não foi possível abrir o pedido de venda.');
        }
    }

    /**
     * Transforma uma carga ARMAZENADO no pedido de venda que a faz avançar
     * ao Picking (Mesmo pedido/id: o card do kanban segue o fluxo completo).
     * Só vale para cargas em repouso físico (ARMAZENADO) e com saldo
     * DISPONIVEL suficiente para os itens.
     *
     * @return array Pedido já convertido
     */
    public static function abrirVendaDaCarga(int $pedidoId, string $destinatario, string $contato = ''): array
    {
        $destinatario = trim($destinatario);
        if ($destinatario === '') {
            throw new RuntimeException('Informe o destinatário/cliente da venda.');
        }

        $pdo = Database::conexao();
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null) {
            throw new RuntimeException('Processo não encontrado no fluxo.');
        }
        if ($pedido['status_kanban'] !== 'ARMAZENADO') {
            throw new RuntimeException('Só é possível liberar ao Picking uma carga que está em Armazenado.');
        }

        foreach (self::itens($pedidoId) as $item) {
            $saldo = EstoqueModel::saldoTotalDisponivel((int) $item['produto_id']);
            if ($saldo < (int) $item['quantidade_esperada']) {
                $nome = $item['sku'] . ' - ' . $item['descricao'];
                throw new RuntimeException(
                    'Estoque já comprometido para ' . SecurityHelper::e(mb_strimwidth($nome, 0, 60, '…')) .
                    ' (solicitado ' . (int) $item['quantidade_esperada'] . ', disponível ' . $saldo . ').'
                );
            }
        }

        $stmt = $pdo->prepare('UPDATE pedidos
                               SET tipo = "VENDA",
                                   status_kanban = "A_SEPARAR",
                                   cliente_nome = :dest,
                                   cliente_contato = :cont,
                                   ts_a_separar = COALESCE(ts_a_separar, NOW())
                               WHERE id = :id');
        $stmt->execute([
            ':dest' => mb_substr($destinatario, 0, 100),
            ':cont' => mb_substr(trim($contato), 0, 100),
            ':id'   => $pedidoId,
        ]);

        self::definirPrioridadeAbc($pedidoId);
        return self::buscarPorId($pedidoId);
    }

    /**
     * Pedidos de venda abertos para o Picking.
     */
    public static function listarVendas(?string $status = null): array
    {
        $pdo = Database::conexao();
        $sql = 'SELECT * FROM pedidos WHERE tipo = "VENDA"';
        $params = [];
        if ($status !== null && in_array($status, self::STATUS_PIPELINE, true)) {
            $sql .= ' AND status_kanban = :s';
            $params[':s'] = $status;
        }
        $sql .= ' ORDER BY FIELD(status_kanban, "A_SEPARAR", "A_EMBALAR", "A_EXPEDIR", "EM_TRANSITO", "ENTREGUE"),
                         FIELD(prioridade_abc, "A", "B", "C"), ts_a_separar DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Conclui o PICKING (A_SEPARAR -> A_EMBALAR): confere a bipagem 100% e
     * BAIXA DO ESTOQUE a quantidade separada, atualizando a acuracidade do
     * dashboard em tempo real.
     */
    public static function concluirPicking(int $pedidoId): array
    {
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_SEPARAR') {
            return ['ok' => false, 'mensagem' => 'Pedido não está na fase de separação (Picking).'];
        }
        if (!self::pickingCompleto($pedidoId)) {
            return ['ok' => false, 'mensagem' => 'Bipagem do picking não está 100% concluída.'];
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            foreach (self::itens($pedidoId) as $item) {
                EstoqueModel::baixarProduto((int) $item['produto_id'], (int) $item['quantidade_esperada']);
            }
            self::alterarStatus($pedidoId, 'A_EMBALAR');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            return ['ok' => false, 'mensagem' => $e->getMessage() ?: 'Não foi possível concluir o picking.'];
        }

        return ['ok' => true, 'mensagem' => 'Picking 100% concluído e estoque baixado. Pedido enviado ao Packing.'];
    }

    /**
     * Conclui a embalagem/packing (A_EMBALAR -> A_EXPEDIR) e libera a expedição.
     */
    public static function concluirEmbalagem(int $pedidoId): array
    {
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_EMBALAR') {
            return ['ok' => false, 'mensagem' => 'Pedido não está na fase de embalagem (Packing).'];
        }
        if (!self::pickingCompleto($pedidoId)) {
            return ['ok' => false, 'mensagem' => 'Picking não registrado corretamente para este pedido.'];
        }
        self::alterarStatus($pedidoId, 'A_EXPEDIR');
        return ['ok' => true, 'mensagem' => 'Pedido embalado e liberado para a Expedição.'];
    }

    /**
     * Inicia a expedição (A_EXPEDIR -> EM_TRANSITO): gera a nota e coloca a
     * mercadoria em rota — "Mercadoria em Trânsito".
     */
    public static function iniciarExpedicao(int $pedidoId, string $transportadora = ''): array
    {
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_EXPEDIR') {
            return ['ok' => false, 'mensagem' => 'Pedido não está na fila de expedição.'];
        }
        self::alterarStatus($pedidoId, 'EM_TRANSITO');
        return [
            'ok'      => true,
            'nota'    => $pedido['numero_nota_xml'],
            'mensagem' => 'Mercadoria em trânsito.',
        ];
    }

    /**
     * Confirma a entrega ao destinatário (EM_TRANSITO -> ENTREGUE) e registra
     * automaticamente uma avaliação OTIF POSITIVA para o pedido.
     */
    public static function confirmarEntrega(int $pedidoId): array
    {
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'EM_TRANSITO') {
            return ['ok' => false, 'mensagem' => 'Pedido não está em trânsito.'];
        }

        self::alterarStatus($pedidoId, 'ENTREGUE');
        $pedido = self::buscarPorId($pedidoId);

        $pesquisa = PesquisaOtifModel::doPedido($pedidoId);
        if ($pesquisa !== null) {
            PesquisaOtifModel::registrarAvaliacaoPositiva((int) $pesquisa['id']);
        }

        return [
            'ok'       => true,
            'mensagem' => 'Produto entregue ao destinatário. Avaliação OTIF positiva registrada.',
        ];
    }

    /**
     * Remove por completo um pedido do fluxo (kanban/filas) e todos os seus
     * registros dependentes (OTIF, divergências, avarias e itens em cascata).
     * Uso exclusivo do Administrador (processo inválido travado no fluxo).
     */
    public static function excluir(int $pedidoId): void
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM pesquisas_otif WHERE pedido_id = :id')->execute([':id' => $pedidoId]);
            $pdo->prepare('DELETE FROM divergencias_recebimento WHERE pedido_id = :id')->execute([':id' => $pedidoId]);
            $pdo->prepare('DELETE FROM avarias WHERE pedido_id = :id')->execute([':id' => $pedidoId]);
            $pdo->prepare('DELETE FROM pedidos WHERE id = :id')->execute([':id' => $pedidoId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            LogHelper::registrarErro($e);
            throw new RuntimeException('Não foi possível excluir o processo.');
        }
    }

    public static function conferenciaPendente(int $pedidoId): bool
    {
        $itens = self::itens($pedidoId);
        foreach ($itens as $item) {
            if ($item['status_conferencia'] !== 'CONFERIDO') {
                return true;
            }
        }
        return false;
    }
}