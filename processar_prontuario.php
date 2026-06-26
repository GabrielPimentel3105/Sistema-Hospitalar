<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Prontuario.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: pacientes.php");
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
        header("Location: pacientes.php");
        exit;
    }

    $acao = $_POST['acao'] ?? '';
    $idPaciente = $_POST['id_paciente'] ?? null;
    $idConsulta = $_POST['id_consulta'] ?? null;
    $idProntuario = $_POST['id_prontuario'] ?? null;

    if (empty($idPaciente) || empty($idConsulta) || empty($idProntuario)) {
        $_SESSION['mensagem'] = "Paciente, consulta ou prontuário não informado.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: pacientes.php");
        exit;
    }

    $prontuarioModel = new Prontuario($db);

    if ($acao === 'salvar_evolucao') {
        $prontuario = new Prontuario($db);

        $prontuario->__set("id_prontuario", $idProntuario);
        $prontuario->__set("evolucao_clinica", $_POST['evolucao_clinica'] ?? '');
        $prontuario->__set("observacoes", $_POST['observacoes'] ?? '');

        if ($prontuario->update()) {
            $_SESSION['mensagem'] = "Evolução clínica salva com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao salvar evolução clínica.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: prontuario.php?id=" . urlencode($idPaciente) . "&id_consulta=" . urlencode($idConsulta));
        exit;
    }

    if ($acao === 'solicitar_exame') {
        $dados = [
            'nome_exame' => $_POST['nome_exame'] ?? '',
            'resultado' => $_POST['resultado'] ?? 'Solicitado - aguardando resultado.',
            'data_exame' => $_POST['data_exame'] ?? date('Y-m-d'),
            'valor_exame' => $_POST['valor_exame'] ?? 0,
            'id_prontuario' => $idProntuario
        ];

        if ($prontuarioModel->solicitarExame($dados)) {
            $_SESSION['mensagem'] = "Exame solicitado/registrado com sucesso.";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao registrar exame.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: prontuario.php?id=" . urlencode($idPaciente) . "&id_consulta=" . urlencode($idConsulta));
        exit;
    }

    if ($acao === 'prescrever') {
        $idMedicamento = $_POST['id_medicamento'] ?? null;

        if (empty($idMedicamento)) {
            $_SESSION['mensagem'] = "Selecione um medicamento para emitir a prescrição.";
            $_SESSION['tipo_mensagem'] = "warning";
            header("Location: prontuario.php?id=" . urlencode($idPaciente) . "&id_consulta=" . urlencode($idConsulta));
            exit;
        }

        $alerta = $prontuarioModel->verificarAlertaMedicamento($idMedicamento, $idPaciente);

        $dados = [
            'dosagem' => $_POST['dosagem'] ?? '',
            'frequencia' => $_POST['frequencia'] ?? '',
            'duracao_tratamento' => $_POST['duracao_tratamento'] ?? '',
            'id_prontuario' => $idProntuario,
            'id_medicamento' => $idMedicamento
        ];

        if ($prontuarioModel->emitirPrescricao($dados)) {
            if (!empty($alerta['possui_alerta'])) {
                $_SESSION['mensagem'] = "Prescrição registrada mediante confirmação do profissional. Atenção: " . ($alerta['mensagem'] ?? 'possível risco identificado nas alergias ou histórico clínico do paciente.');
                $_SESSION['tipo_mensagem'] = "warning";
            } else {
                $_SESSION['mensagem'] = "Prescrição registrada com sucesso. Nenhum alerta automático identificado.";
                $_SESSION['tipo_mensagem'] = "success";
            }
        } else {
            $_SESSION['mensagem'] = "Erro ao registrar prescrição.";
            $_SESSION['tipo_mensagem'] = "danger";
        }

        header("Location: prontuario.php?id=" . urlencode($idPaciente) . "&id_consulta=" . urlencode($idConsulta));
        exit;
    }

    $_SESSION['mensagem'] = "Ação inválida.";
    $_SESSION['tipo_mensagem'] = "warning";

    header("Location: prontuario.php?id=" . urlencode($idPaciente) . "&id_consulta=" . urlencode($idConsulta));
    exit;

} catch (Throwable $e) {
    $_SESSION['mensagem'] = "Erro inesperado: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";

    if (!empty($_POST['id_paciente'])) {
        header("Location: prontuario.php?id=" . urlencode($_POST['id_paciente']) . "&id_consulta=" . urlencode($_POST['id_consulta'] ?? ''));
        exit;
    }

    header("Location: pacientes.php");
    exit;
}
?>