<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Exame.php';
require_once __DIR__ . '/dao/ExameDAO.php';

if (file_exists(__DIR__ . '/dao/FaturamentoDAO.php')) {
    require_once __DIR__ . '/dao/FaturamentoDAO.php';
}

function tabelaExisteProcessarExames($db, $tabela) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function colunaExisteProcessarExames($db, $tabela, $coluna) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
        $stmt->execute([$coluna]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function normalizarValorExame($valor) {
    if ($valor === null || $valor === '') {
        return 0.00;
    }

    $valor = str_replace(',', '.', (string) $valor);
    return is_numeric($valor) ? (float) $valor : 0.00;
}

function buscarDadosProntuarioExame($db, $idProntuario) {
    if (empty($idProntuario)) {
        return null;
    }

    $tabelasPossiveis = ['prontuario', 'prontuarios'];

    foreach ($tabelasPossiveis as $tabela) {
        if (!tabelaExisteProcessarExames($db, $tabela)) {
            continue;
        }

        try {
            $temIdPaciente = colunaExisteProcessarExames($db, $tabela, 'id_paciente');
            $temIdConsulta = colunaExisteProcessarExames($db, $tabela, 'id_consulta');

            $campos = ['id_prontuario'];

            if ($temIdPaciente) {
                $campos[] = 'id_paciente';
            }

            if ($temIdConsulta) {
                $campos[] = 'id_consulta';
            }

            $sql = "SELECT " . implode(', ', $campos) . "
                    FROM {$tabela}
                    WHERE id_prontuario = ?
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$idProntuario]);

            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dados) {
                if (empty($dados['id_paciente']) && !empty($dados['id_consulta']) && tabelaExisteProcessarExames($db, 'consultas')) {
                    $stmtConsulta = $db->prepare("
                        SELECT id_paciente
                        FROM consultas
                        WHERE id_consulta = ?
                        LIMIT 1
                    ");
                    $stmtConsulta->execute([$dados['id_consulta']]);
                    $consulta = $stmtConsulta->fetch(PDO::FETCH_ASSOC);

                    if ($consulta && !empty($consulta['id_paciente'])) {
                        $dados['id_paciente'] = $consulta['id_paciente'];
                    }
                }

                return $dados;
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return null;
}

function atualizarFluxoConsultaExame($db, $idConsulta, $statusConsulta, $statusFluxo, $destinoPaciente = 'EXAME') {
    if (empty($idConsulta) || !tabelaExisteProcessarExames($db, 'consultas')) {
        return false;
    }

    $sets = [];
    $valores = [];

    if (colunaExisteProcessarExames($db, 'consultas', 'status_consulta')) {
        $sets[] = "status_consulta = ?";
        $valores[] = $statusConsulta;
    }

    if (colunaExisteProcessarExames($db, 'consultas', 'status_fluxo')) {
        $sets[] = "status_fluxo = ?";
        $valores[] = $statusFluxo;
    }

    if (colunaExisteProcessarExames($db, 'consultas', 'destino_paciente')) {
        $sets[] = "destino_paciente = ?";
        $valores[] = $destinoPaciente;
    }

    if (empty($sets)) {
        return false;
    }

    $valores[] = $idConsulta;

    try {
        $sql = "UPDATE consultas
                SET " . implode(', ', $sets) . "
                WHERE id_consulta = ?";

        $stmt = $db->prepare($sql);
        return $stmt->execute($valores);
    } catch (Throwable $e) {
        return false;
    }
}

function lancarExameNoFaturamento($db, $idPaciente, $idConsulta, $nomeExame, $valorExame) {
    if (
        empty($idPaciente) ||
        empty($nomeExame) ||
        !class_exists('FaturamentoDAO') ||
        !tabelaExisteProcessarExames($db, 'faturamento') ||
        !tabelaExisteProcessarExames($db, 'itens_faturamento')
    ) {
        return false;
    }

    try {
        $valorExame = normalizarValorExame($valorExame);

        $faturamentoDAO = new FaturamentoDAO($db);

        if (!method_exists($faturamentoDAO, 'lancarItemAutomatico')) {
            return false;
        }

        return $faturamentoDAO->lancarItemAutomatico(
            $idPaciente,
            'Exame: ' . $nomeExame,
            'Exame',
            1,
            $valorExame,
            !empty($idConsulta) ? $idConsulta : null,
            null,
            true
        );
    } catch (Throwable $e) {
        return false;
    }
}

try {
    if (method_exists('Database', 'getInstance')) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        header("Location: exames.php?erro=" . urlencode("Erro de conexão com o banco de dados."));
        exit;
    }

    $exameDAO = new ExameDAO($db);

    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

    $statusPermitidos = [
        "Solicitado",
        "Em análise",
        "Finalizado",
        "Cancelado"
    ];

    if ($acao === 'criar' || $acao === 'cadastrar') {
        $nomeExame = trim($_POST["nome_exame"] ?? "");
        $resultado = $_POST["resultado"] ?? "";
        $dataExame = $_POST["data_exame"] ?? "";
        $valorExame = $_POST["valor_exame"] ?? 0;
        $idProntuario = $_POST["id_prontuario"] ?? "";
        $statusExame = $_POST["status_exame"] ?? "Solicitado";

        if (empty($nomeExame)) {
            header("Location: exames.php?erro=" . urlencode("Informe o nome do exame."));
            exit;
        }

        if (empty($idProntuario)) {
            header("Location: exames.php?erro=" . urlencode("Prontuário não informado para solicitação do exame."));
            exit;
        }

        if (empty($dataExame)) {
            $dataExame = date('Y-m-d');
        }

        $dadosProntuario = buscarDadosProntuarioExame($db, $idProntuario);

        $idPaciente = $dadosProntuario['id_paciente'] ?? null;
        $idConsulta = $dadosProntuario['id_consulta'] ?? null;

        $exame = new Exame();

        $exame->__set("nome_exame", $nomeExame);
        $exame->__set("resultado", $resultado);
        $exame->__set("data_exame", $dataExame);
        $exame->__set("valor_exame", $valorExame);
        $exame->__set("id_prontuario", $idProntuario);
        $exame->__set("status_exame", $statusExame);

        if ($exameDAO->create($exame)) {
            if (!empty($idConsulta)) {
                atualizarFluxoConsultaExame(
                    $db,
                    $idConsulta,
                    "Encaminhado para Exame",
                    "Aguardando exame",
                    "EXAME"
                );
            }

            $guiaGerada = false;

            if (!empty($idPaciente)) {
                $guiaGerada = lancarExameNoFaturamento(
                    $db,
                    $idPaciente,
                    $idConsulta,
                    $nomeExame,
                    $valorExame
                );
            }

            if ($guiaGerada) {
                header("Location: exames.php?msg=" . urlencode("Exame cadastrado com sucesso, paciente encaminhado para a fila de exames e valor lançado no faturamento."));
                exit;
            }

            header("Location: exames.php?msg=" . urlencode("Exame cadastrado com sucesso e paciente encaminhado para a fila de exames. Atenção: não foi possível lançar automaticamente no faturamento."));
            exit;
        }

        header("Location: exames.php?erro=" . urlencode("Erro ao cadastrar exame."));
        exit;
    }

    if ($acao === 'editar') {
        $exame = new Exame();

        $exame->__set("id_exame", $_POST["id_exame"] ?? "");
        $exame->__set("nome_exame", $_POST["nome_exame"] ?? "");
        $exame->__set("resultado", $_POST["resultado"] ?? "");
        $exame->__set("data_exame", $_POST["data_exame"] ?? "");
        $exame->__set("valor_exame", $_POST["valor_exame"] ?? 0);
        $exame->__set("id_prontuario", $_POST["id_prontuario"] ?? "");
        $exame->__set("status_exame", $_POST["status_exame"] ?? "Solicitado");

        if ($exameDAO->update($exame)) {
            header("Location: exames.php?msg=" . urlencode("Exame atualizado com sucesso."));
            exit;
        }

        header("Location: exames.php?erro=" . urlencode("Erro ao atualizar exame."));
        exit;
    }

    if ($acao === 'status') {
        $id_exame = $_GET["id_exame"] ?? $_POST["id_exame"] ?? "";
        $status_exame = $_GET["status_exame"] ?? $_POST["status_exame"] ?? "";

        if (empty($id_exame) || empty($status_exame)) {
            header("Location: exames.php?erro=" . urlencode("Exame ou status não informado."));
            exit;
        }

        if (!in_array($status_exame, $statusPermitidos)) {
            header("Location: exames.php?erro=" . urlencode("Status de exame inválido."));
            exit;
        }

        if ($exameDAO->updateStatus($id_exame, $status_exame)) {
            header("Location: exames.php?msg=" . urlencode("Status do exame atualizado com sucesso."));
            exit;
        }

        header("Location: exames.php?erro=" . urlencode("Erro ao atualizar status do exame."));
        exit;
    }

    if ($acao === 'finalizar') {
        $id_exame = $_POST["id_exame"] ?? "";
        $resultado = trim($_POST["resultado"] ?? "");

        if (empty($id_exame)) {
            header("Location: exames.php?erro=" . urlencode("Exame não informado."));
            exit;
        }

        if (empty($resultado)) {
            header("Location: exames.php?erro=" . urlencode("Informe o resultado antes de finalizar o exame."));
            exit;
        }

        if ($exameDAO->finalizarComResultado($id_exame, $resultado)) {
            header("Location: exames.php?msg=" . urlencode("Exame finalizado com resultado registrado. Agora defina se o resultado foi normal, alterado ou se o paciente deve retornar ao atendimento."));
            exit;
        }

        header("Location: exames.php?erro=" . urlencode("Erro ao finalizar exame."));
        exit;
    }

    if ($acao === 'analise') {
        $id_exame = $_GET["id_exame"] ?? "";

        if ($id_exame && $exameDAO->updateStatus($id_exame, "Em análise")) {
            header("Location: exames.php?msg=" . urlencode("Exame marcado como em análise."));
            exit;
        }

        header("Location: exames.php?erro=" . urlencode("Erro ao alterar exame para em análise."));
        exit;
    }

    if ($acao === 'cancelar' || $acao === 'excluir') {
        $id_exame = $_GET["id_exame"] ?? $_GET["id"] ?? "";

        if ($id_exame && $exameDAO->updateStatus($id_exame, "Cancelado")) {
            header("Location: exames.php?msg=" . urlencode("Exame cancelado com sucesso. O registro foi mantido no histórico."));
            exit;
        }

        header("Location: exames.php?erro=" . urlencode("Erro ao cancelar exame."));
        exit;
    }

    header("Location: exames.php");
    exit;

} catch (Throwable $e) {
    header("Location: exames.php?erro=" . urlencode("Erro no processamento do exame: " . $e->getMessage()));
    exit;
}
?>