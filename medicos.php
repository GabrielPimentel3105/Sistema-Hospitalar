<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}
}

if (file_exists(__DIR__ . '/config/helpers.php')) {
    if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}
}

require_once __DIR__ . '/model/Medico.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $medicoModel = new Medico($db);
    $stmt = $medicoModel->read();
    $num = ($stmt) ? $stmt->rowCount() : 0;

} catch (Exception $e) {
    $erroMedico = $e->getMessage();
    $stmt = false;
    $num = 0;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Corpo Clínico (Médicos)</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="medico_novo.php" class="btn btn-sm btn-primary">
            <i class="fas fa-user-md me-1"></i> Cadastrar Novo Médico
        </a>
    </div>
</div>

<?php if (!empty($erroMedico)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar médicos:</strong>
        <?php echo htmlspecialchars($erroMedico); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'sucesso'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Médico cadastrado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'editado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Dados do médico atualizados!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'excluido'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i> Médico removido do sistema.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'erro'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i> Não foi possível realizar a operação.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <i class="fas fa-list me-1 text-primary"></i> Lista de Médicos Ativos
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CRM</th>
                        <th>Especialidade</th>
                        <th>Telefone</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($num > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idMedico = $row['id_medico'] ?? '';
                                $nome = $row['nome'] ?? '';
                                $crm = $row['crm'] ?? '';
                                $especialidade = $row['especialidade'] ?? '';
                                $telefone = $row['telefone'] ?? '';
                            ?>

                            <tr>
                                <td>
                                    #<?php echo htmlspecialchars($idMedico); ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nome); ?></strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($crm); ?>
                                </td>

                                <td>
                                    <?php if (!empty($especialidade)): ?>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($especialidade); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($telefone)): ?>
                                        <?php echo htmlspecialchars($telefone); ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a 
                                            href="medico_editar.php?id=<?php echo urlencode($idMedico); ?>" 
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Editar médico"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a 
                                            href="processar_medico.php?acao=excluir&id=<?php echo urlencode($idMedico); ?>" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Excluir este médico?')"
                                            title="Excluir médico"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Nenhum médico cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>