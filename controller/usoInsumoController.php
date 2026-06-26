<?php
session_start();

require_once "../config/Database.php";
require_once "../model/UsoInsumo.php";
require_once "../dao/UsoInsumoDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$usoInsumoDAO = new UsoInsumoDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $usoInsumo = new UsoInsumo();

    $usoInsumo->__set("quantidade_utilizada", $_POST["quantidade_utilizada"]);
    $usoInsumo->__set("data_uso", $_POST["data_uso"]);
    $usoInsumo->__set("id_leito", $_POST["id_leito"]);
    $usoInsumo->__set("id_insumo", $_POST["id_insumo"]);

    if ($usoInsumoDAO->create($usoInsumo)) {
        $_SESSION["mensagem"] = "Uso de insumo registrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao registrar uso de insumo. Verifique o estoque disponível.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../uso_insumos.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $usoInsumoDAO->delete($id)) {
        $_SESSION["mensagem"] = "Uso de insumo excluído e estoque restaurado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir uso de insumo.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../uso_insumos.php");
    exit;
}
?>