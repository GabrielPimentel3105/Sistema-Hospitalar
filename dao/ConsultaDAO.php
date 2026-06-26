<?php

class ConsultaDAO {
    private $conn;
    private $table_name = "consultas";

    public function __construct($db) {
        $this->conn = $db;
    }

    private function nullSeVazio($valor) {
        return !empty($valor) ? $valor : null;
    }

    public function existeConflito($data, $horario, $id_medico, $id_sala = null, $id_consulta_ignorar = null) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "SELECT COUNT(*) AS total
                    FROM consultas
                    WHERE data_consulta = :data_consulta
                    AND horario = :horario
                    AND status_consulta NOT IN ('Liberado', 'Cancelada')
                    AND (
                        id_medico = :id_medico
                        OR (:id_sala IS NOT NULL AND id_sala = :id_sala)
                    )";

            if (!empty($id_consulta_ignorar)) {
                $query .= " AND id_consulta != :id_consulta_ignorar";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":data_consulta", $data);
            $stmt->bindValue(":horario", $horario);
            $stmt->bindValue(":id_medico", $id_medico);
            $stmt->bindValue(":id_sala", $this->nullSeVazio($id_sala));

            if (!empty($id_consulta_ignorar)) {
                $stmt->bindValue(":id_consulta_ignorar", $id_consulta_ignorar);
            }

            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (($row['total'] ?? 0) > 0);

        } catch (PDOException $e) {
            return false;
        }
    }

    public function create(Consulta $consulta) {
        if ($this->conn === null) {
            return false;
        }

        try {
            if ($this->existeConflito(
                $consulta->__get("data_consulta"),
                $consulta->__get("horario"),
                $consulta->__get("id_medico"),
                $consulta->__get("id_sala")
            )) {
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . " 
                    (
                        data_consulta,
                        horario,
                        status_consulta,
                        id_paciente,
                        id_medico,
                        id_sala,
                        id_convenio
                    ) 
                    VALUES 
                    (
                        :data_consulta,
                        :horario,
                        :status_consulta,
                        :id_paciente,
                        :id_medico,
                        :id_sala,
                        :id_convenio
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":data_consulta", $consulta->__get("data_consulta"));
            $stmt->bindValue(":horario", $consulta->__get("horario"));
            $stmt->bindValue(":status_consulta", $consulta->__get("status_consulta") ?: "Agendada");
            $stmt->bindValue(":id_paciente", $consulta->__get("id_paciente"));
            $stmt->bindValue(":id_medico", $consulta->__get("id_medico"));
            $stmt->bindValue(":id_sala", $this->nullSeVazio($consulta->__get("id_sala")));
            $stmt->bindValue(":id_convenio", $this->nullSeVazio($consulta->__get("id_convenio")));

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function read() {
        return $this->readTriagens();
    }

    public function readAgendamentos() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        c.*,
                        p.nome AS paciente_nome,
                        p.cpf,
                        p.numero_carteirinha,
                        p.validade_carteirinha,
                        m.nome AS medico_nome,
                        m.especialidade,
                        s.numero_sala,
                        s.tipo_sala,
                        conv.nome_convenio
                    FROM consultas c
                    INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                    INNER JOIN medicos m ON c.id_medico = m.id_medico
                    LEFT JOIN salas s ON c.id_sala = s.id_sala
                    LEFT JOIN convenios conv ON c.id_convenio = conv.id_convenio
                    WHERE c.status_consulta IN ('Agendada', 'Aguardando Triagem')
                    ORDER BY c.data_consulta ASC, c.horario ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readFilaEspera() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        c.*,
                        p.nome AS paciente_nome,
                        p.cpf,
                        m.nome AS medico_nome,
                        m.especialidade,
                        s.numero_sala,
                        s.tipo_sala,
                        conv.nome_convenio
                    FROM consultas c
                    INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                    INNER JOIN medicos m ON c.id_medico = m.id_medico
                    LEFT JOIN salas s ON c.id_sala = s.id_sala
                    LEFT JOIN convenios conv ON c.id_convenio = conv.id_convenio
                    WHERE c.status_consulta = 'Aguardando Triagem'
                    ORDER BY c.data_consulta ASC, c.horario ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readTriagens() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        t.*,
                        c.id_consulta,
                        c.data_consulta,
                        c.horario,
                        c.status_consulta,
                        p.nome AS paciente_nome,
                        p.cpf,
                        m.nome AS medico_nome,
                        m.especialidade,
                        s.numero_sala,
                        s.tipo_sala
                    FROM triagem t
                    INNER JOIN pacientes p ON t.id_paciente = p.id_paciente
                    LEFT JOIN consultas c ON t.id_consulta = c.id_consulta
                    LEFT JOIN medicos m ON c.id_medico = m.id_medico
                    LEFT JOIN salas s ON c.id_sala = s.id_sala
                    WHERE c.status_consulta = 'Em Atendimento'
                    ORDER BY 
                        CASE 
                            WHEN t.classificacao_risco = 'Vermelho' THEN 1
                            WHEN t.classificacao_risco = 'Laranja' THEN 2
                            WHEN t.classificacao_risco = 'Amarelo' THEN 3
                            WHEN t.classificacao_risco = 'Verde' THEN 4
                            WHEN t.classificacao_risco = 'Azul' THEN 5
                            ELSE 6
                        END ASC,
                        t.data_triagem ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function createTriagem($dados) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO triagem 
                    (
                        temperatura,
                        pressao_arterial,
                        frequencia_cardiaca,
                        saturacao_oxigenio,
                        escala_dor,
                        queixa_principal,
                        classificacao_risco,
                        protocolo_manchester,
                        id_paciente,
                        id_consulta
                    ) 
                    VALUES 
                    (
                        :temperatura,
                        :pressao,
                        :frequencia,
                        :saturacao,
                        :dor,
                        :queixa,
                        :risco,
                        :protocolo,
                        :id_paciente,
                        :id_consulta
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":temperatura", $dados["temperatura"] ?? null);
            $stmt->bindValue(":pressao", $dados["pressao"] ?? null);
            $stmt->bindValue(":frequencia", $dados["frequencia"] ?? null);
            $stmt->bindValue(":saturacao", $dados["saturacao"] ?? null);
            $stmt->bindValue(":dor", $dados["dor"] ?? 0);
            $stmt->bindValue(":queixa", $dados["queixa"] ?? "");
            $stmt->bindValue(":risco", $dados["risco"] ?? "Azul");
            $stmt->bindValue(":protocolo", $dados["protocolo"] ?? "Manchester");
            $stmt->bindValue(":id_paciente", $dados["id_paciente"] ?? null);
            $stmt->bindValue(":id_consulta", $dados["id_consulta"] ?? null);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            if (!empty($dados["id_consulta"])) {
                $queryUpdate = "UPDATE consultas
                                SET status_consulta = 'Em Atendimento'
                                WHERE id_consulta = :id_consulta
                                LIMIT 1";

                $stmtUpdate = $this->conn->prepare($queryUpdate);
                $stmtUpdate->bindValue(":id_consulta", $dados["id_consulta"]);

                if (!$stmtUpdate->execute()) {
                    $this->conn->rollBack();
                    return false;
                }
            }

            $this->conn->commit();
            return true;

        } catch(PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function countPendentes() {
        if ($this->conn === null) {
            return 0;
        }

        try {
            $query = "SELECT COUNT(*) AS total
                    FROM consultas
                    WHERE status_consulta = 'Aguardando Triagem'";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row["total"] ?? 0;

        } catch(PDOException $e) {
            return 0;
        }
    }

    public function countConsultasHoje() {
        if ($this->conn === null) {
            return 0;
        }

        try {
            $hoje = date("Y-m-d");

            $query = "SELECT COUNT(*) AS total
                    FROM consultas
                    WHERE data_consulta = :hoje";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":hoje", $hoje);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row["total"] ?? 0;

        } catch(PDOException $e) {
            return 0;
        }
    }

    public function readAlertasManchester() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        t.*,
                        p.nome AS paciente_nome,
                        c.id_consulta
                    FROM triagem t
                    INNER JOIN pacientes p ON t.id_paciente = p.id_paciente
                    LEFT JOIN consultas c ON t.id_consulta = c.id_consulta
                    WHERE t.classificacao_risco IN ('Vermelho', 'Laranja', 'Amarelo')
                    AND c.status_consulta = 'Em Atendimento'
                    ORDER BY 
                        CASE 
                            WHEN t.classificacao_risco = 'Vermelho' THEN 1
                            WHEN t.classificacao_risco = 'Laranja' THEN 2
                            WHEN t.classificacao_risco = 'Amarelo' THEN 3
                            ELSE 4
                        END ASC,
                        t.data_triagem ASC
                    LIMIT 5";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readProximasConsultas() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $hoje = date("Y-m-d");

            $query = "SELECT 
                        c.*,
                        p.nome AS paciente_nome,
                        m.nome AS medico_nome,
                        m.especialidade,
                        s.numero_sala
                    FROM consultas c
                    INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                    INNER JOIN medicos m ON c.id_medico = m.id_medico
                    LEFT JOIN salas s ON c.id_sala = s.id_sala
                    WHERE c.data_consulta = :hoje
                    AND c.status_consulta IN ('Agendada', 'Aguardando Triagem', 'Em Atendimento')
                    ORDER BY c.horario ASC
                    LIMIT 5";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":hoje", $hoje);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function liberarPaciente($id_consulta) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE consultas
                    SET status_consulta = 'Liberado'
                    WHERE id_consulta = :id_consulta
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_consulta", $id_consulta);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function cancelarConsulta($id_consulta) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE consultas
                    SET status_consulta = 'Cancelada'
                    WHERE id_consulta = :id_consulta
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_consulta", $id_consulta);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function readHistoricoLiberados() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        c.*,
                        p.nome AS paciente_nome,
                        m.nome AS medico_nome,
                        m.especialidade,
                        s.numero_sala,
                        conv.nome_convenio
                    FROM consultas c
                    INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                    INNER JOIN medicos m ON c.id_medico = m.id_medico
                    LEFT JOIN salas s ON c.id_sala = s.id_sala
                    LEFT JOIN convenios conv ON c.id_convenio = conv.id_convenio
                    WHERE c.status_consulta = 'Liberado'
                    ORDER BY c.data_consulta DESC, c.horario DESC
                    LIMIT 30";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }
}
?>