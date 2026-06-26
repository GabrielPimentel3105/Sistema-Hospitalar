<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/model/Paciente.php';
require_once __DIR__ . '/model/Convenio.php';
require_once __DIR__ . '/dao/ConvenioDAO.php';

$id = $_GET['id'] ?? null;

if (empty($id)) {
    header("Location: pacientes.php");
    exit;
}

require_once __DIR__ . '/views/header.php';

$convenios = [];

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $pacienteModel = new Paciente($db);
    $dados = $pacienteModel->readOne($id);

    $convenioDAO = new ConvenioDAO($db);
    $stmtConvenios = $convenioDAO->read();
    $convenios = ($stmtConvenios) ? $stmtConvenios->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (Exception $e) {
    $erroPaciente = $e->getMessage();
    $dados = null;
}
?>

<?php if (!empty($erroPaciente)): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <strong>Erro ao carregar paciente:</strong>
        <?php echo htmlspecialchars($erroPaciente); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$dados): ?>
    <div class="alert alert-danger mt-3">
        Paciente não encontrado.
    </div>

    <a href="pacientes.php" class="btn btn-outline-secondary">
        Voltar para Lista
    </a>

    <?php require_once __DIR__ . '/views/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Cadastro de Paciente</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="pacientes.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                Atualizar Informações de:
                <strong><?php echo htmlspecialchars($dados['nome'] ?? ''); ?></strong>
            </div>

            <div class="card-body">
                <form action="processar_paciente.php" method="POST">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id_paciente" value="<?php echo htmlspecialchars($dados['id_paciente'] ?? ''); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="nome" 
                                name="nome" 
                                value="<?php echo htmlspecialchars($dados['nome'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="cpf" class="form-label">CPF</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="cpf" 
                                name="cpf" 
                                value="<?php echo htmlspecialchars($dados['cpf'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="data_nascimento" 
                                name="data_nascimento" 
                                value="<?php echo htmlspecialchars($dados['data_nascimento'] ?? ''); ?>" 
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="telefone" 
                                name="telefone" 
                                value="<?php echo htmlspecialchars($dados['telefone'] ?? ''); ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="tipo_sanguineo" class="form-label">Tipo Sanguíneo</label>

                            <?php
                                $tipos = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                                $tipoAtual = $dados['tipo_sanguineo'] ?? '';
                            ?>

                            <select class="form-select" id="tipo_sanguineo" name="tipo_sanguineo">
                                <option value="">Selecione...</option>

                                <?php foreach ($tipos as $tipo): ?>
                                    <option 
                                        value="<?php echo htmlspecialchars($tipo); ?>"
                                        <?php echo ($tipoAtual === $tipo) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($tipo); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="status_paciente" class="form-label">Status do Paciente</label>

                            <?php $statusAtual = $dados['status_paciente'] ?? 'Ativo'; ?>

                            <select class="form-select" id="status_paciente" name="status_paciente">
                                <option value="Ativo" <?php echo ($statusAtual === 'Ativo') ? 'selected' : ''; ?>>Ativo</option>
                                <option value="Inativo" <?php echo ($statusAtual === 'Inativo') ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="id_convenio" class="form-label">Convênio</label>

                            <?php $convenioAtual = $dados['id_convenio'] ?? ''; ?>

                            <select class="form-select" id="id_convenio" name="id_convenio">
                                <option value="" <?php echo empty($convenioAtual) ? 'selected' : ''; ?>>
                                    Particular / Sem Convênio
                                </option>

                                <?php foreach ($convenios as $convenio): ?>
                                    <?php
                                        $idConvenio = $convenio['id_convenio'] ?? '';
                                        $nomeConvenio = $convenio['nome_convenio'] ?? '';
                                        $statusConvenio = $convenio['status_convenio'] ?? 'Ativo';
                                    ?>

                                    <?php if (!empty($idConvenio) && !empty($nomeConvenio) && $statusConvenio === 'Ativo'): ?>
                                        <option 
                                            value="<?php echo htmlspecialchars($idConvenio); ?>"
                                            <?php echo ((string)$convenioAtual === (string)$idConvenio) ? 'selected' : ''; ?>
                                        >
                                            <?php echo htmlspecialchars($nomeConvenio); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="numero_carteirinha" class="form-label">Número da Carteirinha</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="numero_carteirinha" 
                                name="numero_carteirinha"
                                value="<?php echo htmlspecialchars($dados['numero_carteirinha'] ?? ''); ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="validade_carteirinha" class="form-label">Validade da Carteirinha</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="validade_carteirinha" 
                                name="validade_carteirinha"
                                value="<?php echo htmlspecialchars($dados['validade_carteirinha'] ?? ''); ?>"
                            >
                        </div>

                        <div class="col-12">
                            <label for="endereco" class="form-label">Endereço Completo</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="endereco" 
                                name="endereco" 
                                value="<?php echo htmlspecialchars($dados['endereco'] ?? ''); ?>"
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="alergias" class="form-label">Alergias Conhecidas</label>
                            <textarea 
                                class="form-control" 
                                id="alergias" 
                                name="alergias" 
                                rows="3"
                            ><?php echo htmlspecialchars($dados['alergias'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="historico_clinico" class="form-label">Histórico Clínico</label>
                            <textarea 
                                class="form-control" 
                                id="historico_clinico" 
                                name="historico_clinico" 
                                rows="3"
                            ><?php echo htmlspecialchars($dados['historico_clinico'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-12 mt-4">
                            <hr>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="pacientes.php" class="btn btn-outline-secondary">
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