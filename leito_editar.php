<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/dao/LeitoDAO.php';

try {
    if (method_exists("Database", "getInstance")) {
        $database = Database::getInstance();
        $db = $database->getConnection();
    } else {
        $database = new Database();
        $db = $database->getConnection();
    }

    $id_leito = $_GET["id"] ?? "";

    if ($id_leito === "") {
        $_SESSION["erro"] = "Leito não informado.";
        header("Location: leitos.php");
        exit;
    }

    $leitoDAO = new LeitoDAO($db);
    $leito = $leitoDAO->buscarPorId($id_leito);

    if (!$leito) {
        $_SESSION["erro"] = "Leito não encontrado.";
        header("Location: leitos.php");
        exit;
    }

} catch (Throwable $e) {
    $_SESSION["erro"] = "Erro ao carregar leito: " . $e->getMessage();
    header("Location: leitos.php");
    exit;
}

require_once __DIR__ . '/views/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <h1 class="h2">Editar Status do Leito</h1>

    <a href="leitos.php" class="btn btn-secondary">
        Voltar
    </a>
</div>

<?php if (isset($_SESSION["erro"])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION["erro"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["erro"]); ?>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Status Operacional do Leito</h5>
    </div>

    <div class="card-body">
        <form action="controller/leitoController.php" method="POST">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" name="id_leito" value="<?= htmlspecialchars($leito["id_leito"]); ?>">

            <!-- Enviados escondidos para manter compatibilidade com o controller atual -->
            <input type="hidden" name="numero_leito" value="<?= htmlspecialchars($leito["numero_leito"]); ?>">
            <input type="hidden" name="ala" value="<?= htmlspecialchars($leito["ala"]); ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ID do Leito</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="<?= htmlspecialchars($leito["id_leito"]); ?>"
                        readonly
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Número do Leito</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="<?= htmlspecialchars($leito["numero_leito"]); ?>"
                        readonly
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ala / Setor</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="<?= htmlspecialchars($leito["ala"]); ?>"
                        readonly
                    >
                </div>
            </div>

            <div class="alert alert-info mt-3 small">
                <strong>Regra de negócio:</strong> o número do leito e a ala/setor são dados fixos de identificação.
                Eles ficam bloqueados para preservar o histórico de internações, insumos e auditorias.
            </div>

            <?php if (($leito["status_leito"] ?? '') === "Ocupado"): ?>
                <div class="mb-3">
                    <label class="form-label">Status do Leito</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="Ocupado"
                        readonly
                    >
                    <input type="hidden" name="status_leito" value="Ocupado">
                </div>

                <div class="alert alert-warning">
                    <strong>Leito ocupado:</strong> este leito está vinculado a uma internação em andamento.
                    Para liberar o leito, utilize o fluxo de alta da internação. Após a alta, o leito deve ir para higienização.
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <label for="status_leito" class="form-label">Status do Leito</label>
                    <select name="status_leito" id="status_leito" class="form-select" required>
                        <option value="Disponível" <?= $leito["status_leito"] === "Disponível" ? "selected" : ""; ?>>
                            Disponível
                        </option>

                        <option value="Higienização" <?= $leito["status_leito"] === "Higienização" ? "selected" : ""; ?>>
                            Higienização
                        </option>

                        <option value="Manutenção" <?= $leito["status_leito"] === "Manutenção" ? "selected" : ""; ?>>
                            Manutenção
                        </option>

                        <option value="Inativo" <?= $leito["status_leito"] === "Inativo" ? "selected" : ""; ?>>
                            Inativo
                        </option>
                    </select>
                </div>

                <div class="alert alert-warning">
                    <strong>Atenção:</strong> alterar para <strong>Inativo</strong> impede o uso do leito em novas internações,
                    mas mantém o registro no histórico do sistema.
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">
                Atualizar Status
            </button>

            <a href="leitos.php" class="btn btn-outline-secondary">
                Cancelar
            </a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>