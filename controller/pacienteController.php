<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Paciente.php";
require_once "../dao/PacienteDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$pacienteDAO = new PacienteDAO($db);

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

if ($acao == "cadastrar") {
    $paciente = new Paciente();

    $paciente->__set("nome", $_POST["nome"]);
    $paciente->__set("cpf", $_POST["cpf"]);
    $paciente->__set("data_nascimento", $_POST["data_nascimento"]);
    $paciente->__set("telefone", $_POST["telefone"]);
    $paciente->__set("endereco", $_POST["endereco"]);
    $paciente->__set("alergias", $_POST["alergias"]);
    $paciente->__set("historico_clinico", $_POST["historico_clinico"]);
    $paciente->__set("tipo_sanguineo", $_POST["tipo_sanguineo"]);

    if ($pacienteDAO->create($paciente)) {
        $_SESSION["mensagem"] = "Paciente cadastrado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar paciente.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../pacientes.php");
    exit;
}

if ($acao == "editar") {
    $paciente = new Paciente();

    $paciente->__set("id_paciente", $_POST["id_paciente"]);
    $paciente->__set("nome", $_POST["nome"]);
    $paciente->__set("cpf", $_POST["cpf"]);
    $paciente->__set("data_nascimento", $_POST["data_nascimento"]);
    $paciente->__set("telefone", $_POST["telefone"]);
    $paciente->__set("endereco", $_POST["endereco"]);
    $paciente->__set("alergias", $_POST["alergias"]);
    $paciente->__set("historico_clinico", $_POST["historico_clinico"]);
    $paciente->__set("tipo_sanguineo", $_POST["tipo_sanguineo"]);

    if ($pacienteDAO->update($paciente)) {
        $_SESSION["mensagem"] = "Paciente alterado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao alterar paciente.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../pacientes.php");
    exit;
}

if ($acao == "excluir") {
    $id = $_GET["id"] ?? null;

    if ($id && $pacienteDAO->delete($id)) {
        $_SESSION["mensagem"] = "Paciente excluído com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao excluir paciente.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../pacientes.php");
    exit;
}
?>