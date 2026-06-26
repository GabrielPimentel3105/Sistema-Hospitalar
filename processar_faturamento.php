<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Faturamento.php';
require_once __DIR__ . '/model/Auditoria.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET['acao'])) {
        header("Location: faturamento.php");
        exit;
    }

    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        $_SESSION['mensagem'] = "Erro de conexão com o banco de dados.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: faturamento.php");
        exit;
    }

    $faturamento = new Faturamento($db);
    $auditoria = new Auditoria($db);

    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

    if ($acao === 'nova_guia') {
        $idPaciente = $_POST['id_paciente'] ?? null;
        $idConsulta = $_POST['id_consulta'] ?? null;
        $idInternacao = $_POST['id_internacao'] ?? null;
        $tipoGuia = $_POST['tipo_guia'] ?? 'manual';

        if (empty($idPaciente)) {
            $_SESSION['mensagem'] = "Selecione um paciente para emitir a guia.";
            $_SESSION['tipo_mensagem'] = "warning";
            header("Location: faturamento.php");
            exit;
        }

        if ($tipoGuia === 'consolidada') {
            $idGerado = $faturamento->gerarGuiaConsolidada($idPaciente, $idConsulta, $idInternacao);

            if ($idGerado) {
                $auditoria->log(
                    "Guia consolidada emitida automaticamente com base em prontuário, exames, prescrições e insumos.",
                    "Pendente",
                    $idGerado
                );

                $_SESSION['mensagem'] = "Guia consolidada emitida com sucesso.";
                $_SESSION['tipo_mensagem'] = "success";
                header("Location: faturamento.php");
                exit;
            }

            $_SESSION['mensagem'] = "Erro ao emitir guia consolidada.";
            $_SESSION['tipo_mensagem'] = "danger";
            header("Location: faturamento.php");
            exit;
        }

        $valorManual = $_POST['valor_total'] ?? $_POST['valor'] ?? 0;
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($observacoes === '') {
            $observacoes = 'Guia lançada manualmente.';
        }

        $fat = new Faturamento($db);

        $fat->__set("id_paciente", $idPaciente);
        $fat->__set("id_consulta", !empty($idConsulta) ? $idConsulta : null);
        $fat->__set("id_internacao", !empty($idInternacao) ? $idInternacao : null);
        $fat->__set("valor_total", $valorManual);
        $fat->__set("data_faturamento", date('Y-m-d'));
        $fat->__set("status_pagamento", $_POST['status_pagamento'] ?? $_POST['status'] ?? 'Pendente');
        $fat->__set("observacoes", $observacoes);

        $idGerado = $fat->create();

        if ($idGerado) {
            $descricaoItem = $_POST['descricao_item'] ?? '';

            if (trim($descricaoItem) === '') {
                $descricaoItem = 'Lançamento manual de guia hospitalar';
            }

            $fat->adicionarItem(
                $idGerado,
                $descricaoItem,
                'Manual',
                1,
                $valorManual
            );

            $auditoria->log(
                "Nova guia de faturamento lançada manualmente com item detalhado.",
                "Pendente",
                $idGerado
            );

            $_SESSION['mensagem'] = "Guia de faturamento emitida com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: faturamento.php");
            exit;
        }

        $_SESSION['mensagem'] = "Erro ao emitir guia de faturamento.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: faturamento.php");
        exit;
    }

    if ($acao === 'baixar' || $acao === 'pagar') {
        $id = $_GET['id'] ?? null;

        if (!empty($id) && $faturamento->updateStatus($id, 'Pago')) {
            $auditoria->log(
                "Pagamento registrado para a guia #" . $id . ".",
                "Log",
                $id
            );

            $_SESSION['mensagem'] = "Pagamento baixado com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: faturamento.php");
            exit;
        }

        $_SESSION['mensagem'] = "Erro ao baixar pagamento.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: faturamento.php");
        exit;
    }

    if ($acao === 'cancelar') {
        $id = $_GET['id'] ?? null;

        if (!empty($id) && $faturamento->updateStatus($id, 'Cancelado')) {
            $auditoria->log(
                "Guia #" . $id . " cancelada.",
                "Log",
                $id
            );

            $_SESSION['mensagem'] = "Guia cancelada com sucesso.";
            $_SESSION['tipo_mensagem'] = "info";
            header("Location: faturamento.php");
            exit;
        }

        $_SESSION['mensagem'] = "Erro ao cancelar guia.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: faturamento.php");
        exit;
    }

    if ($acao === 'auditar') {
        $id = $_GET['id'] ?? null;

        if (!empty($id) && $auditoria->auditarFaturamento($id)) {
            $_SESSION['mensagem'] = "Auditoria realizada com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: auditoria.php");
            exit;
        }

        $_SESSION['mensagem'] = "Erro ao realizar auditoria.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: faturamento.php");
        exit;
    }

    $_SESSION['mensagem'] = "Ação inválida.";
    $_SESSION['tipo_mensagem'] = "warning";
    header("Location: faturamento.php");
    exit;

} catch (Throwable $e) {
    $_SESSION['mensagem'] = "Erro inesperado: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: faturamento.php");
    exit;
}
?>