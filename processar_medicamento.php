<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Medicamento.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET['acao'])) {
        header("Location: medicamentos.php");
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
        header("Location: medicamentos.php");
        exit;
    }

    $medicamentoModel = new Medicamento($db);
    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $medicamento = new Medicamento($db);

        $medicamento->__set("nome_medicamento", $_POST['nome_medicamento'] ?? '');
        $medicamento->__set("contraindicacoes", $_POST['contraindicacoes'] ?? '');
        $medicamento->__set("interacoes_medicamentosas", $_POST['interacoes_medicamentosas'] ?? '');
        $medicamento->__set("valor_unitario", $_POST['valor_unitario'] ?? 0);
        $medicamento->__set("quantidade_estoque", $_POST['quantidade_estoque'] ?? 0);

        if ($medicamento->create()) {
            $_SESSION['mensagem'] = "Medicamento cadastrado com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao cadastrar medicamento.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: medicamentos.php");
        exit;
    }

    if ($acao === 'editar') {
        $medicamento = new Medicamento($db);

        $medicamento->__set("id_medicamento", $_POST['id_medicamento'] ?? null);
        $medicamento->__set("nome_medicamento", $_POST['nome_medicamento'] ?? '');
        $medicamento->__set("contraindicacoes", $_POST['contraindicacoes'] ?? '');
        $medicamento->__set("interacoes_medicamentosas", $_POST['interacoes_medicamentosas'] ?? '');
        $medicamento->__set("valor_unitario", $_POST['valor_unitario'] ?? 0);
        $medicamento->__set("quantidade_estoque", $_POST['quantidade_estoque'] ?? 0);

        if ($medicamento->update()) {
            $_SESSION['mensagem'] = "Medicamento atualizado com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao atualizar medicamento.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: medicamentos.php");
        exit;
    }

    if ($acao === 'excluir') {
        $id = $_GET['id'] ?? null;

        if (!empty($id) && $medicamentoModel->delete($id)) {
            $_SESSION['mensagem'] = "Medicamento inativado para uso de estoque. O histórico de prescrições foi preservado.";
            $_SESSION['tipo_mensagem'] = "info";
        } else {
            $_SESSION['mensagem'] = "Erro ao inativar medicamento.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: medicamentos.php");
        exit;
    }

    $_SESSION['mensagem'] = "Ação inválida.";
    $_SESSION['tipo_mensagem'] = "warning";
    header("Location: medicamentos.php");
    exit;

} catch (Throwable $e) {
    $_SESSION['mensagem'] = "Erro inesperado: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: medicamentos.php");
    exit;
}
?>