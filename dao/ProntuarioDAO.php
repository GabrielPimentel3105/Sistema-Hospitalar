<?php

class ProntuarioDAO {
    private $conn;
    private $table_name = "prontuario";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(Prontuario $prontuario) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (
                        evolucao_clinica,
                        observacoes,
                        id_consulta
                    )
                    VALUES
                    (
                        :evolucao_clinica,
                        :observacoes,
                        :id_consulta
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":evolucao_clinica", $prontuario->__get("evolucao_clinica"));
            $stmt->bindValue(":observacoes", $prontuario->__get("observacoes"));
            $stmt->bindValue(":id_consulta", $prontuario->__get("id_consulta"));

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            return false;
        }
    }

    public function update(Prontuario $prontuario) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET
                        evolucao_clinica = :evolucao_clinica,
                        observacoes = :observacoes
                    WHERE id_prontuario = :id_prontuario";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":evolucao_clinica", $prontuario->__get("evolucao_clinica"));
            $stmt->bindValue(":observacoes", $prontuario->__get("observacoes"));
            $stmt->bindValue(":id_prontuario", $prontuario->__get("id_prontuario"));

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function buscarPaciente($idPaciente) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        p.*,
                        c.nome_convenio,
                        c.procedimentos_autorizados
                    FROM pacientes p
                    LEFT JOIN convenios c ON p.id_convenio = c.id_convenio
                    WHERE p.id_paciente = :id_paciente
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_paciente", $idPaciente);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarConsulta($idConsulta) {
        if ($this->conn === null || empty($idConsulta)) {
            return null;
        }

        try {
            $query = "SELECT
                        c.*,
                        p.nome AS paciente_nome,
                        p.cpf,
                        p.alergias,
                        p.historico_clinico,
                        p.tipo_sanguineo,
                        p.numero_carteirinha,
                        p.validade_carteirinha,
                        m.nome AS medico_nome,
                        m.crm,
                        m.especialidade,
                        s.numero_sala,
                        s.tipo_sala,
                        conv.nome_convenio
                    FROM consultas c
                    INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                    INNER JOIN medicos m ON c.id_medico = m.id_medico
                    LEFT JOIN salas s ON c.id_sala = s.id_sala
                    LEFT JOIN convenios conv ON c.id_convenio = conv.id_convenio
                    WHERE c.id_consulta = :id_consulta
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_consulta", $idConsulta);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarUltimaConsultaDoPaciente($idPaciente) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT id_consulta
                    FROM consultas
                    WHERE id_paciente = :id_paciente
                    ORDER BY data_consulta DESC, horario DESC, id_consulta DESC
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_paciente", $idPaciente);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['id_consulta'] ?? null;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarOuCriarPorConsulta($idConsulta) {
        if ($this->conn === null || empty($idConsulta)) {
            return null;
        }

        try {
            $query = "SELECT *
                    FROM " . $this->table_name . "
                    WHERE id_consulta = :id_consulta
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_consulta", $idConsulta);
            $stmt->execute();

            $prontuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($prontuario) {
                return $prontuario;
            }

            $queryInsert = "INSERT INTO " . $this->table_name . "
                            (
                                evolucao_clinica,
                                observacoes,
                                id_consulta
                            )
                            VALUES
                            (
                                '',
                                '',
                                :id_consulta
                            )";

            $stmtInsert = $this->conn->prepare($queryInsert);
            $stmtInsert->bindValue(":id_consulta", $idConsulta);

            if (!$stmtInsert->execute()) {
                return null;
            }

            $idProntuario = $this->conn->lastInsertId();

            $queryNovo = "SELECT *
                        FROM " . $this->table_name . "
                        WHERE id_prontuario = :id_prontuario
                        LIMIT 1";

            $stmtNovo = $this->conn->prepare($queryNovo);
            $stmtNovo->bindValue(":id_prontuario", $idProntuario);
            $stmtNovo->execute();

            return $stmtNovo->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    public function listarExames($idProntuario) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT *
                    FROM exames
                    WHERE id_prontuario = :id_prontuario
                    ORDER BY data_exame DESC, id_exame DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_prontuario", $idProntuario);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function listarPrescricoes($idProntuario) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT
                        pr.*,
                        m.nome_medicamento,
                        m.contraindicacoes,
                        m.interacoes_medicamentosas
                    FROM prescricoes pr
                    INNER JOIN medicamentos m ON pr.id_medicamento = m.id_medicamento
                    WHERE pr.id_prontuario = :id_prontuario
                    ORDER BY pr.data_prescricao DESC, pr.id_prescricao DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_prontuario", $idProntuario);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function listarMedicamentos() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT *
                    FROM medicamentos
                    ORDER BY nome_medicamento ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function solicitarExame($dados) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO exames
                    (
                        nome_exame,
                        resultado,
                        data_exame,
                        valor_exame,
                        id_prontuario
                    )
                    VALUES
                    (
                        :nome_exame,
                        :resultado,
                        :data_exame,
                        :valor_exame,
                        :id_prontuario
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome_exame", $dados['nome_exame'] ?? '');
            $stmt->bindValue(":resultado", $dados['resultado'] ?? '');
            $stmt->bindValue(":data_exame", !empty($dados['data_exame']) ? $dados['data_exame'] : date('Y-m-d'));
            $stmt->bindValue(":valor_exame", !empty($dados['valor_exame']) ? $dados['valor_exame'] : 0);
            $stmt->bindValue(":id_prontuario", $dados['id_prontuario'] ?? null);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function emitirPrescricao($dados) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO prescricoes
                    (
                        dosagem,
                        frequencia,
                        duracao_tratamento,
                        id_prontuario,
                        id_medicamento
                    )
                    VALUES
                    (
                        :dosagem,
                        :frequencia,
                        :duracao_tratamento,
                        :id_prontuario,
                        :id_medicamento
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":dosagem", $dados['dosagem'] ?? '');
            $stmt->bindValue(":frequencia", $dados['frequencia'] ?? '');
            $stmt->bindValue(":duracao_tratamento", $dados['duracao_tratamento'] ?? '');
            $stmt->bindValue(":id_prontuario", $dados['id_prontuario'] ?? null);
            $stmt->bindValue(":id_medicamento", $dados['id_medicamento'] ?? null);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function verificarAlertaMedicamento($idMedicamento, $idPaciente) {
        if ($this->conn === null || empty($idMedicamento) || empty($idPaciente)) {
            return [
                'possui_alerta' => false,
                'mensagem' => ''
            ];
        }

        try {
            $queryPaciente = "SELECT alergias, historico_clinico
                            FROM pacientes
                            WHERE id_paciente = :id_paciente
                            LIMIT 1";

            $stmtPaciente = $this->conn->prepare($queryPaciente);
            $stmtPaciente->bindValue(":id_paciente", $idPaciente);
            $stmtPaciente->execute();

            $paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

            $queryMedicamento = "SELECT nome_medicamento, contraindicacoes, interacoes_medicamentosas
                                FROM medicamentos
                                WHERE id_medicamento = :id_medicamento
                                LIMIT 1";

            $stmtMedicamento = $this->conn->prepare($queryMedicamento);
            $stmtMedicamento->bindValue(":id_medicamento", $idMedicamento);
            $stmtMedicamento->execute();

            $medicamento = $stmtMedicamento->fetch(PDO::FETCH_ASSOC);

            if (!$paciente || !$medicamento) {
                return [
                    'possui_alerta' => false,
                    'mensagem' => ''
                ];
            }

            $alergias = mb_strtolower($paciente['alergias'] ?? '');
            $historico = mb_strtolower($paciente['historico_clinico'] ?? '');
            $textoPaciente = $alergias . ' ' . $historico;

            $nomeMedicamento = mb_strtolower($medicamento['nome_medicamento'] ?? '');
            $contraindicacoes = mb_strtolower($medicamento['contraindicacoes'] ?? '');
            $interacoes = mb_strtolower($medicamento['interacoes_medicamentosas'] ?? '');

            $possuiAlerta = false;
            $motivos = [];

            if (!empty($nomeMedicamento) && strpos($textoPaciente, $nomeMedicamento) !== false) {
                $possuiAlerta = true;
                $motivos[] = "o medicamento aparece no histórico/alergias do paciente";
            }

            $termosRisco = [
                'alergia',
                'hepática',
                'hepatica',
                'fígado',
                'figado',
                'renal',
                'rim',
                'úlcera',
                'ulcera',
                'anticoagulante',
                'hipertensão',
                'hipertensao',
                'cardíaco',
                'cardiaco',
                'gestante',
                'asma'
            ];

            foreach ($termosRisco as $termo) {
                if (
                    strpos($textoPaciente, $termo) !== false &&
                    (
                        strpos($contraindicacoes, $termo) !== false ||
                        strpos($interacoes, $termo) !== false
                    )
                ) {
                    $possuiAlerta = true;
                    $motivos[] = "há possível relação entre o histórico do paciente e as contraindicações/interações do medicamento";
                    break;
                }
            }

            if ($possuiAlerta) {
                return [
                    'possui_alerta' => true,
                    'mensagem' => "Atenção: possível risco ao prescrever " . ($medicamento['nome_medicamento'] ?? 'este medicamento') . ", pois " . implode(" e ", $motivos) . "."
                ];
            }

            return [
                'possui_alerta' => false,
                'mensagem' => ''
            ];

        } catch (PDOException $e) {
            return [
                'possui_alerta' => false,
                'mensagem' => ''
            ];
        }
    }
}
?>