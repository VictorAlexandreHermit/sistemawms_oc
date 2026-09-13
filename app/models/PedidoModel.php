<?php
/**
 * app/models/PedidoModel.php
 * Pedidos/cargas e itens, kanban, timestamps de lead time e token OTIF.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class PedidoModel
{
    public const STATUS_TRANSICOES = [
        'RECEBIDO'     => 'A_ARMAZENAR',
        'A_ARMAZENAR'  => 'A_SEPARAR',
        'A_SEPARAR'    => 'A_EXPEDIR',
        'A_EXPEDIR'    => 'ENTREGUE',
    ];

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

            $sql = 'INSERT INTO pedidos (numero_nota_xml, chave_nfe, fornecedor_nome, cliente_nome, cliente_contato,
                                         status_kanban, prioridade_abc, token_otif)
                    VALUES (:nota, :chave, :fornecedor, :cliente, :contato, :status, :prioridade, :token)';
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
                ORDER BY FIELD(status_kanban, "RECEBIDO", "A_ARMAZENAR", "A_SEPARAR", "A_EXPEDIR"),
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
     */
    public static function classificarSla(array $pedido): array
    {
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
     * Incrementa a quantidade conferida (bipagem USB/manual) de um item.
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

        $up = $pdo->prepare('UPDATE pedido_itens
                             SET quantidade_conferida = quantidade_conferida + 1,
                                 status_conferencia = "CONFERENCIA_EM_ANDAMENTO"
                             WHERE id = :id');
        $up->execute([':id' => $item['id']]);

        $item['quantidade_conferida']++;
        return ['ok' => true, 'mensagem' => 'Bipagem registrada.', 'item' => $item];
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
                             SET quantidade_conferida = quantidade_conferida - 1
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

        // Se todos os itens estiverem guardados, avança para A_SEPARAR
        $restantes = self::itensPendentesDeGuarda($pedidoId);
        $concluido = empty($restantes);
        if ($concluido) {
            self::alterarStatus($pedidoId, 'A_SEPARAR');
        }

        return [
            'ok'        => true,
            'mensagem'  => 'Guarda registrada para ' . (int) $quantidade . ' unidade(s).',
            'concluido' => $concluido,
        ];
    }

    /**
     * Incrementa a bipagem de picking/packing de um item do pedido.
     */
    public static function biparPicking(int $pedidoId, string $codigo): array
    {
        $produto = ProdutoModel::buscarPorCodigoBarras($codigo);
        if ($produto === null) {
            return ['ok' => false, 'mensagem' => 'Código não cadastrado no catálogo.', 'item' => null, 'completo' => false];
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
            return ['ok' => false, 'mensagem' => 'Este item já atingiu a quantidade esperada do pedido.', 'item' => $item, 'completo' => false];
        }

        $novo = $bipado + 1;
        $status = $novo >= $esperado ? 'PICKING_COMPLETO' : 'PICKING_PARCIAL';
        $up = $pdo->prepare('UPDATE pedido_itens
                             SET quantidade_bipada_picking = :qtd, status_conferencia = :status
                             WHERE id = :id');
        $up->execute([':qtd' => $novo, ':status' => $status, ':id' => $item['id']]);

        $item['quantidade_bipada_picking'] = $novo;
        $completo = self::pickingCompleto($pedidoId);

        return ['ok' => true, 'mensagem' => 'Bipagem de picking registrada.', 'item' => $item, 'completo' => $completo];
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

    public static function progressoPicking(int $pedidoId): array
    {
        $itens = self::itens($pedidoId);
        $esperados = 0;
        $bipados   = 0;
        foreach ($itens as $item) {
            $esperados += (int) $item['quantidade_esperada'];
            $bipados   += (int) $item['quantidade_bipada_picking'];
        }
        return [
            'esperados' => $esperados,
            'bipados'   => $bipados,
            'percentual'=> $esperados > 0 ? round(($bipados / $esperados) * 100, 1) : 0,
        ];
    }

    /**
     * Conclui a embalagem e libera a expedição (A_SEPARAR -> A_EXPEDIR).
     */
    public static function concluirEmbalagem(int $pedidoId): array
    {
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_SEPARAR') {
            return ['ok' => false, 'mensagem' => 'Pedido não está na fase de separação.'];
        }
        if (!self::pickingCompleto($pedidoId)) {
            return ['ok' => false, 'mensagem' => 'Bipagem do picking não está 100% concluída.'];
        }
        self::alterarStatus($pedidoId, 'A_EXPEDIR');
        return ['ok' => true, 'mensagem' => 'Pedido embalado e liberado para expedição.'];
    }

    /**
     * Expede o pedido (A_EXPEDIR -> ENTREGUE) e dispara o envio OTIF.
     */
    public static function expedirPedido(int $pedidoId): array
    {
        $pedido = self::buscarPorId($pedidoId);
        if ($pedido === null || $pedido['status_kanban'] !== 'A_EXPEDIR') {
            return ['ok' => false, 'mensagem' => 'Pedido não está na fase de expedição.'];
        }

        self::alterarStatus($pedidoId, 'ENTREGUE');
        $pedido = self::buscarPorId($pedidoId);

        $otifOk = OtifApiHelper::disparar($pedido);

        return [
            'ok'         => true,
            'mensagem'   => 'Pedido entregue. ' . ($otifOk ? 'Link de avaliação OTIF disparado.' : 'Falha no disparo OTIF (verifique a fila).'),
            'otif_ok'    => $otifOk,
        ];
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