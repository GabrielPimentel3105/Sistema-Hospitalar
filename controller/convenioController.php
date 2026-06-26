<?php
session_start();

require_once "../config/Database.php";
require_once "../model/Convenio.php";
require_once "../dao/ConvenioDAO.php";

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        $_SESSION["mensagem"] = "Erro de conexão com o banco de dados.";
        $_SESSION["tipo_mensagem"] = "danger";
        header("Location: ../convenios.php");
        exit;
    }

    $convenioDAO = new ConvenioDAO($db);
    $acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

    if ($acao === "cadastrar") {
        $convenio = new Convenio();

        $convenio->__set("nome_convenio", $_POST["nome_convenio"] ?? "");
        $convenio->__set("telefone", $_POST["telefone"] ?? "");
        $convenio->__set("procedimentos_autorizados", $_POST["procedimentos_autorizados"] ?? "");
        $convenio->__set("status_convenio", $_POST["status_convenio"] ?? "Ativo");

        if ($convenioDAO->create($convenio)) {
            $_SESSION["mensagem"] = "Convênio cadastrado com sucesso!";
            $_SESSION["tipo_mensagem"] = "success";
        } else {
            $_SESSION["mensagem"] = "Erro ao cadastrar convênio.";
            $_SESSION["tipo_mensagem"] = "danger";
        }

        header("Location: ../convenios.php");
        exit;
    }

    if ($acao === "editar") {
        $convenio = new Convenio();

        $convenio->__set("id_convenio", $_POST["id_convenio"] ?? null);
        $convenio->__set("nome_convenio", $_POST["nome_convenio"] ?? "");
        $convenio->__set("telefone", $_POST["telefone"] ?? "");
        $convenio->__set("procedimentos_autorizados", $_POST["procedimentos_autorizados"] ?? "");
        $convenio->__set("status_convenio", $_POST["status_convenio"] ?? "Ativo");

        if ($convenioDAO->update($convenio)) {
            $_SESSION["mensagem"] = "Convênio atualizado com sucesso!";
            $_SESSION["tipo_mensagem"] = "success";
        } else {
            $_SESSION["mensagem"] = "Erro ao atualizar convênio.";
            $_SESSION["tipo_mensagem"] = "danger";
        }

        header("Location: ../convenios.php");
        exit;
    }

    if ($acao === "excluir") {
        $id = $_GET["id"] ?? null;

        if (!empty($id) && $convenioDAO->delete($id)) {
            $_SESSION["mensagem"] = "Convênio inativado com sucesso.";
            $_SESSION["tipo_mensagem"] = "info";
        } else {
            $_SESSION["mensagem"] = "Erro ao inativar convênio.";
            $_SESSION["tipo_mensagem"] = "danger";
        }

        header("Location: ../convenios.php");
        exit;
    }

    $_SESSION["mensagem"] = "Ação inválida.";
    $_SESSION["tipo_mensagem"] = "warning";
    header("Location: ../convenios.php");
    exit;

} catch (Exception $e) {
    $_SESSION["mensagem"] = "Erro inesperado: " . $e->getMessage();
    $_SESSION["tipo_mensagem"] = "danger";
    header("Location: ../convenios.php");
    exit;
}
?>