<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}

require_once __DIR__ . '/model/Exame.php';
require_once __DIR__ . '/dao/ExameDAO.php';

$filtroStatus = $_GET['status_exame'] ?? '';

function tabelaExisteExames($db, $tabela) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function colunaExisteExames($db, $tabela, $coluna) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
        $stmt->execute([$coluna]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function classeFluxoExame($statusFluxo) {
    switch ($statusFluxo) {
        case 'Aguardando exame':
            return 'warning text-dark';
        case 'Realizando exame':
            return 'primary';
        case 'Retornou do exame':
            return 'info text-dark';
        case 'Exame normal - alta':
            return 'success';
        case 'Exame alterado - alta com prescrição':
            return 'success';
        case 'Exame alterado - retornou ao atendimento':
            return 'info text-dark';
        case 'Aguardando internação':
            return 'danger';
        default:
            return 'secondary';
    }
}

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $exameModel = new ExameDAO($db);
    $stmtExames = $exameModel->read();

    $exames = [];

    if ($stmtExames) {
        $exames = $stmtExames->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!empty($filtroStatus)) {
        $exames = array_filter($exames, function ($exame) use ($filtroStatus) {
            return ($exame['status_exame'] ?? '') === $filtroStatus;
        });
    }

    $pacientesExame = [];

    $temDestinoPaciente = colunaExisteExames($db, 'consultas', 'destino_paciente');
    $temStatusFluxo = colunaExisteExames($db, 'consultas', 'status_fluxo');

    if ($temDestinoPaciente && $temStatusFluxo) {
        $sqlPacientesExame = "
            SELECT 
                c.id_consulta,
                c.id_paciente,
                c.data_consulta,
                c.horario,
                c.status_consulta,
                c.status_fluxo,
                c.destino_paciente,
                p.nome AS nome_paciente,
                p.cpf,
                p.numero_carteirinha,
                p.validade_carteirinha,
                conv.nome_convenio,
                m.nome AS nome_medico,
                m.especialidade
            FROM consultas c
            INNER JOIN pacientes p ON p.id_paciente = c.id_paciente
            LEFT JOIN convenios conv ON p.id_convenio = conv.id_convenio
            LEFT JOIN medicos m ON c.id_medico = m.id_medico
            WHERE c.destino_paciente = 'EXAME'
              AND c.status_fluxo IN ('Aguardando exame', 'Realizando exame')
            ORDER BY c.data_consulta DESC, c.horario DESC
        ";

        $stmtPacientesExame = $db->prepare($sqlPacientesExame);
        $stmtPacientesExame->execute();
        $pacientesExame = $stmtPacientesExame->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $erroExame = $e->getMessage();
    $exames = [];
    $pacientesExame = [];
}

require_once __DIR__ . '/views/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Exames Solicitados</h1>
        <p class="text-muted mb-0">
            Acompanhamento dos exames vinculados aos prontuários e fluxo de pacientes encaminhados para exame.
        </p>
    </div>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="pacientes.php" class="btn btn-outline-primary">
            <i class="fas fa-user-injured me-1"></i> Abrir Prontuário do Paciente
        </a>
    </div>
</div>

<?php if (!empty($erroExame)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar exames:</strong>
        <?php echo htmlspecialchars($erroExame); ?>
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

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['erro'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['erro']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>Fluxo de exames:</strong>
    para solicitar um novo exame, acesse o prontuário do paciente e utilize a opção
    <strong>Novo Exame</strong>. Após o resultado, o paciente pode receber alta, retornar ao atendimento ou ser encaminhado para internação.
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-user-clock me-2 text-warning"></i>
            Pacientes aguardando/realizando exame
        </h5>

        <span class="badge bg-warning text-dark">
            <?php echo count($pacientesExame); ?> paciente(s)
        </span>
    </div>

    <div class="card-body">
        <div class="alert alert-light border small">
            Aqui aparecem os pacientes que estão com o fluxo marcado como
            <strong>Aguardando exame</strong> ou <strong>Realizando exame</strong>.
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Convênio</th>
                        <th>Médico</th>
                        <th>Data</th>
                        <th>Status do Fluxo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($pacientesExame)): ?>
                        <?php foreach ($pacientesExame as $pacienteFluxo): ?>
                            <?php
                                $idConsultaFluxo = $pacienteFluxo['id_consulta'] ?? '';
                                $idPacienteFluxo = $pacienteFluxo['id_paciente'] ?? '';
                                $nomePacienteFluxo = $pacienteFluxo['nome_paciente'] ?? 'Paciente não informado';
                                $cpfPacienteFluxo = $pacienteFluxo['cpf'] ?? '';
                                $nomeConvenioFluxo = $pacienteFluxo['nome_convenio'] ?? '';
                                $nomeMedicoFluxo = $pacienteFluxo['nome_medico'] ?? 'Médico não informado';
                                $statusFluxo = $pacienteFluxo['status_fluxo'] ?? 'Aguardando exame';
                                $classeFluxo = classeFluxoExame($statusFluxo);
                            ?>

                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($nomePacienteFluxo); ?></strong>

                                    <?php if (!empty($cpfPacienteFluxo)): ?>
                                        <br>
                                        <small class="text-muted">
                                            CPF: <?php echo htmlspecialchars($cpfPacienteFluxo); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($nomeConvenioFluxo)): ?>
                                        <?php echo htmlspecialchars($nomeConvenioFluxo); ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Particular</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($nomeMedicoFluxo); ?>

                                    <?php if (!empty($pacienteFluxo['especialidade'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($pacienteFluxo['especialidade']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($pacienteFluxo['data_consulta'])): ?>
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($pacienteFluxo['data_consulta']))); ?>
                                    <?php else: ?>
                                        <span class="text-muted">--/--/----</span>
                                    <?php endif; ?>

                                    <?php if (!empty($pacienteFluxo['horario'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($pacienteFluxo['horario'], 0, 5)); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge bg-<?php echo $classeFluxo; ?>">
                                        <?php echo htmlspecialchars($statusFluxo); ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group flex-wrap">
                                        <?php if ($statusFluxo === 'Aguardando exame'): ?>
                                            <a 
                                                href="processar_fluxo_exame.php?acao=realizando&id_consulta=<?php echo urlencode($idConsultaFluxo); ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                onclick="return confirm('Marcar paciente como realizando exame?')"
                                            >
                                                Realizando
                                            </a>
                                        <?php endif; ?>

                                        <a 
                                            href="prontuario.php?id=<?php echo urlencode($idPacienteFluxo); ?>&id_consulta=<?php echo urlencode($idConsultaFluxo); ?>" 
                                            class="btn btn-sm btn-outline-success"
                                        >
                                            Prontuário
                                        </a>

                                        <a 
                                            href="processar_fluxo_exame.php?acao=normal_alta&id_consulta=<?php echo urlencode($idConsultaFluxo); ?>"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Confirmar exame normal e liberar o paciente com alta?')"
                                        >
                                            Normal - Alta
                                        </a>

                                        <a 
                                            href="processar_fluxo_exame.php?acao=alterado_alta&id_consulta=<?php echo urlencode($idConsultaFluxo); ?>"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Confirmar exame alterado, mas liberar paciente com alta/prescrição?')"
                                        >
                                            Alterado - Alta
                                        </a>

                                        <a 
                                            href="processar_fluxo_exame.php?acao=alterado_retorno&id_consulta=<?php echo urlencode($idConsultaFluxo); ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="return confirm('Confirmar exame alterado e retornar paciente para atendimento?')"
                                        >
                                            Alterado - Retornar
                                        </a>

                                        <a 
                                            href="processar_fluxo_exame.php?acao=alterado_internacao&id_consulta=<?php echo urlencode($idConsultaFluxo); ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Confirmar exame alterado e encaminhar paciente para internação?')"
                                        >
                                            Alterado - Internação
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhum paciente aguardando ou realizando exame no momento.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning small mb-0">
            Esta fila controla o <strong>fluxo do paciente</strong>. Já a lista abaixo controla os 
            <strong>exames solicitados no prontuário</strong>, com resultado, valor, cancelamento e finalização clínica.
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2 text-primary"></i>
            Filtro de exames
        </h5>
    </div>

    <div class="card-body">
        <form method="GET" action="exames.php" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="status_exame" class="form-label">Buscar por status</label>

                <select name="status_exame" id="status_exame" class="form-select">
                    <option value="">Todos os status</option>

                    <option value="Solicitado" <?php echo ($filtroStatus === 'Solicitado') ? 'selected' : ''; ?>>
                        Solicitado
                    </option>

                    <option value="Em análise" <?php echo ($filtroStatus === 'Em análise') ? 'selected' : ''; ?>>
                        Em análise
                    </option>

                    <option value="Finalizado" <?php echo ($filtroStatus === 'Finalizado') ? 'selected' : ''; ?>>
                        Finalizado
                    </option>

                    <option value="Cancelado" <?php echo ($filtroStatus === 'Cancelado') ? 'selected' : ''; ?>>
                        Cancelado
                    </option>
                </select>
            </div>

            <div class="col-md-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i> Filtrar
                </button>

                <a href="exames.php" class="btn btn-outline-secondary">
                    Limpar filtro
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-file-medical-alt me-2 text-primary"></i>
            Lista de exames solicitados
        </h5>

        <span class="badge bg-secondary">
            <?php echo count($exames); ?> registro(s)
        </span>
    </div>

    <div class="card-body">
        <div class="alert alert-light border small">
            Os exames ficam vinculados ao prontuário do atendimento. O valor informado no exame será considerado no faturamento.
            Exames não são excluídos fisicamente; quando necessário, devem ser cancelados para manter o histórico clínico do paciente.
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Paciente</th>
                        <th>Convênio</th>
                        <th>Médico</th>
                        <th>Exame</th>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Resultado</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($exames)): ?>
                        <?php foreach ($exames as $row): ?>
                            <?php
                                $idExame = $row['id_exame'] ?? '';
                                $nomePaciente = $row['nome_paciente'] ?? 'Não informado';
                                $nomeMedico = $row['nome_medico'] ?? 'Não informado';
                                $nomeConvenio = $row['nome_convenio'] ?? '';
                                $numeroCarteirinha = $row['numero_carteirinha'] ?? '';
                                $validadeCarteirinha = $row['validade_carteirinha'] ?? '';
                                $nomeExame = $row['nome_exame'] ?? '';
                                $dataExame = $row['data_exame'] ?? '';
                                $valorExame = $row['valor_exame'] ?? 0;
                                $resultado = $row['resultado'] ?? '';
                                $statusExame = $row['status_exame'] ?? 'Solicitado';

                                $classeStatus = 'secondary';

                                if ($statusExame === 'Solicitado') {
                                    $classeStatus = 'primary';
                                } elseif ($statusExame === 'Em análise') {
                                    $classeStatus = 'warning text-dark';
                                } elseif ($statusExame === 'Finalizado') {
                                    $classeStatus = 'success';
                                } elseif ($statusExame === 'Cancelado') {
                                    $classeStatus = 'danger';
                                }

                                $temConvenio = !empty($nomeConvenio);
                                $convenioValido = false;

                                if ($temConvenio && !empty($validadeCarteirinha)) {
                                    $convenioValido = strtotime($validadeCarteirinha) >= strtotime(date('Y-m-d'));
                                }
                            ?>

                            <tr>
                                <td>#<?php echo htmlspecialchars($idExame); ?></td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nomePaciente); ?></strong>
                                </td>

                                <td>
                                    <?php if ($temConvenio): ?>
                                        <?php echo htmlspecialchars($nomeConvenio); ?>

                                        <?php if (!empty($numeroCarteirinha)): ?>
                                            <br>
                                            <small class="text-muted">
                                                Carteirinha: <?php echo htmlspecialchars($numeroCarteirinha); ?>
                                            </small>
                                        <?php endif; ?>

                                        <?php if (!empty($validadeCarteirinha)): ?>
                                            <br>

                                            <?php if ($convenioValido): ?>
                                                <span class="badge bg-success">
                                                    Válida até <?php echo date('d/m/Y', strtotime($validadeCarteirinha)); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    Vencida em <?php echo date('d/m/Y', strtotime($validadeCarteirinha)); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <br>
                                            <span class="badge bg-warning text-dark">
                                                Validade não informada
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Particular</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($nomeMedico); ?></td>

                                <td><?php echo htmlspecialchars($nomeExame); ?></td>

                                <td>
                                    <?php if (!empty($dataExame)): ?>
                                        <?php echo date('d/m/Y', strtotime($dataExame)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não informada</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    R$ <?php echo number_format((float)$valorExame, 2, ',', '.'); ?>
                                </td>

                                <td>
                                    <?php
                                        if (!empty($resultado)) {
                                            if (function_exists('mb_strimwidth')) {
                                                echo htmlspecialchars(mb_strimwidth($resultado, 0, 70, '...'));
                                            } else {
                                                echo htmlspecialchars(substr($resultado, 0, 70) . (strlen($resultado) > 70 ? '...' : ''));
                                            }
                                        } else {
                                            echo '<span class="text-muted">Sem resultado</span>';
                                        }
                                    ?>
                                </td>

                                <td>
                                    <span class="badge bg-<?php echo $classeStatus; ?>">
                                        <?php echo htmlspecialchars($statusExame); ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <button 
                                            type="button"
                                            class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalVisualizarExame<?php echo htmlspecialchars($idExame); ?>"
                                            title="Visualizar exame"
                                        >
                                            Ver
                                        </button>

                                        <?php if ($statusExame !== 'Cancelado' && $statusExame !== 'Finalizado'): ?>
                                            <a 
                                                href="processar_exames.php?acao=analise&id_exame=<?php echo urlencode($idExame); ?>"
                                                class="btn btn-sm btn-outline-warning"
                                                title="Marcar em análise"
                                            >
                                                Análise
                                            </a>

                                            <button 
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalFinalizarExame<?php echo htmlspecialchars($idExame); ?>"
                                                title="Finalizar"
                                            >
                                                Finalizar
                                            </button>

                                            <a 
                                                href="processar_exames.php?acao=cancelar&id_exame=<?php echo urlencode($idExame); ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Deseja cancelar este exame? Ele será mantido no histórico.')"
                                                title="Cancelar"
                                            >
                                                Cancelar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalVisualizarExame<?php echo htmlspecialchars($idExame); ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detalhes do Exame</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <small class="text-muted">Paciente</small>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($nomePaciente); ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <small class="text-muted">Médico</small>
                                                    <div>
                                                        <?php echo htmlspecialchars($nomeMedico); ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <small class="text-muted">Exame</small>
                                                    <div class="fw-bold">
                                                        <?php echo htmlspecialchars($nomeExame); ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <small class="text-muted">Data</small>
                                                    <div>
                                                        <?php if (!empty($dataExame)): ?>
                                                            <?php echo date('d/m/Y', strtotime($dataExame)); ?>
                                                        <?php else: ?>
                                                            Não informada
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <small class="text-muted">Valor</small>
                                                    <div>
                                                        R$ <?php echo number_format((float)$valorExame, 2, ',', '.'); ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <small class="text-muted">Convênio</small>
                                                    <div>
                                                        <?php if ($temConvenio): ?>
                                                            <?php echo htmlspecialchars($nomeConvenio); ?>

                                                            <?php if (!empty($numeroCarteirinha)): ?>
                                                                <br>
                                                                <small class="text-muted">
                                                                    Carteirinha: <?php echo htmlspecialchars($numeroCarteirinha); ?>
                                                                </small>
                                                            <?php endif; ?>

                                                            <?php if (!empty($validadeCarteirinha)): ?>
                                                                <br>

                                                                <?php if ($convenioValido): ?>
                                                                    <span class="badge bg-success">
                                                                        Válida até <?php echo date('d/m/Y', strtotime($validadeCarteirinha)); ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">
                                                                        Vencida em <?php echo date('d/m/Y', strtotime($validadeCarteirinha)); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <br>
                                                                <span class="badge bg-warning text-dark">
                                                                    Validade não informada
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-dark">Particular</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <small class="text-muted">Status</small>
                                                    <div>
                                                        <span class="badge bg-<?php echo $classeStatus; ?>">
                                                            <?php echo htmlspecialchars($statusExame); ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <small class="text-muted">Resultado / Observação</small>
                                                    <div class="border rounded p-3 bg-light">
                                                        <?php if (!empty($resultado)): ?>
                                                            <?php echo nl2br(htmlspecialchars($resultado)); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sem resultado registrado.</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
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

                            <div class="modal fade" id="modalFinalizarExame<?php echo htmlspecialchars($idExame); ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="processar_exames.php" method="POST">
                                            <input type="hidden" name="acao" value="finalizar">
                                            <input type="hidden" name="id_exame" value="<?php echo htmlspecialchars($idExame); ?>">

                                            <div class="modal-header">
                                                <h5 class="modal-title">Finalizar Exame</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>

                                            <div class="modal-body">
                                                <p class="mb-2">
                                                    <strong>Exame:</strong>
                                                    <?php echo htmlspecialchars($nomeExame); ?>
                                                </p>

                                                <p class="mb-2">
                                                    <strong>Paciente:</strong>
                                                    <?php echo htmlspecialchars($nomePaciente); ?>
                                                </p>

                                                <p class="mb-3">
                                                    <strong>Valor:</strong>
                                                    R$ <?php echo number_format((float)$valorExame, 2, ',', '.'); ?>
                                                </p>

                                                <div class="mb-3">
                                                    <label class="form-label">Resultado do exame</label>
                                                    <textarea 
                                                        name="resultado" 
                                                        class="form-control" 
                                                        rows="5" 
                                                        required
                                                        placeholder="Digite o resultado do exame antes de finalizar..."
                                                    ><?php echo htmlspecialchars($resultado); ?></textarea>
                                                </div>

                                                <div class="alert alert-warning small mb-0">
                                                    Ao finalizar, o exame será marcado como <strong>Finalizado</strong>.
                                                    Depois, use a fila superior para definir a conduta do paciente:
                                                    alta, retorno ao atendimento ou internação.
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancelar
                                                </button>

                                                <button type="submit" class="btn btn-success">
                                                    Salvar Resultado e Finalizar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Nenhum exame encontrado para o filtro selecionado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>