<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Medico.php";
require_once "../dao/MedicoDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$medicoDAO = new MedicoDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $medico = new Medico();

    $medico->__set("nome", $_POST["nome"]);
    $medico->__set("crm", $_POST["crm"]);
    $medico->__set("especialidade", $_POST["especialidade"]);
    $medico->__set("telefone", $_POST["telefone"]);
    $medico->__set("email", $_POST["email"]);

    if ($medicoDAO->create($medico)) {
        $_SESSION["mensagem"] = "Médico cadastrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar médico.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../medicos.php");
    exit;
}

if ($acao == "editar") {
    $medico = new Medico();

    $medico->__set("id_medico", $_POST["id_medico"]);
    $medico->__set("nome", $_POST["nome"]);
    $medico->__set("crm", $_POST["crm"]);
    $medico->__set("especialidade", $_POST["especialidade"]);
    $medico->__set("telefone", $_POST["telefone"]);
    $medico->__set("email", $_POST["email"]);

    if ($medicoDAO->update($medico)) {
        $_SESSION["mensagem"] = "Médico alterado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar médico.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../medicos.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $medicoDAO->delete($id)) {
        $_SESSION["mensagem"] = "Médico excluído com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir médico.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../medicos.php");
    exit;
}
?>