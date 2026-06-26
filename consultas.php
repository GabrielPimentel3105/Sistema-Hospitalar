<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}

if (file_exists(__DIR__ . '/dao/FaturamentoDAO.php')) {
    require_once __DIR__ . '/dao/FaturamentoDAO.php';
}

$mensagem = '';
$tipoMensagem = 'info';

function tabelaExiste($db, $tabela) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function colunaExiste($db, $tabela, $coluna) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
        $stmt->execute([$coluna]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function normalizarNumero($valor) {
    $valor = str_replace(',', '.', (string) $valor);
    return is_numeric($valor) ? (float) $valor : 0;
}

function classificarManchester($temperatura, $frequenciaCardiaca, $saturacao, $escalaDor) {
    $temperatura = normalizarNumero($temperatura);
    $frequenciaCardiaca = normalizarNumero($frequenciaCardiaca);
    $saturacao = normalizarNumero($saturacao);
    $escalaDor = normalizarNumero($escalaDor);

    if (
        $escalaDor >= 9 ||
        $temperatura >= 40 ||
        ($saturacao > 0 && $saturacao < 90) ||
        $frequenciaCardiaca >= 130
    ) {
        return 'Vermelho';
    }

    if (
        $escalaDor >= 7 ||
        $temperatura >= 39 ||
        ($saturacao > 0 && $saturacao < 94) ||
        $frequenciaCardiaca >= 120
    ) {
        return 'Laranja';
    }

    if (
        $escalaDor >= 4 ||
        $temperatura >= 38 ||
        $frequenciaCardiaca >= 100
    ) {
        return 'Amarelo';
    }

    if ($escalaDor >= 1) {
        return 'Verde';
    }

    return 'Azul';
}

function classeRisco($risco) {
    switch ($risco) {
        case 'Vermelho':
            return 'bg-danger text-white';
        case 'Laranja':
            return 'bg-warning text-dark';
        case 'Amarelo':
            return 'bg-warning text-dark';
        case 'Verde':
            return 'bg-success text-white';
        case 'Azul':
            return 'bg-primary text-white';
        default:
            return 'bg-secondary text-white';
    }
}

function classeFluxo($statusFluxo) {
    switch ($statusFluxo) {
        case 'Aguardando internação':
            return 'bg-danger text-white';
        case 'Paciente internado':
            return 'bg-danger text-white';
        case 'Aguardando exame':
            return 'bg-warning text-dark';
        case 'Realizando exame':
            return 'bg-primary text-white';
        case 'Exame finalizado':
            return 'bg-success text-white';
        case 'Paciente liberado':
            return 'bg-success text-white';
        case 'Alta com prescrição':
            return 'bg-success text-white';
        case 'Alta hospitalar':
            return 'bg-success text-white';
        case 'Exame normal - alta':
            return 'bg-success text-white';
        case 'Exame alterado - alta com prescrição':
            return 'bg-success text-white';
        case 'Retornou do exame':
            return 'bg-info text-dark';
        case 'Exame alterado - retornou ao atendimento':
            return 'bg-info text-dark';
        case 'Triagem realizada':
            return 'bg-secondary text-white';
        case 'Aguardando atendimento':
            return 'bg-light text-dark border';
        default:
            return 'bg-light text-dark border';
    }
}

function lancarConsultaNoFaturamento($db, $idPaciente, $idConsulta, $valorConsulta) {
    if (empty($idPaciente) || empty($idConsulta)) {
        return false;
    }

    if (!class_exists('FaturamentoDAO')) {
        return false;
    }

    if (!tabelaExiste($db, 'faturamento') || !tabelaExiste($db, 'itens_faturamento')) {
        return false;
    }

    $valorConsulta = normalizarNumero($valorConsulta);

    if ($valorConsulta <= 0) {
        $valorConsulta = 150.00;
    }

    try {
        $faturamentoDAO = new FaturamentoDAO($db);

        if (!method_exists($faturamentoDAO, 'lancarItemAutomatico')) {
            return false;
        }

        return $faturamentoDAO->lancarItemAutomatico(
            $idPaciente,
            'Consulta médica #' . $idConsulta,
            'Consulta',
            1,
            $valorConsulta,
            $idConsulta,
            null,
            true
        );
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

    $temDestinoPaciente = colunaExiste($db, 'consultas', 'destino_paciente');
    $temStatusFluxo = colunaExiste($db, 'consultas', 'status_fluxo');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'cadastrar_consulta') {
            $idPaciente = $_POST['id_paciente'] ?? '';
            $idMedico = $_POST['id_medico'] ?? '';
            $idSala = $_POST['id_sala'] ?? null;
            $dataConsulta = $_POST['data_consulta'] ?? '';
            $horario = $_POST['horario'] ?? '';
            $statusConsulta = $_POST['status_consulta'] ?? 'Pendente';
            $valorConsulta = $_POST['valor_consulta'] ?? 150.00;

            if (empty($idPaciente) || empty($idMedico) || empty($dataConsulta) || empty($horario)) {
                throw new Exception('Preencha paciente, médico, data e horário da consulta.');
            }

            $campos = ['id_paciente', 'id_medico', 'data_consulta', 'horario', 'status_consulta'];
            $valores = [$idPaciente, $idMedico, $dataConsulta, $horario, $statusConsulta];

            if (!empty($idSala) && colunaExiste($db, 'consultas', 'id_sala')) {
                $campos[] = 'id_sala';
                $valores[] = $idSala;
            }

            if ($temDestinoPaciente) {
                $campos[] = 'destino_paciente';
                $valores[] = 'ATENDIMENTO';
            }

            if ($temStatusFluxo) {
                $campos[] = 'status_fluxo';
                $valores[] = 'Aguardando atendimento';
            }

            $placeholders = implode(', ', array_fill(0, count($campos), '?'));
            $sql = "INSERT INTO consultas (" . implode(', ', $campos) . ") VALUES ({$placeholders})";

            $stmt = $db->prepare($sql);
            $stmt->execute($valores);

            $idConsultaGerada = $db->lastInsertId();

            $guiaGerada = lancarConsultaNoFaturamento(
                $db,
                $idPaciente,
                $idConsultaGerada,
                $valorConsulta
            );

            if ($guiaGerada) {
                $_SESSION['mensagem'] = 'Consulta cadastrada com sucesso e lançada no faturamento.';
            } else {
                $_SESSION['mensagem'] = 'Consulta cadastrada com sucesso. Atenção: não foi possível lançar automaticamente no faturamento.';
            }

            $_SESSION['tipo_mensagem'] = 'success';

            header('Location: consultas.php');
            exit;
        }

        if ($acao === 'salvar_triagem') {
            $idConsulta = $_POST['id_consulta'] ?? '';
            $idPaciente = $_POST['id_paciente'] ?? '';

            $pressaoArterial = $_POST['pressao_arterial'] ?? '';
            $temperatura = $_POST['temperatura'] ?? '';
            $frequenciaCardiaca = $_POST['frequencia_cardiaca'] ?? '';
            $saturacao = $_POST['saturacao'] ?? '';
            $escalaDor = $_POST['escala_dor'] ?? '';
            $observacoes = $_POST['observacoes'] ?? '';

            if (empty($idConsulta) || empty($idPaciente)) {
                throw new Exception('Consulta ou paciente não informado para a triagem.');
            }

            $classificacao = classificarManchester($temperatura, $frequenciaCardiaca, $saturacao, $escalaDor);

            if (!tabelaExiste($db, 'triagem')) {
                throw new Exception('A tabela triagem não foi encontrada no banco de dados.');
            }

            $camposTriagem = [];
            $valoresTriagem = [];

            if (colunaExiste($db, 'triagem', 'id_consulta')) {
                $camposTriagem[] = 'id_consulta';
                $valoresTriagem[] = $idConsulta;
            }

            if (colunaExiste($db, 'triagem', 'id_paciente')) {
                $camposTriagem[] = 'id_paciente';
                $valoresTriagem[] = $idPaciente;
            }

            if (colunaExiste($db, 'triagem', 'pressao_arterial')) {
                $camposTriagem[] = 'pressao_arterial';
                $valoresTriagem[] = $pressaoArterial;
            }

            if (colunaExiste($db, 'triagem', 'temperatura')) {
                $camposTriagem[] = 'temperatura';
                $valoresTriagem[] = $temperatura;
            }

            if (colunaExiste($db, 'triagem', 'frequencia_cardiaca')) {
                $camposTriagem[] = 'frequencia_cardiaca';
                $valoresTriagem[] = $frequenciaCardiaca;
            }

            if (colunaExiste($db, 'triagem', 'saturacao')) {
                $camposTriagem[] = 'saturacao';
                $valoresTriagem[] = $saturacao;
            }

            if (colunaExiste($db, 'triagem', 'escala_dor')) {
                $camposTriagem[] = 'escala_dor';
                $valoresTriagem[] = $escalaDor;
            }

            if (colunaExiste($db, 'triagem', 'classificacao_risco')) {
                $camposTriagem[] = 'classificacao_risco';
                $valoresTriagem[] = $classificacao;
            }

            if (colunaExiste($db, 'triagem', 'observacoes')) {
                $camposTriagem[] = 'observacoes';
                $valoresTriagem[] = $observacoes;
            }

            if (colunaExiste($db, 'triagem', 'data_triagem')) {
                $camposTriagem[] = 'data_triagem';
                $valoresTriagem[] = date('Y-m-d');
            }

            if (colunaExiste($db, 'triagem', 'hora_triagem')) {
                $camposTriagem[] = 'hora_triagem';
                $valoresTriagem[] = date('H:i:s');
            }

            $jaExisteTriagem = false;

            if (colunaExiste($db, 'triagem', 'id_consulta')) {
                $stmtCheck = $db->prepare("SELECT id_triagem FROM triagem WHERE id_consulta = ? LIMIT 1");
                $stmtCheck->execute([$idConsulta]);
                $jaExisteTriagem = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            }

            if ($jaExisteTriagem) {
                $sets = [];
                $valoresUpdate = [];

                foreach ($camposTriagem as $index => $campo) {
                    if ($campo === 'id_consulta') {
                        continue;
                    }

                    $sets[] = "{$campo} = ?";
                    $valoresUpdate[] = $valoresTriagem[$index];
                }

                $valoresUpdate[] = $idConsulta;

                $sqlUpdate = "UPDATE triagem SET " . implode(', ', $sets) . " WHERE id_consulta = ?";
                $stmtUpdate = $db->prepare($sqlUpdate);
                $stmtUpdate->execute($valoresUpdate);
            } else {
                $placeholders = implode(', ', array_fill(0, count($camposTriagem), '?'));
                $sqlTriagem = "INSERT INTO triagem (" . implode(', ', $camposTriagem) . ") VALUES ({$placeholders})";
                $stmtTriagem = $db->prepare($sqlTriagem);
                $stmtTriagem->execute($valoresTriagem);
            }

            $setsConsulta = [];
            $valoresConsulta = [];

            if (colunaExiste($db, 'consultas', 'status_consulta')) {
                $setsConsulta[] = 'status_consulta = ?';
                $valoresConsulta[] = 'Triagem realizada';
            }

            if ($temStatusFluxo) {
                $setsConsulta[] = 'status_fluxo = ?';
                $valoresConsulta[] = 'Triagem realizada';
            }

            if ($temDestinoPaciente) {
                $setsConsulta[] = 'destino_paciente = ?';
                $valoresConsulta[] = 'ATENDIMENTO';
            }

            if (!empty($setsConsulta)) {
                $valoresConsulta[] = $idConsulta;

                $sqlStatus = "UPDATE consultas SET " . implode(', ', $setsConsulta) . " WHERE id_consulta = ?";
                $stmtStatus = $db->prepare($sqlStatus);
                $stmtStatus->execute($valoresConsulta);
            }

            $_SESSION['mensagem'] = 'Triagem salva com sucesso. Classificação: ' . $classificacao . '. Agora escolha o destino do paciente.';
            $_SESSION['tipo_mensagem'] = 'success';

            header('Location: consultas.php');
            exit;
        }

        if ($acao === 'definir_destino') {
            $idConsulta = $_POST['id_consulta'] ?? '';
            $destino = $_POST['destino'] ?? '';

            if (empty($idConsulta) || empty($destino)) {
                throw new Exception('Consulta ou destino não informado.');
            }

            $destinosPermitidos = [
                'ATENDIMENTO',
                'INTERNACAO',
                'EXAME',
                'LIBERADO'
            ];

            if (!in_array($destino, $destinosPermitidos)) {
                throw new Exception('Destino inválido.');
            }

            switch ($destino) {
                case 'INTERNACAO':
                    $statusConsulta = 'Encaminhado para Internação';
                    $statusFluxo = 'Aguardando internação';
                    $mensagemDestino = 'Paciente encaminhado para internação. Ele deverá aparecer em Nova Internação.';
                    break;

                case 'EXAME':
                    $statusConsulta = 'Encaminhado para Exame';
                    $statusFluxo = 'Aguardando exame';
                    $mensagemDestino = 'Paciente encaminhado para exame. Ele deverá aparecer na fila de exames.';
                    break;

                case 'LIBERADO':
                    $statusConsulta = 'Finalizada';
                    $statusFluxo = 'Alta com prescrição';
                    $mensagemDestino = 'Paciente recebeu alta com prescrição. Verifique a prescrição no prontuário.';
                    break;

                case 'ATENDIMENTO':
                default:
                    $statusConsulta = 'Em Atendimento';
                    $statusFluxo = 'Aguardando atendimento';
                    $mensagemDestino = 'Paciente encaminhado para atendimento.';
                    break;
            }

            $setsDestino = [];
            $valoresDestino = [];

            if (colunaExiste($db, 'consultas', 'status_consulta')) {
                $setsDestino[] = 'status_consulta = ?';
                $valoresDestino[] = $statusConsulta;
            }

            if ($temDestinoPaciente) {
                $setsDestino[] = 'destino_paciente = ?';
                $valoresDestino[] = $destino;
            }

            if ($temStatusFluxo) {
                $setsDestino[] = 'status_fluxo = ?';
                $valoresDestino[] = $statusFluxo;
            }

            if (empty($setsDestino)) {
                throw new Exception('As colunas de fluxo não foram encontradas na tabela consultas.');
            }

            $valoresDestino[] = $idConsulta;

            $sqlDestino = "UPDATE consultas SET " . implode(', ', $setsDestino) . " WHERE id_consulta = ?";
            $stmtDestino = $db->prepare($sqlDestino);
            $stmtDestino->execute($valoresDestino);

            $_SESSION['mensagem'] = $mensagemDestino;
            $_SESSION['tipo_mensagem'] = 'success';

            header('Location: consultas.php');
            exit;
        }

        if ($acao === 'alterar_status') {
            $idConsulta = $_POST['id_consulta'] ?? '';
            $statusConsulta = $_POST['status_consulta'] ?? '';

            if (empty($idConsulta) || empty($statusConsulta)) {
                throw new Exception('Consulta ou status não informado.');
            }

            $setsStatus = ['status_consulta = ?'];
            $valoresStatus = [$statusConsulta];

            if ($temStatusFluxo) {
                $setsStatus[] = 'status_fluxo = ?';
                $valoresStatus[] = $statusConsulta;
            }

            if ($temDestinoPaciente) {
                if ($statusConsulta === 'Encaminhado para Internação') {
                    $setsStatus[] = 'destino_paciente = ?';
                    $valoresStatus[] = 'INTERNACAO';
                } elseif ($statusConsulta === 'Encaminhado para Exame') {
                    $setsStatus[] = 'destino_paciente = ?';
                    $valoresStatus[] = 'EXAME';
                } elseif ($statusConsulta === 'Finalizada') {
                    $setsStatus[] = 'destino_paciente = ?';
                    $valoresStatus[] = 'LIBERADO';
                } elseif ($statusConsulta === 'Em Atendimento') {
                    $setsStatus[] = 'destino_paciente = ?';
                    $valoresStatus[] = 'ATENDIMENTO';
                }
            }

            $valoresStatus[] = $idConsulta;

            $sqlAlterarStatus = "UPDATE consultas SET " . implode(', ', $setsStatus) . " WHERE id_consulta = ?";
            $stmt = $db->prepare($sqlAlterarStatus);
            $stmt->execute($valoresStatus);

            $_SESSION['mensagem'] = 'Status da consulta atualizado com sucesso.';
            $_SESSION['tipo_mensagem'] = 'success';

            header('Location: consultas.php');
            exit;
        }
    }

    $pacientes = [];
    $medicos = [];
    $salas = [];
    $consultas = [];

    $stmtPacientes = $db->prepare("
        SELECT 
            p.*,
            c.nome_convenio
        FROM pacientes p
        LEFT JOIN convenios c ON p.id_convenio = c.id_convenio
        ORDER BY p.nome ASC
    ");
    $stmtPacientes->execute();
    $pacientes = $stmtPacientes->fetchAll(PDO::FETCH_ASSOC);

    $stmtMedicos = $db->prepare("
        SELECT 
            id_medico,
            nome,
            crm,
            especialidade
        FROM medicos
        ORDER BY nome ASC
    ");
    $stmtMedicos->execute();
    $medicos = $stmtMedicos->fetchAll(PDO::FETCH_ASSOC);

    if (tabelaExiste($db, 'salas')) {
        $stmtSalas = $db->prepare("
            SELECT *
            FROM salas
            ORDER BY id_sala ASC
        ");
        $stmtSalas->execute();
        $salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);
    }

    $joinSala = colunaExiste($db, 'consultas', 'id_sala') && tabelaExiste($db, 'salas');

    $sqlConsultas = "
        SELECT
            c.id_consulta,
            c.data_consulta,
            c.horario,
            c.status_consulta,
            c.id_paciente,
            c.id_medico
    ";

    if ($temDestinoPaciente) {
        $sqlConsultas .= ",
            c.destino_paciente
        ";
    }

    if ($temStatusFluxo) {
        $sqlConsultas .= ",
            c.status_fluxo
        ";
    }

    $sqlConsultas .= ",
            p.nome AS paciente_nome,
            p.cpf,
            p.numero_carteirinha,
            p.validade_carteirinha,
            conv.nome_convenio,
            m.nome AS medico_nome,
            m.crm,
            m.especialidade
    ";

    if ($joinSala) {
        $sqlConsultas .= ",
            s.numero_sala,
            s.tipo_sala
        ";
    }

    $sqlConsultas .= "
        FROM consultas c
        LEFT JOIN pacientes p ON c.id_paciente = p.id_paciente
        LEFT JOIN convenios conv ON p.id_convenio = conv.id_convenio
        LEFT JOIN medicos m ON c.id_medico = m.id_medico
    ";

    if ($joinSala) {
        $sqlConsultas .= " LEFT JOIN salas s ON c.id_sala = s.id_sala ";
    }

    $sqlConsultas .= "
        ORDER BY c.data_consulta DESC, c.horario DESC
    ";

    $stmtConsultas = $db->prepare($sqlConsultas);
    $stmtConsultas->execute();
    $consultas = $stmtConsultas->fetchAll(PDO::FETCH_ASSOC);

    $triagensPorConsulta = [];

    if (tabelaExiste($db, 'triagem')) {
        $temIdConsultaTriagem = colunaExiste($db, 'triagem', 'id_consulta');

        if ($temIdConsultaTriagem) {
            $stmtTriagens = $db->prepare("SELECT * FROM triagem");
            $stmtTriagens->execute();

            while ($triagem = $stmtTriagens->fetch(PDO::FETCH_ASSOC)) {
                $triagensPorConsulta[$triagem['id_consulta']] = $triagem;
            }
        }
    }

} catch (Throwable $e) {
    $mensagem = $e->getMessage();
    $tipoMensagem = 'danger';

    $pacientes = [];
    $medicos = [];
    $salas = [];
    $consultas = [];
    $triagensPorConsulta = [];
}

require_once __DIR__ . '/views/header.php';
?>

<style>
    .page-header-clean {
        background-color: #ffffff;
        border: 1px solid #e9eef5;
        border-radius: 14px;
        padding: 22px 24px;
        margin-bottom: 22px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.035);
    }

    .page-header-clean h1 {
        font-size: 1.55rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .page-header-clean p {
        color: #6b7280;
        margin-bottom: 0;
        font-size: 0.95rem;
    }

    .card-clean {
        background-color: #ffffff;
        border: 1px solid #e9eef5;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.035);
        overflow: hidden;
    }

    .card-clean .card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #edf2f7;
        padding: 16px 18px;
        font-weight: 700;
        color: #1f2937;
    }

    .table-clean {
        table-layout: auto;
    }

    .table-clean thead th {
        background-color: #f9fafb;
        color: #6b7280;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04rem;
        border-bottom: 1px solid #e5e7eb;
        padding: 13px 14px;
        white-space: nowrap;
    }

    .table-clean tbody td {
        padding: 14px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.92rem;
    }

    .table-clean tbody tr:hover {
        background-color: #fbfdff;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .badge-soft {
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.76rem;
        font-weight: 600;
    }

    .aviso-convenio {
        font-size: 0.78rem;
        color: #b42318;
        font-weight: 600;
    }

    .text-small-muted {
        font-size: 0.82rem;
        color: #6b7280;
    }

    .btn-destino {
        min-width: 170px;
        text-align: left;
    }

    .coluna-acoes {
        min-width: 270px;
        width: 270px;
    }

    .acoes-consulta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        min-width: 245px;
        max-width: 260px;
        margin-left: auto;
    }

    .acoes-consulta .btn {
        width: 100%;
        font-size: 0.78rem;
        padding: 0.32rem 0.45rem;
        white-space: nowrap;
    }
</style>

<div class="page-header-clean">
    <div class="d-flex justify-content-between flex-wrap align-items-center gap-3">
        <div>
            <h1>
                <i class="fas fa-calendar-check me-2 text-primary"></i>
                Consultas & Triagem
            </h1>
            <p>
                Cadastre consultas, registre triagens e acompanhe o fluxo do paciente.
            </p>
        </div>

        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaConsulta">
                <i class="fas fa-plus me-1"></i> Nova Consulta
            </button>
        </div>
    </div>
</div>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($tipoMensagem); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensagem); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['tipo_mensagem'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <?php
        unset($_SESSION['mensagem']);
        unset($_SESSION['tipo_mensagem']);
    ?>
<?php endif; ?>

<div class="card card-clean">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-list me-2 text-primary"></i>
            Consultas cadastradas
        </span>

        <span class="text-small-muted">
            Total: <?php echo count($consultas); ?>
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-clean">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Status</th>
                        <th>Fluxo</th>
                        <th>Triagem</th>
                        <th class="text-end coluna-acoes">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($consultas)): ?>
                        <?php foreach ($consultas as $consulta): ?>
                            <?php
                                $idConsulta = $consulta['id_consulta'] ?? '';
                                $idPaciente = $consulta['id_paciente'] ?? '';
                                $statusConsulta = $consulta['status_consulta'] ?? 'Pendente';
                                $statusFluxo = $consulta['status_fluxo'] ?? 'Aguardando atendimento';
                                $destinoPaciente = $consulta['destino_paciente'] ?? 'ATENDIMENTO';

                                $validade = $consulta['validade_carteirinha'] ?? null;
                                $carteiraVencida = false;

                                if (!empty($validade) && strtotime($validade) < strtotime(date('Y-m-d'))) {
                                    $carteiraVencida = true;
                                }

                                $triagem = $triagensPorConsulta[$idConsulta] ?? null;
                                $classificacao = $triagem['classificacao_risco'] ?? '';
                                $temTriagem = !empty($classificacao);
                            ?>

                            <tr>
                                <td>
                                    <?php if (!empty($consulta['data_consulta'])): ?>
                                        <strong><?php echo date('d/m/Y', strtotime($consulta['data_consulta'])); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">--/--/----</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($consulta['horario'])): ?>
                                        <?php echo htmlspecialchars(substr($consulta['horario'], 0, 5)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">--:--</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($consulta['paciente_nome'] ?? 'Paciente não informado'); ?></strong>

                                    <?php if (!empty($consulta['nome_convenio'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($consulta['nome_convenio']); ?>
                                        </small>
                                    <?php else: ?>
                                        <br>
                                        <small class="text-muted">Particular</small>
                                    <?php endif; ?>

                                    <?php if ($carteiraVencida): ?>
                                        <br>
                                        <span class="aviso-convenio">
                                            <i class="fas fa-triangle-exclamation me-1"></i>
                                            Carteirinha vencida
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($consulta['medico_nome'] ?? 'Médico não informado'); ?>

                                    <?php if (!empty($consulta['especialidade'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($consulta['especialidade']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge badge-soft bg-light text-dark border">
                                        <?php echo htmlspecialchars($statusConsulta); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge-soft <?php echo classeFluxo($statusFluxo); ?>">
                                        <?php echo htmlspecialchars($statusFluxo); ?>
                                    </span>

                                    <?php if (!empty($destinoPaciente)): ?>
                                        <br>
                                        <small class="text-muted">
                                            Destino: <?php echo htmlspecialchars($destinoPaciente); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($classificacao)): ?>
                                        <span class="badge badge-soft <?php echo classeRisco($classificacao); ?>">
                                            <?php echo htmlspecialchars($classificacao); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Não realizada</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end coluna-acoes">
                                    <div class="acoes-consulta">
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalTriagem<?php echo htmlspecialchars($idConsulta); ?>"
                                            <?php if ($carteiraVencida): ?>
                                                onclick="return confirm('Atenção: este paciente está com a carteirinha vencida. Deseja continuar mesmo assim?');"
                                            <?php endif; ?>
                                        >
                                            Triagem
                                        </button>

                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDestino<?php echo htmlspecialchars($idConsulta); ?>"
                                            <?php if (!$temTriagem): ?>
                                                title="Faça a triagem antes de encaminhar o paciente"
                                            <?php endif; ?>
                                        >
                                            Destino
                                        </button>

                                        <a 
                                            href="prontuario.php?id=<?php echo urlencode($idPaciente); ?>&id_consulta=<?php echo urlencode($idConsulta); ?>" 
                                            class="btn btn-sm btn-outline-success"
                                            <?php if ($carteiraVencida): ?>
                                                onclick="return confirm('Atenção: este paciente está com a carteirinha vencida. Deseja abrir o prontuário mesmo assim?');"
                                            <?php endif; ?>
                                        >
                                            Prontuário
                                        </a>

                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalStatus<?php echo htmlspecialchars($idConsulta); ?>"
                                        >
                                            Status
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalTriagem<?php echo htmlspecialchars($idConsulta); ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" action="consultas.php">
                                            <input type="hidden" name="acao" value="salvar_triagem">
                                            <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">
                                            <input type="hidden" name="id_paciente" value="<?php echo htmlspecialchars($idPaciente); ?>">

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Triagem - <?php echo htmlspecialchars($consulta['paciente_nome'] ?? 'Paciente'); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                <?php if ($carteiraVencida): ?>
                                                    <div class="alert alert-warning">
                                                        <strong>Atenção:</strong> este paciente está com a carteirinha vencida.
                                                        Confirme os dados do convênio antes de prosseguir.
                                                    </div>
                                                <?php endif; ?>

                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Pressão Arterial</label>
                                                        <input 
                                                            type="text" 
                                                            name="pressao_arterial" 
                                                            class="form-control" 
                                                            placeholder="Ex: 120/80"
                                                            value="<?php echo htmlspecialchars($triagem['pressao_arterial'] ?? ''); ?>"
                                                        >
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Temperatura</label>
                                                        <input 
                                                            type="number" 
                                                            step="0.1" 
                                                            name="temperatura" 
                                                            class="form-control" 
                                                            placeholder="Ex: 36.5"
                                                            value="<?php echo htmlspecialchars($triagem['temperatura'] ?? ''); ?>"
                                                        >
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Frequência Cardíaca</label>
                                                        <input 
                                                            type="number" 
                                                            name="frequencia_cardiaca" 
                                                            class="form-control" 
                                                            placeholder="Ex: 80"
                                                            value="<?php echo htmlspecialchars($triagem['frequencia_cardiaca'] ?? ''); ?>"
                                                        >
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Saturação O₂</label>
                                                        <input 
                                                            type="number" 
                                                            name="saturacao" 
                                                            class="form-control" 
                                                            placeholder="Ex: 98"
                                                            value="<?php echo htmlspecialchars($triagem['saturacao'] ?? ''); ?>"
                                                        >
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Escala de Dor</label>
                                                        <select name="escala_dor" class="form-select">
                                                            <option value="">Selecione...</option>
                                                            <?php for ($i = 0; $i <= 10; $i++): ?>
                                                                <option 
                                                                    value="<?php echo $i; ?>"
                                                                    <?php echo (($triagem['escala_dor'] ?? '') == $i) ? 'selected' : ''; ?>
                                                                >
                                                                    <?php echo $i; ?>
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Classificação</label>
                                                        <input 
                                                            type="text" 
                                                            class="form-control" 
                                                            value="<?php echo htmlspecialchars($classificacao ?: 'Automática após salvar'); ?>"
                                                            readonly
                                                        >
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label">Observações</label>
                                                        <textarea 
                                                            name="observacoes" 
                                                            class="form-control" 
                                                            rows="3"
                                                            placeholder="Descreva sintomas, queixas e observações da triagem..."
                                                        ><?php echo htmlspecialchars($triagem['observacoes'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancelar
                                                </button>

                                                <button type="submit" class="btn btn-primary">
                                                    Salvar Triagem
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="modalDestino<?php echo htmlspecialchars($idConsulta); ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Encaminhar paciente
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p class="mb-2">
                                                <strong>Paciente:</strong>
                                                <?php echo htmlspecialchars($consulta['paciente_nome'] ?? 'Paciente'); ?>
                                            </p>

                                            <p class="mb-3">
                                                <strong>Status atual:</strong>
                                                <?php echo htmlspecialchars($statusConsulta); ?>
                                                <br>
                                                <strong>Fluxo atual:</strong>
                                                <?php echo htmlspecialchars($statusFluxo); ?>
                                            </p>

                                            <?php if (!$temTriagem): ?>
                                                <div class="alert alert-warning">
                                                    Este paciente ainda não possui triagem registrada.
                                                    O ideal é salvar a triagem antes de escolher o destino.
                                                </div>
                                            <?php endif; ?>

                                            <div class="alert alert-light border small">
                                                Fluxo definido: após a triagem, o paciente pode receber alta com prescrição,
                                                seguir para exame ou ser encaminhado para internação.
                                            </div>

                                            <div class="d-grid gap-2">
                                                <form method="POST" action="consultas.php">
                                                    <input type="hidden" name="acao" value="definir_destino">
                                                    <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">
                                                    <input type="hidden" name="destino" value="LIBERADO">

                                                    <button 
                                                        type="submit" 
                                                        class="btn btn-success btn-destino w-100"
                                                        onclick="return confirm('Confirmar alta com prescrição para este paciente?');"
                                                    >
                                                        <i class="fas fa-file-prescription me-2"></i>
                                                        Alta com Prescrição
                                                    </button>
                                                </form>

                                                <form method="POST" action="consultas.php">
                                                    <input type="hidden" name="acao" value="definir_destino">
                                                    <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">
                                                    <input type="hidden" name="destino" value="EXAME">

                                                    <button type="submit" class="btn btn-warning btn-destino w-100">
                                                        <i class="fas fa-vial me-2"></i>
                                                        Encaminhar para Exame
                                                    </button>
                                                </form>

                                                <form method="POST" action="consultas.php">
                                                    <input type="hidden" name="acao" value="definir_destino">
                                                    <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">
                                                    <input type="hidden" name="destino" value="INTERNACAO">

                                                    <button type="submit" class="btn btn-danger btn-destino w-100">
                                                        <i class="fas fa-bed-pulse me-2"></i>
                                                        Encaminhar para Internação
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                Fechar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="modalStatus<?php echo htmlspecialchars($idConsulta); ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="consultas.php">
                                            <input type="hidden" name="acao" value="alterar_status">
                                            <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">

                                            <div class="modal-header">
                                                <h5 class="modal-title">Alterar Status da Consulta</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                <label class="form-label">Status</label>
                                                <select name="status_consulta" class="form-select" required>
                                                    <?php
                                                        $statusOptions = [
                                                            'Pendente',
                                                            'Aguardando',
                                                            'Triagem realizada',
                                                            'Em Atendimento',
                                                            'Encaminhado para Exame',
                                                            'Encaminhado para Internação',
                                                            'Finalizada',
                                                            'Cancelada'
                                                        ];
                                                    ?>

                                                    <?php foreach ($statusOptions as $status): ?>
                                                        <option 
                                                            value="<?php echo htmlspecialchars($status); ?>"
                                                            <?php echo ($statusConsulta === $status) ? 'selected' : ''; ?>
                                                        >
                                                            <?php echo htmlspecialchars($status); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <div class="alert alert-info mt-3 mb-0">
                                                    Para encaminhar corretamente para exame ou internação, prefira usar o botão 
                                                    <strong>Destino</strong>, pois ele atualiza também a fila do paciente.
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancelar
                                                </button>

                                                <button type="submit" class="btn btn-primary">
                                                    Salvar Status
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Nenhuma consulta cadastrada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovaConsulta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="consultas.php">
                <input type="hidden" name="acao" value="cadastrar_consulta">

                <div class="modal-header">
                    <h5 class="modal-title">Nova Consulta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Paciente</label>
                            <select name="id_paciente" class="form-select" required>
                                <option value="">Selecione...</option>

                                <?php foreach ($pacientes as $paciente): ?>
                                    <?php
                                        $validadePaciente = $paciente['validade_carteirinha'] ?? null;
                                        $vencidaPaciente = false;

                                        if (!empty($validadePaciente) && strtotime($validadePaciente) < strtotime(date('Y-m-d'))) {
                                            $vencidaPaciente = true;
                                        }
                                    ?>

                                    <option value="<?php echo htmlspecialchars($paciente['id_paciente']); ?>">
                                        <?php echo htmlspecialchars($paciente['nome']); ?>
                                        <?php if (!empty($paciente['nome_convenio'])): ?>
                                            - <?php echo htmlspecialchars($paciente['nome_convenio']); ?>
                                        <?php else: ?>
                                            - Particular
                                        <?php endif; ?>

                                        <?php if ($vencidaPaciente): ?>
                                            - CARTEIRINHA VENCIDA
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Médico</label>
                            <select name="id_medico" class="form-select" required>
                                <option value="">Selecione...</option>

                                <?php foreach ($medicos as $medico): ?>
                                    <option value="<?php echo htmlspecialchars($medico['id_medico']); ?>">
                                        <?php echo htmlspecialchars($medico['nome']); ?>
                                        <?php if (!empty($medico['especialidade'])): ?>
                                            - <?php echo htmlspecialchars($medico['especialidade']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (!empty($salas) && colunaExiste($db, 'consultas', 'id_sala')): ?>
                            <div class="col-md-4">
                                <label class="form-label">Sala</label>
                                <select name="id_sala" class="form-select">
                                    <option value="">Selecione...</option>

                                    <?php foreach ($salas as $sala): ?>
                                        <option value="<?php echo htmlspecialchars($sala['id_sala']); ?>">
                                            Sala 
                                            <?php 
                                                echo htmlspecialchars(
                                                    $sala['numero_sala'] 
                                                    ?? $sala['nome_sala'] 
                                                    ?? $sala['id_sala']
                                                ); 
                                            ?>

                                            <?php if (!empty($sala['tipo_sala'])): ?>
                                                - <?php echo htmlspecialchars($sala['tipo_sala']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-4">
                            <label class="form-label">Data da Consulta</label>
                            <input 
                                type="date" 
                                name="data_consulta" 
                                class="form-control" 
                                value="<?php echo date('Y-m-d'); ?>"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Horário</label>
                            <input 
                                type="time" 
                                name="horario" 
                                class="form-control" 
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Valor da Consulta (R$)</label>
                            <input 
                                type="number" 
                                name="valor_consulta" 
                                class="form-control" 
                                step="0.01"
                                min="0"
                                value="150.00"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status_consulta" class="form-select">
                                <option value="Pendente">Pendente</option>
                                <option value="Aguardando">Aguardando</option>
                                <option value="Confirmada">Confirmada</option>
                                <option value="Em Atendimento">Em Atendimento</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <strong>Faturamento:</strong> ao cadastrar a consulta, o sistema lançará automaticamente o item
                                <strong>Consulta médica</strong> na guia pendente do paciente. Se o paciente tiver convênio, a cobrança fica vinculada ao convênio; se não tiver, fica como particular.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                <strong>Observação:</strong> se o paciente estiver com carteirinha vencida, o sistema exibirá um aviso antes da triagem e antes de abrir o prontuário.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Cadastrar Consulta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>