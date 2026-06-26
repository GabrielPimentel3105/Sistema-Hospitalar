<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/model/Convenio.php';
require_once __DIR__ . '/dao/ConvenioDAO.php';
require_once __DIR__ . '/views/header.php';

$convenios = [];

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $convenioDAO = new ConvenioDAO($db);
    $stmtConvenios = $convenioDAO->read();
    $convenios = ($stmtConvenios) ? $stmtConvenios->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (Exception $e) {
    $erroConvenio = $e->getMessage();
    $convenios = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Novo Cadastro de Paciente</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="pacientes.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
        </a>
    </div>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] === 'cpf_duplicado'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>CPF já cadastrado.</strong> Verifique se este paciente já existe no sistema antes de realizar um novo cadastro.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'campos_obrigatorios'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Campos obrigatórios não preenchidos.</strong> Informe pelo menos nome, CPF e data de nascimento.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'erro'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        Não foi possível cadastrar o paciente. Verifique os dados informados.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($erroConvenio)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Aviso:</strong> Não foi possível carregar os convênios cadastrados.
        O paciente ainda pode ser cadastrado como particular.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                Informações pessoais, clínicas e convênio
            </div>

            <div class="card-body">
                <form action="processar_paciente.php" method="POST">
                    <input type="hidden" name="acao" value="cadastrar">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>

                        <div class="col-md-3">
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" required placeholder="000.000.000-00">
                        </div>

                        <div class="col-md-3">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
                        </div>

                        <div class="col-md-4">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000">
                        </div>

                        <div class="col-md-4">
                            <label for="tipo_sanguineo" class="form-label">Tipo Sanguíneo</label>
                            <select class="form-select" id="tipo_sanguineo" name="tipo_sanguineo">
                                <option value="" selected>Selecione...</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="status_paciente" class="form-label">Status do Paciente</label>
                            <select class="form-select" id="status_paciente" name="status_paciente">
                                <option value="Ativo" selected>Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="id_convenio" class="form-label">Convênio</label>
                            <select class="form-select" id="id_convenio" name="id_convenio">
                                <option value="" selected>Particular / Sem Convênio</option>

                                <?php foreach ($convenios as $convenio): ?>
                                    <?php if (($convenio['status_convenio'] ?? 'Ativo') === 'Ativo'): ?>
                                        <option value="<?php echo htmlspecialchars($convenio['id_convenio']); ?>">
                                            <?php echo htmlspecialchars($convenio['nome_convenio']); ?>
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
                                placeholder="Obrigatório apenas se tiver convênio"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="validade_carteirinha" class="form-label">Validade da Carteirinha</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="validade_carteirinha" 
                                name="validade_carteirinha"
                            >
                        </div>

                        <div class="col-12">
                            <label for="endereco" class="form-label">Endereço Completo</label>
                            <input type="text" class="form-control" id="endereco" name="endereco">
                        </div>

                        <div class="col-md-6">
                            <label for="alergias" class="form-label">Alergias Conhecidas</label>
                            <textarea 
                                class="form-control" 
                                id="alergias" 
                                name="alergias" 
                                rows="3" 
                                placeholder="Ex: dipirona, penicilina, látex..."
                            ></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="historico_clinico" class="form-label">Histórico Clínico</label>
                            <textarea 
                                class="form-control" 
                                id="historico_clinico" 
                                name="historico_clinico" 
                                rows="3" 
                                placeholder="Doenças crônicas, cirurgias, uso contínuo de medicamentos..."
                            ></textarea>
                        </div>

                        <div class="col-12 mt-4">
                            <hr>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-outline-secondary">
                                    Limpar Campos
                                </button>

                                <button type="submit" class="btn btn-primary px-4">
                                    Salvar Cadastro
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