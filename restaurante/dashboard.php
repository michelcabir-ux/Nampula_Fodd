<?php
/**
 * Dashboard do Restaurante
 * Aceitar/Recusar pedidos + ver saldo
 */
$titulo = 'Dashboard Restaurante';
require_once '../includes/header.php';
proteger(['restaurante']);

$user = utilizadorAtual();

// Busca o restaurante do dono
$stmt = $pdo->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$rest = $stmt->fetch();

if (!$rest) {
    // Ainda não criou o restaurante
    echo '<div class="container" style="padding:40px;"><div class="alert alert-erro">Ainda não registou o seu restaurante. <a href="criar_restaurante.php">Criar agora</a></div></div>';
    require_once '../includes/footer.php';
    exit;
}

// Acções de pedidos
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $pedidoId = (int)$_GET['id'];
    $acao = $_GET['acao'];

    $statusMap = [
        'aceitar' => 'aceito',
        'recusar' => 'recusado',
        'preparar' => 'em_preparo',
        'entregar' => 'entregue'
    ];

    if (isset($statusMap[$acao])) {
        $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ? AND restaurante_id = ?");
        $stmt->execute([$statusMap[$acao], $pedidoId, $rest['id']]);
    }
    redirect('dashboard.php');
}

// Solicitar saque
if (isset($_GET['solicitar_saque'])) {
    $stmt = $pdo->prepare("UPDATE pedidos SET status_saque = 'solicitado' 
        WHERE restaurante_id = ? AND status = 'entregue' AND status_saque = 'nao_solicitado'");
    $stmt->execute([$rest['id']]);
    redirect('dashboard.php');
}

// Pedidos pendentes / recentes
$stmt = $pdo->prepare("SELECT p.*, u.nome as cliente_nome, u.telefone as cliente_tel
    FROM pedidos p 
    JOIN usuarios u ON u.id = p.cliente_id
    WHERE p.restaurante_id = ?
    ORDER BY FIELD(p.status, 'pendente', 'aceito', 'em_preparo', 'entregue', 'recusado', 'cancelado'), p.criado_em DESC
    LIMIT 30");
$stmt->execute([$rest['id']]);
$pedidos = $stmt->fetchAll();

// Estatísticas
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
    SUM(valor_restaurante_90) as total_ganho
    FROM pedidos WHERE restaurante_id = ? AND status != 'recusado'");
$stmt->execute([$rest['id']]);
$stats = $stmt->fetch();
?>

<div class="container" style="padding-top:30px;">
    <h1 class="secao-titulo"><i class="fas fa-tachometer-alt"></i> Dashboard - <?= htmlspecialchars($rest['nome']) ?></h1>

    <div class="dashboard-grid">
        <div class="stat-card">
            <h3><?= $stats['pendentes'] ?? 0 ?></h3>
            <p>Pedidos Pendentes</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats['total_pedidos'] ?? 0 ?></h3>
            <p>Total de Pedidos</p>
        </div>
        <div class="stat-card">
            <h3><?= number_format($rest['saldo_a_receber'], 2) ?> MT</h3>
            <p>Saldo a Receber (90%)</p>
        </div>
        <div class="stat-card">
            <a href="cardapio.php" class="btn-ver" style="display:block;">Gerir Cardápio</a>
            <a href="?solicitar_saque=1" class="btn-ver" style="display:block;margin-top:10px;background:#28a745;" 
               data-confirm="Solicitar saque de todos os valores disponíveis?">
                Solicitar Saque
            </a>
        </div>
    </div>

    <h2 class="secao-titulo"><i class="fas fa-clipboard-list"></i> Pedidos</h2>

    <?php if (empty($pedidos)): ?>
        <p>Ainda não há pedidos.</p>
    <?php else: ?>
        <table class="tabela">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Seu valor (90%)</th>
                    <th>Status</th>
                    <th>Saque</th>
                    <th>Acções</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($p['cliente_nome']) ?><br>
                            <small><?= htmlspecialchars($p['cliente_tel']) ?></small>
                        </td>
                        <td><?= number_format($p['valor_total'], 2) ?> MT</td>
                        <td><?= number_format($p['valor_restaurante_90'], 2) ?> MT</td>
                        <td>
                            <span class="badge-status" style="background:<?= 
                                $p['status'] === 'entregue' ? 'var(--sucesso)' : 
                                ($p['status'] === 'pendente' ? 'var(--laranja)' : 
                                ($p['status'] === 'recusado' ? 'var(--erro)' : '#666')) 
                            ?>">
                                <?= ucfirst(str_replace('_', ' ', $p['status'])) ?>
                            </span>
                        </td>
                        <td><?= ucfirst(str_replace('_', ' ', $p['status_saque'])) ?></td>
                        <td>
                            <?php if ($p['status'] === 'pendente'): ?>
                                <a href="?acao=aceitar&id=<?= $p['id'] ?>" style="color:var(--sucesso);margin-right:8px;" title="Aceitar">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="?acao=recusar&id=<?= $p['id'] ?>" style="color:var(--erro);" title="Recusar" data-confirm="Recusar este pedido?">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php elseif ($p['status'] === 'aceito'): ?>
                                <a href="?acao=preparar&id=<?= $p['id'] ?>" style="color:var(--laranja);">Em preparo</a>
                            <?php elseif ($p['status'] === 'em_preparo'): ?>
                                <a href="?acao=entregar&id=<?= $p['id'] ?>" style="color:var(--sucesso);">Marcar entregue</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
