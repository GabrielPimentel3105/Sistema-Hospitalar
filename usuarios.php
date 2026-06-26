<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/views/header.php';

$usuarios = [];
$erroUsuarios = '';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT id_usuario, nome, email, tipo_usuario, status_usuario, criado_em
        FROM usuarios
        ORDER BY nome ASC
    ");

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $erroUsuarios = $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <h1 class="h2">Usuários do Sistema</h1>

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario">
        <i class="fas fa-user-plus me-1"></i> Novo Usuário
    </button>
</div>

<?php if (!empty($erroUsuarios)): ?>
    <div class="alert alert-danger">
        <strong>Erro:</strong> <?php echo htmlspecialchars($erroUsuarios); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['erro'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['erro']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-users me-2 text-primary"></i>
            Usuários cadastrados
        </h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($usuarios)): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></td>
                                <td>
                                    <?php if ($usuario['status_usuario'] === 'Ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($usuario['criado_em'])); ?>
                                </td>
                                <td class="text-end">
                                    <a 
                                        href="processar_usuario.php?acao=alternar_status&id_usuario=<?php echo urlencode($usuario['id_usuario']); ?>"
                                        class="btn btn-sm btn-outline-warning"
                                        onclick="return confirm('Deseja alterar o status deste usuário?')"
                                    >
                                        <i class="fas fa-sync-alt"></i>
                                    </a>

                                    <a 
                                        href="processar_usuario.php?acao=excluir&id_usuario=<?php echo urlencode($usuario['id_usuario']); ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Tem certeza que deseja excluir este usuário?')"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhum usuário cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoUsuario" tabindex="-1" aria-labelledby="modalNovoUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_usuario.php" method="POST">
                <input type="hidden" name="acao" value="criar">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovoUsuarioLabel">Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" name="senha" id="senha" class="form-control" minlength="6" required>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_usuario" class="form-label">Tipo de usuário</label>
                        <select name="tipo_usuario" id="tipo_usuario" class="form-select" required>
                            <option value="Administrador">Administrador</option>
                            <option value="Médico">Médico</option>
                            <option value="Recepção">Recepção</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="status_usuario" class="form-label">Status</label>
                        <select name="status_usuario" id="status_usuario" class="form-select" required>
                            <option value="Ativo">Ativo</option>
                            <option value="Inativo">Inativo</option>
                        </select>
                    </div>

                    <div class="alert alert-info mb-0">
                        A senha será criptografada automaticamente.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Salvar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>