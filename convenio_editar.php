<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/model/Convenio.php';
require_once __DIR__ . '/dao/ConvenioDAO.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $convenioDAO = new ConvenioDAO($db);

    $id = $_GET['id'] ?? null;
    $convenio = null;

    if (!empty($id)) {
        $convenio = $convenioDAO->readOne($id);
    }

} catch (Exception $e) {
    $erroConvenio = $e->getMessage();
    $convenio = null;
}
?>

<?php if (!empty($erroConvenio)): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <strong>Erro ao carregar convênio:</strong>
        <?php echo htmlspecialchars($erroConvenio); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$convenio): ?>
    <div class="alert alert-danger mt-3">
        Convênio não encontrado.
    </div>

    <a href="convenios.php" class="btn btn-outline-secondary">
        Voltar
    </a>

    <?php require_once __DIR__ . '/views/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Convênio</h1>

    <a href="convenios.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                Atualizar dados do convênio:
                <strong><?php echo htmlspecialchars($convenio['nome_convenio'] ?? ''); ?></strong>
            </div>

            <div class="card-body">
                <form action="controller/convenioController.php" method="POST">
                    <input type="hidden" name="acao" value="editar">

                    <input 
                        type="hidden" 
                        name="id_convenio" 
                        value="<?php echo htmlspecialchars($convenio['id_convenio'] ?? ''); ?>"
                    >

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Nome do Convênio</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="nome_convenio" 
                                value="<?php echo htmlspecialchars($convenio['nome_convenio'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Telefone de Contato</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="telefone" 
                                value="<?php echo htmlspecialchars($convenio['telefone'] ?? ''); ?>"
                            >
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>

                            <?php $statusAtual = $convenio['status_convenio'] ?? 'Ativo'; ?>

                            <select class="form-select" name="status_convenio">
                                <option value="Ativo" <?php echo ($statusAtual === 'Ativo') ? 'selected' : ''; ?>>
                                    Ativo
                                </option>

                                <option value="Inativo" <?php echo ($statusAtual === 'Inativo') ? 'selected' : ''; ?>>
                                    Inativo
                                </option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Procedimentos Autorizados</label>

                            <textarea 
                                class="form-control" 
                                name="procedimentos_autorizados" 
                                rows="5"
                                placeholder="Ex: consultas, exames laboratoriais, internações, medicamentos autorizados..."
                            ><?php echo htmlspecialchars($convenio['procedimentos_autorizados'] ?? ''); ?></textarea>

                            <div class="form-text">
                                Esses procedimentos representam o que o plano permite ou cobre no sistema.
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <hr>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="convenios.php" class="btn btn-light px-4">
                                    Cancelar
                                </a>

                                <button type="submit" class="btn btn-primary px-4">
                                    Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>