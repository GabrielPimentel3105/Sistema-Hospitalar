<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Prontuario.php";
require_once "../dao/ProntuarioDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$prontuarioDAO = new ProntuarioDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $prontuario = new Prontuario();

    $prontuario->__set("evolucao_clinica", $_POST["evolucao_clinica"]);
    $prontuario->__set("observacoes", $_POST["observacoes"]);
    $prontuario->__set("data_registro", $_POST["data_registro"]);
    $prontuario->__set("id_consulta", $_POST["id_consulta"]);

    if ($prontuarioDAO->create($prontuario)) {
        $_SESSION["mensagem"] = "Prontuário cadastrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar prontuário.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../prontuario.php");
    exit;
}

if ($acao == "editar") {
    $prontuario = new Prontuario();

    $prontuario->__set("id_prontuario", $_POST["id_prontuario"]);
    $prontuario->__set("evolucao_clinica", $_POST["evolucao_clinica"]);
    $prontuario->__set("observacoes", $_POST["observacoes"]);
    $prontuario->__set("data_registro", $_POST["data_registro"]);
    $prontuario->__set("id_consulta", $_POST["id_consulta"]);

    if ($prontuarioDAO->update($prontuario)) {
        $_SESSION["mensagem"] = "Prontuário alterado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar prontuário.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../prontuario.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $prontuarioDAO->delete($id)) {
        $_SESSION["mensagem"] = "Prontuário excluído com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir prontuário.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../prontuario.php");
    exit;
}
?>