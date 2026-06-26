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

require_once __DIR__ . '/model/UsoInsumo.php';
require_once __DIR__ . '/model/Insumo.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $usoModel = new UsoInsumo($db);
    $insumoModel = new Insumo($db);

    $stmtUsos = $usoModel->read();
    $stmtInsumos = $insumoModel->read();

    $stmtLeitos = $db->query("
        SELECT id_leito, numero_leito, ala, status_leito
        FROM leitos
        ORDER BY ala ASC, numero_leito ASC
    ");

    $stmtInternacoes = $db->query("
        SELECT
            i.id_internacao,
            p.nome AS paciente_nome,
            l.numero_leito,
            l.ala
        FROM internacoes i
        INNER JOIN pacientes p ON i.id_paciente = p.id_paciente
        INNER JOIN leitos l ON i.id_leito = l.id_leito
        WHERE i.status_internacao = 'Ativa'
        AND i.data_alta IS NULL
        ORDER BY p.nome ASC
    ");

} catch (Throwable $e) {
    $erroUso = $e->getMessage();
    $stmtUsos = false;
    $stmtInsumos = false;
    $stmtLeitos = false;
    $stmtInternacoes = false;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Uso de Insumos</h1>

    <div>
        <a href="estoque.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-boxes-stacked me-1"></i> Estoque
        </a>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsoInsumo">
            <i class="fas fa-plus me-1"></i> Registrar Uso
        </button>
    </div>
</div>

<?php if (!empty($erroUso)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro:</strong> <?php echo htmlspecialchars($erroUso); ?>
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
    <div class="card-header bg-white">
        <i class="fas fa-clipboard-list me-1 text-primary"></i>
        Histórico de uso de insumos por leito/internação
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Insumo</th>
                        <th>Quantidade</th>
                        <th>Leito</th>
                        <th>Paciente/Internação</th>
                        <th>Valor Unitário</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($stmtUsos && $stmtUsos->rowCount() > 0): ?>
                        <?php while ($uso = $stmtUsos->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($uso['data_uso']))); ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($uso['nome_insumo']); ?></strong>
                                </td>

                                <td>
                                    <?php echo (int)$uso['quantidade_utilizada']; ?> un.
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($uso['numero_leito']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($uso['ala']); ?></small>
                                </td>

                                <td>
                                    <?php if (!empty($uso['paciente_nome'])): ?>
                                        <?php echo htmlspecialchars($uso['paciente_nome']); ?>
                                        <br>
                                        <small class="text-muted">
                                            Internação #<?php echo htmlspecialchars($uso['id_internacao']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Sem internação vinculada</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    R$ <?php echo number_format((float)$uso['valor_unitario'], 2, ',', '.'); ?>
                                </td>

                                <td>
                                    <strong>
                                        R$ <?php echo number_format((float)$uso['valor_total'], 2, ',', '.'); ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nenhum uso de insumo registrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsoInsumo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_insumos.php" method="POST">
                <input type="hidden" name="acao" value="registrar_uso">

                <div class="modal-header">
                    <h5 class="modal-title">Registrar Uso de Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Insumo</label>
                        <select name="id_insumo" class="form-select" required>
                            <option value="">Selecione...</option>

                            <?php if ($stmtInsumos): ?>
                                <?php while ($insumo = $stmtInsumos->fetch(PDO::FETCH_ASSOC)): ?>
                                    <?php if ((int)$insumo['quantidade_estoque'] > 0): ?>
                                        <option value="<?php echo htmlspecialchars($insumo['id_insumo']); ?>">
                                            <?php echo htmlspecialchars($insumo['nome_insumo']); ?>
                                            — estoque: <?php echo (int)$insumo['quantidade_estoque']; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade Utilizada</label>
                        <input type="number" name="quantidade_utilizada" class="form-control" value="1" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Leito</label>
                        <select name="id_leito" class="form-select" required>
                            <option value="">Selecione...</option>

                            <?php if ($stmtLeitos): ?>
                                <?php while ($leito = $stmtLeitos->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo htmlspecialchars($leito['id_leito']); ?>">
                                        <?php echo htmlspecialchars($leito['numero_leito']); ?>
                                        - <?php echo htmlspecialchars($leito['ala']); ?>
                                        (<?php echo htmlspecialchars($leito['status_leito']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Internação vinculada</label>
                        <select name="id_internacao" class="form-select">
                            <option value="">Sem internação vinculada</option>

                            <?php if ($stmtInternacoes): ?>
                                <?php while ($internacao = $stmtInternacoes->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo htmlspecialchars($internacao['id_internacao']); ?>">
                                        #<?php echo htmlspecialchars($internacao['id_internacao']); ?>
                                        - <?php echo htmlspecialchars($internacao['paciente_nome']); ?>
                                        - Leito <?php echo htmlspecialchars($internacao['numero_leito']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>

                        <div class="form-text">
                            Vincular à internação ajuda no faturamento e auditoria.
                        </div>
                    </div>

                    <div class="alert alert-info small mb-0">
                        Ao salvar, o sistema dará baixa automática no estoque do insumo.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Registrar Uso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>