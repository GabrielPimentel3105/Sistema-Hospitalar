<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Prescricao.php";
require_once "../dao/PrescricaoDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$prescricaoDAO = new PrescricaoDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $prescricao = new Prescricao();

    $prescricao->__set("dosagem", $_POST["dosagem"]);
    $prescricao->__set("frequencia", $_POST["frequencia"]);
    $prescricao->__set("duracao_tratamento", $_POST["duracao_tratamento"]);
    $prescricao->__set("id_prontuario", $_POST["id_prontuario"]);
    $prescricao->__set("id_medicamento", $_POST["id_medicamento"]);

    if ($prescricaoDAO->create($prescricao)) {
        $_SESSION["mensagem"] = "Prescrição cadastrada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar prescrição. Verifique o medicamento selecionado.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../prescricoes.php");
    exit;
}

if ($acao == "editar") {
    $prescricao = new Prescricao();

    $prescricao->__set("id_prescricao", $_POST["id_prescricao"]);
    $prescricao->__set("dosagem", $_POST["dosagem"]);
    $prescricao->__set("frequencia", $_POST["frequencia"]);
    $prescricao->__set("duracao_tratamento", $_POST["duracao_tratamento"]);
    $prescricao->__set("id_prontuario", $_POST["id_prontuario"]);
    $prescricao->__set("id_medicamento", $_POST["id_medicamento"]);

    if ($prescricaoDAO->update($prescricao)) {
        $_SESSION["mensagem"] = "Prescrição alterada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar prescrição.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../prescricoes.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $prescricaoDAO->delete($id)) {
        $_SESSION["mensagem"] = "Prescrição excluída com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir prescrição.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../prescricoes.php");
    exit;
}
?>