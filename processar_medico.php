<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Medico.php';

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" || isset($_GET['acao'])) {
        if (method_exists('Database', 'getInstance')) {
            $database = Database::getInstance();
        } else {
            $database = new Database();
        }

        $db = $database->getConnection();
        $medico = new Medico($db);

        $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

        if ($acao === 'excluir') {
            $id = $_GET['id'] ?? null;

            if (!empty($id) && $medico->delete($id)) {
                header("Location: medicos.php?status=excluido");
                exit;
            }

            header("Location: medicos.php?status=erro");
            exit;
        }

        $medico->id_medico = $_POST['id_medico'] ?? null;
        $medico->nome = $_POST['nome'] ?? '';
        $medico->crm = $_POST['crm'] ?? '';
        $medico->especialidade = $_POST['especialidade'] ?? '';
        $medico->telefone = $_POST['telefone'] ?? '';
        $medico->email = $_POST['email'] ?? '';

        if ($acao === 'editar') {
            if ($medico->update()) {
                header("Location: medicos.php?status=editado");
                exit;
            }

            header("Location: medico_editar.php?id=" . urlencode($medico->id_medico) . "&status=erro");
            exit;
        }

        if ($acao === 'cadastrar') {
            if ($medico->create()) {
                header("Location: medicos.php?status=sucesso");
                exit;
            }

            header("Location: medico_novo.php?status=erro");
            exit;
        }

        header("Location: medicos.php?status=acao_invalida");
        exit;
    }

    header("Location: medicos.php");
    exit;

} catch (Exception $e) {
    header("Location: medicos.php?status=erro&mensagem=" . urlencode($e->getMessage()));
    exit;
}
?>