<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erro ao conectar ao banco de dados: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once __DIR__ . '/views/footer.php';
    exit;
}

$idSala = $_GET['id'] ?? null;

if (!$idSala) {
    header("Location: salas.php?status=erro");
    exit;
}

$erro = '';

try {
    $query = "SELECT * FROM salas WHERE id_sala = :id_sala LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
    $stmt->execute();

    $sala = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sala) {
        header("Location: salas.php?status=erro");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $numeroSala = trim($_POST['numero_sala'] ?? '');
        $tipoSala = trim($_POST['tipo_sala'] ?? '');
        $statusSala = trim($_POST['status_sala'] ?? '');

        if (empty($numeroSala) || empty($tipoSala) || empty($statusSala)) {
            $erro = "Preencha todos os campos obrigatórios.";
        } else {
            $queryUpdate = "UPDATE salas 
                            SET numero_sala = :numero_sala,
                                tipo_sala = :tipo_sala,
                                status_sala = :status_sala
                            WHERE id_sala = :id_sala";

            $stmtUpdate = $db->prepare($queryUpdate);
            $stmtUpdate->bindParam(':numero_sala', $numeroSala);
            $stmtUpdate->bindParam(':tipo_sala', $tipoSala);
            $stmtUpdate->bindParam(':status_sala', $statusSala);
            $stmtUpdate->bindParam(':id_sala', $idSala, PDO::PARAM_INT);

            if ($stmtUpdate->execute()) {
                header("Location: salas.php?status=editado");
                exit;
            } else {
                $erro = "Não foi possível atualizar a sala.";
            }
        }
    }

} catch (PDOException $e) {
    $erro = "Erro no banco de dados: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Sala</h1>

    <a href="salas.php" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($erro); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="numero_sala" class="form-label">Número da Sala</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="numero_sala"
                    name="numero_sala" 
                    value="<?php echo htmlspecialchars($sala['numero_sala'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="tipo_sala" class="form-label">Tipo</label>

                <select class="form-select" id="tipo_sala" name="tipo_sala" required>
                    <option value="">Selecione</option>

                    <option value="Consultório" <?php echo (($sala['tipo_sala'] ?? '') == 'Consultório') ? 'selected' : ''; ?>>
                        Consultório
                    </option>

                    <option value="Cirurgia" <?php echo (($sala['tipo_sala'] ?? '') == 'Cirurgia') ? 'selected' : ''; ?>>
                        Cirurgia
                    </option>

                    <option value="Exame" <?php echo (($sala['tipo_sala'] ?? '') == 'Exame') ? 'selected' : ''; ?>>
                        Exame
                    </option>

                    <option value="Triagem" <?php echo (($sala['tipo_sala'] ?? '') == 'Triagem') ? 'selected' : ''; ?>>
                        Triagem
                    </option>

                    <option value="Internação" <?php echo (($sala['tipo_sala'] ?? '') == 'Internação') ? 'selected' : ''; ?>>
                        Internação
                    </option>

                    <option value="Sala de Procedimentos" <?php echo (($sala['tipo_sala'] ?? '') == 'Sala de Procedimentos') ? 'selected' : ''; ?>>
                        Sala de Procedimentos
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label for="status_sala" class="form-label">Status</label>

                <select class="form-select" id="status_sala" name="status_sala" required>
                    <option value="">Selecione</option>

                    <option value="Disponível" <?php echo (($sala['status_sala'] ?? '') == 'Disponível') ? 'selected' : ''; ?>>
                        Disponível
                    </option>

                    <option value="Ocupada" <?php echo (($sala['status_sala'] ?? '') == 'Ocupada') ? 'selected' : ''; ?>>
                        Ocupada
                    </option>

                    <option value="Manutenção" <?php echo (($sala['status_sala'] ?? '') == 'Manutenção') ? 'selected' : ''; ?>>
                        Manutenção
                    </option>

                    <option value="Indisponível" <?php echo (($sala['status_sala'] ?? '') == 'Indisponível') ? 'selected' : ''; ?>>
                        Indisponível
                    </option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Salvar Alterações
                </button>

                <a href="salas.php" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>