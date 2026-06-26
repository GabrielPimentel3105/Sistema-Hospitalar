<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Insumo.php';

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (method_exists('Database', 'getInstance')) {
            $database = Database::getInstance();
        } else {
            $database = new Database();
        }

        $db = $database->getConnection();
        $insumo = new Insumo($db);

        $acao = $_POST['acao'] ?? '';

        if ($acao === 'novo') {
            $insumo->nome_insumo = $_POST['nome'] ?? '';
            $insumo->quantidade_estoque = $_POST['quantidade'] ?? 0;
            $insumo->valor_unitario = $_POST['valor'] ?? 0;

            if ($insumo->create()) {
                header("Location: estoque.php?status=sucesso");
                exit;
            }

            header("Location: estoque.php?status=erro");
            exit;
        }

        if ($acao === 'ajuste') {
            $id = $_POST['id_insumo'] ?? null;
            $qtd = $_POST['quantidade'] ?? 0;
            $operacao = $_POST['operacao'] ?? '';

            if (!empty($id) && $insumo->updateStock($id, $qtd, $operacao)) {
                header("Location: estoque.php?status=ajustado");
                exit;
            }

            header("Location: estoque.php?status=erro");
            exit;
        }

        if ($acao === 'compra') {
            $id = $_POST['id_insumo'] ?? null;
            $qtd = $_POST['quantidade'] ?? 0;

            if (!empty($id) && $insumo->updateStock($id, $qtd, 'adicionar')) {
                header("Location: estoque.php?status=compra_sucesso");
                exit;
            }

            header("Location: estoque.php?status=erro");
            exit;
        }

        header("Location: estoque.php?status=acao_invalida");
        exit;
    }

    header("Location: estoque.php");
    exit;

} catch (Exception $e) {
    header("Location: estoque.php?status=erro&mensagem=" . urlencode($e->getMessage()));
    exit;
}
?>