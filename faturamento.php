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

if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}

require_once __DIR__ . '/model/Faturamento.php';
require_once __DIR__ . '/model/Paciente.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $fatModel = new Faturamento($db);
    $pacienteModel = new Paciente($db);

    $totalPendente = $fatModel->getTotalPorStatus('Pendente');
    $totalRecebido = $fatModel->getTotalPorStatus('Pago');
    $totalCancelado = $fatModel->getTotalPorStatus('Cancelado');

    $qtdPendentes = $fatModel->getContagemPorStatus('Pendente');
    $qtdPagas = $fatModel->getContagemPorStatus('Pago');
    $qtdCanceladas = $fatModel->getContagemPorStatus('Cancelado');

    $stmt = $fatModel->readPending();
    $num = ($stmt) ? $stmt->rowCount() : 0;

    $stmtPacientes = $pacienteModel->read();
    $pacientes_list = ($stmtPacientes) ? $stmtPacientes->fetchAll(PDO::FETCH_ASSOC) : [];

    $stmtConsultas = $db->query("
        SELECT 
            c.id_consulta,
            c.data_consulta,
            c.horario,
            p.nome AS paciente_nome,
            p.id_paciente
        FROM consultas c
        INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
        ORDER BY c.data_consulta DESC, c.horario DESC
    ");

    $consultas_list = ($stmtConsultas) ? $stmtConsultas->fetchAll(PDO::FETCH_ASSOC) : [];

    $stmtInternacoes = $db->query("
        SELECT 
            i.id_internacao,
            i.data_entrada,
            i.data_alta,
            i.status_internacao,
            p.nome AS paciente_nome,
            p.id_paciente,
            l.numero_leito,
            l.ala
        FROM internacoes i
        INNER JOIN pacientes p ON i.id_paciente = p.id_paciente
        INNER JOIN leitos l ON i.id_leito = l.id_leito
        ORDER BY i.data_entrada DESC
    ");

    $internacoes_list = ($stmtInternacoes) ? $stmtInternacoes->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (Throwable $e) {
    $erroFaturamento = $e->getMessage();

    $totalPendente = 0;
    $totalRecebido = 0;
    $totalCancelado = 0;

    $qtdPendentes = 0;
    $qtdPagas = 0;
    $qtdCanceladas = 0;

    $stmt = false;
    $num = 0;
    $pacientes_list = [];
    $consultas_list = [];
    $internacoes_list = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Faturamento Hospitalar</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="auditoria.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-shield-alt me-1"></i> Auditoria
        </a>

        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="window.print()">
            <i class="fas fa-file-export me-1"></i> Exportar Relatório
        </button>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaGuia">
            <i class="fas fa-plus me-1"></i> Nova Guia
        </button>
    </div>
</div>

<?php if (!empty($erroFaturamento)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar faturamento:</strong>
        <?php echo htmlspecialchars($erroFaturamento); ?>
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

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card border-start border-4 border-primary shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted small text-uppercase">
                    Total Pendente
                </h6>

                <h3 class="card-title mb-0">
                    R$ <?php echo number_format((float) $totalPendente, 2, ',', '.'); ?>
                </h3>

                <small class="text-muted">
                    <?php echo (int)$qtdPendentes; ?> guia(s) pendente(s)
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-start border-4 border-success shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted small text-uppercase">
                    Recebido
                </h6>

                <h3 class="card-title mb-0">
                    R$ <?php echo number_format((float) $totalRecebido, 2, ',', '.'); ?>
                </h3>

                <small class="text-muted">
                    <?php echo (int)$qtdPagas; ?> guia(s) paga(s)
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-start border-4 border-secondary shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted small text-uppercase">
                    Cancelado
                </h6>

                <h3 class="card-title mb-0">
                    R$ <?php echo number_format((float) $totalCancelado, 2, ',', '.'); ?>
                </h3>

                <small class="text-muted">
                    <?php echo (int)$qtdCanceladas; ?> guia(s) cancelada(s)
                </small>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-file-invoice-dollar me-1 text-primary"></i>
            Guias de Faturamento
        </span>

        <span class="text-muted small">
            Consolidação de consultas, exames, medicamentos, internações e insumos.
        </span>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Paciente</th>
                        <th>Cobrança</th>
                        <th>Origem</th>
                        <th>Itens realizados</th>
                        <th>Data</th>
                        <th>Valor Total</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($num > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idFaturamento = $row['id_faturamento'] ?? '';
                                $nomePaciente = $row['paciente_nome'] ?? 'Paciente não informado';
                                $cpfPaciente = $row['cpf'] ?? '';
                                $dataFaturamento = $row['data_faturamento'] ?? '';
                                $valorTotal = $row['valor_total'] ?? 0;
                                $statusPagamento = $row['status_pagamento'] ?? 'Pendente';
                                $observacoes = $row['observacoes'] ?? '';
                                $idConsulta = $row['id_consulta'] ?? '';
                                $idInternacao = $row['id_internacao'] ?? '';
                                $nomeConvenio = $row['nome_convenio'] ?? '';

                                $tipoCobranca = !empty($nomeConvenio) ? 'Convênio' : 'Particular';
                                $responsavelPagamento = !empty($nomeConvenio) ? $nomeConvenio : 'Paciente';

                                $badgeClass = 'bg-primary';

                                if ($statusPagamento === 'Pago') {
                                    $badgeClass = 'bg-success';
                                } elseif ($statusPagamento === 'Cancelado') {
                                    $badgeClass = 'bg-secondary';
                                }

                                $stmtItens = $fatModel->listarItens($idFaturamento);
                                $itensGuia = ($stmtItens) ? $stmtItens->fetchAll(PDO::FETCH_ASSOC) : [];
                            ?>

                            <tr>
                                <td>
                                    #<?php echo htmlspecialchars($idFaturamento); ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nomePaciente); ?></strong>

                                    <?php if (!empty($cpfPaciente)): ?>
                                        <br>
                                        <small class="text-muted">
                                            CPF: <?php echo htmlspecialchars($cpfPaciente); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($tipoCobranca === 'Convênio'): ?>
                                        <span class="badge bg-info text-dark">Convênio</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Particular</span>
                                    <?php endif; ?>

                                    <br>
                                    <small class="text-muted">
                                        Responsável: <?php echo htmlspecialchars($responsavelPagamento); ?>
                                    </small>
                                </td>

                                <td>
                                    <?php if (!empty($idConsulta)): ?>
                                        <span class="badge bg-light text-dark border">
                                            Consulta #<?php echo htmlspecialchars($idConsulta); ?>
                                        </span>
                                        <br>
                                    <?php endif; ?>

                                    <?php if (!empty($idInternacao)): ?>
                                        <span class="badge bg-light text-dark border mt-1">
                                            Internação #<?php echo htmlspecialchars($idInternacao); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (empty($idConsulta) && empty($idInternacao)): ?>
                                        <span class="text-muted small">Manual</span>
                                    <?php endif; ?>
                                </td>

                                <td style="min-width: 260px;">
                                    <?php if (!empty($itensGuia)): ?>
                                        <ul class="mb-1 ps-3 small">
                                            <?php foreach ($itensGuia as $item): ?>
                                                <li>
                                                    <strong><?php echo htmlspecialchars($item['tipo_item'] ?? 'Item'); ?>:</strong>
                                                    <?php echo htmlspecialchars($item['descricao'] ?? 'Sem descrição'); ?>

                                                    <br>
                                                    <span class="text-muted">
                                                        Qtd: <?php echo number_format((float)($item['quantidade'] ?? 1), 2, ',', '.'); ?>
                                                        |
                                                        Unitário: R$ <?php echo number_format((float)($item['valor_unitario'] ?? 0), 2, ',', '.'); ?>
                                                        |
                                                        Total: R$ <?php echo number_format((float)($item['valor_total'] ?? 0), 2, ',', '.'); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            Nenhum item detalhado cadastrado.
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($observacoes)): ?>
                                        <div class="small text-muted mt-1">
                                            Obs: <?php echo htmlspecialchars(mb_strimwidth($observacoes, 0, 100, '...')); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($dataFaturamento)): ?>
                                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($dataFaturamento))); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não informada</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        R$ <?php echo number_format((float) $valorTotal, 2, ',', '.'); ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($statusPagamento); ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <?php if ($statusPagamento === 'Pendente'): ?>
                                            <a 
                                                href="processar_faturamento.php?acao=baixar&id=<?php echo urlencode($idFaturamento); ?>" 
                                                class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Confirmar baixa desta guia?')"
                                                title="Baixar pagamento"
                                            >
                                                <i class="fas fa-check"></i>
                                            </a>

                                            <a 
                                                href="processar_faturamento.php?acao=cancelar&id=<?php echo urlencode($idFaturamento); ?>" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Cancelar esta guia?')"
                                                title="Cancelar guia"
                                            >
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a 
                                            href="processar_faturamento.php?acao=auditar&id=<?php echo urlencode($idFaturamento); ?>" 
                                            class="btn btn-sm btn-outline-primary"
                                            title="Auditar guia"
                                        >
                                            <i class="fas fa-shield-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                Nenhuma guia de faturamento encontrada na base de dados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovaGuia" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processar_faturamento.php" method="POST">
                <input type="hidden" name="acao" value="nova_guia">

                <div class="modal-header">
                    <h5 class="modal-title">Emitir Nova Guia de Faturamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info small">
                        A guia manual permite informar um valor livre e descrever o procedimento realizado.
                        A guia consolidada calcula automaticamente honorários, exames, medicamentos e insumos vinculados.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Paciente</label>

                            <select class="form-select" name="id_paciente" id="id_paciente" required>
                                <option value="">Selecione um paciente</option>

                                <?php foreach ($pacientes_list as $paciente): ?>
                                    <?php
                                        $idPaciente = $paciente['id_paciente'] ?? '';
                                        $nomePacienteLista = $paciente['nome'] ?? '';
                                        $cpfPaciente = $paciente['cpf'] ?? '';
                                    ?>

                                    <option value="<?php echo htmlspecialchars($idPaciente); ?>">
                                        <?php echo htmlspecialchars($nomePacienteLista); ?>
                                        <?php if (!empty($cpfPaciente)): ?>
                                            (CPF: <?php echo htmlspecialchars($cpfPaciente); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tipo de Guia</label>

                            <select class="form-select" name="tipo_guia" id="tipo_guia" onchange="alternarTipoGuia()" required>
                                <option value="manual">Manual</option>
                                <option value="consolidada">Consolidada automática</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Consulta vinculada</label>

                            <select class="form-select" name="id_consulta">
                                <option value="">Sem consulta vinculada</option>

                                <?php foreach ($consultas_list as $consulta): ?>
                                    <option value="<?php echo htmlspecialchars($consulta['id_consulta']); ?>">
                                        #<?php echo htmlspecialchars($consulta['id_consulta']); ?>
                                        - <?php echo htmlspecialchars($consulta['paciente_nome']); ?>
                                        - <?php echo htmlspecialchars(date('d/m/Y', strtotime($consulta['data_consulta']))); ?>
                                        às <?php echo htmlspecialchars(substr($consulta['horario'], 0, 5)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Internação vinculada</label>

                            <select class="form-select" name="id_internacao">
                                <option value="">Sem internação vinculada</option>

                                <?php foreach ($internacoes_list as $internacao): ?>
                                    <option value="<?php echo htmlspecialchars($internacao['id_internacao']); ?>">
                                        #<?php echo htmlspecialchars($internacao['id_internacao']); ?>
                                        - <?php echo htmlspecialchars($internacao['paciente_nome']); ?>
                                        - Leito <?php echo htmlspecialchars($internacao['numero_leito']); ?>
                                        / <?php echo htmlspecialchars($internacao['ala']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="campo_valor_manual">
                            <label class="form-label">Valor Total Manual (R$)</label>

                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="form-control" 
                                name="valor_total" 
                                value="0.00"
                            >
                        </div>

                        <div class="col-md-6" id="campo_item_manual">
                            <label class="form-label">O que foi realizado?</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="descricao_item" 
                                placeholder="Ex: consulta médica, exame de imagem, medicação, curativo..."
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Inicial</label>

                            <select class="form-select" name="status_pagamento">
                                <option value="Pendente" selected>Pendente</option>
                                <option value="Pago">Pago</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>

                            <textarea 
                                class="form-control" 
                                name="observacoes" 
                                rows="3"
                                placeholder="Ex: guia lançada manualmente, pendente de autorização do convênio..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Gerar Guia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function alternarTipoGuia() {
    const tipo = document.getElementById('tipo_guia').value;
    const campoValor = document.getElementById('campo_valor_manual');
    const campoItem = document.getElementById('campo_item_manual');

    if (tipo === 'consolidada') {
        campoValor.style.display = 'none';
        campoItem.style.display = 'none';
    } else {
        campoValor.style.display = 'block';
        campoItem.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', alternarTipoGuia);
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>