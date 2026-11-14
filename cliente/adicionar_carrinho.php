<?php
/**
 * Adicionar produto ao carrinho (sessão)
 * Retorna JSON
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';

if (!estaLogado() || !temPermissao('cliente')) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Precisa de estar logado como cliente.']);
    exit;
}

$produto_id = (int)($_POST['produto_id'] ?? 0);
$quantidade = max(1, (int)($_POST['quantidade'] ?? 1));

if (!$produto_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Produto inválido.']);
    exit;
}

// Busca o produto
$stmt = $pdo->prepare("SELECT p.*, r.aberto FROM produtos p JOIN restaurantes r ON r.id = p.restaurante_id WHERE p.id = ? AND p.ativo = 1");
$stmt->execute([$produto_id]);
$produto = $stmt->fetch();

if (!$produto) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Produto não encontrado.']);
    exit;
}

if (!$produto['aberto']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Restaurante está fechado.']);
    exit;
}

// Inicializa carrinho na sessão
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Se já existe, aumenta quantidade
if (isset($_SESSION['carrinho'][$produto_id])) {
    $_SESSION['carrinho'][$produto_id]['quantidade'] += $quantidade;
} else {
    // Calcula preço (com desconto se for prato do dia)
    $preco = $produto['preco'];
    if ($produto['prato_do_dia'] && $produto['desconto_prato_dia'] > 0) {
        $preco = $preco * (1 - $produto['desconto_prato_dia'] / 100);
    }

    $_SESSION['carrinho'][$produto_id] = [
        'produto_id'     => $produto_id,
        'restaurante_id' => $produto['restaurante_id'],
        'nome'           => $produto['nome'],
        'preco'          => $preco,
        'quantidade'     => $quantidade,
        'foto'           => $produto['foto']
    ];
}

echo json_encode(['sucesso' => true, 'mensagem' => 'Adicionado ao carrinho!', 'total_itens' => count($_SESSION['carrinho'])]);
?>
