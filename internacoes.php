<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/auth.php')) {
    require_once __DIR__ . '/auth.php';
}

require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/model/Internacao.php';
require_once __DIR__ . '/dao/LeitoDAO.php';

function tabelaExisteInternacao($db, $tabela) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function colunaExisteInternacao($db, $tabela, $coluna) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
        $stmt->execute([$coluna]);
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

    $internacaoModel = new Internacao($db);
    $leitoDAO = new LeitoDAO($db);

    $stmtInternacoes = $internacaoModel->read();
    $stmtAtivas = $internacaoModel->readAtivas();

    $stmtLeitosDisponiveis = $leitoDAO->listarDisponiveis();
    $leitosModal = $leitoDAO->listarDisponiveis();

    $temDestinoPaciente = colunaExisteInternacao($db, 'consultas', 'destino_paciente');
    $temStatusFluxo = colunaExisteInternacao($db, 'consultas', 'status_fluxo');

    if ($temDestinoPaciente && $temStatusFluxo) {
        $sqlPacientes = "
            SELECT 
                p.id_paciente,
                p.nome,
                p.cpf,
                c.id_consulta,
                c.data_consulta,
                c.horario,
                c.status_consulta,
                c.status_fluxo,
                c.destino_paciente
            FROM consultas c
            INNER JOIN pacientes p ON p.id_paciente = c.id_paciente
            WHERE c.destino_paciente = 'INTERNACAO'
              AND c.status_fluxo = 'Aguardando internação'
              AND p.id_paciente NOT IN (
                    SELECT i.id_paciente
                    FROM internacoes i
                    WHERE i.status_internacao = 'Ativa'
              )
            ORDER BY c.data_consulta DESC, c.horario DESC
        ";

        $pacientes = $db->prepare($sqlPacientes);
        $pacientes->execute();
    } else {
        $pacientes = $db->query("
            SELECT id_paciente, nome, cpf
            FROM pacientes
            WHERE status_paciente = 'Ativo'
            ORDER BY nome ASC
        ");
    }

    $totalAtivas = $internacaoModel->countAtivas();
    $totalDisponiveis = $leitoDAO->countPorStatus('Disponível');
    $totalOcupados = $leitoDAO->countPorStatus('Ocupado');
    $totalHigienizacao = $leitoDAO->countPorStatus('Higienização');

} catch (Throwable $e) {
    $erroInternacao = $e->getMessage();
    $stmtInternacoes = false;
    $stmtAtivas = false;
    $stmtLeitosDisponiveis = false;
    $pacientes = false;
    $leitosModal = false;
    $totalAtivas = 0;
    $totalDisponiveis = 0;
    $totalOcupados = 0;
    $totalHigienizacao = 0;
}

require_once __DIR__ . '/views/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Internações</h1>
        <p class="text-muted mb-0">
            Controle de pacientes encaminhados para internação, leitos disponíveis e histórico.
        </p>
    </div>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInternacao">
        <i class="fas fa-procedures me-1"></i> Nova Internação
    </button>
</div>

<?php if (!empty($erroInternacao)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro:</strong> <?php echo htmlspecialchars($erroInternacao); ?>
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

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Internações ativas</small>
                <div class="h3 mb-0 text-primary"><?php echo (int)$totalAtivas; ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Leitos disponíveis</small>
                <div class="h3 mb-0 text-success"><?php echo (int)$totalDisponiveis; ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Leitos ocupados</small>
                <div class="h3 mb-0 text-danger"><?php echo (int)$totalOcupados; ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <small class="text-muted">Em higienização</small>
                <div class="h3 mb-0 text-warning"><?php echo (int)$totalHigienizacao; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-user-clock me-1 text-danger"></i>
            Pacientes aguardando internação
        </span>

        <small class="text-muted">
            Vindos da triagem
        </small>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>CPF</th>
                        <th>Data da Consulta</th>
                        <th>Horário</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($pacientes && $pacientes->rowCount() > 0): ?>
                        <?php
                            $pacientesLista = $pacientes->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php foreach ($pacientesLista as $pacienteFila): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($pacienteFila['nome']); ?></strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($pacienteFila['cpf']); ?>
                                </td>

                                <td>
                                    <?php if (!empty($pacienteFila['data_consulta'])): ?>
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($pacienteFila['data_consulta']))); ?>
                                    <?php else: ?>
                                        <span class="text-muted">--/--/----</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($pacienteFila['horario'])): ?>
                                        <?php echo htmlspecialchars(substr($pacienteFila['horario'], 0, 5)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">--:--</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge bg-danger">
                                        <?php echo htmlspecialchars($pacienteFila['status_fluxo'] ?? 'Aguardando internação'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php $pacientesLista = []; ?>

                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Nenhum paciente aguardando internação no momento.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info small mb-0">
            Para o paciente aparecer aqui, ele precisa passar pela triagem e ser encaminhado pelo botão 
            <strong>Destino &gt; Encaminhar para Internação</strong>.
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <i class="fas fa-bed-pulse me-1 text-danger"></i>
        Internações Ativas
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Leito</th>
                        <th>Ala</th>
                        <th>Entrada</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($stmtAtivas && $stmtAtivas->rowCount() > 0): ?>
                        <?php while ($internacao = $stmtAtivas->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($internacao['paciente_nome']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($internacao['cpf']); ?></small>
                                </td>

                                <td><?php echo htmlspecialchars($internacao['numero_leito']); ?></td>

                                <td><?php echo htmlspecialchars($internacao['ala']); ?></td>

                                <td>
                                    <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($internacao['data_entrada']))); ?>
                                </td>

                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($internacao['status_internacao']); ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <button 
                                            type="button"
                                            class="btn btn-sm btn-outline-warning"
                                            onclick="abrirTransferencia('<?php echo htmlspecialchars($internacao['id_internacao']); ?>')"
                                            title="Transferir leito"
                                        >
                                            <i class="fas fa-right-left"></i>
                                        </button>

                                        <a 
                                            href="processar_internacao.php?acao=alta&id=<?php echo urlencode($internacao['id_internacao']); ?>"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Registrar alta deste paciente? O leito será enviado para higienização.')"
                                            title="Dar alta"
                                        >
                                            <i class="fas fa-check"></i>
                                        </a>

                                        <a 
                                            href="processar_internacao.php?acao=cancelar&id=<?php echo urlencode($internacao['id_internacao']); ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Cancelar esta internação? O leito será liberado.')"
                                            title="Cancelar internação"
                                        >
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhuma internação ativa.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <i class="fas fa-clock-rotate-left me-1 text-secondary"></i>
        Histórico de Internações
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Leito</th>
                        <th>Entrada</th>
                        <th>Alta</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($stmtInternacoes && $stmtInternacoes->rowCount() > 0): ?>
                        <?php while ($internacao = $stmtInternacoes->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($internacao['paciente_nome']); ?></td>
                                <td><?php echo htmlspecialchars($internacao['numero_leito']); ?> - <?php echo htmlspecialchars($internacao['ala']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($internacao['data_entrada']))); ?></td>
                                <td>
                                    <?php if (!empty($internacao['data_alta'])): ?>
                                        <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($internacao['data_alta']))); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Em andamento</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($internacao['status_internacao']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Nenhum histórico de internação.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInternacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_internacao.php" method="POST">
                <input type="hidden" name="acao" value="internar">

                <div class="modal-header">
                    <h5 class="modal-title">Nova Internação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Paciente encaminhado pela triagem</label>
                        <select name="id_paciente" class="form-select" required>
                            <option value="">Selecione...</option>

                            <?php if (!empty($pacientesLista)): ?>
                                <?php foreach ($pacientesLista as $paciente): ?>
                                    <option value="<?php echo htmlspecialchars($paciente['id_paciente']); ?>">
                                        <?php echo htmlspecialchars($paciente['nome']); ?> 
                                        - <?php echo htmlspecialchars($paciente['cpf']); ?>
                                        - Aguardando internação
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <?php if (empty($pacientesLista)): ?>
                            <small class="text-danger">
                                Nenhum paciente foi encaminhado para internação pela triagem.
                            </small>
                        <?php else: ?>
                            <small class="text-muted">
                                Aqui aparecem apenas pacientes com status: Aguardando internação.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Leito Disponível</label>
                        <select name="id_leito" class="form-select" required>
                            <option value="">Selecione...</option>

                            <?php if ($stmtLeitosDisponiveis): ?>
                                <?php while ($leito = $stmtLeitosDisponiveis->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo htmlspecialchars($leito['id_leito']); ?>">
                                        <?php echo htmlspecialchars($leito['numero_leito']); ?> - <?php echo htmlspecialchars($leito['ala']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="alert alert-info small mb-0">
                        Ao registrar a internação, o leito será automaticamente marcado como ocupado.
                        Depois precisaremos atualizar o fluxo da consulta para 
                        <strong>Paciente internado</strong>.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button 
                        type="submit" 
                        class="btn btn-primary"
                        <?php if (empty($pacientesLista)): ?>
                            disabled
                        <?php endif; ?>
                    >
                        Registrar Internação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTransferencia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_internacao.php" method="POST">
                <input type="hidden" name="acao" value="transferir">
                <input type="hidden" name="id_internacao" id="transferencia_id_internacao">

                <div class="modal-header">
                    <h5 class="modal-title">Transferir Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Novo Leito Disponível</label>
                        <select name="novo_leito" class="form-select" required>
                            <option value="">Selecione...</option>

                            <?php if ($leitosModal): ?>
                                <?php while ($leito = $leitosModal->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo htmlspecialchars($leito['id_leito']); ?>">
                                        <?php echo htmlspecialchars($leito['numero_leito']); ?> - <?php echo htmlspecialchars($leito['ala']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="alert alert-warning small mb-0">
                        O leito anterior será enviado automaticamente para higienização.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Confirmar Transferência
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirTransferencia(idInternacao) {
    document.getElementById('transferencia_id_internacao').value = idInternacao;

    const modal = new bootstrap.Modal(document.getElementById('modalTransferencia'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>