<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/model/Internacao.php';
require_once __DIR__ . '/dao/InternacaoDAO.php';

if (file_exists(__DIR__ . '/dao/FaturamentoDAO.php')) {
    require_once __DIR__ . '/dao/FaturamentoDAO.php';
}

function colunaExisteProcessarInternacao($db, $tabela, $coluna) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
        $stmt->execute([$coluna]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function tabelaExisteProcessarInternacao($db, $tabela) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function normalizarValorInternacao($valor) {
    if ($valor === null || $valor === '') {
        return 0.00;
    }

    $valor = str_replace(',', '.', (string) $valor);
    return is_numeric($valor) ? (float) $valor : 0.00;
}

function buscarUltimaConsultaInternacao($db, $idPaciente) {
    if (empty($idPaciente) || !tabelaExisteProcessarInternacao($db, 'consultas')) {
        return null;
    }

    try {
        $sql = "
            SELECT id_consulta
            FROM consultas
            WHERE id_paciente = ?
              AND (
                    status_fluxo = 'Aguardando internação'
                    OR status_consulta = 'Encaminhado para Internação'
                    OR destino_paciente = 'INTERNACAO'
              )
            ORDER BY data_consulta DESC, horario DESC, id_consulta DESC
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$idPaciente]);

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados['id_consulta'] ?? null;
    } catch (Throwable $e) {
        return null;
    }
}

function buscarUltimaInternacaoPaciente($db, $idPaciente, $idLeito = null) {
    if (empty($idPaciente) || !tabelaExisteProcessarInternacao($db, 'internacoes')) {
        return null;
    }

    try {
        $sql = "
            SELECT id_internacao
            FROM internacoes
            WHERE id_paciente = ?
        ";

        $valores = [$idPaciente];

        if (!empty($idLeito) && colunaExisteProcessarInternacao($db, 'internacoes', 'id_leito')) {
            $sql .= " AND id_leito = ? ";
            $valores[] = $idLeito;
        }

        if (colunaExisteProcessarInternacao($db, 'internacoes', 'status_internacao')) {
            $sql .= " AND status_internacao = 'Ativa' ";
        }

        $sql .= "
            ORDER BY id_internacao DESC
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($valores);

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados['id_internacao'] ?? null;
    } catch (Throwable $e) {
        return null;
    }
}

function atualizarFluxoConsultaInternacao($db, $idPaciente, $novoStatusConsulta, $novoStatusFluxo, $destinoPaciente = 'INTERNACAO') {
    if (empty($idPaciente)) {
        return false;
    }

    if (!tabelaExisteProcessarInternacao($db, 'consultas')) {
        return false;
    }

    $temStatusConsulta = colunaExisteProcessarInternacao($db, 'consultas', 'status_consulta');
    $temStatusFluxo = colunaExisteProcessarInternacao($db, 'consultas', 'status_fluxo');
    $temDestinoPaciente = colunaExisteProcessarInternacao($db, 'consultas', 'destino_paciente');

    $sets = [];
    $valores = [];

    if ($temStatusConsulta) {
        $sets[] = "status_consulta = ?";
        $valores[] = $novoStatusConsulta;
    }

    if ($temStatusFluxo) {
        $sets[] = "status_fluxo = ?";
        $valores[] = $novoStatusFluxo;
    }

    if ($temDestinoPaciente) {
        $sets[] = "destino_paciente = ?";
        $valores[] = $destinoPaciente;
    }

    if (empty($sets)) {
        return false;
    }

    $valores[] = $idPaciente;

    $sql = "
        UPDATE consultas
        SET " . implode(", ", $sets) . "
        WHERE id_paciente = ?
          AND (
                status_fluxo = 'Aguardando internação'
                OR status_consulta = 'Encaminhado para Internação'
                OR destino_paciente = 'INTERNACAO'
          )
        ORDER BY data_consulta DESC, horario DESC, id_consulta DESC
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    return $stmt->execute($valores);
}

function lancarInternacaoNoFaturamento($db, $idPaciente, $idConsulta, $idInternacao, $valorDiaria = 250.00) {
    if (
        empty($idPaciente) ||
        !class_exists('FaturamentoDAO') ||
        !tabelaExisteProcessarInternacao($db, 'faturamento') ||
        !tabelaExisteProcessarInternacao($db, 'itens_faturamento')
    ) {
        return false;
    }

    try {
        $valorDiaria = normalizarValorInternacao($valorDiaria);

        if ($valorDiaria <= 0) {
            $valorDiaria = 250.00;
        }

        $faturamentoDAO = new FaturamentoDAO($db);

        if (!method_exists($faturamentoDAO, 'lancarItemAutomatico')) {
            return false;
        }

        return $faturamentoDAO->lancarItemAutomatico(
            $idPaciente,
            'Internação / diária hospitalar' . (!empty($idInternacao) ? ' #' . $idInternacao : ''),
            'Internação',
            1,
            $valorDiaria,
            !empty($idConsulta) ? $idConsulta : null,
            !empty($idInternacao) ? $idInternacao : null,
            true
        );
    } catch (Throwable $e) {
        return false;
    }
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET["acao"])) {
        header("Location: internacoes.php");
        exit;
    }

    if (method_exists("Database", "getInstance")) {
        $database = Database::getInstance();
    } else {
        $database = new Database();
    }

    $db = $database->getConnection();

    if ($db === null) {
        $_SESSION["mensagem"] = "Erro de conexão com o banco de dados.";
        $_SESSION["tipo_mensagem"] = "danger";
        header("Location: internacoes.php");
        exit;
    }

    $internacaoDAO = new InternacaoDAO($db);
    $acao = $_GET["acao"] ?? $_POST["acao"] ?? "";

    if ($acao === "internar") {
        $idPaciente = $_POST["id_paciente"] ?? "";
        $idLeito = $_POST["id_leito"] ?? "";
        $valorDiaria = $_POST["valor_diaria"] ?? 250.00;

        if (empty($idPaciente) || empty($idLeito)) {
            $_SESSION["mensagem"] = "Informe o paciente e o leito para registrar a internação.";
            $_SESSION["tipo_mensagem"] = "danger";
            header("Location: internacoes.php");
            exit;
        }

        $idConsultaInternacao = buscarUltimaConsultaInternacao($db, $idPaciente);

        $internacao = new Internacao($db);
        $internacao->__set("id_paciente", $idPaciente);
        $internacao->__set("id_leito", $idLeito);
        $internacao->__set("status_internacao", "Ativa");
        $internacao->__set("data_entrada", date("Y-m-d H:i:s"));
        $internacao->__set("data_alta", null);

        if ($internacaoDAO->create($internacao)) {
            $idInternacaoGerada = buscarUltimaInternacaoPaciente($db, $idPaciente, $idLeito);

            atualizarFluxoConsultaInternacao(
                $db,
                $idPaciente,
                "Internado",
                "Paciente internado",
                "INTERNACAO"
            );

            $guiaGerada = lancarInternacaoNoFaturamento(
                $db,
                $idPaciente,
                $idConsultaInternacao,
                $idInternacaoGerada,
                $valorDiaria
            );

            if ($guiaGerada) {
                $_SESSION["mensagem"] = "Internação registrada com sucesso. O leito foi marcado como ocupado, o paciente saiu da fila de aguardando internação e a diária foi lançada no faturamento.";
            } else {
                $_SESSION["mensagem"] = "Internação registrada com sucesso. Atenção: não foi possível lançar automaticamente a diária no faturamento.";
            }

            $_SESSION["tipo_mensagem"] = "success";
            header("Location: internacoes.php");
            exit;
        }

        $_SESSION["mensagem"] = "Não foi possível registrar a internação. Verifique se o leito está disponível.";
        $_SESSION["tipo_mensagem"] = "danger";
        header("Location: internacoes.php");
        exit;
    }

    if ($acao === "alta") {
        $idInternacao = $_GET["id"] ?? "";

        if (empty($idInternacao)) {
            $_SESSION["mensagem"] = "Internação não informada.";
            $_SESSION["tipo_mensagem"] = "danger";
            header("Location: internacoes.php");
            exit;
        }

        $idPacienteAlta = null;

        try {
            $stmtBuscaPaciente = $db->prepare("
                SELECT id_paciente 
                FROM internacoes 
                WHERE id_internacao = ? 
                LIMIT 1
            ");
            $stmtBuscaPaciente->execute([$idInternacao]);
            $dadosInternacao = $stmtBuscaPaciente->fetch(PDO::FETCH_ASSOC);

            if ($dadosInternacao && !empty($dadosInternacao["id_paciente"])) {
                $idPacienteAlta = $dadosInternacao["id_paciente"];
            }
        } catch (Throwable $e) {
            $idPacienteAlta = null;
        }

        if ($internacaoDAO->darAlta($idInternacao)) {
            if (!empty($idPacienteAlta)) {
                atualizarFluxoConsultaInternacao(
                    $db,
                    $idPacienteAlta,
                    "Finalizada",
                    "Alta hospitalar",
                    "LIBERADO"
                );
            }

            $_SESSION["mensagem"] = "Alta registrada com sucesso. O leito foi enviado para higienização.";
            $_SESSION["tipo_mensagem"] = "success";
            header("Location: internacoes.php");
            exit;
        }

        $_SESSION["mensagem"] = "Erro ao registrar alta. Verifique se a internação ainda está ativa.";
        $_SESSION["tipo_mensagem"] = "danger";
        header("Location: internacoes.php");
        exit;
    }

    if ($acao === "cancelar") {
        $idInternacao = $_GET["id"] ?? "";

        if (empty($idInternacao)) {
            $_SESSION["mensagem"] = "Internação não informada.";
            $_SESSION["tipo_mensagem"] = "danger";
            header("Location: internacoes.php");
            exit;
        }

        $idPacienteCancelamento = null;

        try {
            $stmtBuscaPaciente = $db->prepare("
                SELECT id_paciente 
                FROM internacoes 
                WHERE id_internacao = ? 
                LIMIT 1
            ");
            $stmtBuscaPaciente->execute([$idInternacao]);
            $dadosInternacao = $stmtBuscaPaciente->fetch(PDO::FETCH_ASSOC);

            if ($dadosInternacao && !empty($dadosInternacao["id_paciente"])) {
                $idPacienteCancelamento = $dadosInternacao["id_paciente"];
            }
        } catch (Throwable $e) {
            $idPacienteCancelamento = null;
        }

        if ($internacaoDAO->cancelar($idInternacao)) {
            if (!empty($idPacienteCancelamento)) {
                atualizarFluxoConsultaInternacao(
                    $db,
                    $idPacienteCancelamento,
                    "Cancelada",
                    "Internação cancelada",
                    "INTERNACAO"
                );
            }

            $_SESSION["mensagem"] = "Internação cancelada com sucesso. O leito foi liberado.";
            $_SESSION["tipo_mensagem"] = "success";
            header("Location: internacoes.php");
            exit;
        }

        $_SESSION["mensagem"] = "Erro ao cancelar internação. Verifique se ela ainda está ativa.";
        $_SESSION["tipo_mensagem"] = "danger";
        header("Location: internacoes.php");
        exit;
    }

    if ($acao === "transferir") {
        $idInternacao = $_POST["id_internacao"] ?? "";
        $novoLeito = $_POST["novo_leito"] ?? "";

        if (empty($idInternacao) || empty($novoLeito)) {
            $_SESSION["mensagem"] = "Informe a internação e o novo leito para realizar a transferência.";
            $_SESSION["tipo_mensagem"] = "danger";
            header("Location: internacoes.php");
            exit;
        }

        if ($internacaoDAO->transferir($idInternacao, $novoLeito)) {
            $_SESSION["mensagem"] = "Paciente transferido com sucesso. O leito anterior foi enviado para higienização.";
            $_SESSION["tipo_mensagem"] = "success";
            header("Location: internacoes.php");
            exit;
        }

        $_SESSION["mensagem"] = "Erro ao transferir paciente. Verifique se a internação está ativa e se o novo leito está disponível.";
        $_SESSION["tipo_mensagem"] = "danger";
        header("Location: internacoes.php");
        exit;
    }

    $_SESSION["mensagem"] = "Ação inválida.";
    $_SESSION["tipo_mensagem"] = "danger";
    header("Location: internacoes.php");
    exit;

} catch (Throwable $e) {
    $_SESSION["mensagem"] = "Erro no processamento da internação: " . $e->getMessage();
    $_SESSION["tipo_mensagem"] = "danger";
    header("Location: internacoes.php");
    exit;
}
?>