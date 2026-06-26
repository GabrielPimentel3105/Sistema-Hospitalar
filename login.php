<?php

session_start();

require_once __DIR__ . '/config/Database.php';

if (!empty($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    try {
        if (method_exists('Database', 'getInstance')) {
            $database = Database::getInstance();
        } else {
            $database = new Database();
        }

        $db = $database->getConnection();

        $stmt = $db->prepare("
            SELECT id_usuario, nome, email, senha, tipo_usuario, status_usuario
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && $usuario['status_usuario'] === 'Ativo' && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_logado'] = true;
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nome_usuario'] = $usuario['nome'];
            $_SESSION['email_usuario'] = $usuario['email'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

            header("Location: index.php");
            exit;
        } else {
            $erro = "E-mail ou senha inválidos.";
        }

    } catch (Throwable $e) {
        $erro = "Erro ao fazer login: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema Hospitalar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            min-height: 100vh;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .login-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #2c3e50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 15px;
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <div class="login-icon">
                <i class="fas fa-hospital-user"></i>
            </div>

            <h4 class="mb-1">Sistema Hospitalar</h4>
            <p class="text-muted mb-0">Acesse sua conta</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="form-control" 
                    required
                    autofocus
                >
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input 
                    type="password" 
                    name="senha" 
                    id="senha" 
                    class="form-control" 
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-1"></i> Entrar
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                Usuário inicial: admin@hospital.com | Senha: 123456
            </small>
        </div>
    </div>
</div>

</body>
</html>