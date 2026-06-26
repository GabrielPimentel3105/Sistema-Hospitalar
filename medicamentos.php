<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}
}

require_once __DIR__ . '/model/Medicamento.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $medModel = new Medicamento($db);
    $stmt = $medModel->read();
    $num = ($stmt) ? $stmt->rowCount() : 0;

} catch (Exception $e) {
    $erroMedicamento = $e->getMessage();
    $stmt = false;
    $num = 0;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Farmácia e Medicamentos</h1>

    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoMed">
        <i class="fas fa-pills me-1"></i> Novo Medicamento
    </button>
</div>

<?php if (!empty($erroMedicamento)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar medicamentos:</strong>
        <?php echo htmlspecialchars($erroMedicamento); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome do Medicamento</th>
                        <th>Contraindicações</th>
                        <th>Interações</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($num > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idMedicamento = $row['id_medicamento'] ?? '';
                                $nomeMedicamento = $row['nome_medicamento'] ?? '';
                                $contraindicacoes = $row['contraindicacoes'] ?? '';
                                $interacoes = $row['interacoes_medicamentosas'] ?? '';
                            ?>

                            <tr>
                                <td>
                                    #<?php echo htmlspecialchars($idMedicamento); ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nomeMedicamento); ?></strong>
                                </td>

                                <td>
                                    <?php if (!empty($contraindicacoes)): ?>
                                        <small><?php echo htmlspecialchars($contraindicacoes); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($interacoes)): ?>
                                        <small><?php echo htmlspecialchars($interacoes); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <a 
                                        href="medicamento_editar.php?id=<?php echo urlencode($idMedicamento); ?>" 
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Editar medicamento"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Nenhum medicamento cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Novo Medicamento -->
<div class="modal fade" id="modalNovoMed" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_clinico.php" method="POST">
                <input type="hidden" name="acao" value="novo_medicamento">

                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Medicamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>

                        <input 
                            type="text" 
                            class="form-control" 
                            name="nome" 
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraindicações</label>

                        <textarea 
                            class="form-control" 
                            name="contra" 
                            rows="3"
                        ></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Interações</label>

                        <textarea 
                            class="form-control" 
                            name="inter" 
                            rows="3"
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>