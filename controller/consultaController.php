<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Consulta.php";
require_once "../dao/ConsultaDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$consultaDAO = new ConsultaDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $consulta = new Consulta();

    $consulta->__set("data_consulta", $_POST["data_consulta"]);
    $consulta->__set("horario", $_POST["horario"]);
    $consulta->__set("status_consulta", $_POST["status_consulta"]);
    $consulta->__set("id_paciente", $_POST["id_paciente"]);
    $consulta->__set("id_medico", $_POST["id_medico"]);
    $consulta->__set("id_sala", $_POST["id_sala"]);
    $consulta->__set("id_convenio", $_POST["id_convenio"]);

    if ($consultaDAO->create($consulta)) {
        $_SESSION["mensagem"] = "Consulta agendada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao agendar consulta.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../consultas.php");
    exit;
}

if ($acao == "triagem") {
    $dados = [
        "temperatura" => $_POST["temperatura"],
        "pressao" => $_POST["pressao"],
        "frequencia" => $_POST["frequencia"],
        "saturacao" => $_POST["saturacao"],
        "dor" => $_POST["dor"],
        "risco" => $_POST["risco"],
        "protocolo" => $_POST["protocolo"],
        "id_paciente" => $_POST["id_paciente"]
    ];

    if ($consultaDAO->createTriagem($dados)) {
        $_SESSION["mensagem"] = "Triagem registrada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao registrar triagem.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../consultas.php");
    exit;
}

if ($acao == "liberar") {
    $id_paciente = $_GET["id_paciente"] ?? null;

    if ($id_paciente && $consultaDAO->liberarPaciente($id_paciente)) {
        $_SESSION["mensagem"] = "Paciente liberado com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao liberar paciente.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../consultas.php");
    exit;
}
?>