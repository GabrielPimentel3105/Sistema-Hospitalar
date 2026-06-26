<?php

require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}

require_once __DIR__ . '/views/header.php';

$totalInternados = 0;
$leitosDisponiveis = 0;
$consultasHoje = 0;
$triagensPendentes = 0;
$proximasConsultas = [];
$alertasManchester = [];
$erroDashboard = '';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total 
            FROM internacoes 
            WHERE status_internacao IN ('Ativa', 'Internado', 'Em andamento')
        ");
        $stmt->execute();
        $totalInternados = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Throwable $e) {
        $totalInternados = 0;
    }

    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total 
            FROM leitos 
            WHERE status_leito IN ('Disponível', 'Disponivel', 'Livre')
        ");
        $stmt->execute();
        $leitosDisponiveis = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Throwable $e) {
        $leitosDisponiveis = 0;
    }

    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total 
            FROM consultas 
            WHERE DATE(data_consulta) = CURDATE()
        ");
        $stmt->execute();
        $consultasHoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Throwable $e) {
        $consultasHoje = 0;
    }

    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS total 
            FROM consultas 
            WHERE status_consulta IN ('Pendente', 'Aguardando', 'Em espera')
        ");
        $stmt->execute();
        $triagensPendentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Throwable $e) {
        $triagensPendentes = 0;
    }

    try {
        $stmt = $db->prepare("
            SELECT 
                c.id_consulta,
                c.data_consulta,
                c.horario,
                c.status_consulta,
                p.id_paciente,
                p.nome AS paciente_nome,
                m.nome AS medico_nome,
                m.especialidade
            FROM consultas c
            LEFT JOIN pacientes p ON c.id_paciente = p.id_paciente
            LEFT JOIN medicos m ON c.id_medico = m.id_medico
            WHERE DATE(c.data_consulta) >= CURDATE()
            ORDER BY c.data_consulta ASC, c.horario ASC
            LIMIT 10
        ");
        $stmt->execute();
        $proximasConsultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $proximasConsultas = [];
    }

    try {
        $stmt = $db->prepare("
            SELECT 
                t.id_triagem,
                t.classificacao_risco,
                t.pressao_arterial,
                t.temperatura,
                p.nome
            FROM triagem t
            LEFT JOIN pacientes p ON t.id_paciente = p.id_paciente
            WHERE t.classificacao_risco IN ('Vermelho', 'Laranja', 'Amarelo')
            ORDER BY 
                CASE 
                    WHEN t.classificacao_risco = 'Vermelho' THEN 1
                    WHEN t.classificacao_risco = 'Laranja' THEN 2
                    WHEN t.classificacao_risco = 'Amarelo' THEN 3
                    ELSE 4
                END
            LIMIT 10
        ");
        $stmt->execute();
        $alertasManchester = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $alertasManchester = [];
    }

} catch (Throwable $e) {
    $erroDashboard = $e->getMessage();
}
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

    .btn-clean-print {
        border-radius: 8px;
        font-size: 0.86rem;
        padding: 8px 14px;
    }

    .metric-card-clean {
        background-color: #ffffff;
        border: 1px solid #e9eef5;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.035);
        height: 100%;
    }

    .metric-card-clean .card-body {
        padding: 20px;
    }

    .metric-title {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04rem;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .metric-value {
        font-size: 1.85rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0;
    }

    .metric-icon-clean {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.18rem;
        background-color: #f3f6fb;
        color: #2c3e50;
    }

    .metric-line {
        width: 38px;
        height: 3px;
        border-radius: 999px;
        margin-top: 14px;
        background-color: #dbe7f5;
    }

    .metric-line.blue {
        background-color: #9cc4ee;
    }

    .metric-line.green {
        background-color: #a8dbc0;
    }

    .metric-line.yellow {
        background-color: #f3d889;
    }

    .metric-line.red {
        background-color: #efaaaa;
    }

    .panel-clean {
        background-color: #ffffff;
        border: 1px solid #e9eef5;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.035);
        overflow: hidden;
    }

    .panel-clean .card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #edf2f7;
        padding: 16px 18px;
    }

    .panel-title-clean {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
        font-size: 1rem;
    }

    .panel-subtitle-clean {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 0.85rem;
    }

    .table-clean {
        margin-bottom: 0;
    }

    .table-clean thead th {
        background-color: #f9fafb;
        color: #6b7280;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04rem;
        border-bottom: 1px solid #e5e7eb;
        padding: 13px 14px;
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

    .badge-clean {
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.76rem;
        font-weight: 600;
    }

    .triagem-item-clean {
        border: 1px solid #edf2f7;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        background-color: #fcfdff;
    }

    .triagem-title {
        font-weight: 700;
        margin-bottom: 0;
        font-size: 0.94rem;
    }

    .risk-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 999px;
        margin-right: 7px;
    }

    .risk-vermelho {
        background-color: #dc3545;
    }

    .risk-laranja {
        background-color: #fd7e14;
    }

    .risk-amarelo {
        background-color: #ffc107;
    }

    .empty-clean {
        text-align: center;
        padding: 42px 20px;
        color: #6b7280;
    }

    .empty-clean i {
        font-size: 2.3rem;
        margin-bottom: 12px;
        opacity: 0.75;
    }

    @media (max-width: 768px) {
        .page-header-clean {
            padding: 18px;
        }

        .page-header-clean h1 {
            font-size: 1.35rem;
        }

        .metric-value {
            font-size: 1.55rem;
        }
    }
</style>

<div class="page-header-clean">
    <div class="d-flex justify-content-between flex-wrap align-items-center gap-3">
        <div>
            <h1>
                <i class="fas fa-chart-line me-2 text-primary"></i>
                Dashboard Administrativo
            </h1>
            <p>
                Resumo dos atendimentos, leitos, consultas e alertas de triagem.
            </p>
        </div>

        <div>
            <button type="button" class="btn btn-outline-secondary btn-clean-print" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Relatório Diário
            </button>
        </div>
    </div>
</div>

<?php if (!empty($erroDashboard)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar dashboard:</strong>
        <?php echo htmlspecialchars($erroDashboard); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card metric-card-clean">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="metric-title">Pacientes Internados</div>
                        <h2 class="metric-value"><?php echo htmlspecialchars($totalInternados); ?></h2>
                    </div>

                    <div class="metric-icon-clean">
                        <i class="fas fa-user-injured"></i>
                    </div>
                </div>

                <div class="metric-line blue"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card metric-card-clean">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="metric-title">Leitos Disponíveis</div>
                        <h2 class="metric-value"><?php echo htmlspecialchars($leitosDisponiveis); ?></h2>
                    </div>

                    <div class="metric-icon-clean">
                        <i class="fas fa-bed"></i>
                    </div>
                </div>

                <div class="metric-line green"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card metric-card-clean">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="metric-title">Consultas Hoje</div>
                        <h2 class="metric-value"><?php echo htmlspecialchars($consultasHoje); ?></h2>
                    </div>

                    <div class="metric-icon-clean">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>

                <div class="metric-line yellow"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card metric-card-clean">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="metric-title">Triagens Pendentes</div>
                        <h2 class="metric-value"><?php echo htmlspecialchars($triagensPendentes); ?></h2>
                    </div>

                    <div class="metric-icon-clean">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                </div>

                <div class="metric-line red"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card panel-clean h-100">
            <div class="card-header">
                <h6 class="panel-title-clean">
                    <i class="fas fa-clock me-2 text-primary"></i>
                    Próximas Consultas
                </h6>
                <p class="panel-subtitle-clean">
                    Consultas agendadas a partir da data atual.
                </p>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-clean">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($proximasConsultas)): ?>
                                <?php foreach ($proximasConsultas as $row): ?>
                                    <?php
                                        $dataConsulta = $row['data_consulta'] ?? '';
                                        $horario = $row['horario'] ?? '';
                                        $pacienteNome = $row['paciente_nome'] ?? 'Paciente não informado';
                                        $medicoNome = $row['medico_nome'] ?? 'Médico não informado';
                                        $especialidade = $row['especialidade'] ?? '';
                                        $statusConsulta = $row['status_consulta'] ?? '';
                                        $idPaciente = $row['id_paciente'] ?? '';

                                        $badgeClass = 'bg-warning text-dark';

                                        if ($statusConsulta === 'Confirmada') {
                                            $badgeClass = 'bg-success';
                                        } elseif ($statusConsulta === 'Cancelada') {
                                            $badgeClass = 'bg-danger';
                                        } elseif ($statusConsulta === 'Realizada' || $statusConsulta === 'Finalizada') {
                                            $badgeClass = 'bg-primary';
                                        }
                                    ?>

                                    <tr>
                                        <td>
                                            <?php if (!empty($dataConsulta)): ?>
                                                <strong><?php echo date('d/m/Y', strtotime($dataConsulta)); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">--/--/----</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($horario)): ?>
                                                <?php echo date('H:i', strtotime($horario)); ?>
                                            <?php else: ?>
                                                <span class="text-muted">--:--</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <strong><?php echo htmlspecialchars($pacienteNome); ?></strong>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($medicoNome); ?>

                                            <?php if (!empty($especialidade)): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($especialidade); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="badge badge-clean <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($statusConsulta ?: 'Sem status'); ?>
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            <?php if (!empty($idPaciente)): ?>
                                                <a 
                                                    href="prontuario.php?id=<?php echo urlencode($idPaciente); ?>" 
                                                    class="btn btn-sm btn-outline-primary"
                                                >
                                                    <i class="fas fa-eye me-1"></i> Ver
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-clean">
                                            <i class="fas fa-calendar-times"></i>
                                            <p class="mb-0">Nenhuma consulta agendada.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card panel-clean h-100">
            <div class="card-header">
                <h6 class="panel-title-clean">
                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                    Alertas de Triagem
                </h6>
                <p class="panel-subtitle-clean">
                    Pacientes com prioridade no Protocolo de Manchester.
                </p>
            </div>

            <div class="card-body">
                <?php if (!empty($alertasManchester)): ?>
                    <?php foreach ($alertasManchester as $row): ?>
                        <?php
                            $classificacao = $row['classificacao_risco'] ?? '';
                            $nomePaciente = $row['nome'] ?? 'Paciente não informado';
                            $pressao = $row['pressao_arterial'] ?? '-';
                            $temperatura = $row['temperatura'] ?? '-';

                            $riskClass = 'risk-amarelo';
                            $textClass = 'text-warning';

                            if ($classificacao === 'Vermelho') {
                                $riskClass = 'risk-vermelho';
                                $textClass = 'text-danger';
                            } elseif ($classificacao === 'Laranja') {
                                $riskClass = 'risk-laranja';
                                $textClass = 'text-warning';
                            } elseif ($classificacao === 'Amarelo') {
                                $riskClass = 'risk-amarelo';
                                $textClass = 'text-warning';
                            }
                        ?>

                        <div class="triagem-item-clean">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="triagem-title <?php echo $textClass; ?>">
                                    <span class="risk-dot <?php echo $riskClass; ?>"></span>
                                    <?php echo htmlspecialchars($classificacao); ?>
                                </h6>

                                <small class="text-muted">Ativo</small>
                            </div>

                            <p class="mb-2">
                                Paciente:
                                <strong><?php echo htmlspecialchars($nomePaciente); ?></strong>
                            </p>

                            <small class="text-muted">
                                PA: <?php echo htmlspecialchars($pressao); ?>
                                <span class="mx-1">|</span>
                                T: <?php echo htmlspecialchars($temperatura); ?>°C
                            </small>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-grid mt-3">
                        <a href="consultas.php" class="btn btn-outline-primary">
                            <i class="fas fa-list-check me-1"></i>
                            Ver Todas as Triagens
                        </a>
                    </div>
                <?php else: ?>
                    <div class="empty-clean">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mb-0">Nenhum paciente crítico em espera.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>