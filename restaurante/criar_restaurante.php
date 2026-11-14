<?php
/**
 * Criar / Registar o Restaurante (uma vez)
 */
$titulo = 'Registar Restaurante';
require_once '../includes/header.php';
proteger(['restaurante']);

$user = utilizadorAtual();

// Já tem restaurante?
$stmt = $pdo->prepare("SELECT id FROM restaurantes WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
if ($stmt->fetch()) {
    redirect('dashboard.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $cidade = $_POST['cidade'] ?? '';
    $tipo = trim($_POST['tipo_comida'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $lat = floatval($_POST['latitude'] ?? 0);
    $lng = floatval($_POST['longitude'] ?? 0);

    if (!$nome || !$cidade) {
        $erro = 'Nome e cidade são obrigatórios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO restaurantes 
            (usuario_id, nome, descricao, cidade, tipo_comida, endereco, latitude, longitude) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $nome, $descricao, $cidade, $tipo, $endereco, $lat ?: null, $lng ?: null]);
        redirect('dashboard.php');
    }
}
?>

<div class="container">
    <div class="form-container" style="max-width:550px;">
        <h2 style="text-align:center;margin-bottom:25px;">Registar o seu Restaurante</h2>

        <?php if ($erro): ?>
            <div class="alert alert-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nome do Restaurante *</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Cidade *</label>
                <select name="cidade" required>
                    <option value="Nampula">Nampula</option>
                    <option value="Nacala-Porto">Nacala-Porto</option>
                    <option value="Nacala-a-Velha">Nacala-a-Velha</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Comida</label>
                <input type="text" name="tipo_comida" placeholder="Ex: Moçambicana, Pizza, Fast Food">
            </div>
            <div class="form-group">
                <label>Endereço</label>
                <input type="text" name="endereco">
            </div>
            <div class="form-group">
                <label>Latitude (Google Maps)</label>
                <input type="text" name="latitude" placeholder="-15.1167">
            </div>
            <div class="form-group">
                <label>Longitude (Google Maps)</label>
                <input type="text" name="longitude" placeholder="39.2667">
            </div>
            <p style="font-size:0.85rem;color:#666;margin-bottom:15px;">
                Dica: Abra o Google Maps, clique com o botão direito no local e copie as coordenadas.
            </p>
            <button type="submit" class="btn-primary">Criar Restaurante</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
