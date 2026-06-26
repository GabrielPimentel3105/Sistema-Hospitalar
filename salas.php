<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/model/Sala.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $salaModel = new Sala($db);
    $stmt = $salaModel->read();
    $num = ($stmt) ? $stmt->rowCount() : 0;

} catch (Exception $e) {
    $erroSala = $e->getMessage();
    $stmt = false;
    $num = 0;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestão de Salas</h1>

    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaSala">
        <i class="fas fa-plus me-1"></i> Nova Sala
    </button>
</div>

<?php if (!empty($erroSala)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar salas:</strong>
        <?php echo htmlspecialchars($erroSala); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'sucesso'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Sala cadastrada com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'editado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Sala atualizada com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'erro'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Não foi possível realizar a operação.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($num > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idSala = $row['id_sala'] ?? '';
                                $numeroSala = $row['numero_sala'] ?? '';
                                $tipoSala = $row['tipo_sala'] ?? '';
                                $statusSala = $row['status_sala'] ?? '';

                                $badgeClass = 'bg-info text-dark';

                                if ($statusSala == 'Disponível') {
                                    $badgeClass = 'bg-success';
                                } elseif ($statusSala == 'Ocupada' || $statusSala == 'Ocupado') {
                                    $badgeClass = 'bg-danger';
                                } elseif ($statusSala == 'Manutenção') {
                                    $badgeClass = 'bg-warning text-dark';
                                } elseif ($statusSala == 'Indisponível') {
                                    $badgeClass = 'bg-secondary';
                                }
                            ?>

                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($numeroSala); ?></strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($tipoSala); ?>
                                </td>

                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($statusSala); ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a 
                                        href="sala_editar.php?id=<?php echo urlencode($idSala); ?>" 
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Editar sala"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Nenhuma sala cadastrada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nova Sala -->
<div class="modal fade" id="modalNovaSala" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_administrativo.php" method="POST">
                <input type="hidden" name="acao" value="nova_sala">

                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Sala</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Número da Sala</label>

                        <input 
                            type="text" 
                            class="form-control" 
                            name="numero" 
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>

                        <select class="form-select" name="tipo" required>
                            <option value="Consultório">Consultório</option>
                            <option value="Cirurgia">Cirurgia</option>
                            <option value="Exame">Exame</option>
                            <option value="Triagem">Triagem</option>
                            <option value="Internação">Internação</option>
                            <option value="Sala de Procedimentos">Sala de Procedimentos</option>
                        </select>
                    </div>

                    <div class="mb-3 form-check">
                        <input 
                            type="checkbox" 
                            class="form-check-input" 
                            name="vincular_leito" 
                            id="vincular_leito" 
                            value="1"
                        >

                        <label class="form-check-label" for="vincular_leito">
                            Vincular como leito disponível
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ala</label>

                        <input 
                            type="text" 
                            class="form-control" 
                            name="ala" 
                            placeholder="Ex: Ala Norte, UTI"
                        >

                        <small class="text-muted">
                            Preencha este campo caso deseje vincular a sala como leito.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button 
                        type="button" 
                        class="btn btn-secondary" 
                        data-bs-dismiss="modal"
                    >
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