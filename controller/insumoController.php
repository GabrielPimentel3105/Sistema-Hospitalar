<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Insumo.php";
require_once "../dao/InsumoDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$insumoDAO = new InsumoDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $insumo = new Insumo();

    $insumo->__set("nome_insumo", $_POST["nome_insumo"]);
    $insumo->__set("quantidade_estoque", $_POST["quantidade_estoque"]);
    $insumo->__set("valor_unitario", $_POST["valor_unitario"]);

    if ($insumoDAO->create($insumo)) {
        $_SESSION["mensagem"] = "Insumo cadastrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar insumo.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../estoque.php");
    exit;
}

if ($acao == "editar") {
    $insumo = new Insumo();

    $insumo->__set("id_insumo", $_POST["id_insumo"]);
    $insumo->__set("nome_insumo", $_POST["nome_insumo"]);
    $insumo->__set("quantidade_estoque", $_POST["quantidade_estoque"]);
    $insumo->__set("valor_unitario", $_POST["valor_unitario"]);

    if ($insumoDAO->update($insumo)) {
        $_SESSION["mensagem"] = "Insumo alterado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar insumo.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../estoque.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $insumoDAO->delete($id)) {
        $_SESSION["mensagem"] = "Insumo excluído com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir insumo.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../estoque.php");
    exit;
}

if ($acao == "atualizar_estoque") {
    $id = $_POST["id_insumo"] ?? $_GET["id"] ?? null;
    $quantidade = $_POST["quantidade"] ?? $_GET["quantidade"] ?? null;
    $operacao = $_POST["operacao"] ?? $_GET["operacao"] ?? "adicionar";

    if ($id && $quantidade && $insumoDAO->updateStock($id, $quantidade, $operacao)) {
        $_SESSION["mensagem"] = "Estoque atualizado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao atualizar estoque.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../estoque.php");
    exit;
}
?>