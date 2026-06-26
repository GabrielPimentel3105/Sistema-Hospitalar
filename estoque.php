<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/auth.php')) {
    require_once __DIR__ . '/auth.php';
}

require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

if (file_exists(__DIR__ . '/config/helpers.php')) {
    require_once __DIR__ . '/config/helpers.php';
}

require_once __DIR__ . '/model/Insumo.php';
require_once __DIR__ . '/views/header.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $insumoModel = new Insumo($db);
    $stmt = $insumoModel->read();
    $num = ($stmt) ? $stmt->rowCount() : 0;
    $totalBaixoEstoque = $insumoModel->countBaixoEstoque();

} catch (Throwable $e) {
    $erroInsumo = $e->getMessage();
    $stmt = false;
    $num = 0;
    $totalBaixoEstoque = 0;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Estoque de Insumos Hospitalares</h1>

    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="uso_insumos.php" class="btn btn-outline-primary me-2">
            <i class="fas fa-clipboard-list me-1"></i> Uso de Insumos
        </a>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoItem">
            <i class="fas fa-plus me-1"></i> Novo Item
        </button>
    </div>
</div>

<?php if (!empty($erroInsumo)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro ao carregar insumos:</strong>
        <?php echo htmlspecialchars($erroInsumo); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['tipo_mensagem'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <?php
        unset($_SESSION['mensagem']);
        unset($_SESSION['tipo_mensagem']);
    ?>
<?php endif; ?>

<?php if ((int)$totalBaixoEstoque > 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-triangle-exclamation me-1"></i>
        Existem <?php echo (int)$totalBaixoEstoque; ?> insumo(s) com estoque baixo ou zerado.
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-boxes me-1 text-primary"></i>
            Inventário de Insumos
        </span>

        <input 
            type="text" 
            class="form-control form-control-sm w-25" 
            id="buscaInsumo" 
            placeholder="Buscar insumo..."
            onkeyup="filtrarInsumos()"
        >
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaInsumos">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Insumo</th>
                        <th>Quantidade</th>
                        <th>Estoque Mínimo</th>
                        <th>Valor Unitário</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($num > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                                $idInsumo = $row['id_insumo'] ?? '';
                                $nomeInsumo = $row['nome_insumo'] ?? '';
                                $quantidadeEstoque = (int)($row['quantidade_estoque'] ?? 0);
                                $valorUnitario = $row['valor_unitario'] ?? 0;
                                $estoqueMinimo = (int)($row['estoque_minimo'] ?? 5);
                                $baixoEstoque = $quantidadeEstoque <= $estoqueMinimo;
                            ?>

                            <tr class="<?php echo $baixoEstoque ? 'table-warning' : ''; ?>">
                                <td class="ps-4">
                                    #<?php echo htmlspecialchars($idInsumo); ?>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($nomeInsumo); ?></strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($quantidadeEstoque); ?> un.
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($estoqueMinimo); ?> un.
                                </td>

                                <td>
                                    R$ <?php echo number_format((float) $valorUnitario, 2, ',', '.'); ?>
                                </td>

                                <td>
                                    <?php if ($quantidadeEstoque <= 0): ?>
                                        <span class="badge bg-danger">Zerado</span>
                                    <?php elseif ($baixoEstoque): ?>
                                        <span class="badge bg-warning text-dark">Estoque baixo</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Normal</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end pe-4">
                                    <button 
                                        class="btn btn-sm btn-outline-primary" 
                                        onclick="abrirEdicao(
                                            '<?php echo htmlspecialchars($idInsumo); ?>',
                                            '<?php echo htmlspecialchars(addslashes($nomeInsumo)); ?>',
                                            '<?php echo htmlspecialchars($quantidadeEstoque); ?>',
                                            '<?php echo htmlspecialchars($valorUnitario); ?>',
                                            '<?php echo htmlspecialchars($estoqueMinimo); ?>'
                                        )"
                                        title="Editar insumo"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <a 
                                        href="processar_insumos.php?acao=zerar_insumo&id=<?php echo urlencode($idInsumo); ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Deseja zerar o estoque deste insumo? O histórico será preservado.')"
                                        title="Zerar estoque"
                                    >
                                        <i class="fas fa-ban"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Nenhum insumo cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoItem" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_insumos.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar_insumo">

                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Novo Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Insumo</label>
                        <input type="text" class="form-control" name="nome_insumo" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade Inicial</label>
                        <input type="number" class="form-control" name="quantidade_estoque" min="0" value="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estoque Mínimo</label>
                        <input type="number" class="form-control" name="estoque_minimo" min="0" value="5" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Valor Unitário (R$)</label>
                        <input type="number" step="0.01" class="form-control" name="valor_unitario" min="0" value="0.00" required>
                    </div>

                    <div class="alert alert-info small mb-0">
                        O estoque mínimo será usado para gerar alerta de baixo estoque.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Salvar Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarInsumo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_insumos.php" method="POST">
                <input type="hidden" name="acao" value="editar_insumo">
                <input type="hidden" name="id_insumo" id="edit_id_insumo">

                <div class="modal-header">
                    <h5 class="modal-title">Editar Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Insumo</label>
                        <input type="text" class="form-control" name="nome_insumo" id="edit_nome_insumo" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade em Estoque</label>
                        <input type="number" class="form-control" name="quantidade_estoque" id="edit_quantidade_estoque" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estoque Mínimo</label>
                        <input type="number" class="form-control" name="estoque_minimo" id="edit_estoque_minimo" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Valor Unitário (R$)</label>
                        <input type="number" step="0.01" class="form-control" name="valor_unitario" id="edit_valor_unitario" min="0" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirEdicao(id, nome, quantidade, valor, minimo) {
    document.getElementById('edit_id_insumo').value = id;
    document.getElementById('edit_nome_insumo').value = nome;
    document.getElementById('edit_quantidade_estoque').value = quantidade;
    document.getElementById('edit_valor_unitario').value = valor;
    document.getElementById('edit_estoque_minimo').value = minimo;

    const modal = new bootstrap.Modal(document.getElementById('modalEditarInsumo'));
    modal.show();
}

function filtrarInsumos() {
    const input = document.getElementById('buscaInsumo');
    const filtro = input.value.toLowerCase();
    const tabela = document.getElementById('tabelaInsumos');
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