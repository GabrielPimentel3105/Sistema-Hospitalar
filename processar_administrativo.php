<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Convenio.php';
require_once __DIR__ . '/model/Sala.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['acao'])) {
    try {
        if (method_exists('Database', 'getInstance')) {
            $database = Database::getInstance();
        } else {
            $database = new Database();
        }

        $db = $database->getConnection();

        $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

        if ($acao == 'novo_convenio') {
            $conv = new Convenio($db);

            $conv->nome_convenio = $_POST['nome'] ?? '';
            $conv->validade_carteira = !empty($_POST['validade']) ? $_POST['validade'] : null;
            $conv->telefone = $_POST['telefone'] ?? '';

            if ($conv->create()) {
                header("Location: convenios.php?status=sucesso");
                exit;
            }

            header("Location: convenios.php?status=erro");
            exit;
        }

        if ($acao == 'editar_convenio') {
            $conv = new Convenio($db);

            $conv->id_convenio = $_POST['id_convenio'] ?? null;
            $conv->nome_convenio = $_POST['nome'] ?? '';
            $conv->validade_carteira = !empty($_POST['validade']) ? $_POST['validade'] : null;
            $conv->telefone = $_POST['telefone'] ?? '';

            if ($conv->update()) {
                header("Location: convenios.php?status=editado");
                exit;
            }

            header("Location: convenios.php?status=erro");
            exit;
        }

        if ($acao == 'excluir_convenio') {
            $conv = new Convenio($db);
            $id = $_GET['id'] ?? null;

            if (!empty($id) && $conv->delete($id)) {
                header("Location: convenios.php?status=excluido");
                exit;
            }

            header("Location: convenios.php?status=erro");
            exit;
        }

        if ($acao == 'nova_sala') {
            $sala = new Sala($db);

            $sala->numero_sala = $_POST['numero'] ?? '';
            $sala->tipo_sala = $_POST['tipo'] ?? '';
            $sala->status_sala = 'Disponível';

            if ($sala->create()) {
                if (isset($_POST['vincular_leito']) && $_POST['vincular_leito'] == '1') {
                    require_once __DIR__ . '/model/Leito.php';

                    $leito = new Leito($db);
                    $leito->numero_leito = $_POST['numero'] ?? '';
                    $leito->ala = !empty($_POST['ala']) ? $_POST['ala'] : 'Geral';
                    $leito->status_leito = 'Disponível';
                    $leito->create();
                }

                header("Location: salas.php?status=sucesso");
                exit;
            }

            header("Location: salas.php?status=erro");
            exit;
        }

        header("Location: index.php?status=acao_invalida");
        exit;

    } catch (Exception $e) {
        header("Location: index.php?status=erro&mensagem=" . urlencode($e->getMessage()));
        exit;
    }
}

header("Location: index.php");
exit;
?>