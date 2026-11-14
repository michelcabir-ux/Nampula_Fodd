<?php
/**
 * Página do Restaurante - Cardápio completo
 */
require_once 'config/database.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT r.*, 
    COALESCE(AVG(c.nota), 0) as media_estrelas,
    COUNT(c.id) as total_comentarios
    FROM restaurantes r
    LEFT JOIN comentarios c ON c.restaurante_id = r.id
    WHERE r.id = ?
    GROUP BY r.id");
$stmt->execute([$id]);
$rest = $stmt->fetch();

if (!$rest) {
    header('Location: index.php');
    exit;
}

$titulo = $rest['nome'];
require_once 'includes/header.php';

// Produtos
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE restaurante_id = ? AND ativo = 1 ORDER BY prato_do_dia DESC, categoria, nome");
$stmt->execute([$id]);
$produtos = $stmt->fetchAll();

// Separar prato do dia
$pratoDia = null;
$outros = [];
foreach ($produtos as $p) {
    if ($p['prato_do_dia']) {
        $pratoDia = $p;
    } else {
        $outros[] = $p;
    }
}

// Comentários
$stmt = $pdo->prepare("SELECT c.*, u.nome as cliente_nome 
    FROM comentarios c 
    JOIN usuarios u ON u.id = c.cliente_id 
    WHERE c.restaurante_id = ? 
    ORDER BY c.criado_em DESC LIMIT 10");
$stmt->execute([$id]);
$comentarios = $stmt->fetchAll();
?>

<div class="container" style="padding-top:30px;">
    <!-- Cabeçalho do restaurante -->
    <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--sombra);margin-bottom:30px;">
        <div style="display:flex;flex-wrap:wrap;gap:25px;align-items:center;">
            <img src="uploads/<?= htmlspecialchars($rest['foto'] ?: 'default-rest.jpg') ?>" 
                 alt="<?= htmlspecialchars($rest['nome']) ?>"
                 style="width:150px;height:150px;object-fit:cover;border-radius:12px;"
                 onerror="this.src='https://via.placeholder.com/150?text=Rest'">
            
            <div style="flex:1;">
                <h1 style="margin-bottom:8px;"><?= htmlspecialchars($rest['nome']) ?></h1>
                <p style="color:#666;margin-bottom:12px;"><?= htmlspecialchars($rest['descricao']) ?></p>
                
                <div style="display:flex;flex-wrap:wrap;gap:15px;margin-bottom:15px;">
                    <span><i class="fas fa-map-marker-alt" style="color:var(--laranja)"></i> <?= htmlspecialchars($rest['cidade']) ?></span>
                    <span><i class="fas fa-utensils" style="color:var(--laranja)"></i> <?= htmlspecialchars($rest['tipo_comida']) ?></span>
                    <span class="estrelas">
                        <i class="fas fa-star"></i> <?= number_format($rest['media_estrelas'], 1) ?>
                        (<?= $rest['total_comentarios'] ?> avaliações)
                    </span>
                    <span class="badge-status <?= $rest['aberto'] ? 'badge-aberto' : 'badge-fechado' ?>">
                        <?= $rest['aberto'] ? 'Aberto agora' : 'Fechado' ?>
                    </span>
                </div>

                <?php if ($rest['latitude'] && $rest['longitude']): ?>
                    <a href="https://www.google.com/maps?q=<?= $rest['latitude'] ?>,<?= $rest['longitude'] ?>" 
                       target="_blank" class="btn-ver" style="display:inline-block;width:auto;padding:10px 20px;">
                        <i class="fas fa-map"></i> Ver no Mapa
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Prato do Dia -->
    <?php if ($pratoDia): 
        $precoFinal = $pratoDia['preco'] * (1 - $pratoDia['desconto_prato_dia']/100);
    ?>
        <div class="prato-do-dia">
            <img src="uploads/<?= htmlspecialchars($pratoDia['foto'] ?: 'default-prato.jpg') ?>" 
                 alt="<?= htmlspecialchars($pratoDia['nome']) ?>"
                 onerror="this.src='https://via.placeholder.com/120?text=Prato'">
            <div>
                <span class="badge-dia">🔥 PRATO DO DIA - <?= number_format($pratoDia['desconto_prato_dia'], 0) ?>% OFF</span>
                <h2 style="margin:10px 0 5px;"><?= htmlspecialchars($pratoDia['nome']) ?></h2>
                <p style="color:#666;"><?= htmlspecialchars($pratoDia['descricao']) ?></p>
                <p>
                    <span style="text-decoration:line-through;color:#999;"><?= number_format($pratoDia['preco'], 2) ?> MT</span>
                    <span class="preco" style="margin-left:10px;"><?= number_format($precoFinal, 2) ?> MT</span>
                </p>
                <?php if (estaLogado() && temPermissao('cliente')): ?>
                    <button class="btn-add btn-add-carrinho" data-id="<?= $pratoDia['id'] ?>" style="width:auto;margin-top:10px;">
                        <i class="fas fa-plus"></i> Adicionar ao Carrinho
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cardápio -->
    <h2 class="secao-titulo"><i class="fas fa-book-open"></i> Cardápio</h2>

    <?php if (empty($outros) && !$pratoDia): ?>
        <p>Este restaurante ainda não tem produtos no cardápio.</p>
    <?php else: ?>
        <div class="produtos-grid">
            <?php foreach ($outros as $p): ?>
                <div class="card-produto">
                    <img src="uploads/<?= htmlspecialchars($p['foto'] ?: 'default-prato.jpg') ?>" 
                         alt="<?= htmlspecialchars($p['nome']) ?>"
                         onerror="this.src='https://via.placeholder.com/250x150?text=Prato'">
                    <div class="info">
                        <small style="color:#888;"><?= htmlspecialchars($p['categoria']) ?></small>
                        <h3 style="margin:5px 0;"><?= htmlspecialchars($p['nome']) ?></h3>
                        <p style="font-size:0.9rem;color:#666;"><?= htmlspecialchars($p['descricao']) ?></p>
                        <p class="preco"><?= number_format($p['preco'], 2) ?> MT</p>
                        
                        <?php if (estaLogado() && temPermissao('cliente') && $rest['aberto']): ?>
                            <button class="btn-add btn-add-carrinho" data-id="<?= $p['id'] ?>">
                                <i class="fas fa-plus"></i> Adicionar
                            </button>
                        <?php elseif (!$rest['aberto']): ?>
                            <button class="btn-add" disabled style="background:#ccc;">Fechado</button>
                        <?php else: ?>
                            <a href="login.php" class="btn-add" style="display:block;text-align:center;text-decoration:none;">
                                Entrar para pedir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Comentários -->
    <h2 class="secao-titulo" style="margin-top:50px;"><i class="fas fa-comments"></i> Avaliações</h2>
    
    <?php if (empty($comentarios)): ?>
        <p style="color:#666;">Ainda não há avaliações para este restaurante.</p>
    <?php else: ?>
        <div style="display:grid;gap:15px;">
            <?php foreach ($comentarios as $c): ?>
                <div style="background:white;padding:20px;border-radius:12px;box-shadow:var(--sombra);">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <strong><?= htmlspecialchars($c['cliente_nome']) ?></strong>
                        <span class="estrelas">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $c['nota'] ? '' : '-o' ?>"></i>
                            <?php endfor; ?>
                        </span>
                    </div>
                    <p><?= htmlspecialchars($c['texto']) ?></p>
                    <small style="color:#999;"><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
