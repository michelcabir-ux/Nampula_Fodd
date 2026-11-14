<?php
/**
 * MINHA CARTEIRA - Página mais importante da monetização
 * Mostra tudo o que o ADMIN ganhou com os 10% de comissão
 */
$titulo = 'Minha Carteira - Admin';
require_once '../includes/header.php';
proteger(['admin']);

// Total de comissões
$totalComissao = $pdo->query("SELECT COALESCE(SUM(minha_comissao_10), 0) as total 
    FROM pedidos WHERE status NOT IN ('recusado', 'cancelado')")->fetch()['total'];

// Comissões por status de saque
$porSaque = $pdo->query("SELECT status_saque, 
    COUNT(*) as qtd, 
    SUM(minha_comissao_10) as comissao,
    SUM(valor_restaurante_90) as valor_rest
    FROM pedidos 
    WHERE status NOT IN ('recusado', 'cancelado')
    GROUP BY status_saque")->fetchAll();

// Todos os pedidos com detalhe da comissão
$pedidos = $pdo->query("SELECT p.*, r.nome as rest_nome, u.nome as cliente_nome
    FROM pedidos p
    JOIN restaurantes r ON r.id = p.restaurante_id
    JOIN usuarios u ON u.id = p.cliente_id
    WHERE p.status NOT IN ('recusado', 'cancelado')
    ORDER BY p.criado_em DESC")->fetchAll();

// Marcar saque como pago
if (isset($_GET['pagar_saque'])) {
    $id = (int)$_GET['pagar_saque'];
    $pdo->prepare("UPDATE pedidos SET status_saque = 'pago' WHERE id = ?")->execute([$id]);
    
    // Zera o saldo a receber do restaurante desse pedido
    $stmt = $pdo->prepare("SELECT restaurante_id, valor_restaurante_90 FROM pedidos WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
        $pdo->prepare("UPDATE restaurantes SET saldo_a_receber = saldo_a_receber - ? WHERE id = ?")
            ->execute([$p['valor_restaurante_90'], $p['restaurante_id']]);
    }
    redirect('carteira.php');
}
?>

<div class="container" style="padding-top:30px;">
    <h1 class="secao-titulo"><i class="fas fa-coins"></i> Minha Carteira (Comissões)</h1>
    <p style="margin-bottom:25px;"><a href="dashboard.php" style="color:var(--laranja)">← Voltar ao Dashboard</a></p>

    <!-- Resumo -->
    <div class="dashboard-grid">
        <div class="stat-card" style="background:linear-gradient(135deg,#FF6B00,#E05A00);color:white;">
            <h3 style="color:white;font-size:2.2rem;"><?= number_format($totalComissao, 2) ?> MT</h3>
            <p style="color:rgba(255,255,255,0.9)">Total ganho com 10%</p>
        </div>
        <?php foreach ($porSaque as $s): ?>
            <div class="stat-card">
                <h3><?= number_format($s['comissao'], 2) ?> MT</h3>
                <p><?= ucfirst(str_replace('_', ' ', $s['status_saque'])) ?> (<?= $s['qtd'] ?> pedidos)</p>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="background:#FFF8F0;border:2px solid var(--laranja);border-radius:12px;padding:20px;margin:30px 0;">
        <h3 style="color:var(--laranja);margin-bottom:10px;">Como funciona a monetização</h3>
        <ol style="padding-left:20px;line-height:1.8;">
            <li>Cliente faz pedido e paga (carteira ou na entrega).</li>
            <li>O valor total entra no sistema.</li>
            <li>Automaticamente o sistema retém <strong>10%</strong> de comissão para si (ADMIN).</li>
            <li>O restaurante fica com <strong>90%</strong> e pode solicitar saque.</li>
            <li>Quando o restaurante solicita saque, você marca como "pago" aqui.</li>
        </ol>
    </div>

    <h2 class="secao-titulo">Tabela de Pedidos e Comissões</h2>
    <table class="tabela">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Restaurante</th>
                <th>Valor Total</th>
                <th>Minha Comissão 10%</th>
                <th>Valor Rest. 90%</th>
                <th>Status Saque</th>
                <th>Acção</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pedidos)): ?>
                <tr><td colspan="8" style="text-align:center;">Ainda não há pedidos.</td></tr>
            <?php else: ?>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?><br><small><?= date('d/m/Y', strtotime($p['criado_em'])) ?></small></td>
                        <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
                        <td><?= htmlspecialchars($p['rest_nome']) ?></td>
                        <td><?= number_format($p['valor_total'], 2) ?> MT</td>
                        <td style="color:var(--sucesso);font-weight:700;"><?= number_format($p['minha_comissao_10'], 2) ?> MT</td>
                        <td><?= number_format($p['valor_restaurante_90'], 2) ?> MT</td>
                        <td>
                            <span class="badge-status" style="background:<?= 
                                $p['status_saque'] === 'pago' ? 'var(--sucesso)' : 
                                ($p['status_saque'] === 'solicitado' ? 'var(--laranja)' : '#999') 
                            ?>">
                                <?= ucfirst(str_replace('_', ' ', $p['status_saque'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['status_saque'] === 'solicitado'): ?>
                                <a href="?pagar_saque=<?= $p['id'] ?>" class="btn-ver" style="padding:6px 12px;font-size:0.85rem;"
                                   data-confirm="Confirmar pagamento do saque ao restaurante?">
                                    Marcar como Pago
                                </a>
                            <?php elseif ($p['status_saque'] === 'pago'): ?>
                                <span style="color:var(--sucesso);"><i class="fas fa-check"></i> Pago</span>
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
