<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Medicamento.php";
require_once "../dao/MedicamentoDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$medicamentoDAO = new MedicamentoDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $medicamento = new Medicamento();

    $medicamento->__set("nome_medicamento", $_POST["nome_medicamento"]);
    $medicamento->__set("contraindicacoes", $_POST["contraindicacoes"]);
    $medicamento->__set("interacoes_medicamentosas", $_POST["interacoes_medicamentosas"]);

    if ($medicamentoDAO->create($medicamento)) {
        $_SESSION["mensagem"] = "Medicamento cadastrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar medicamento.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../medicamentos.php");
    exit;
}

if ($acao == "editar") {
    $medicamento = new Medicamento();

    $medicamento->__set("id_medicamento", $_POST["id_medicamento"]);
    $medicamento->__set("nome_medicamento", $_POST["nome_medicamento"]);
    $medicamento->__set("contraindicacoes", $_POST["contraindicacoes"]);
    $medicamento->__set("interacoes_medicamentosas", $_POST["interacoes_medicamentosas"]);

    if ($medicamentoDAO->update($medicamento)) {
        $_SESSION["mensagem"] = "Medicamento alterado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar medicamento.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../medicamentos.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $medicamentoDAO->delete($id)) {
        $_SESSION["mensagem"] = "Medicamento excluído com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir medicamento.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../medicamentos.php");
    exit;
}
?>