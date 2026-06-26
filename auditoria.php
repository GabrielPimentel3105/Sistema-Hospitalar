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

require_once __DIR__ . '/model/Auditoria.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $auditoriaModel = new Auditoria($db);
    $stmt = $auditoriaModel->read();

} catch (Throwable $e) {
    $stmt = false;
    $erroAuditoria = $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <h1 class="h2">Auditoria e Logs do Sistema</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="faturamento.php" class="btn btn-outline-primary btn-sm me-2">
            <i class="fas fa-file-invoice-dollar me-1"></i> Faturamento
        </a>

        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Imprimir Relatório
        </button>
    </div>
</div>

<?php if (!empty($erroAuditoria)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar auditoria:</strong>
        <?php echo htmlspecialchars($erroAuditoria); ?>
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

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-history me-2 text-primary"></i>
            Histórico de Auditoria
        </h5>

        <span class="text-muted small">
            Conferência entre faturamento, itens cobrados e registros do sistema.
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Data/Hora</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th>Paciente</th>
                        <th>Guia</th>
                        <th>Valor</th>
                        <th>Pagamento</th>
                        <th class="text-end pe-4">ID Log</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($stmt && $stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $status = $row['status_auditoria'] ?? 'Log';
                                $badgeClass = 'bg-secondary';

                                if ($status === 'Conforme') {
                                    $badgeClass = 'bg-success';
                                } elseif ($status === 'Divergente') {
                                    $badgeClass = 'bg-danger';
                                } elseif ($status === 'Pendente') {
                                    $badgeClass = 'bg-warning text-dark';
                                } elseif ($status === 'Log') {
                                    $badgeClass = 'bg-info text-dark';
                                }

                                $dataAuditoria = $row['data_auditoria'] ?? null;
                                $descricao = $row['descricao'] ?? '';
                                $idFaturamento = $row['id_faturamento'] ?? null;
                                $nomePaciente = $row['paciente_nome'] ?? '';
                                $idAuditoria = $row['id_auditoria'] ?? '';
                                $valorTotal = $row['valor_total'] ?? null;
                                $statusPagamento = $row['status_pagamento'] ?? '';
                            ?>

                            <tr>
                                <td class="ps-4">
                                    <span class="text-muted small">
                                        <i class="far fa-clock me-1"></i>
                                        <?php
                                            if (!empty($dataAuditoria)) {
                                                echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($dataAuditoria)));
                                            } else {
                                                echo 'Data não informada';
                                            }
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($descricao); ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge <?php echo $badgeClass; ?> px-2 py-1">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($nomePaciente)): ?>
                                        <?php echo htmlspecialchars($nomePaciente); ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Sistema</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($idFaturamento)): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fas fa-file-invoice-dollar me-1"></i>
                                            Guia #<?php echo htmlspecialchars($idFaturamento); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Geral</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($valorTotal !== null && $valorTotal !== ''): ?>
                                        R$ <?php echo number_format((float)$valorTotal, 2, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($statusPagamento)): ?>
                                        <?php
                                            $badgePagamento = 'bg-primary';

                                            if ($statusPagamento === 'Pago') {
                                                $badgePagamento = 'bg-success';
                                            } elseif ($statusPagamento === 'Cancelado') {
                                                $badgePagamento = 'bg-secondary';
                                            }
                                        ?>

                                        <span class="badge <?php echo $badgePagamento; ?>">
                                            <?php echo htmlspecialchars($statusPagamento); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end pe-4 text-muted small">
                                    #<?php echo htmlspecialchars($idAuditoria); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-search mb-3 d-block fa-3x text-light"></i>
                                <p class="text-muted">
                                    Nenhum registro de auditoria encontrado na base de dados.
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4 alert alert-info border-0 shadow-sm d-flex align-items-center">
    <i class="fas fa-info-circle fa-2x me-3"></i>

    <div>
        <h6 class="mb-1 fw-bold">Sobre a Auditoria</h6>
        <p class="mb-0 small">
            Este módulo registra ações críticas do sistema e permite conferir se o valor faturado
            corresponde aos itens lançados na guia, como honorários, exames, medicamentos e insumos.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>