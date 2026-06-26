<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Faturamento.php";
require_once "../dao/FaturamentoDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$faturamentoDAO = new FaturamentoDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $faturamento = new Faturamento();

    $faturamento->__set("valor_total", $_POST["valor_total"]);
    $faturamento->__set("data_faturamento", $_POST["data_faturamento"]);
    $faturamento->__set("status_pagamento", $_POST["status_pagamento"]);
    $faturamento->__set("id_paciente", $_POST["id_paciente"]);

    if ($faturamentoDAO->create($faturamento)) {
        $_SESSION["mensagem"] = "Faturamento cadastrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar faturamento.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../faturamento.php");
    exit;
}

if ($acao == "atualizar_status") {
    $id = $_POST["id_faturamento"] ?? $_GET["id"] ?? null;
    $status = $_POST["status_pagamento"] ?? $_GET["status"] ?? null;

    if ($id && $status && $faturamentoDAO->updateStatus($id, $status)) {
        $_SESSION["mensagem"] = "Status do faturamento atualizado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao atualizar status do faturamento.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../faturamento.php");
    exit;
}

if ($acao == "consolidar") {
    $id_paciente = $_GET["id_paciente"] ?? $_POST["id_paciente"] ?? null;

    if ($id_paciente) {
        $gastos = $faturamentoDAO->consolidarGastos($id_paciente);
        $_SESSION["gastos_consolidados"] = $gastos;
        $_SESSION["mensagem"] = "Gastos consolidados com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Paciente não informado para consolidação.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../faturamento.php");
    exit;
}
?>