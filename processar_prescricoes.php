<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Prescricao.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        header("Location: prescricoes.php?erro=" . urlencode("Erro de conexão com o banco de dados."));
        exit;
    }

    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

    if ($acao === 'criar_multipla') {
        $id_prontuario = $_POST['id_prontuario'] ?? '';
        $medicamentos = $_POST['medicamentos'] ?? [];

        if (empty($id_prontuario) || empty($medicamentos)) {
            header("Location: prescricoes.php?erro=" . urlencode("Dados insuficientes para a prescrição."));
            exit;
        }

        $sucesso = true;

        foreach ($medicamentos as $med) {
            $prescricao = new Prescricao($db);

            $prescricao->__set("id_prontuario", $id_prontuario);
            $prescricao->__set("id_medicamento", $med['id_medicamento'] ?? '');
            $prescricao->__set("dosagem", $med['dosagem'] ?? '');
            $prescricao->__set("frequencia", $med['frequencia'] ?? '');
            $prescricao->__set("duracao_tratamento", $med['duracao'] ?? '');
            $prescricao->__set("status_prescricao", "Ativa");

            if (!$prescricao->create()) {
                $sucesso = false;
            }
        }

        if ($sucesso) {
            header("Location: prescricoes.php?msg=" . urlencode("Prescrição múltipla cadastrada com sucesso."));
            exit;
        }

        header("Location: prescricoes.php?erro=" . urlencode("Erro ao cadastrar alguns medicamentos da prescrição."));
        exit;
    }

    if ($acao === 'criar') {
        $prescricao = new Prescricao($db);

        $prescricao->__set("dosagem", $_POST['dosagem'] ?? '');
        $prescricao->__set("frequencia", $_POST['frequencia'] ?? '');
        $prescricao->__set("duracao_tratamento", $_POST['duracao_tratamento'] ?? '');
        $prescricao->__set("id_prontuario", $_POST['id_prontuario'] ?? '');
        $prescricao->__set("id_medicamento", $_POST['id_medicamento'] ?? '');
        $prescricao->__set("status_prescricao", "Ativa");

        if ($prescricao->create()) {
            header("Location: prescricoes.php?msg=" . urlencode("Prescrição cadastrada com sucesso."));
            exit;
        }

        header("Location: prescricoes.php?erro=" . urlencode("Erro ao cadastrar prescrição."));
        exit;
    }

    if ($acao === 'editar') {
        $prescricao = new Prescricao($db);

        $prescricao->__set("id_prescricao", $_POST['id_prescricao'] ?? '');
        $prescricao->__set("dosagem", $_POST['dosagem'] ?? '');
        $prescricao->__set("frequencia", $_POST['frequencia'] ?? '');
        $prescricao->__set("duracao_tratamento", $_POST['duracao_tratamento'] ?? '');
        $prescricao->__set("id_prontuario", $_POST['id_prontuario'] ?? '');
        $prescricao->__set("id_medicamento", $_POST['id_medicamento'] ?? '');

        if ($prescricao->update()) {
            header("Location: prescricoes.php?msg=" . urlencode("Prescrição atualizada com sucesso."));
            exit;
        }

        header("Location: prescricoes.php?erro=" . urlencode("Erro ao atualizar prescrição. Prescrições canceladas não podem ser alteradas."));
        exit;
    }

    if ($acao === 'cancelar' || $acao === 'excluir') {
        $idPrescricao = $_GET['id_prescricao'] ?? $_GET['id'] ?? '';

        if (empty($idPrescricao)) {
            header("Location: prescricoes.php?erro=" . urlencode("Prescrição não informada."));
            exit;
        }

        $prescricao = new Prescricao($db);
        $prescricao->__set("id_prescricao", $idPrescricao);

        if ($prescricao->cancelar()) {
            header("Location: prescricoes.php?msg=" . urlencode("Prescrição cancelada com sucesso. O registro foi mantido no histórico clínico."));
            exit;
        }

        header("Location: prescricoes.php?erro=" . urlencode("Erro ao cancelar prescrição."));
        exit;
    }

    header("Location: prescricoes.php");
    exit;

} catch (Throwable $e) {
    header("Location: prescricoes.php?erro=" . urlencode("Erro no processamento da prescrição: " . $e->getMessage()));
    exit;
}
?>