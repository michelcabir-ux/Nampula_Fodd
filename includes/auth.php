<?php
/**
 * Funções de autenticação e verificação de permissões
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Verifica se o utilizador está logado
 */
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

/**
 * Retorna os dados do utilizador logado
 */
function utilizadorAtual() {
    global $pdo;
    if (!estaLogado()) return null;

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetch();
}

/**
 * Verifica se o utilizador tem um determinado tipo
 */
function temPermissao($tipo) {
    $user = utilizadorAtual();
    return $user && $user['tipo'] === $tipo;
}

/**
 * Protege páginas - redireciona se não tiver permissão
 */
function proteger($tiposPermitidos = []) {
    if (!estaLogado()) {
        redirect('/nampula-fodd/login.php');
    }
    if (!empty($tiposPermitidos)) {
        $user = utilizadorAtual();
        if (!in_array($user['tipo'], $tiposPermitidos)) {
            redirect('/nampula-fodd/index.php');
        }
    }
}

/**
 * Faz login do utilizador
 */
function fazerLogin($email, $senha) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_tipo'] = $user['tipo'];
        return true;
    }
    return false;
}

/**
 * Faz logout
 */
function fazerLogout() {
    session_destroy();
    redirect('/nampula-fodd/index.php');
}
?>
