<?php
/**
 * app/controllers/DashboardController.php
 * Dashboard Executivo do Gestor com os 9 indicadores operacionais.
 */

defined('WMS_EXEC') or die('Acesso direto não permitido.');

final class DashboardController
{
    public function actionIndex(): void
    {
        AuthHelper::requireLogin();
        AuthHelper::requirePerfil('GESTOR');

        $pdo = Database::conexao();

        // 1) Ocupação do estoque (apenas produtos ativos no catálogo)
        $ocupacao = $pdo->query(
            'SELECT COALESCE(SUM(e.capacidade_maxima),0) AS cap,
                    COALESCE((SELECT SUM(es.quantidade)
                              FROM estoque_saldos es
                              INNER JOIN produtos p ON p.id = es.produto_id AND p.deleted_at IS NULL
                              WHERE es.status_saldo = "DISPONIVEL"),0) AS usado
             FROM enderecos e WHERE e.deleted_at IS NULL AND e.quarantena = 0'
        )->fetch();

        // 2) Taxa OTIF acumulada do mês
        $taxasMes = $pdo->query(
            'SELECT COUNT(*) AS total,
                    SUM(prazo_cumprido="SIM") AS prazo_ok,
                    SUM(sem_avaria="SIM") AS avaria_ok,
                    SUM(conformidade_itens="SIM") AS conformidade_ok
             FROM pesquisas_otif
             WHERE respondido_em IS NOT NULL
               AND respondido_em >= DATE_FORMAT(CURDATE(), "%Y-%m-01")'
        )->fetch();
        $perfeitasMes = $pdo->query(
            'SELECT COUNT(*) AS c FROM pesquisas_otif
             WHERE respondido_em IS NOT NULL
               AND respondido_em >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
               AND prazo_cumprido="SIM" AND sem_avaria="SIM" AND conformidade_itens="SIM"'
        )->fetch()['c'];
        $totMes = (int) $taxasMes['total'];

        // 3) Gargalos de lead time por etapa
        $gargalos = [];
        foreach (['RECEBIDO', 'A_ARMAZENAR', 'A_SEPARAR', 'A_EMBALAR', 'A_EXPEDIR', 'EM_TRANSITO'] as $etapa) {
            $coluna = [
                'RECEBIDO'    => 'ts_recebido',
                'A_ARMAZENAR' => 'ts_a_armazenar',
                'A_SEPARAR'   => 'ts_a_separar',
                'A_EMBALAR'   => 'ts_a_embalar',
                'A_EXPEDIR'   => 'ts_a_expedir',
                'EM_TRANSITO' => 'ts_em_transito',
            ][$etapa];
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS qtd,
                        COALESCE(AVG(TIMESTAMPDIFF(MINUTE, ' . $coluna . ', NOW())),0) AS media
                 FROM pedidos
                 WHERE status_kanban = :s'
            );
            $stmt->execute([':s' => $etapa]);
            $reg = $stmt->fetch();
            $gargalos[$etapa] = [
                'qtd'   => (int) $reg['qtd'],
                'media' => (int) round((float) $reg['media']),
                'limite'=> ConfigSlaModel::limiteDaEtapa($etapa),
            ];
        }

        // 4) Avarias por fornecedor
        $avarias = AvariaModel::ocorrenciasPorFornecedor();

        // 5) Acuracidade de estoque (proxy: conferências sem divergência)
        $totItens = $pdo->query(
            'SELECT COUNT(*) AS c FROM pedido_itens WHERE quantidade_conferida > 0'
        )->fetch()['c'];
        $itensDivergentes = $pdo->query(
            'SELECT COUNT(DISTINCT pedido_id, produto_id) AS c FROM divergencias_recebimento WHERE status_aprovacao = "APROVADO"'
        )->fetch()['c'];
        $acuracidade = $totItens > 0
            ? round((1 - ((int) $itensDivergentes / (int) $totItens)) * 100, 1)
            : 100.0;

        // 6) Pedidos atrasados
        $atrasados = [];
        foreach (PedidoModel::listarKanban() as $p) {
            $p['sla'] = PedidoModel::classificarSla($p);
            if ($p['sla']['classe'] === 'atrasado') {
                $atrasados[] = $p;
            }
        }

        // 7) Curva ABC (contagem de pedidos e itens)
        $abc = $pdo->query(
            'SELECT p.prioridade_abc, COUNT(DISTINCT p.id) AS pedidos,
                    COUNT(pi.id) AS itens
             FROM pedidos p
             LEFT JOIN pedido_itens pi ON pi.pedido_id = p.id
             GROUP BY p.prioridade_abc'
        )->fetchAll();

        // 8) Endereçamento de cargas
        $cargasAguardando = 0;
        $itensAguardando  = 0;
        foreach (PedidoModel::listarPorStatus('A_ARMAZENAR') as $carga) {
            $cargasAguardando++;
            $itensAguardando += count(PedidoModel::itensPendentesDeGuarda((int) $carga['id']));
        }
        $enderecadasHoje = $pdo->query(
            'SELECT COUNT(*) AS c FROM pedidos
             WHERE status_kanban IN ("ARMAZENADO","A_SEPARAR","A_EMBALAR","A_EXPEDIR","EM_TRANSITO","ENTREGUE")
               AND ts_armazenado >= CURDATE()'
        )->fetch()['c'];

        // 9) Produtos movimentados no dia
        $movimentadoshoje = $pdo->query(
            'SELECT COUNT(DISTINCT es.produto_id) AS c FROM estoque_saldos es
             INNER JOIN produtos p ON p.id = es.produto_id AND p.deleted_at IS NULL
             WHERE es.quantidade > 0 AND DATE(es.updated_at) = CURDATE()'
        )->fetch()['c'];

        ViewHelper::render('dashboard/index', [
            'titulo'    => 'Dashboard Executivo',
            'subtitulo' => 'Panorama da operação em tempo real — atualização automática a cada acesso.',
            'ocupacao'      => $ocupacao,
            'taxasMes'      => ['total' => $totMes, 'perfeitas' => (int) $perfeitasMes,
                                'otif' => $totMes > 0 ? round(((int) $perfeitasMes / $totMes) * 100, 1) : 0.0,
                                'prazo_ok' => (int) $taxasMes['prazo_ok'], 'avaria_ok' => (int) $taxasMes['avaria_ok'], 'conformidade_ok' => (int) $taxasMes['conformidade_ok']],
            'gargalos'      => $gargalos,
            'avarias'       => $avarias,
            'acuracidade'   => $acuracidade,
            'atrasados'     => $atrasados,
            'abc'           => $abc,
            'enderecamento' => ['aguardando' => $cargasAguardando, 'itens' => $itensAguardando, 'hoje' => (int) $enderecadasHoje],
            'movimentados'  => (int) $movimentadoshoje,
        ]);
    }
}