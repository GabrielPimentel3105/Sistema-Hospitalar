<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Medicamento.php';

if (file_exists(__DIR__ . '/model/Exame.php')) {
    require_once __DIR__ . '/model/Exame.php';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (method_exists('Database', 'getInstance')) {
            $database = Database::getInstance();
        } else {
            $database = new Database();
        }

        $db = $database->getConnection();
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'novo_medicamento') {
            $med = new Medicamento($db);

            $med->nome_medicamento = $_POST['nome'] ?? '';
            $med->contraindicacoes = $_POST['contra'] ?? '';
            $med->interacoes_medicamentosas = $_POST['inter'] ?? '';

            if ($med->create()) {
                header("Location: medicamentos.php?status=sucesso");
                exit;
            }

            header("Location: medicamentos.php?status=erro");
            exit;
        }

        if ($acao === 'editar_medicamento') {
            $med = new Medicamento($db);

            $med->id_medicamento = $_POST['id_medicamento'] ?? null;
            $med->nome_medicamento = $_POST['nome'] ?? '';
            $med->contraindicacoes = $_POST['contra'] ?? '';
            $med->interacoes_medicamentosas = $_POST['inter'] ?? '';

            if ($med->update()) {
                header("Location: medicamentos.php?status=editado");
                exit;
            }

            header("Location: medicamentos.php?status=erro");
            exit;
        }

        header("Location: medicamentos.php?status=acao_invalida");
        exit;

    } catch (Exception $e) {
        header("Location: medicamentos.php?status=erro&mensagem=" . urlencode($e->getMessage()));
        exit;
    }
}

header("Location: medicamentos.php");
exit;
?>