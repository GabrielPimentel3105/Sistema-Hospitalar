<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}
}

require_once __DIR__ . '/model/Medico.php';

$id = $_GET['id'] ?? null;

if (empty($id)) {
    header("Location: medicos.php");
    exit;
}

require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $medicoModel = new Medico($db);
    $dados = $medicoModel->readOne($id);

} catch (Exception $e) {
    $erroMedico = $e->getMessage();
    $dados = null;
}
?>

<?php if (!empty($erroMedico)): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <strong>Erro ao carregar médico:</strong>
        <?php echo htmlspecialchars($erroMedico); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$dados): ?>
    <div class="alert alert-danger mt-3">
        Médico não encontrado.
    </div>

    <a href="medicos.php" class="btn btn-outline-secondary">
        Voltar
    </a>

    <?php require_once __DIR__ . '/views/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Médico</h1>

    <a href="medicos.php" class="btn btn-sm btn-outline-secondary">
        Voltar
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="processar_medico.php" method="POST">
                    <input type="hidden" name="acao" value="editar">

                    <input 
                        type="hidden" 
                        name="id_medico" 
                        value="<?php echo htmlspecialchars($dados['id_medico'] ?? ''); ?>"
                    >

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nome Completo</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="nome" 
                                value="<?php echo htmlspecialchars($dados['nome'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CRM</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="crm" 
                                value="<?php echo htmlspecialchars($dados['crm'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Especialidade</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="especialidade" 
                                value="<?php echo htmlspecialchars($dados['especialidade'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                name="telefone" 
                                value="<?php echo htmlspecialchars($dados['telefone'] ?? ''); ?>"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>

                            <input 
                                type="email" 
                                class="form-control" 
                                name="email" 
                                value="<?php echo htmlspecialchars($dados['email'] ?? ''); ?>"
                            >
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary w-100">
                                Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>