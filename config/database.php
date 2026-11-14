<?php
/**
 * Nampula Fodd - Configuração da Base de Dados
 * Altere estes dados conforme o seu servidor de hospedagem
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Altere para o usuário do seu hosting
define('DB_PASS', '');              // Altere para a senha do seu hosting
define('DB_NAME', 'nampula_fodd');

// Conexão PDO (mais segura e moderna que mysqli para iniciantes também)
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão com a base de dados: " . $e->getMessage());
}

// Função auxiliar para redirecionar
function redirect($url) {
    header("Location: $url");
    exit;
}

// Inicia a sessão em todas as páginas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
