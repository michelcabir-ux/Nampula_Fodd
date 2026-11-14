<?php
/**
 * Carrinho e Checkout
 */
$titulo = 'Carrinho';
require_once '../includes/header.php';
proteger(['cliente']);

$carrinho = $_SESSION['carrinho'] ?? [];
$erro = '';
$sucesso = '';

// Remover item
if (isset($_GET['remover'])) {
    $id = (int)$_GET['remover'];
    unset($_SESSION['carrinho'][$id]);
    redirect('carrinho.php');
}

// Finalizar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar'])) {
    if (empty($carrinho)) {
        $erro = 'O carrinho está vazio.';
    } else {
        // Agrupa por restaurante (um pedido por restaurante)
        $porRest = [];
        foreach ($carrinho as $item) {
            $porRest[$item['restaurante_id']][] = $item;
        }

        $forma = $_POST['forma_pagamento'] ?? 'entrega';
        $user = utilizadorAtual();

        foreach ($porRest as $restId => $itens) {
            $valorTotal = 0;
            foreach ($itens as $item) {
                $valorTotal += $item['preco'] * $item['quantidade'];
            }

            $comissao = round($valorTotal * 0.10, 2);
            $valorRest = round($valorTotal * 0.90, 2);

            // Se pagar com carteira, verifica saldo
            if ($forma === 'carteira') {
                if ($user['saldo_carteira'] < $valorTotal) {
                    $erro = 'Saldo insuficiente na carteira. Carregue mais saldo ou pague na entrega.';
                    break;
                }
                // Debita a carteira
                $stmt = $pdo->prepare("UPDATE usuarios SET saldo_carteira = saldo_carteira - ? WHERE id = ?");
                $stmt->execute([$valorTotal, $user['id']]);

                // Regista movimento
                $stmt = $pdo->prepare("INSERT INTO carteira (usuario_id, tipo, valor, descricao) VALUES (?, 'pagamento', ?, ?)");
                $stmt->execute([$user['id'], -$valorTotal, "Pagamento de pedido"]);
            }

            // Cria o pedido
            $stmt = $pdo->prepare("INSERT INTO pedidos 
                (cliente_id, restaurante_id, valor_total, minha_comissao_10, valor_restaurante_90, forma_pagamento, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendente')");
            $stmt->execute([$user['id'], $restId, $valorTotal, $comissao, $valorRest, $forma]);
            $pedidoId = $pdo->lastInsertId();

            // Itens do pedido
            $stmtItem = $pdo->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            foreach ($itens as $item) {
                $stmtItem->execute([$pedidoId, $item['produto_id'], $item['quantidade'], $item['preco']]);
            }

            // Credita comissão ao admin (simulado - vai para a tabela carteira do admin)
            $stmt = $pdo->prepare("INSERT INTO carteira (usuario_id, tipo, valor, descricao, pedido_id) 
                SELECT id, 'comissao', ?, ?, ? FROM usuarios WHERE tipo = 'admin' LIMIT 1");
            $stmt->execute([$comissao, "Comissão 10% do pedido #$pedidoId", $pedidoId]);

            // Atualiza saldo a receber do restaurante
            $stmt = $pdo->prepare("UPDATE restaurantes SET saldo_a_receber = saldo_a_receber + ? WHERE id = ?");
            $stmt->execute([$valorRest, $restId]);
        }

        if (!$erro) {
            $_SESSION['carrinho'] = [];
            $sucesso = 'Pedido realizado com sucesso! Acompanhe em "Meus Pedidos".';
            $carrinho = [];
        }
    }
}

// Calcula totais
$totalGeral = 0;
foreach ($carrinho as $item) {
    $totalGeral += $item['preco'] * $item['quantidade'];
}
$user = utilizadorAtual();
?>

<div class="container" style="padding-top:30px;">
    <h1 class="secao-titulo"><i class="fas fa-shopping-cart"></i> Meu Carrinho</h1>

    <?php if ($erro): ?>
        <div class="alert alert-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <div class="alert alert-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if (empty($carrinho)): ?>
        <p style="text-align:center;padding:40px;">O seu carrinho está vazio. <a href="../index.php" style="color:var(--laranja)">Ver restaurantes</a></p>
    <?php else: ?>
        <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--sombra);margin-bottom:30px;">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço</th>
                        <th>Qtd</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carrinho as $id => $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome']) ?></td>
                            <td><?= number_format($item['preco'], 2) ?> MT</td>
                            <td><?= $item['quantidade'] ?></td>
                            <td><strong><?= number_format($item['preco'] * $item['quantidade'], 2) ?> MT</strong></td>
                            <td>
                                <a href="?remover=<?= $id ?>" style="color:var(--erro);" data-confirm="Remover este item?">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="text-align:right;margin-top:20px;font-size:1.3rem;">
                <strong>Total: <span style="color:var(--laranja)"><?= number_format($totalGeral, 2) ?> MT</span></strong>
            </div>
        </div>

        <!-- Checkout -->
        <div class="form-container" style="max-width:500px;">
            <h3 style="margin-bottom:20px;">Finalizar Pedido</h3>
            <p style="margin-bottom:15px;">Saldo na carteira: <strong><?= number_format($user['saldo_carteira'], 2) ?> MT</strong></p>

            <form method="POST">
                <div class="form-group">
                    <label>Forma de pagamento</label>
                    <select name="forma_pagamento">
                        <option value="entrega">Pagar na entrega (dinheiro/M-Pesa)</option>
                        <option value="carteira" <?= $user['saldo_carteira'] >= $totalGeral ? '' : 'disabled' ?>>
                            Carteira Virtual <?= $user['saldo_carteira'] < $totalGeral ? '(saldo insuficiente)' : '' ?>
                        </option>
                    </select>
                </div>
                <button type="submit" name="finalizar" class="btn-primary">
                    <i class="fas fa-check"></i> Confirmar Pedido
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
