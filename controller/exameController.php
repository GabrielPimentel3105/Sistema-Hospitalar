<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Exame.php";
require_once "../dao/ExameDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$exameDAO = new ExameDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $exame = new Exame();

    $exame->__set("nome_exame", $_POST["nome_exame"]);
    $exame->__set("resultado", $_POST["resultado"]);
    $exame->__set("data_exame", $_POST["data_exame"]);
    $exame->__set("id_prontuario", $_POST["id_prontuario"]);

    if ($exameDAO->create($exame)) {
        $_SESSION["mensagem"] = "Exame cadastrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar exame.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../exames.php");
    exit;
}

if ($acao == "editar") {
    $exame = new Exame();

    $exame->__set("id_exame", $_POST["id_exame"]);
    $exame->__set("nome_exame", $_POST["nome_exame"]);
    $exame->__set("resultado", $_POST["resultado"]);
    $exame->__set("data_exame", $_POST["data_exame"]);
    $exame->__set("id_prontuario", $_POST["id_prontuario"]);

    if ($exameDAO->update($exame)) {
        $_SESSION["mensagem"] = "Exame alterado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar exame.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../exames.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $exameDAO->delete($id)) {
        $_SESSION["mensagem"] = "Exame excluído com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir exame.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../exames.php");
    exit;
}
?>