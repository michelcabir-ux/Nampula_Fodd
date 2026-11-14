<?php
/**
 * Página de Registo
 * Cliente ou Dono de Restaurante
 */
$titulo = 'Criar Conta';
require_once 'includes/header.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha    = $_POST['senha'] ?? '';
    $tipo     = $_POST['tipo'] ?? 'cliente';

    if (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        // Verifica se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Este email já está registado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha, tipo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $hash, $tipo]);

            $sucesso = 'Conta criada com sucesso! Já pode entrar.';
        }
    }
}
?>

<div class="container">
    <div class="form-container">
        <h2 style="text-align:center;margin-bottom:25px;">
            <i class="fas fa-user-plus" style="color:var(--laranja)"></i> Criar Conta
        </h2>

        <?php if ($erro): ?>
            <div class="alert alert-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alert alert-sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nome completo</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="tel" name="telefone" placeholder="84XXXXXXX">
            </div>
            <div class="form-group">
                <label>Tipo de conta</label>
                <select name="tipo">
                    <option value="cliente">Cliente (quero pedir comida)</option>
                    <option value="restaurante">Dono de Restaurante</option>
                </select>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required minlength="6">
            </div>
            <button type="submit" class="btn-primary">Criar Conta</button>
        </form>

        <p style="text-align:center;margin-top:20px;">
            Já tem conta? <a href="login.php" style="color:var(--laranja)">Entrar</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
