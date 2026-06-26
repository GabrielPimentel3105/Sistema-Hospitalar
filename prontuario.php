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

require_once __DIR__ . '/model/Prontuario.php';
require_once __DIR__ . '/views/header.php';

function avaliarConvenioProntuario($paciente) {
    if (empty($paciente['nome_convenio'])) {
        return [
            "status" => "particular",
            "classe" => "light",
            "titulo" => "Paciente particular",
            "mensagem" => "Paciente sem convênio vinculado."
        ];
    }

    if (empty($paciente['validade_carteirinha'])) {
        return [
            "status" => "sem_valididade",
            "classe" => "warning",
            "titulo" => "Validade do convênio não informada",
            "mensagem" => "O paciente possui convênio, mas a validade da carteirinha não foi informada. Conferir autorização antes do faturamento."
        ];
    }

    $hoje = strtotime(date("Y-m-d"));
    $validade = strtotime($paciente['validade_carteirinha']);

    if ($validade < $hoje) {
        return [
            "status" => "vencido",
            "classe" => "danger",
            "titulo" => "Convênio vencido",
            "mensagem" => "Convênio vencido em " . date("d/m/Y", $validade) . ". O atendimento pode prosseguir, porém a cobrança deverá ser tratada como particular ou dependerá de autorização."
        ];
    }

    return [
        "status" => "valido",
        "classe" => "success",
        "titulo" => "Convênio válido",
        "mensagem" => "Convênio válido até " . date("d/m/Y", $validade) . "."
    ];
}

$idPaciente = $_GET['id'] ?? $_GET['id_paciente'] ?? null;
$idConsulta = $_GET['id_consulta'] ?? null;

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $prontuarioModel = new Prontuario($db);

    if (empty($idPaciente)) {
        throw new Exception("Paciente não informado.");
    }

    $paciente = $prontuarioModel->buscarPaciente($idPaciente);

    if (!$paciente) {
        throw new Exception("Paciente não encontrado.");
    }

    $infoConvenioProntuario = avaliarConvenioProntuario($paciente);

    if (empty($idConsulta)) {
        $daoPath = __DIR__ . '/dao/ProntuarioDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        $daoTemp = new ProntuarioDAO($db);
        $idConsulta = $daoTemp->buscarUltimaConsultaDoPaciente($idPaciente);
    }

    if (empty($idConsulta)) {
        $consulta = null;
        $prontuario = null;
        $stmtExames = null;
        $stmtPrescricoes = null;
    } else {
        $consulta = $prontuarioModel->buscarConsulta($idConsulta);
        $prontuario = $prontuarioModel->buscarOuCriarPorConsulta($idConsulta);

        if ($prontuario) {
            $stmtExames = $prontuarioModel->listarExames($prontuario['id_prontuario']);
            $stmtPrescricoes = $prontuarioModel->listarPrescricoes($prontuario['id_prontuario']);
        } else {
            $stmtExames = null;
            $stmtPrescricoes = null;
        }
    }

    $stmtMedicamentos = $prontuarioModel->listarMedicamentos();

} catch (Throwable $e) {
    $erroProntuario = $e->getMessage();
    $paciente = null;
    $consulta = null;
    $prontuario = null;
    $stmtExames = null;
    $stmtPrescricoes = null;
    $stmtMedicamentos = null;

    $infoConvenioProntuario = [
        "status" => "erro",
        "classe" => "secondary",
        "titulo" => "Convênio não verificado",
        "mensagem" => "Não foi possível verificar os dados do convênio."
    ];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Prontuário Eletrônico</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="consultas.php" class="btn btn-sm btn-outline-primary me-2">
            <i class="fas fa-calendar-check me-1"></i> Consultas
        </a>

        <a href="pacientes.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Pacientes
        </a>
    </div>
</div>

<?php if (!empty($erroProntuario)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro:</strong> <?php echo htmlspecialchars($erroProntuario); ?>
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

<?php if (!$paciente): ?>
    <div class="alert alert-warning">
        Não foi possível carregar os dados do paciente.
    </div>

    <?php require_once __DIR__ . '/views/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<?php if (($infoConvenioProntuario['status'] ?? '') === 'vencido' || ($infoConvenioProntuario['status'] ?? '') === 'sem_valididade'): ?>
    <div class="alert alert-<?php echo htmlspecialchars($infoConvenioProntuario['classe']); ?> alert-dismissible fade show" role="alert">
        <strong><?php echo htmlspecialchars($infoConvenioProntuario['titulo']); ?>:</strong>
        <?php echo htmlspecialchars($infoConvenioProntuario['mensagem']); ?>
        <br>
        <small>
            O atendimento médico não será bloqueado, mas a equipe deve conferir autorização, regularização ou cobrança particular no faturamento.
        </small>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <i class="fas fa-user-injured me-1 text-primary"></i>
                Dados do Paciente
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Nome</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($paciente['nome'] ?? ''); ?></div>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">CPF</small>
                        <div><?php echo htmlspecialchars($paciente['cpf'] ?? ''); ?></div>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">Tipo Sanguíneo</small>
                        <div><?php echo htmlspecialchars($paciente['tipo_sanguineo'] ?? 'Não informado'); ?></div>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted">Convênio</small>
                        <div>
                            <?php if (!empty($paciente['nome_convenio'])): ?>
                                <?php echo htmlspecialchars($paciente['nome_convenio']); ?>

                                <?php if (($infoConvenioProntuario['status'] ?? '') === 'vencido'): ?>
                                    <br>
                                    <span class="badge bg-danger">Convênio vencido</span>
                                <?php elseif (($infoConvenioProntuario['status'] ?? '') === 'sem_valididade'): ?>
                                    <br>
                                    <span class="badge bg-warning text-dark">Validade não informada</span>
                                <?php elseif (($infoConvenioProntuario['status'] ?? '') === 'valido'): ?>
                                    <br>
                                    <span class="badge bg-success">Convênio válido</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">Particular</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">Carteirinha</small>
                        <div><?php echo htmlspecialchars($paciente['numero_carteirinha'] ?? 'Não informado'); ?></div>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">Validade</small>
                        <div>
                            <?php if (!empty($paciente['validade_carteirinha'])): ?>
                                <?php echo htmlspecialchars(date('d/m/Y', strtotime($paciente['validade_carteirinha']))); ?>

                                <?php if (($infoConvenioProntuario['status'] ?? '') === 'vencido'): ?>
                                    <br>
                                    <span class="badge bg-danger">Vencida</span>
                                <?php elseif (($infoConvenioProntuario['status'] ?? '') === 'valido'): ?>
                                    <br>
                                    <span class="badge bg-success">Válida</span>
                                <?php endif; ?>

                            <?php else: ?>
                                Não informado

                                <?php if (!empty($paciente['nome_convenio'])): ?>
                                    <br>
                                    <span class="badge bg-warning text-dark">Conferir</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted">Alergias</small>
                        <div class="border rounded p-2 bg-light min-vh-25">
                            <?php echo nl2br(htmlspecialchars($paciente['alergias'] ?? 'Nenhuma alergia registrada.')); ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted">Histórico Clínico</small>
                        <div class="border rounded p-2 bg-light min-vh-25">
                            <?php echo nl2br(htmlspecialchars($paciente['historico_clinico'] ?? 'Nenhum histórico registrado.')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <i class="fas fa-stethoscope me-1 text-success"></i>
                Atendimento Atual
            </div>

            <div class="card-body">
                <?php if ($consulta): ?>
                    <small class="text-muted">Consulta</small>
                    <div class="fw-bold">#<?php echo htmlspecialchars($consulta['id_consulta'] ?? ''); ?></div>

                    <hr>

                    <small class="text-muted">Médico</small>
                    <div>
                        <?php echo htmlspecialchars($consulta['medico_nome'] ?? ''); ?>
                        <?php if (!empty($consulta['especialidade'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($consulta['especialidade']); ?></small>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <small class="text-muted">Data e horário</small>
                    <div>
                        <?php if (!empty($consulta['data_consulta'])): ?>
                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($consulta['data_consulta']))); ?>
                        <?php endif; ?>

                        às <?php echo htmlspecialchars(substr($consulta['horario'] ?? '', 0, 5)); ?>
                    </div>

                    <hr>

                    <small class="text-muted">Sala</small>
                    <div>
                        <?php if (!empty($consulta['numero_sala'])): ?>
                            Sala <?php echo htmlspecialchars($consulta['numero_sala']); ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($consulta['tipo_sala'] ?? ''); ?></small>
                        <?php else: ?>
                            Não definida
                        <?php endif; ?>
                    </div>

                    <?php if (($infoConvenioProntuario['status'] ?? '') === 'vencido' || ($infoConvenioProntuario['status'] ?? '') === 'sem_valididade'): ?>
                        <hr>
                        <div class="alert alert-warning small mb-0">
                            <strong>Atenção:</strong>
                            conferir autorização/cobrança no faturamento antes de fechar a guia.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        Este paciente ainda não possui consulta vinculada ao prontuário.
                        Crie uma consulta antes de registrar evolução, exames ou prescrições.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($consulta && $prontuario): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <i class="fas fa-notes-medical me-1 text-primary"></i>
            Evolução Clínica
        </div>

        <div class="card-body">
            <?php if (($infoConvenioProntuario['status'] ?? '') === 'vencido' || ($infoConvenioProntuario['status'] ?? '') === 'sem_valididade'): ?>
                <div class="alert alert-warning small">
                    <strong>Atenção ao convênio:</strong>
                    <?php echo htmlspecialchars($infoConvenioProntuario['mensagem']); ?>
                    O registro clínico pode ser feito normalmente.
                </div>
            <?php endif; ?>

            <form action="processar_prontuario.php" method="POST">
                <input type="hidden" name="acao" value="salvar_evolucao">
                <input type="hidden" name="id_paciente" value="<?php echo htmlspecialchars($idPaciente); ?>">
                <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">
                <input type="hidden" name="id_prontuario" value="<?php echo htmlspecialchars($prontuario['id_prontuario']); ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Evolução Clínica</label>
                        <textarea 
                            name="evolucao_clinica" 
                            class="form-control" 
                            rows="6"
                            placeholder="Registre a evolução do atendimento, conduta médica, sinais observados e orientações..."
                        ><?php echo htmlspecialchars($prontuario['evolucao_clinica'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Observações</label>
                        <textarea 
                            name="observacoes" 
                            class="form-control" 
                            rows="6"
                            placeholder="Observações complementares..."
                        ><?php echo htmlspecialchars($prontuario['observacoes'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvar Evolução
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-vial me-1 text-info"></i>
                        Exames Solicitados
                    </span>

                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExame">
                        Novo Exame
                    </button>
                </div>

                <div class="card-body">
                    <?php if ($stmtExames && $stmtExames->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Exame</th>
                                        <th>Data</th>
                                        <th>Valor</th>
                                        <th>Resultado</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($exame = $stmtExames->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exame['nome_exame'] ?? ''); ?></td>

                                            <td>
                                                <?php if (!empty($exame['data_exame'])): ?>
                                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($exame['data_exame']))); ?>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                R$ <?php echo number_format((float) ($exame['valor_exame'] ?? 0), 2, ',', '.'); ?>
                                            </td>

                                            <td>
                                                <?php
                                                    $resultadoExame = $exame['resultado'] ?? '';
                                                    if (!empty($resultadoExame)) {
                                                        if (function_exists('mb_strimwidth')) {
                                                            echo htmlspecialchars(mb_strimwidth($resultadoExame, 0, 60, '...'));
                                                        } else {
                                                            echo htmlspecialchars(substr($resultadoExame, 0, 60) . (strlen($resultadoExame) > 60 ? '...' : ''));
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">Sem resultado</span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhum exame registrado para este atendimento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-prescription-bottle-alt me-1 text-danger"></i>
                        Prescrições Digitais
                    </span>

                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPrescricao">
                        Nova Prescrição
                    </button>
                </div>

                <div class="card-body">
                    <?php if ($stmtPrescricoes && $stmtPrescricoes->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Medicamento</th>
                                        <th>Dosagem</th>
                                        <th>Frequência</th>
                                        <th>Duração</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($prescricao = $stmtPrescricoes->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($prescricao['nome_medicamento'] ?? ''); ?></strong>
                                            </td>

                                            <td><?php echo htmlspecialchars($prescricao['dosagem'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($prescricao['frequencia'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($prescricao['duracao_tratamento'] ?? ''); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma prescrição registrada para este atendimento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalExame" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="processar_prontuario.php" method="POST">
                    <input type="hidden" name="acao" value="solicitar_exame">
                    <input type="hidden" name="id_paciente" value="<?php echo htmlspecialchars($idPaciente); ?>">
                    <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">
                    <input type="hidden" name="id_prontuario" value="<?php echo htmlspecialchars($prontuario['id_prontuario']); ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">Solicitar / Registrar Exame</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <?php if (($infoConvenioProntuario['status'] ?? '') === 'vencido' || ($infoConvenioProntuario['status'] ?? '') === 'sem_valididade'): ?>
                            <div class="alert alert-warning small">
                                <strong>Atenção:</strong>
                                convênio pendente de conferência. O exame pode ser solicitado, mas o faturamento deverá verificar autorização/cobrança.
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Nome do Exame</label>
                            <input 
                                type="text" 
                                name="nome_exame" 
                                class="form-control" 
                                placeholder="Ex: Hemograma completo"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data</label>
                            <input 
                                type="date" 
                                name="data_exame" 
                                class="form-control" 
                                value="<?php echo date('Y-m-d'); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Valor do Exame</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                name="valor_exame" 
                                class="form-control" 
                                value="0.00"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resultado / Observação</label>
                            <textarea 
                                name="resultado" 
                                class="form-control" 
                                rows="3"
                                placeholder="Pode deixar como solicitado e preencher o resultado posteriormente."
                            >Solicitado - aguardando resultado.</textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Salvar Exame
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPrescricao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="processar_prontuario.php" method="POST" id="formPrescricao" onsubmit="return confirmarRiscoPrescricao();">
                    <input type="hidden" name="acao" value="prescrever">
                    <input type="hidden" name="id_paciente" value="<?php echo htmlspecialchars($idPaciente); ?>">
                    <input type="hidden" name="id_consulta" value="<?php echo htmlspecialchars($idConsulta); ?>">
                    <input type="hidden" name="id_prontuario" value="<?php echo htmlspecialchars($prontuario['id_prontuario']); ?>">

                    <input 
                        type="hidden" 
                        id="textoAlergiasPaciente" 
                        value="<?php echo htmlspecialchars($paciente['alergias'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <input 
                        type="hidden" 
                        id="textoHistoricoPaciente" 
                        value="<?php echo htmlspecialchars($paciente['historico_clinico'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <div class="modal-header">
                        <h5 class="modal-title">Nova Prescrição Digital</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <?php if (($infoConvenioProntuario['status'] ?? '') === 'vencido' || ($infoConvenioProntuario['status'] ?? '') === 'sem_valididade'): ?>
                            <div class="alert alert-warning small">
                                <strong>Atenção:</strong>
                                convênio pendente de conferência. A prescrição pode ser registrada, mas a cobrança/autorização deverá ser validada pelo faturamento.
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-warning small">
                            O sistema verificará automaticamente se há possível risco entre o medicamento escolhido e as alergias/histórico clínico do paciente.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Medicamento</label>
                            <select name="id_medicamento" id="id_medicamento_prescricao" class="form-select" required>
                                <option value="">Selecione...</option>

                                <?php if ($stmtMedicamentos): ?>
                                    <?php while ($med = $stmtMedicamentos->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option 
                                            value="<?php echo htmlspecialchars($med['id_medicamento']); ?>"
                                            data-nome="<?php echo htmlspecialchars($med['nome_medicamento'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <?php echo htmlspecialchars($med['nome_medicamento']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dosagem</label>
                            <input 
                                type="text" 
                                name="dosagem" 
                                class="form-control" 
                                placeholder="Ex: 500mg"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Frequência</label>
                            <input 
                                type="text" 
                                name="frequencia" 
                                class="form-control" 
                                placeholder="Ex: 1 comprimido a cada 8 horas"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Duração do Tratamento</label>
                            <input 
                                type="text" 
                                name="duracao_tratamento" 
                                class="form-control" 
                                placeholder="Ex: 5 dias"
                                required
                            >
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Emitir Prescrição
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function normalizarTexto(texto) {
    return (texto || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function confirmarRiscoPrescricao() {
    const selectMedicamento = document.getElementById('id_medicamento_prescricao');

    if (!selectMedicamento) {
        return true;
    }

    const optionSelecionada = selectMedicamento.options[selectMedicamento.selectedIndex];

    if (!optionSelecionada || !optionSelecionada.value) {
        return true;
    }

    const nomeMedicamento = optionSelecionada.getAttribute('data-nome') || optionSelecionada.textContent || '';

    const alergiasPaciente = document.getElementById('textoAlergiasPaciente') 
        ? document.getElementById('textoAlergiasPaciente').value 
        : '';

    const historicoPaciente = document.getElementById('textoHistoricoPaciente') 
        ? document.getElementById('textoHistoricoPaciente').value 
        : '';

    const medicamentoNormalizado = normalizarTexto(nomeMedicamento);
    const alergiasNormalizadas = normalizarTexto(alergiasPaciente);
    const historicoNormalizado = normalizarTexto(historicoPaciente);

    const apareceEmAlergias = medicamentoNormalizado !== '' && alergiasNormalizadas.includes(medicamentoNormalizado);
    const apareceNoHistorico = medicamentoNormalizado !== '' && historicoNormalizado.includes(medicamentoNormalizado);

    if (apareceEmAlergias || apareceNoHistorico) {
        return confirm(
            'Atenção: possível risco ao prescrever ' + nomeMedicamento +
            ', pois o medicamento aparece no histórico/alergias do paciente.\n\n' +
            'Deseja continuar com o registro da prescrição?'
        );
    }

    return true;
}
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>