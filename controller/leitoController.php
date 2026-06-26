<?php
session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Leito.php';
require_once __DIR__ . '/../dao/LeitoDAO.php';

try {
    if (method_exists("Database", "getInstance")) {
        $database = Database::getInstance();
        $db = $database->getConnection();
    } else {
        $database = new Database();
        $db = $database->getConnection();
    }

    $leitoDAO = new LeitoDAO($db);

    $acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

    $statusPermitidos = [
        "Disponível",
        "Ocupado",
        "Manutenção",
        "Higienização",
        "Inativo"
    ];

    if ($acao === "cadastrar") {
        $numero_leito = trim($_POST["numero_leito"] ?? "");
        $ala = trim($_POST["ala"] ?? "");
        $status_leito = $_POST["status_leito"] ?? "Disponível";

        if ($numero_leito === "" || $ala === "" || $status_leito === "") {
            $_SESSION["erro"] = "Preencha todos os campos obrigatórios.";
            header("Location: ../leitos.php");
            exit;
        }

        if (!in_array($status_leito, $statusPermitidos)) {
            $_SESSION["erro"] = "Status do leito inválido.";
            header("Location: ../leitos.php");
            exit;
        }

        $leito = new Leito();
        $leito->__set("numero_leito", $numero_leito);
        $leito->__set("ala", $ala);
        $leito->__set("status_leito", $status_leito);

        if ($leitoDAO->cadastrar($leito)) {
            $_SESSION["sucesso"] = "Leito cadastrado com sucesso.";
        } else {
            $_SESSION["erro"] = "Erro ao cadastrar leito.";
        }

        header("Location: ../leitos.php");
        exit;
    }

    if ($acao === "editar") {
        $id_leito = $_POST["id_leito"] ?? "";
        $numero_leito = trim($_POST["numero_leito"] ?? "");
        $ala = trim($_POST["ala"] ?? "");
        $status_leito = $_POST["status_leito"] ?? "Disponível";

        if ($id_leito === "" || $numero_leito === "" || $ala === "" || $status_leito === "") {
            $_SESSION["erro"] = "Preencha todos os campos obrigatórios.";
            header("Location: ../leitos.php");
            exit;
        }

        if (!in_array($status_leito, $statusPermitidos)) {
            $_SESSION["erro"] = "Status do leito inválido.";
            header("Location: ../leitos.php");
            exit;
        }

        $leito = new Leito();
        $leito->__set("id_leito", $id_leito);
        $leito->__set("numero_leito", $numero_leito);
        $leito->__set("ala", $ala);
        $leito->__set("status_leito", $status_leito);

        if ($leitoDAO->atualizar($leito)) {
            $_SESSION["sucesso"] = "Leito atualizado com sucesso.";
        } else {
            $_SESSION["erro"] = "Erro ao atualizar leito.";
        }

        header("Location: ../leitos.php");
        exit;
    }

    if ($acao === "inativar") {
        $id_leito = $_GET["id"] ?? "";

        if ($id_leito === "") {
            $_SESSION["erro"] = "Leito não informado.";
            header("Location: ../leitos.php");
            exit;
        }

        if ($leitoDAO->inativar($id_leito)) {
            $_SESSION["sucesso"] = "Leito inativado com sucesso. O registro foi mantido para preservar o histórico hospitalar.";
        } else {
            $_SESSION["erro"] = "Erro ao inativar leito.";
        }

        header("Location: ../leitos.php");
        exit;
    }

    $_SESSION["erro"] = "Ação inválida.";
    header("Location: ../leitos.php");
    exit;

} catch (Exception $e) {
    $_SESSION["erro"] = "Erro no processamento de leitos: " . $e->getMessage();
    header("Location: ../leitos.php");
    exit;
}