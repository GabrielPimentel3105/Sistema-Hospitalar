<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Insumo.php';
require_once __DIR__ . '/model/UsoInsumo.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET['acao'])) {
        header("Location: estoque.php");
        exit;
    }

    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        $_SESSION['mensagem'] = "Erro de conexão com o banco de dados.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: estoque.php");
        exit;
    }

    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

    if ($acao === 'cadastrar_insumo' || $acao === 'cadastrar') {
        $insumo = new Insumo($db);

        $insumo->__set("nome_insumo", $_POST['nome_insumo'] ?? '');
        $insumo->__set("quantidade_estoque", $_POST['quantidade_estoque'] ?? 0);
        $insumo->__set("valor_unitario", $_POST['valor_unitario'] ?? 0);
        $insumo->__set("estoque_minimo", $_POST['estoque_minimo'] ?? 5);

        if ($insumo->create()) {
            $_SESSION['mensagem'] = "Insumo cadastrado com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao cadastrar insumo.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: estoque.php");
        exit;
    }

    if ($acao === 'editar_insumo' || $acao === 'editar') {
        $insumo = new Insumo($db);

        $insumo->__set("id_insumo", $_POST['id_insumo'] ?? null);
        $insumo->__set("nome_insumo", $_POST['nome_insumo'] ?? '');
        $insumo->__set("quantidade_estoque", $_POST['quantidade_estoque'] ?? 0);
        $insumo->__set("valor_unitario", $_POST['valor_unitario'] ?? 0);
        $insumo->__set("estoque_minimo", $_POST['estoque_minimo'] ?? 5);

        if ($insumo->update()) {
            $_SESSION['mensagem'] = "Insumo atualizado com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao atualizar insumo.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: estoque.php");
        exit;
    }

    if ($acao === 'zerar_insumo' || $acao === 'excluir') {
        $id = $_GET['id'] ?? null;
        $insumo = new Insumo($db);

        if (!empty($id) && $insumo->delete($id)) {
            $_SESSION['mensagem'] = "Estoque do insumo zerado. Histórico preservado.";
            $_SESSION['tipo_mensagem'] = "info";
        } else {
            $_SESSION['mensagem'] = "Erro ao zerar estoque do insumo.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: estoque.php");
        exit;
    }

    if ($acao === 'registrar_uso') {
        $uso = new UsoInsumo($db);

        $uso->__set("quantidade_utilizada", $_POST['quantidade_utilizada'] ?? 0);
        $uso->__set("id_leito", $_POST['id_leito'] ?? null);
        $uso->__set("id_insumo", $_POST['id_insumo'] ?? null);
        $uso->__set("id_internacao", !empty($_POST['id_internacao']) ? $_POST['id_internacao'] : null);

        if ($uso->create()) {
            $_SESSION['mensagem'] = "Uso de insumo registrado com sucesso. Estoque atualizado automaticamente.";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao registrar uso. Verifique se há estoque suficiente.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: uso_insumos.php");
        exit;
    }

    $_SESSION['mensagem'] = "Ação inválida.";
    $_SESSION['tipo_mensagem'] = "warning";
    header("Location: estoque.php");
    exit;

} catch (Throwable $e) {
    $_SESSION['mensagem'] = "Erro inesperado: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: estoque.php");
    exit;
}
?>