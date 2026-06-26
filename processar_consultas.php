<?php

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Consulta.php';

function classificarManchester($temperatura, $pressao, $frequencia, $saturacao, $dor, $queixa = '') {
    $temperatura = str_replace(',', '.', (string) $temperatura);
    $temperatura = (float) $temperatura;

    $frequencia = (int) $frequencia;
    $saturacao = (int) $saturacao;
    $dor = (int) $dor;
    $queixa = strtolower(trim((string) $queixa));

    $sistolica = null;
    $diastolica = null;

    if (!empty($pressao) && strpos($pressao, '/') !== false) {
        $partesPressao = explode('/', $pressao);

        $sistolica = isset($partesPressao[0]) ? (int) trim($partesPressao[0]) : null;
        $diastolica = isset($partesPressao[1]) ? (int) trim($partesPressao[1]) : null;

        if ($sistolica !== null && $sistolica > 0 && $sistolica < 30) {
            $sistolica = $sistolica * 10;
        }

        if ($diastolica !== null && $diastolica > 0 && $diastolica < 20) {
            $diastolica = $diastolica * 10;
        }
    }

    $termosVermelho = [
        'parada cardiorrespiratoria',
        'parada cardiorrespiratória',
        'inconsciente',
        'nao responsivo',
        'não responsivo',
        'convulsao ativa',
        'convulsão ativa',
        'hemorragia intensa',
        'sangramento intenso',
        'choque',
        'cianose',
        'falta de ar intensa',
        'dor no peito intensa',
        'desmaio prolongado'
    ];

    foreach ($termosVermelho as $termo) {
        if (strpos($queixa, $termo) !== false) {
            return 'Vermelho';
        }
    }

    if (
        ($saturacao > 0 && $saturacao < 90) ||
        $dor >= 9 ||
        $frequencia >= 140 ||
        ($sistolica !== null && $sistolica < 80) ||
        $temperatura >= 40
    ) {
        return 'Vermelho';
    }

    $termosLaranja = [
        'dor no peito',
        'falta de ar',
        'dispneia',
        'desmaio',
        'confusao mental',
        'confusão mental',
        'vomitos persistentes',
        'vômitos persistentes',
        'sangramento',
        'suspeita de avc',
        'avc',
        'fraqueza em um lado',
        'alteracao neurologica',
        'alteração neurológica'
    ];

    foreach ($termosLaranja as $termo) {
        if (strpos($queixa, $termo) !== false) {
            return 'Laranja';
        }
    }

    if (
        ($saturacao >= 90 && $saturacao <= 93) ||
        $dor >= 7 ||
        $temperatura >= 39 ||
        $frequencia >= 120 ||
        ($sistolica !== null && ($sistolica >= 180 || $sistolica <= 90))
    ) {
        return 'Laranja';
    }

    $termosAmarelo = [
        'febre',
        'dor moderada',
        'vomito',
        'vômito',
        'diarreia',
        'tontura',
        'queda',
        'mal estar',
        'mal-estar'
    ];

    foreach ($termosAmarelo as $termo) {
        if (strpos($queixa, $termo) !== false) {
            return 'Amarelo';
        }
    }

    if (
        $dor >= 4 ||
        $temperatura >= 38 ||
        $frequencia >= 100 ||
        ($sistolica !== null && $sistolica >= 160)
    ) {
        return 'Amarelo';
    }

    if (
        $dor >= 1 ||
        $temperatura >= 37.5 ||
        !empty($queixa)
    ) {
        return 'Verde';
    }

    return 'Azul';
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET['acao'])) {
        header("Location: consultas.php");
        exit;
    }

    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        header("Location: consultas.php?status=erro&mensagem=" . urlencode("Erro de conexão com o banco de dados."));
        exit;
    }

    $consulta = new Consulta($db);
    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

    if ($acao === 'novo_agendamento' || $acao === 'agendar') {
        $idPaciente = $_POST['id_paciente'] ?? null;
        $idMedico = $_POST['id_medico'] ?? null;
        $idSala = $_POST['id_sala'] ?? null;
        $idConvenio = $_POST['id_convenio'] ?? null;
        $data = $_POST['data'] ?? $_POST['data_consulta'] ?? null;
        $horario = $_POST['horario'] ?? null;

        if (empty($idPaciente) || empty($idMedico) || empty($data) || empty($horario)) {
            header("Location: consultas.php?status=erro&mensagem=" . urlencode("Preencha paciente, médico, data e horário."));
            exit;
        }

        $consulta->__set("data_consulta", $data);
        $consulta->__set("horario", $horario);
        $consulta->__set("id_paciente", $idPaciente);
        $consulta->__set("id_medico", $idMedico);
        $consulta->__set("id_sala", !empty($idSala) ? $idSala : null);
        $consulta->__set("id_convenio", !empty($idConvenio) ? $idConvenio : null);

        if (isset($_POST['enviar_triagem'])) {
            $consulta->__set("status_consulta", "Aguardando Triagem");
        } else {
            $consulta->__set("status_consulta", "Agendada");
        }

        if ($consulta->create()) {
            header("Location: consultas.php?status=agendamento_sucesso");
            exit;
        }

        header("Location: consultas.php?status=erro&mensagem=" . urlencode("Não foi possível agendar. Pode existir conflito de médico ou sala no mesmo horário."));
        exit;
    }

    if ($acao === 'enviar_triagem') {
        $idConsulta = $_GET['id_consulta'] ?? $_GET['id'] ?? null;

        if (empty($idConsulta)) {
            header("Location: consultas.php?status=erro&mensagem=" . urlencode("Consulta não informada."));
            exit;
        }

        $query = "UPDATE consultas
                  SET status_consulta = 'Aguardando Triagem'
                  WHERE id_consulta = :id_consulta
                  AND status_consulta = 'Agendada'
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindValue(":id_consulta", $idConsulta);

        if ($stmt->execute()) {
            header("Location: consultas.php?status=enviado_triagem");
            exit;
        }

        header("Location: consultas.php?status=erro");
        exit;
    }

    if ($acao === 'registrar_triagem' || $acao === 'triagem') {
        $idConsulta = $_POST['id_consulta'] ?? null;
        $idPaciente = $_POST['id_paciente'] ?? null;

        if (empty($idConsulta) || empty($idPaciente)) {
            header("Location: consultas.php?status=erro&mensagem=" . urlencode("Consulta ou paciente não informado para triagem."));
            exit;
        }

        $temperatura = $_POST['temp'] ?? $_POST['temperatura'] ?? null;
        $pressao = $_POST['pa'] ?? $_POST['pressao'] ?? null;
        $frequencia = $_POST['fc'] ?? $_POST['frequencia'] ?? null;
        $saturacao = $_POST['saturacao'] ?? 98;
        $dor = $_POST['dor'] ?? 0;
        $queixa = $_POST['queixa'] ?? '';

        $classificacaoAutomatica = classificarManchester(
            $temperatura,
            $pressao,
            $frequencia,
            $saturacao,
            $dor,
            $queixa
        );

        $dados = [
            'temperatura' => $temperatura,
            'pressao' => $pressao,
            'frequencia' => $frequencia,
            'saturacao' => $saturacao,
            'dor' => $dor,
            'id_paciente' => $idPaciente,
            'id_consulta' => $idConsulta,
            'risco' => $classificacaoAutomatica,
            'classificacao_risco' => $classificacaoAutomatica,
            'queixa' => $queixa,
            'protocolo' => 'Manchester'
        ];

        if ($consulta->createTriagem($dados)) {
            header("Location: consultas.php?status=triagem_sucesso&risco=" . urlencode($classificacaoAutomatica));
            exit;
        }

        header("Location: consultas.php?status=erro&mensagem=" . urlencode("Não foi possível registrar a triagem."));
        exit;
    }

    if ($acao === 'definir_destino') {
        $idConsulta = $_POST['id_consulta'] ?? null;
        $idPaciente = $_POST['id_paciente'] ?? null;
        $destino = $_POST['destino'] ?? null;
        $idSalaImagem = $_POST['id_sala_imagem'] ?? null;

        if (empty($idConsulta) || empty($idPaciente) || empty($destino)) {
            header("Location: consultas.php?status=erro&mensagem=" . urlencode("Consulta, paciente ou destino não informado."));
            exit;
        }

        if ($destino === 'liberar') {
            if ($consulta->liberarPaciente($idConsulta)) {
                header("Location: consultas.php?status=liberado");
                exit;
            }

            header("Location: consultas.php?status=erro&mensagem=" . urlencode("Não foi possível liberar a consulta selecionada."));
            exit;
        }

        if ($destino === 'internar') {
            $query = "UPDATE consultas
                      SET status_consulta = 'Encaminhado para Internação'
                      WHERE id_consulta = :id_consulta
                      LIMIT 1";

            $stmt = $db->prepare($query);
            $stmt->bindValue(":id_consulta", $idConsulta);

            if ($stmt->execute()) {
                header("Location: internacoes.php?id_paciente=" . urlencode($idPaciente) . "&id_consulta=" . urlencode($idConsulta) . "&origem=triagem");
                exit;
            }

            header("Location: consultas.php?status=erro&mensagem=" . urlencode("Não foi possível encaminhar para internação."));
            exit;
        }

        if ($destino === 'exame_imagem') {
            if (!empty($idSalaImagem)) {
                $query = "UPDATE consultas
                          SET status_consulta = 'Encaminhado para Exame de Imagem',
                              id_sala = :id_sala
                          WHERE id_consulta = :id_consulta
                          LIMIT 1";

                $stmt = $db->prepare($query);
                $stmt->bindValue(":id_sala", $idSalaImagem);
                $stmt->bindValue(":id_consulta", $idConsulta);
            } else {
                $query = "UPDATE consultas
                          SET status_consulta = 'Encaminhado para Exame de Imagem'
                          WHERE id_consulta = :id_consulta
                          LIMIT 1";

                $stmt = $db->prepare($query);
                $stmt->bindValue(":id_consulta", $idConsulta);
            }

            if ($stmt->execute()) {
                header("Location: consultas.php?status=encaminhado_exame");
                exit;
            }

            header("Location: consultas.php?status=erro&mensagem=" . urlencode("Não foi possível encaminhar para exame de imagem."));
            exit;
        }

        header("Location: consultas.php?status=erro&mensagem=" . urlencode("Destino inválido."));
        exit;
    }

    if ($acao === 'voltar_atendimento') {
        $idConsulta = $_GET['id_consulta'] ?? $_GET['id'] ?? null;

        if (empty($idConsulta)) {
            header("Location: consultas.php?status=erro&mensagem=" . urlencode("ID da consulta não informado."));
            exit;
        }

        $query = "UPDATE consultas
                  SET status_consulta = 'Em Atendimento'
                  WHERE id_consulta = :id_consulta
                  AND status_consulta = 'Encaminhado para Exame de Imagem'
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindValue(":id_consulta", $idConsulta);

        if ($stmt->execute()) {
            header("Location: consultas.php?status=voltou_atendimento");
            exit;
        }

        header("Location: consultas.php?status=erro&mensagem=" . urlencode("Não foi possível retornar o paciente para atendimento."));
        exit;
    }

    if ($acao === 'liberar_paciente' || $acao === 'liberar') {
        $idConsulta = $_GET['id_consulta'] ?? $_GET['id'] ?? null;

        if (empty($idConsulta)) {
            header("Location: consultas.php?status=erro&mensagem=" . urlencode("ID da consulta não informado."));
            exit;
        }

        if ($consulta->liberarPaciente($idConsulta)) {
            header("Location: consultas.php?status=liberado");
            exit;
        }

        header("Location: consultas.php?status=erro&mensagem=" . urlencode("Não foi possível liberar a consulta selecionada."));
        exit;
    }

    if ($acao === 'cancelar') {
        $idConsulta = $_GET['id_consulta'] ?? $_GET['id'] ?? null;

        if (empty($idConsulta)) {
            header("Location: consultas.php?status=erro&mensagem=" . urlencode("ID da consulta não informado."));
            exit;
        }

        $query = "UPDATE consultas
                  SET status_consulta = 'Cancelada'
                  WHERE id_consulta = :id_consulta
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindValue(":id_consulta", $idConsulta);

        if ($stmt->execute()) {
            header("Location: consultas.php?status=cancelada");
            exit;
        }

        header("Location: consultas.php?status=erro");
        exit;
    }

    header("Location: consultas.php?status=acao_invalida");
    exit;

} catch (Throwable $e) {
    header("Location: consultas.php?status=erro&mensagem=" . urlencode($e->getMessage()));
    exit;
}
?>