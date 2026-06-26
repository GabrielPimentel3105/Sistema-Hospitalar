<?php
require_once __DIR__ . '/config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: consultas.php");
    exit;
}

$id_consulta = $_POST['id_consulta'] ?? null;
$destino = $_POST['destino'] ?? null;

if (!$id_consulta || !$destino) {
    header("Location: consultas.php?erro=Destino inválido");
    exit;
}

$destinosPermitidos = [
    'ATENDIMENTO',
    'INTERNACAO',
    'EXAME',
    'LIBERADO'
];

if (!in_array($destino, $destinosPermitidos)) {
    header("Location: consultas.php?erro=Destino não permitido");
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    switch ($destino) {
        case 'INTERNACAO':
            $statusConsulta = 'Encaminhado para Internação';
            $statusFluxo = 'Aguardando internação';
            break;

        case 'EXAME':
            $statusConsulta = 'Encaminhado para Exame';
            $statusFluxo = 'Aguardando exame';
            break;

        case 'LIBERADO':
            $statusConsulta = 'Finalizada';
            $statusFluxo = 'Paciente liberado';
            break;

        case 'ATENDIMENTO':
        default:
            $statusConsulta = 'Em Atendimento';
            $statusFluxo = 'Aguardando atendimento';
            break;
    }

    $sql = "
        UPDATE consultas 
        SET 
            destino_paciente = :destino_paciente,
            status_consulta = :status_consulta,
            status_fluxo = :status_fluxo
        WHERE id_consulta = :id_consulta
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':destino_paciente', $destino);
    $stmt->bindParam(':status_consulta', $statusConsulta);
    $stmt->bindParam(':status_fluxo', $statusFluxo);
    $stmt->bindParam(':id_consulta', $id_consulta);

    $stmt->execute();

    if ($destino === 'INTERNACAO') {
        header("Location: internacoes.php?sucesso=Paciente encaminhado para internação");
        exit;
    }

    if ($destino === 'EXAME') {
        header("Location: exames.php?sucesso=Paciente encaminhado para exame");
        exit;
    }

    if ($destino === 'LIBERADO') {
        header("Location: consultas.php?sucesso=Paciente liberado com sucesso");
        exit;
    }

    header("Location: consultas.php?sucesso=Paciente encaminhado para atendimento");
    exit;

} catch (PDOException $e) {
    header("Location: consultas.php?erro=" . urlencode("Erro ao atualizar destino do paciente: " . $e->getMessage()));
    exit;
}