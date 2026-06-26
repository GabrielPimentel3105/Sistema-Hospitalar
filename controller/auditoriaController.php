<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Auditoria.php";
require_once "../dao/AuditoriaDAO.php";

$database = Database::getInstance();
$db = $database->getConnection();

$auditoriaDAO = new AuditoriaDAO($db);

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

if ($acao == "listar") {
    header("Location: ../auditoria.php");
    exit;
}
?>