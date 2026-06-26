<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}

require_once __DIR__ . '/model/Paciente.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $pacienteModel = new Paciente($db);
    $stmt = $pacienteModel->read();
    $num = ($stmt) ? $stmt->rowCount() : 0;
    $error_db = false;

} catch (Exception $e) {
    $erroPaciente = $e->getMessage();
    $stmt = false;
    $num = 0;
    $error_db = true;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestão de Pacientes</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="paciente_novo.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Novo Paciente
        </a>
    </div>
</div>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'sucesso'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Paciente cadastrado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    <?php elseif ($_GET['status'] == 'editado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Dados do paciente atualizados com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    <?php elseif ($_GET['status'] == 'excluido'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-user-slash me-2"></i> Paciente inativado com sucesso.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    <?php elseif ($_GET['status'] == 'erro_conexao'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção:</strong> Não foi possível gravar os dados porque a base de dados está inacessível.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    <?php elseif ($_GET['status'] == 'erro'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            Não foi possível realizar a operação.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($erroPaciente)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar pacientes:</strong>
        <?php echo htmlspecialchars($erroPaciente); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-users me-1 text-primary"></i>
            Lista de Pacientes Registrados
        </span>

        <div class="input-group input-group-sm w-25">
            <input 
                type="text" 
                class="form-control" 
                id="buscaPaciente" 
                placeholder="Buscar por nome, CPF ou convênio..."
                onkeyup="filtrarPacientes()"
            >

            <button class="btn btn-outline-secondary" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if ($error_db): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erro de Conexão:</strong> Não foi possível conectar à base de dados.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="tabelaPacientes">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Convênio</th>
                        <th>Carteirinha</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($num > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idPaciente = $row['id_paciente'] ?? '';
                                $nome = $row['nome'] ?? '';
                                $cpf = $row['cpf'] ?? '';
                                $telefone = $row['telefone'] ?? '';
                                $nomeConvenio = $row['nome_convenio'] ?? '';
                                $numeroCarteirinha = $row['numero_carteirinha'] ?? '';
                                $validadeCarteirinha = $row['validade_carteirinha'] ?? '';
                                $statusPaciente = $row['status_paciente'] ?? 'Ativo';

                                if (function_exists('formatarCPF')) {
                                    $cpfFormatado = formatarCPF($cpf);
                                } else {
                                    $cpfFormatado = $cpf;
                                }

                                if (function_exists('formatarTelefone')) {
                                    $telefoneFormatado = formatarTelefone($telefone);
                                } else {
                                    $telefoneFormatado = $telefone;
                                }

                                if (!empty($validadeCarteirinha)) {
                                    $validadeFormatada = date('d/m/Y', strtotime($validadeCarteirinha));
                                } else {
                                    $validadeFormatada = '';
                                }
                            ?>

                            <tr class="<?php echo ($statusPaciente === 'Inativo') ? 'table-secondary opacity-75' : ''; ?>">
                                <td>#<?php echo htmlspecialchars($idPaciente); ?></td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nome); ?></strong>
                                </td>

                                <td><?php echo htmlspecialchars($cpfFormatado); ?></td>

                                <td>
                                    <?php if (!empty($telefoneFormatado)): ?>
                                        <?php echo htmlspecialchars($telefoneFormatado); ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($nomeConvenio)): ?>
                                        <?php echo htmlspecialchars($nomeConvenio); ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Particular</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($numeroCarteirinha)): ?>
                                        <div><?php echo htmlspecialchars($numeroCarteirinha); ?></div>

                                        <?php if (!empty($validadeFormatada)): ?>
                                            <small class="text-muted">
                                                Validade: <?php echo htmlspecialchars($validadeFormatada); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($statusPaciente === 'Ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a 
                                            href="prontuario.php?id=<?php echo urlencode($idPaciente); ?>" 
                                            class="btn btn-sm btn-outline-primary" 
                                            title="Ver Prontuário"
                                        >
                                            <i class="fas fa-file-medical"></i>
                                        </a>

                                        <a 
                                            href="paciente_editar.php?id=<?php echo urlencode($idPaciente); ?>" 
                                            class="btn btn-sm btn-outline-secondary" 
                                            title="Editar"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <?php if ($statusPaciente === 'Ativo'): ?>
                                            <a 
                                                href="processar_paciente.php?acao=excluir&id=<?php echo urlencode($idPaciente); ?>" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Inativar" 
                                                onclick="return confirm('Tem certeza que deseja inativar este paciente?')"
                                            >
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a 
                                                href="paciente_editar.php?id=<?php echo urlencode($idPaciente); ?>" 
                                                class="btn btn-sm btn-outline-success" 
                                                title="Reativar pelo formulário de edição"
                                            >
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Nenhum paciente cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filtrarPacientes() {
    const input = document.getElementById('buscaPaciente');
    const filtro = input.value.toLowerCase();
    const tabela = document.getElementById('tabelaPacientes');
    const linhas = tabela.getElementsByTagName('tr');

    for (let i = 1; i < linhas.length; i++) {
        const textoLinha = linhas[i].innerText.toLowerCase();

        if (textoLinha.includes(filtro)) {
            linhas[i].style.display = '';
        } else {
            linhas[i].style.display = 'none';
        }
    }
}
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>