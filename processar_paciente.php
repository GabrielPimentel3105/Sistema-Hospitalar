<?php
require_once __DIR__ . '/config/Database.php';

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

require_once __DIR__ . '/model/Paciente.php';

function cpfJaCadastrado($db, $cpf, $idPacienteAtual = null) {
    if (empty($cpf)) {
        return false;
    }

    try {
        if (!empty($idPacienteAtual)) {
            $query = "SELECT id_paciente 
                      FROM pacientes 
                      WHERE cpf = :cpf 
                      AND id_paciente <> :id_paciente
                      LIMIT 1";

            $stmt = $db->prepare($query);
            $stmt->bindValue(":cpf", $cpf);
            $stmt->bindValue(":id_paciente", $idPacienteAtual);
        } else {
            $query = "SELECT id_paciente 
                      FROM pacientes 
                      WHERE cpf = :cpf 
                      LIMIT 1";

            $stmt = $db->prepare($query);
            $stmt->bindValue(":cpf", $cpf);
        }

        $stmt->execute();

        return $stmt->rowCount() > 0;

    } catch (PDOException $e) {
        return false;
    }
}

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" || isset($_GET['acao'])) {
        if (method_exists('Database', 'getInstance')) {
            $database = Database::getInstance();
        } else {
            $database = new Database();
        }

        $db = $database->getConnection();

        if ($db === null) {
            header("Location: pacientes.php?status=erro_conexao");
            exit;
        }

        $paciente = new Paciente($db);
        $acao = $_POST['acao'] ?? $_GET['acao'] ?? 'cadastrar';

        if ($acao === 'excluir') {
            $id = $_GET['id'] ?? null;

            if (!empty($id)) {
                $paciente->__set("id_paciente", $id);

                if ($paciente->delete()) {
                    header("Location: pacientes.php?status=excluido");
                    exit;
                }
            }

            header("Location: pacientes.php?status=erro");
            exit;
        }

        $idPaciente = $_POST['id_paciente'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $dataNascimento = $_POST['data_nascimento'] ?? null;

        if (empty($nome) || empty($cpf) || empty($dataNascimento)) {
            if ($acao === 'editar' && !empty($idPaciente)) {
                header("Location: paciente_editar.php?id=" . urlencode($idPaciente) . "&status=campos_obrigatorios");
                exit;
            }

            header("Location: paciente_novo.php?status=campos_obrigatorios");
            exit;
        }

        if ($acao === 'cadastrar') {
            if (cpfJaCadastrado($db, $cpf)) {
                header("Location: paciente_novo.php?status=cpf_duplicado");
                exit;
            }
        }

        if ($acao === 'editar') {
            if (cpfJaCadastrado($db, $cpf, $idPaciente)) {
                header("Location: paciente_editar.php?id=" . urlencode($idPaciente) . "&status=cpf_duplicado");
                exit;
            }
        }

        $paciente->__set("id_paciente", $idPaciente);
        $paciente->__set("nome", $nome);
        $paciente->__set("cpf", $cpf);
        $paciente->__set("data_nascimento", $dataNascimento);
        $paciente->__set("telefone", $_POST['telefone'] ?? '');
        $paciente->__set("endereco", $_POST['endereco'] ?? '');
        $paciente->__set("alergias", $_POST['alergias'] ?? '');
        $paciente->__set("historico_clinico", $_POST['historico_clinico'] ?? '');
        $paciente->__set("tipo_sanguineo", $_POST['tipo_sanguineo'] ?? '');
        $paciente->__set("id_convenio", !empty($_POST['id_convenio']) ? $_POST['id_convenio'] : null);
        $paciente->__set("numero_carteirinha", $_POST['numero_carteirinha'] ?? '');
        $paciente->__set("validade_carteirinha", !empty($_POST['validade_carteirinha']) ? $_POST['validade_carteirinha'] : null);
        $paciente->__set("status_paciente", $_POST['status_paciente'] ?? 'Ativo');

        if ($acao === 'editar') {
            if ($paciente->update()) {
                header("Location: pacientes.php?status=editado");
                exit;
            }

            header("Location: paciente_editar.php?id=" . urlencode($paciente->__get("id_paciente")) . "&status=erro");
            exit;
        }

        if ($acao === 'cadastrar') {
            $novoId = $paciente->create();

            if ($novoId) {
                header("Location: pacientes.php?status=sucesso");
                exit;
            }

            header("Location: paciente_novo.php?status=erro");
            exit;
        }

        header("Location: pacientes.php?status=acao_invalida");
        exit;
    }

    header("Location: index.php");
    exit;

} catch (Exception $e) {
    header("Location: pacientes.php?status=erro&mensagem=" . urlencode($e->getMessage()));
    exit;
}
?>