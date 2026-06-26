<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $stmt = $convenioDAO->read();
    $num = ($stmt) ? $stmt->rowCount() : 0;

} catch (Exception $e) {
    $erroConvenio = $e->getMessage();
    $stmt = false;
    $num = 0;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestão de Convênios</h1>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoConvenio">
        <i class="fas fa-plus me-1"></i> Novo Convênio
    </button>
</div>

<?php if (!empty($erroConvenio)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar convênios:</strong>
        <?php echo htmlspecialchars($erroConvenio); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION["mensagem"])): ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION["tipo_mensagem"] ?? 'info'); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION["mensagem"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <?php
        unset($_SESSION["mensagem"]);
        unset($_SESSION["tipo_mensagem"]);
    ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-handshake me-1 text-primary"></i>
            Convênios Cadastrados
        </span>

        <div class="input-group input-group-sm w-25">
            <input 
                type="text" 
                class="form-control" 
                id="buscaConvenio" 
                placeholder="Buscar convênio..."
                onkeyup="filtrarConvenios()"
            >

            <button class="btn btn-outline-secondary" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaConvenios">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Nome do Convênio</th>
                        <th>Telefone</th>
                        <th>Procedimentos Autorizados</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($num > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idConvenio = $row['id_convenio'] ?? '';
                                $nomeConvenio = $row['nome_convenio'] ?? '';
                                $telefone = $row['telefone'] ?? '';
                                $procedimentos = $row['procedimentos_autorizados'] ?? '';
                                $statusConvenio = $row['status_convenio'] ?? 'Ativo';
                            ?>

                            <tr class="<?php echo ($statusConvenio === 'Inativo') ? 'table-secondary opacity-75' : ''; ?>">
                                <td class="ps-4">
                                    #<?php echo htmlspecialchars($idConvenio); ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nomeConvenio); ?></strong>
                                </td>

                                <td>
                                    <?php if (!empty($telefone)): ?>
                                        <?php echo htmlspecialchars($telefone); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($procedimentos)): ?>
                                        <?php echo htmlspecialchars(mb_strimwidth($procedimentos, 0, 90, '...')); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($statusConvenio === 'Ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a 
                                            href="convenio_editar.php?id=<?php echo urlencode($idConvenio); ?>" 
                                            class="btn btn-sm btn-outline-primary" 
                                            title="Editar"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <?php if ($statusConvenio === 'Ativo'): ?>
                                            <a 
                                                href="javascript:void(0)" 
                                                onclick="confirmarInativacao(<?php echo (int) $idConvenio; ?>)" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Inativar"
                                            >
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a 
                                                href="convenio_editar.php?id=<?php echo urlencode($idConvenio); ?>" 
                                                class="btn btn-sm btn-outline-success" 
                                                title="Reativar pelo formulário de edição"
                                            >
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Nenhum convênio cadastrado na base de dados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoConvenio" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="controller/convenioController.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Novo Convênio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Nome do Convênio</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="nome_convenio" 
                                placeholder="Ex: Convênio Saúde Total" 
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Telefone de Contato</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="telefone" 
                                placeholder="(00) 0000-0000"
                            >
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status_convenio">
                                <option value="Ativo" selected>Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Procedimentos Autorizados</label>
                            <textarea 
                                class="form-control" 
                                name="procedimentos_autorizados" 
                                rows="4"
                                placeholder="Ex: consultas clínicas, triagem, exames laboratoriais, prescrição digital, internação em enfermaria..."
                            ></textarea>

                            <div class="form-text">
                                Informe quais procedimentos esse convênio cobre ou autoriza.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Salvar Convênio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarInativacao(id) {
    if (confirm('Tem certeza que deseja inativar este convênio? Ele não será apagado do histórico.')) {
        window.location.href = 'controller/convenioController.php?acao=excluir&id=' + encodeURIComponent(id);
    }
}

function filtrarConvenios() {
    const input = document.getElementById('buscaConvenio');
    const filtro = input.value.toLowerCase();
    const tabela = document.getElementById('tabelaConvenios');
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