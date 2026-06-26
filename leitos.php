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

    $leitoDAO = new LeitoDAO($db);
    $stmtLeitos = $leitoDAO->listar();

} catch (Throwable $e) {
    $erroLeitos = $e->getMessage();
    $stmtLeitos = false;
}

require_once __DIR__ . '/views/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <h1 class="h2">Gestão de Leitos</h1>
</div>

<?php if (!empty($erroLeitos)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro:</strong> <?= htmlspecialchars($erroLeitos); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION["sucesso"])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION["sucesso"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["sucesso"]); ?>
<?php endif; ?>

<?php if (isset($_SESSION["erro"])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION["erro"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION["erro"]); ?>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Cadastrar Leito</h5>
            </div>

            <div class="card-body">
                <form action="controller/leitoController.php" method="POST">
                    <input type="hidden" name="acao" value="cadastrar">

                    <div class="mb-3">
                        <label for="numero_leito" class="form-label">Número do Leito</label>
                        <input 
                            type="text" 
                            name="numero_leito" 
                            id="numero_leito" 
                            class="form-control" 
                            placeholder="Ex: 101, 102, UTI-01"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="ala" class="form-label">Ala / Setor</label>
                        <input 
                            type="text" 
                            name="ala" 
                            id="ala" 
                            class="form-control" 
                            placeholder="Ex: Clínica Médica, Pediatria, UTI"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="status_leito" class="form-label">Status inicial do Leito</label>
                        <select name="status_leito" id="status_leito" class="form-select" required>
                            <option value="Disponível" selected>Disponível</option>
                            <option value="Higienização">Higienização</option>
                            <option value="Manutenção">Manutenção</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                    </div>

                    <div class="alert alert-info small">
                        <strong>Regra de negócio:</strong> após o cadastro, o número do leito e a ala/setor ficam bloqueados para preservar o histórico hospitalar.
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Salvar Leito
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Leitos Cadastrados</h5>
                <small class="text-muted">
                    Todos os leitos aparecem na listagem administrativa, inclusive os inativos.
                </small>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Número</th>
                                <th>Ala / Setor</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($stmtLeitos && $stmtLeitos->rowCount() > 0): ?>
                                <?php while ($leito = $stmtLeitos->fetch(PDO::FETCH_ASSOC)): ?>
                                    <?php
                                        $status = $leito["status_leito"] ?? '';
                                        $classe = "secondary";

                                        if ($status === "Disponível") {
                                            $classe = "success";
                                        } elseif ($status === "Ocupado") {
                                            $classe = "danger";
                                        } elseif ($status === "Higienização") {
                                            $classe = "info text-dark";
                                        } elseif ($status === "Manutenção") {
                                            $classe = "warning text-dark";
                                        } elseif ($status === "Inativo") {
                                            $classe = "secondary";
                                        }
                                    ?>

                                    <tr>
                                        <td><?= htmlspecialchars($leito["id_leito"]); ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($leito["numero_leito"]); ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($leito["ala"]); ?></td>
                                        <td>
                                            <span class="badge bg-<?= $classe; ?>">
                                                <?= htmlspecialchars($status); ?>
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            <a 
                                                href="leito_editar.php?id=<?= urlencode($leito["id_leito"]); ?>" 
                                                class="btn btn-sm btn-outline-primary"
                                                title="Alterar status operacional do leito"
                                            >
                                                Editar Status
                                            </a>

                                            <?php if ($status === "Ocupado"): ?>
                                                <button 
                                                    class="btn btn-sm btn-outline-secondary" 
                                                    disabled
                                                    title="Leito ocupado não pode ser inativado manualmente"
                                                >
                                                    Inativar
                                                </button>
                                            <?php elseif ($status !== "Inativo"): ?>
                                                <a 
                                                    href="controller/leitoController.php?acao=inativar&id=<?= urlencode($leito["id_leito"]); ?>" 
                                                    class="btn btn-sm btn-outline-secondary"
                                                    onclick="return confirm('Deseja realmente inativar este leito? Ele não será excluído do histórico.')"
                                                >
                                                    Inativar
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    Inativo
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        Nenhum leito cadastrado.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning mt-3 mb-0 small">
                    <strong>Regra de negócio:</strong> o sistema não realiza exclusão física de leitos, pois eles podem estar vinculados a internações anteriores. 
                    A inativação preserva o histórico hospitalar e evita inconsistência nos registros. 
                    Leitos ocupados devem ser liberados pelo fluxo de alta da internação, não por edição manual.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>