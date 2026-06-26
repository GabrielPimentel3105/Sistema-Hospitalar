<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/model/Medicamento.php';
require_once __DIR__ . '/views/header.php';

$id = $_GET['id'] ?? null;

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if (empty($id)) {
        throw new Exception("Medicamento não informado.");
    }

    $medicamentoModel = new Medicamento($db);
    $medicamento = $medicamentoModel->readOne($id);

    if (!$medicamento) {
        throw new Exception("Medicamento não encontrado.");
    }

} catch (Throwable $e) {
    $erroMedicamento = $e->getMessage();
    $medicamento = null;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Medicamento</h1>

    <a href="medicamentos.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<?php if (!empty($erroMedicamento)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro:</strong> <?php echo htmlspecialchars($erroMedicamento); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$medicamento): ?>
    <div class="alert alert-warning">
        Não foi possível carregar o medicamento.
    </div>

    <?php require_once __DIR__ . '/views/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                Atualizar medicamento:
                <strong><?php echo htmlspecialchars($medicamento['nome_medicamento'] ?? ''); ?></strong>
            </div>

            <div class="card-body">
                <form action="processar_medicamento.php" method="POST">
                    <input type="hidden" name="acao" value="editar">

                    <input 
                        type="hidden" 
                        name="id_medicamento" 
                        value="<?php echo htmlspecialchars($medicamento['id_medicamento'] ?? ''); ?>"
                    >

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome do Medicamento</label>
                            <input 
                                type="text" 
                                name="nome_medicamento" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($medicamento['nome_medicamento'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Valor Unitário</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                name="valor_unitario" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($medicamento['valor_unitario'] ?? '0.00'); ?>"
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Quantidade em Estoque</label>
                            <input 
                                type="number" 
                                min="0" 
                                name="quantidade_estoque" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($medicamento['quantidade_estoque'] ?? '0'); ?>"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contraindicações</label>
                            <textarea 
                                name="contraindicacoes" 
                                class="form-control" 
                                rows="5"
                            ><?php echo htmlspecialchars($medicamento['contraindicacoes'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Interações Medicamentosas</label>
                            <textarea 
                                name="interacoes_medicamentosas" 
                                class="form-control" 
                                rows="5"
                            ><?php echo htmlspecialchars($medicamento['interacoes_medicamentosas'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info small mb-0">
                                Essas informações serão usadas pelo prontuário eletrônico para emitir alertas automáticos em prescrições.
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <hr>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="medicamentos.php" class="btn btn-light px-4">
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