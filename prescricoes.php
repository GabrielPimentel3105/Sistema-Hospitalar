<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}

require_once __DIR__ . '/model/Prescricao.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $prescricaoModel = new Prescricao($db);
    $stmtPrescricoes = $prescricaoModel->read();

    $queryProntuarios = "SELECT 
                            pront.id_prontuario,
                            pront.data_registro,
                            pac.nome AS nome_paciente,
                            med.nome AS nome_medico
                        FROM prontuario pront
                        LEFT JOIN consultas c ON pront.id_consulta = c.id_consulta
                        LEFT JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                        LEFT JOIN medicos med ON c.id_medico = med.id_medico
                        ORDER BY pront.data_registro DESC";

    $stmtProntuarios = $db->prepare($queryProntuarios);
    $stmtProntuarios->execute();
    $prontuariosList = $stmtProntuarios->fetchAll(PDO::FETCH_ASSOC);

    $queryMedicamentos = "SELECT 
                            id_medicamento,
                            nome_medicamento,
                            interacoes_medicamentosas
                        FROM medicamentos
                        ORDER BY nome_medicamento ASC";

    $stmtMedicamentos = $db->prepare($queryMedicamentos);
    $stmtMedicamentos->execute();
    $medicamentos_list = $stmtMedicamentos->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $erroPrescricao = $e->getMessage();

    $stmtPrescricoes = false;
    $prontuariosList = [];
    $medicamentos_list = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Prescrições</h1>
        <p class="text-muted mb-0">
            Registro e acompanhamento das prescrições digitais vinculadas ao prontuário do paciente.
        </p>
    </div>

    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaPrescricao">
            <i class="fas fa-prescription-bottle-alt me-1"></i> Nova Prescrição Múltipla
        </button>
    </div>
</div>

<?php if (!empty($erroPrescricao)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar prescrições:</strong>
        <?php echo htmlspecialchars($erroPrescricao); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['erro'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['erro']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>Regra de prontuário:</strong>
    prescrições não são excluídas fisicamente do sistema. Quando necessário, elas são canceladas para preservar o histórico clínico e a rastreabilidade do atendimento.
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-file-prescription me-2 text-primary"></i>
            Prescrições cadastradas
        </h5>

        <span class="badge bg-secondary">
            Histórico clínico
        </span>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Medicamento</th>
                        <th>Dosagem</th>
                        <th>Frequência</th>
                        <th>Duração</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($stmtPrescricoes && $stmtPrescricoes->rowCount() > 0): ?>
                        <?php while ($row = $stmtPrescricoes->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idPrescricao = $row['id_prescricao'] ?? '';
                                $nomePaciente = $row['nome_paciente'] ?? 'Não informado';
                                $nomeMedico = $row['nome_medico'] ?? 'Não informado';
                                $nomeMedicamento = $row['nome_medicamento'] ?? 'Não informado';
                                $dosagem = $row['dosagem'] ?? '';
                                $frequencia = $row['frequencia'] ?? '';
                                $duracao = $row['duracao_tratamento'] ?? '';
                                $statusPrescricao = $row['status_prescricao'] ?? 'Ativa';

                                $classeStatus = 'success';

                                if ($statusPrescricao === 'Cancelada') {
                                    $classeStatus = 'secondary';
                                }
                            ?>

                            <tr class="<?php echo ($statusPrescricao === 'Cancelada') ? 'table-light text-muted' : ''; ?>">
                                <td>
                                    #<?php echo htmlspecialchars($idPrescricao); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($nomePaciente); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($nomeMedico); ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nomeMedicamento); ?></strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($dosagem); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($frequencia); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($duracao); ?>
                                </td>

                                <td>
                                    <span class="badge bg-<?php echo $classeStatus; ?>">
                                        <?php echo htmlspecialchars($statusPrescricao); ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <?php if ($statusPrescricao !== 'Cancelada'): ?>
                                        <a 
                                            href="processar_prescricoes.php?acao=cancelar&id_prescricao=<?php echo urlencode($idPrescricao); ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Deseja cancelar esta prescrição? Ela será mantida no histórico clínico.')"
                                            title="Cancelar prescrição"
                                        >
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <button 
                                            type="button"
                                            class="btn btn-sm btn-secondary"
                                            disabled
                                            title="Prescrição já cancelada"
                                        >
                                            Cancelada
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Nenhuma prescrição cadastrada até o momento.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nova Prescrição -->
<div class="modal fade" id="modalNovaPrescricao" tabindex="-1" aria-labelledby="modalNovaPrescricaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="processar_prescricoes.php" method="POST">
                <input type="hidden" name="acao" value="criar_multipla">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaPrescricaoLabel">
                        Nova Prescrição Múltipla
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning small">
                        Antes de cadastrar, confira alergias, histórico clínico e possíveis interações medicamentosas.
                        A prescrição ficará vinculada ao prontuário selecionado.
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label for="id_prontuario" class="form-label fw-bold">Selecione o Prontuário</label>

                            <select name="id_prontuario" id="id_prontuario" class="form-select" required>
                                <option value="">Selecione um prontuário</option>

                                <?php foreach ($prontuariosList as $prontuario): ?>
                                    <?php
                                        $idProntuario = $prontuario['id_prontuario'] ?? '';
                                        $nomePacienteProntuario = $prontuario['nome_paciente'] ?? 'Paciente não informado';
                                        $nomeMedicoProntuario = $prontuario['nome_medico'] ?? 'Médico não informado';
                                        $dataRegistro = $prontuario['data_registro'] ?? '';
                                    ?>

                                    <option value="<?php echo htmlspecialchars($idProntuario); ?>">
                                        Prontuário #<?php echo htmlspecialchars($idProntuario); ?> -
                                        <?php echo htmlspecialchars($nomePacienteProntuario); ?> -
                                        Dr(a). <?php echo htmlspecialchars($nomeMedicoProntuario); ?>

                                        <?php if (!empty($dataRegistro)): ?>
                                            - <?php echo date('d/m/Y H:i', strtotime($dataRegistro)); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if (empty($prontuariosList)): ?>
                                <small class="text-muted">
                                    Nenhum prontuário disponível para prescrição.
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="container-medicamentos">
                        <div class="medicamento-item border rounded p-3 mb-3 bg-light position-relative">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Medicamento</label>

                                    <select name="medicamentos[0][id_medicamento]" class="form-select" required>
                                        <option value="">Selecione um medicamento</option>

                                        <?php foreach ($medicamentos_list as $med): ?>
                                            <?php
                                                $idMedicamento = $med['id_medicamento'] ?? '';
                                                $nomeMedicamento = $med['nome_medicamento'] ?? '';
                                                $interacoes = $med['interacoes_medicamentosas'] ?? '';
                                            ?>

                                            <option 
                                                value="<?php echo htmlspecialchars($idMedicamento); ?>"
                                                title="<?php echo htmlspecialchars($interacoes); ?>"
                                            >
                                                <?php echo htmlspecialchars($nomeMedicamento); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Dosagem</label>
                                    <input type="text" name="medicamentos[0][dosagem]" class="form-control" placeholder="Ex: 500mg" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Frequência</label>
                                    <input type="text" name="medicamentos[0][frequencia]" class="form-control" placeholder="Ex: 8/8h" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Duração</label>
                                    <input type="text" name="medicamentos[0][duracao]" class="form-control" placeholder="Ex: 7 dias" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btn-adicionar-medicamento" class="btn btn-outline-success btn-sm mt-2">
                        <i class="fas fa-plus me-1"></i> Adicionar Outro Medicamento
                    </button>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary px-4">
                        Salvar Prescrição Completa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let medicamentoIndex = 1;
    const container = document.getElementById('container-medicamentos');
    const btnAdicionar = document.getElementById('btn-adicionar-medicamento');
    const medicamentosList = <?php echo json_encode($medicamentos_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    if (!btnAdicionar || !container) {
        return;
    }

    btnAdicionar.addEventListener('click', function() {
        const novoItem = document.createElement('div');
        novoItem.className = 'medicamento-item border rounded p-3 mb-3 bg-light position-relative';

        let optionsHtml = '<option value="">Selecione um medicamento</option>';

        medicamentosList.forEach(function(med) {
            const id = String(med.id_medicamento ?? '');
            const nome = String(med.nome_medicamento ?? '');
            const interacoes = String(med.interacoes_medicamentosas ?? '');

            optionsHtml += `<option value="${id.replace(/"/g, '&quot;')}" title="${interacoes.replace(/"/g, '&quot;')}">${nome}</option>`;
        });

        novoItem.innerHTML = `
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2 btn-remover" aria-label="Remover"></button>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Medicamento</label>
                    <select name="medicamentos[${medicamentoIndex}][id_medicamento]" class="form-select" required>
                        ${optionsHtml}
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Dosagem</label>
                    <input type="text" name="medicamentos[${medicamentoIndex}][dosagem]" class="form-control" placeholder="Ex: 500mg" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Frequência</label>
                    <input type="text" name="medicamentos[${medicamentoIndex}][frequencia]" class="form-control" placeholder="Ex: 8/8h" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Duração</label>
                    <input type="text" name="medicamentos[${medicamentoIndex}][duracao]" class="form-control" placeholder="Ex: 7 dias" required>
                </div>
            </div>
        `;

        container.appendChild(novoItem);
        medicamentoIndex++;

        novoItem.querySelector('.btn-remover').addEventListener('click', function() {
            novoItem.remove();
        });
    });
});
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>