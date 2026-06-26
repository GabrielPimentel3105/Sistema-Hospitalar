<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Sala.php";
require_once "../dao/SalaDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$salaDAO = new SalaDAO($db);

$acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

if ($acao == "cadastrar") {
    $sala = new Sala();

    $sala->__set("numero_sala", $_POST["numero_sala"]);
    $sala->__set("tipo_sala", $_POST["tipo_sala"]);
    $sala->__set("status_sala", $_POST["status_sala"]);

    if ($salaDAO->create($sala)) {
        $_SESSION["mensagem"] = "Sala cadastrada com sucesso!";
        $_SESSION["tipo_mensagem"] = "success";
    } else {
        $_SESSION["mensagem"] = "Erro ao cadastrar sala.";
        $_SESSION["tipo_mensagem"] = "danger";
    }

    header("Location: ../salas.php");
    exit;
}
?>