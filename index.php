<?php
/**
 * Nampula Fodd - Página Inicial
 * Lista todos os restaurantes com filtros
 */
$titulo = 'Início';
require_once 'includes/header.php';
require_once 'config/database.php';

// Filtros
$cidade = $_GET['cidade'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;

// Query base
$sql = "SELECT r.*, 
               COALESCE(AVG(c.nota), 0) as media_estrelas,
               COUNT(c.id) as total_comentarios
        FROM restaurantes r
        LEFT JOIN comentarios c ON c.restaurante_id = r.id
        WHERE 1=1";

$params = [];

if ($cidade) {
    $sql .= " AND r.cidade = ?";
    $params[] = $cidade;
}
if ($tipo) {
    $sql .= " AND r.tipo_comida LIKE ?";
    $params[] = "%$tipo%";
}

$sql .= " GROUP BY r.id ORDER BY r.aberto DESC, media_estrelas DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$restaurantes = $stmt->fetchAll();

// Se tiver GPS, ordenar por distância aproximada (fórmula simples)
if ($lat && $lng) {
    foreach ($restaurantes as &$r) {
        if ($r['latitude'] && $r['longitude']) {
            // Distância aproximada em km (Haversine simplificado)
            $dlat = deg2rad($r['latitude'] - $lat);
            $dlng = deg2rad($r['longitude'] - $lng);
            $a = sin($dlat/2)**2 + cos(deg2rad($lat)) * cos(deg2rad($r['latitude'])) * sin($dlng/2)**2;
            $r['distancia'] = 6371 * 2 * asin(sqrt($a));
        } else {
            $r['distancia'] = 9999;
        }
    }
    usort($restaurantes, fn($a, $b) => $a['distancia'] <=> $b['distancia']);
}
?>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <h1>Comida deliciosa em Nampula e Nacala</h1>
        <p>Os melhores restaurantes, takeaways e lanchonetes num só lugar</p>
    </div>
</section>

<div class="container">
    <!-- Filtros -->
    <form class="filtros" method="GET" action="index.php">
        <select name="cidade">
            <option value="">Todas as cidades</option>
            <option value="Nampula" <?= $cidade === 'Nampula' ? 'selected' : '' ?>>Nampula</option>
            <option value="Nacala-Porto" <?= $cidade === 'Nacala-Porto' ? 'selected' : '' ?>>Nacala-Porto</option>
            <option value="Nacala-a-Velha" <?= $cidade === 'Nacala-a-Velha' ? 'selected' : '' ?>>Nacala-a-Velha</option>
        </select>

        <select name="tipo">
            <option value="">Todos os tipos</option>
            <option value="Moçambicana" <?= $tipo === 'Moçambicana' ? 'selected' : '' ?>>Moçambicana</option>
            <option value="Pizza" <?= $tipo === 'Pizza' ? 'selected' : '' ?>>Pizza</option>
            <option value="Fast Food" <?= $tipo === 'Fast Food' ? 'selected' : '' ?>>Fast Food</option>
            <option value="Marisco" <?= $tipo === 'Marisco' ? 'selected' : '' ?>>Marisco</option>
            <option value="Chinesa" <?= $tipo === 'Chinesa' ? 'selected' : '' ?>>Chinesa</option>
        </select>

        <button type="submit" class="btn-filtrar">
            <i class="fas fa-search"></i> Filtrar
        </button>

        <button type="button" class="btn-gps" id="btnGps">
            <i class="fas fa-location-arrow"></i> Perto de mim
        </button>
    </form>

    <!-- Lista de Restaurantes -->
    <h2 class="secao-titulo">
        <i class="fas fa-store"></i>
        <?= $lat ? 'Restaurantes perto de si' : 'Todos os Restaurantes' ?>
        <small style="font-size:0.9rem;color:#666;font-weight:400;">
            (<?= count($restaurantes) ?> encontrados)
        </small>
    </h2>

    <?php if (empty($restaurantes)): ?>
        <p style="text-align:center;padding:40px;color:#666;">
            Nenhum restaurante encontrado com estes filtros.
        </p>
    <?php else: ?>
        <div class="restaurantes-grid">
            <?php foreach ($restaurantes as $r): ?>
                <div class="card-restaurante">
                    <div class="card-img">
                        <img src="uploads/<?= htmlspecialchars($r['foto'] ?: 'default-rest.jpg') ?>" 
                             alt="<?= htmlspecialchars($r['nome']) ?>"
                             onerror="this.src='https://via.placeholder.com/400x200?text=Restaurante'">
                        <span class="badge-status <?= $r['aberto'] ? 'badge-aberto' : 'badge-fechado' ?>">
                            <?= $r['aberto'] ? 'Aberto' : 'Fechado' ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h3><?= htmlspecialchars($r['nome']) ?></h3>
                        <div class="card-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($r['cidade']) ?></span>
                            <span><i class="fas fa-utensils"></i> <?= htmlspecialchars($r['tipo_comida']) ?></span>
                            <span class="estrelas">
                                <i class="fas fa-star"></i>
                                <?= number_format($r['media_estrelas'], 1) ?>
                                (<?= $r['total_comentarios'] ?>)
                            </span>
                            <?php if (isset($r['distancia']) && $r['distancia'] < 9999): ?>
                                <span><i class="fas fa-walking"></i> <?= number_format($r['distancia'], 1) ?> km</span>
                            <?php endif; ?>
                        </div>
                        <a href="restaurante.php?id=<?= $r['id'] ?>" class="btn-ver">
                            Ver Cardápio
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
