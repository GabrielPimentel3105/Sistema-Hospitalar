<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';

function colunaExisteFluxoExame($db, $tabela, $coluna) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
        $stmt->execute([$coluna]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function tabelaExisteFluxoExame($db, $tabela) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        $_SESSION['mensagem'] = 'Erro de conexão com o banco de dados.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: exames.php');
        exit;
    }

    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
    $idConsulta = $_GET['id_consulta'] ?? $_POST['id_consulta'] ?? '';

    if (empty($acao) || empty($idConsulta)) {
        $_SESSION['mensagem'] = 'Ação ou consulta não informada.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: exames.php');
        exit;
    }

    if (!tabelaExisteFluxoExame($db, 'consultas')) {
        $_SESSION['mensagem'] = 'Tabela consultas não encontrada.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: exames.php');
        exit;
    }

    $temStatusConsulta = colunaExisteFluxoExame($db, 'consultas', 'status_consulta');
    $temStatusFluxo = colunaExisteFluxoExame($db, 'consultas', 'status_fluxo');
    $temDestinoPaciente = colunaExisteFluxoExame($db, 'consultas', 'destino_paciente');

    if (!$temStatusFluxo || !$temDestinoPaciente) {
        $_SESSION['mensagem'] = 'As colunas de fluxo do paciente não foram encontradas. Verifique se o SQL de alteração foi executado.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: exames.php');
        exit;
    }

    if ($acao === 'realizando') {
        $statusConsulta = 'Encaminhado para Exame';
        $statusFluxo = 'Realizando exame';
        $destinoPaciente = 'EXAME';
        $mensagem = 'Paciente marcado como realizando exame.';

    } elseif ($acao === 'normal_alta') {
        $statusConsulta = 'Finalizada';
        $statusFluxo = 'Exame normal - alta';
        $destinoPaciente = 'LIBERADO';
        $mensagem = 'Exame normal. Paciente liberado com alta.';

    } elseif ($acao === 'alterado_alta') {
        $statusConsulta = 'Finalizada';
        $statusFluxo = 'Exame alterado - alta com prescrição';
        $destinoPaciente = 'LIBERADO';
        $mensagem = 'Exame alterado, mas paciente liberado com alta/prescrição.';

    } elseif ($acao === 'alterado_retorno') {
        $statusConsulta = 'Em Atendimento';
        $statusFluxo = 'Exame alterado - retornou ao atendimento';
        $destinoPaciente = 'ATENDIMENTO';
        $mensagem = 'Exame alterado. Paciente retornou ao atendimento para reavaliação.';

    } elseif ($acao === 'alterado_internacao') {
        $statusConsulta = 'Encaminhado para Internação';
        $statusFluxo = 'Aguardando internação';
        $destinoPaciente = 'INTERNACAO';
        $mensagem = 'Exame alterado. Paciente encaminhado para internação.';

    } elseif ($acao === 'retornar') {
        $statusConsulta = 'Em Atendimento';
        $statusFluxo = 'Retornou do exame';
        $destinoPaciente = 'ATENDIMENTO';
        $mensagem = 'Paciente retornou do exame para atendimento.';

    } else {
        $_SESSION['mensagem'] = 'Ação inválida para fluxo de exame.';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: exames.php');
        exit;
    }

    $sets = [];
    $valores = [];

    if ($temStatusConsulta) {
        $sets[] = 'status_consulta = ?';
        $valores[] = $statusConsulta;
    }

    if ($temStatusFluxo) {
        $sets[] = 'status_fluxo = ?';
        $valores[] = $statusFluxo;
    }

    if ($temDestinoPaciente) {
        $sets[] = 'destino_paciente = ?';
        $valores[] = $destinoPaciente;
    }

    $valores[] = $idConsulta;

    $sql = "
        UPDATE consultas
        SET " . implode(', ', $sets) . "
        WHERE id_consulta = ?
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($valores);

    $_SESSION['mensagem'] = $mensagem;
    $_SESSION['tipo_mensagem'] = 'success';

    header('Location: exames.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['mensagem'] = 'Erro ao processar fluxo de exame: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: exames.php');
    exit;
}
?>