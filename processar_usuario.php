<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/Database.php';

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $tipo_usuario = $_POST['tipo_usuario'] ?? 'Recepção';
        $status_usuario = $_POST['status_usuario'] ?? 'Ativo';

        if (empty($nome) || empty($email) || empty($senha)) {
            header("Location: usuarios.php?erro=" . urlencode("Preencha todos os campos obrigatórios"));
            exit;
        }

        if (strlen($senha) < 6) {
            header("Location: usuarios.php?erro=" . urlencode("A senha deve ter pelo menos 6 caracteres"));
            exit;
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO usuarios (nome, email, senha, tipo_usuario, status_usuario)
            VALUES (:nome, :email, :senha, :tipo_usuario, :status_usuario)
        ");

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
        $stmt->bindParam(':status_usuario', $status_usuario);

        if ($stmt->execute()) {
            header("Location: usuarios.php?msg=" . urlencode("Usuário cadastrado com sucesso"));
            exit;
        }

        header("Location: usuarios.php?erro=" . urlencode("Erro ao cadastrar usuário"));
        exit;
    }

    if ($acao === 'alternar_status') {
        $id_usuario = $_GET['id_usuario'] ?? '';

        if (empty($id_usuario)) {
            header("Location: usuarios.php?erro=" . urlencode("ID do usuário não informado"));
            exit;
        }

        $stmt = $db->prepare("
            UPDATE usuarios
            SET status_usuario = CASE 
                WHEN status_usuario = 'Ativo' THEN 'Inativo'
                ELSE 'Ativo'
            END
            WHERE id_usuario = :id_usuario
        ");

        $stmt->bindParam(':id_usuario', $id_usuario);

        if ($stmt->execute()) {
            header("Location: usuarios.php?msg=" . urlencode("Status do usuário alterado com sucesso"));
            exit;
        }

        header("Location: usuarios.php?erro=" . urlencode("Erro ao alterar status do usuário"));
        exit;
    }

    if ($acao === 'excluir') {
        $id_usuario = $_GET['id_usuario'] ?? '';

        if (empty($id_usuario)) {
            header("Location: usuarios.php?erro=" . urlencode("ID do usuário não informado"));
            exit;
        }

        if (!empty($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $id_usuario) {
            header("Location: usuarios.php?erro=" . urlencode("Você não pode excluir o próprio usuário logado"));
            exit;
        }

        $stmt = $db->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");
        $stmt->bindParam(':id_usuario', $id_usuario);

        if ($stmt->execute()) {
            header("Location: usuarios.php?msg=" . urlencode("Usuário excluído com sucesso"));
            exit;
        }

        header("Location: usuarios.php?erro=" . urlencode("Erro ao excluir usuário"));
        exit;
    }

    header("Location: usuarios.php");
    exit;

} catch (Throwable $e) {
    header("Location: usuarios.php?erro=" . urlencode("Erro: " . $e->getMessage()));
    exit;
}