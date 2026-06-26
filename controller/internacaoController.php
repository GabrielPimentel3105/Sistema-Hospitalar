<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Internacao.php";
require_once "../dao/InternacaoDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$internacaoDAO = new InternacaoDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $internacao = new Internacao();

    $internacao->__set("data_entrada", $_POST["data_entrada"]);
    $internacao->__set("data_alta", $_POST["data_alta"] ?? null);
    $internacao->__set("status_internacao", $_POST["status_internacao"]);
    $internacao->__set("id_paciente", $_POST["id_paciente"]);
    $internacao->__set("id_leito", $_POST["id_leito"]);

    if ($internacaoDAO->create($internacao)) {
        $_SESSION["mensagem"] = "Internação cadastrada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar internação.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../internacoes.php");
    exit;
}

if ($acao == "editar") {
    $internacao = new Internacao();

    $internacao->__set("id_internacao", $_POST["id_internacao"]);
    $internacao->__set("data_entrada", $_POST["data_entrada"]);
    $internacao->__set("data_alta", $_POST["data_alta"] ?? null);
    $internacao->__set("status_internacao", $_POST["status_internacao"]);
    $internacao->__set("id_paciente", $_POST["id_paciente"]);
    $internacao->__set("id_leito", $_POST["id_leito"]);

    if ($internacaoDAO->update($internacao)) {
        $_SESSION["mensagem"] = "Internação alterada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar internação.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../internacoes.php");
    exit;
}

if ($acao == "alta") {
    $id = $_GET["id"] ?? null;

    if ($id && $internacaoDAO->giveDischarge($id)) {
        $_SESSION["mensagem"] = "Alta registrada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao registrar alta.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../internacoes.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $internacaoDAO->delete($id)) {
        $_SESSION["mensagem"] = "Internação excluída com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir internação.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../internacoes.php");
    exit;
}
?>