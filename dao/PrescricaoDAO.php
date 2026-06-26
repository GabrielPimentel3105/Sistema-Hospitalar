<?php

class PrescricaoDAO {
    private $conn;
    private $table_name = "prescricoes";
    private $table_medicamentos = "medicamentos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(Prescricao $prescricao) {
        if ($this->conn === null) {
            return false;
        }

        try {
            if (!$this->verificarInteracoes($prescricao->__get("id_medicamento"))) {
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . "
                    (
                        dosagem,
                        frequencia,
                        duracao_tratamento,
                        id_prontuario,
                        id_medicamento,
                        status_prescricao
                    )
                    VALUES
                    (
                        :dosagem,
                        :frequencia,
                        :duracao_tratamento,
                        :id_prontuario,
                        :id_medicamento,
                        'Ativa'
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":dosagem", $prescricao->__get("dosagem"));
            $stmt->bindValue(":frequencia", $prescricao->__get("frequencia"));
            $stmt->bindValue(":duracao_tratamento", $prescricao->__get("duracao_tratamento"));
            $stmt->bindValue(":id_prontuario", $prescricao->__get("id_prontuario"));
            $stmt->bindValue(":id_medicamento", $prescricao->__get("id_medicamento"));

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    private function verificarInteracoes($id_medicamento) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "SELECT 
                        id_medicamento, 
                        interacoes_medicamentosas 
                    FROM " . $this->table_medicamentos . " 
                    WHERE id_medicamento = :id_medicamento 
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_medicamento", $id_medicamento);
            $stmt->execute();

            $medicamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$medicamento) {
                return false;
            }

            return true;

        } catch (PDOException $e) {
            return false;
        }
    }

    public function read() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        pr.id_prescricao,
                        pr.dosagem,
                        pr.frequencia,
                        pr.duracao_tratamento,
                        pr.id_prontuario,
                        pr.id_medicamento,
                        pr.status_prescricao,
                        m.nome_medicamento,
                        m.interacoes_medicamentosas,
                        pront.data_registro,
                        pac.nome AS nome_paciente,
                        med.nome AS nome_medico
                    FROM " . $this->table_name . " pr
                    LEFT JOIN medicamentos m ON pr.id_medicamento = m.id_medicamento
                    LEFT JOIN prontuario pront ON pr.id_prontuario = pront.id_prontuario
                    LEFT JOIN consultas c ON pront.id_consulta = c.id_consulta
                    LEFT JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                    LEFT JOIN medicos med ON c.id_medico = med.id_medico
                    ORDER BY pr.id_prescricao DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function readOne($id) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT *
                    FROM " . $this->table_name . "
                    WHERE id_prescricao = :id_prescricao
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_prescricao", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    public function readByProntuario($id_prontuario) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        pr.*, 
                        m.nome_medicamento, 
                        m.interacoes_medicamentosas 
                    FROM " . $this->table_name . " pr
                    LEFT JOIN " . $this->table_medicamentos . " m 
                        ON pr.id_medicamento = m.id_medicamento
                    WHERE pr.id_prontuario = :id_prontuario
                    ORDER BY pr.id_prescricao DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_prontuario", $id_prontuario);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(Prescricao $prescricao) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET
                        dosagem = :dosagem,
                        frequencia = :frequencia,
                        duracao_tratamento = :duracao_tratamento,
                        id_prontuario = :id_prontuario,
                        id_medicamento = :id_medicamento
                    WHERE id_prescricao = :id_prescricao
                    AND status_prescricao <> 'Cancelada'";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":id_prescricao", $prescricao->__get("id_prescricao"));
            $stmt->bindValue(":dosagem", $prescricao->__get("dosagem"));
            $stmt->bindValue(":frequencia", $prescricao->__get("frequencia"));
            $stmt->bindValue(":duracao_tratamento", $prescricao->__get("duracao_tratamento"));
            $stmt->bindValue(":id_prontuario", $prescricao->__get("id_prontuario"));
            $stmt->bindValue(":id_medicamento", $prescricao->__get("id_medicamento"));

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function cancelar($id) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET status_prescricao = 'Cancelada'
                    WHERE id_prescricao = :id_prescricao";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_prescricao", $id);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        return $this->cancelar($id);
    }
}
?>