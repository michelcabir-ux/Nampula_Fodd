<?php
/**
 * CRUD do Cardápio + Definir Prato do Dia
 */
$titulo = 'Gerir Cardápio';
require_once '../includes/header.php';
proteger(['restaurante']);

$user = utilizadorAtual();
$stmt = $pdo->prepare("SELECT * FROM restaurantes WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$rest = $stmt->fetch();

if (!$rest) {
    redirect('criar_restaurante.php');
}

$msg = '';

// Apagar produto
if (isset($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ? AND restaurante_id = ?");
    $stmt->execute([$id, $rest['id']]);
    $msg = '<div class="alert alert-sucesso">Produto apagado.</div>';
}

// Definir / remover prato do dia
if (isset($_GET['prato_dia'])) {
    $id = (int)$_GET['prato_dia'];
    // Remove prato do dia actual
    $pdo->prepare("UPDATE produtos SET prato_do_dia = 0 WHERE restaurante_id = ?")->execute([$rest['id']]);
    // Define o novo
    $desconto = floatval($_GET['desconto'] ?? 10);
    $stmt = $pdo->prepare("UPDATE produtos SET prato_do_dia = 1, desconto_prato_dia = ? WHERE id = ? AND restaurante_id = ?");
    $stmt->execute([$desconto, $id, $rest['id']]);
    $msg = '<div class="alert alert-sucesso">Prato do dia actualizado!</div>';
}

// Adicionar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = floatval($_POST['preco'] ?? 0);
    $categoria = trim($_POST['categoria'] ?? 'Geral');

    if ($nome && $preco > 0) {
        if ($id > 0) {
            // Editar
            $stmt = $pdo->prepare("UPDATE produtos SET nome=?, descricao=?, preco=?, categoria=? WHERE id=? AND restaurante_id=?");
            $stmt->execute([$nome, $descricao, $preco, $categoria, $id, $rest['id']]);
            $msg = '<div class="alert alert-sucesso">Produto actualizado.</div>';
        } else {
            // Novo
            $stmt = $pdo->prepare("INSERT INTO produtos (restaurante_id, nome, descricao, preco, categoria) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$rest['id'], $nome, $descricao, $preco, $categoria]);
            $msg = '<div class="alert alert-sucesso">Produto adicionado!</div>';
        }
    }
}

// Lista
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE restaurante_id = ? ORDER BY prato_do_dia DESC, categoria, nome");
$stmt->execute([$rest['id']]);
$produtos = $stmt->fetchAll();

// Se estiver a editar
$editando = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    foreach ($produtos as $p) {
        if ($p['id'] == $id) {
            $editando = $p;
            break;
        }
    }
}
?>

<div class="container" style="padding-top:30px;">
    <h1 class="secao-titulo"><i class="fas fa-utensils"></i> Gerir Cardápio</h1>
    <p><a href="dashboard.php" style="color:var(--laranja)">← Voltar ao Dashboard</a></p>
    <?= $msg ?>

    <!-- Formulário -->
    <div class="form-container" style="max-width:500px;margin:20px 0 40px;">
        <h3><?= $editando ? 'Editar Produto' : 'Adicionar Novo Prato' ?></h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $editando['id'] ?? 0 ?>">
            <div class="form-group">
                <label>Nome *</label>
                <input type="text" name="nome" required value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" rows="2"><?= htmlspecialchars($editando['descricao'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Preço (MT) *</label>
                <input type="number" name="preco" step="0.01" min="1" required value="<?= $editando['preco'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria" value="<?= htmlspecialchars($editando['categoria'] ?? 'Geral') ?>" 
                       placeholder="Ex: Entradas, Pratos, Bebidas">
            </div>
            <button type="submit" class="btn-primary"><?= $editando ? 'Actualizar' : 'Adicionar' ?></button>
            <?php if ($editando): ?>
                <a href="cardapio.php" style="display:block;text-align:center;margin-top:10px;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Lista de produtos -->
    <h2 class="secao-titulo">Produtos (<?= count($produtos) ?>)</h2>
    <?php if (empty($produtos)): ?>
        <p>Ainda não tem produtos. Adicione o primeiro!</p>
    <?php else: ?>
        <table class="tabela">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Prato do Dia</th>
                    <th>Acções</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($p['nome']) ?>
                            <?php if ($p['prato_do_dia']): ?>
                                <span class="badge-dia" style="margin-left:8px;">🔥 DIA</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['categoria']) ?></td>
                        <td><?= number_format($p['preco'], 2) ?> MT</td>
                        <td>
                            <?php if ($p['prato_do_dia']): ?>
                                <?= number_format($p['desconto_prato_dia'], 0) ?>% desconto
                            <?php else: ?>
                                <a href="?prato_dia=<?= $p['id'] ?>&desconto=10" style="color:var(--laranja);">
                                    Definir como Prato do Dia
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?editar=<?= $p['id'] ?>" style="color:var(--laranja);margin-right:10px;"><i class="fas fa-edit"></i></a>
                            <a href="?apagar=<?= $p['id'] ?>" style="color:var(--erro);" data-confirm="Apagar este produto?"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
