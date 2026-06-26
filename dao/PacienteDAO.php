<?php

class PacienteDAO {
    private $conn;
    private $table_name = "pacientes";

    public function __construct($db) {
        $this->conn = $db;
    }

    private function normalizarData($data) {
        return !empty($data) ? $data : null;
    }

    private function normalizarConvenio($idConvenio) {
        return !empty($idConvenio) ? $idConvenio : null;
    }

    public function create(Paciente $paciente) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (
                        nome,
                        cpf,
                        data_nascimento,
                        telefone,
                        endereco,
                        alergias,
                        historico_clinico,
                        tipo_sanguineo,
                        id_convenio,
                        numero_carteirinha,
                        validade_carteirinha,
                        status_paciente
                    ) 
                    VALUES 
                    (
                        :nome,
                        :cpf,
                        :data_nascimento,
                        :telefone,
                        :endereco,
                        :alergias,
                        :historico_clinico,
                        :tipo_sanguineo,
                        :id_convenio,
                        :numero_carteirinha,
                        :validade_carteirinha,
                        :status_paciente
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome", $paciente->__get("nome"));
            $stmt->bindValue(":cpf", $paciente->__get("cpf"));
            $stmt->bindValue(":data_nascimento", $this->normalizarData($paciente->__get("data_nascimento")));
            $stmt->bindValue(":telefone", $paciente->__get("telefone"));
            $stmt->bindValue(":endereco", $paciente->__get("endereco"));
            $stmt->bindValue(":alergias", $paciente->__get("alergias"));
            $stmt->bindValue(":historico_clinico", $paciente->__get("historico_clinico"));
            $stmt->bindValue(":tipo_sanguineo", $paciente->__get("tipo_sanguineo"));
            $stmt->bindValue(":id_convenio", $this->normalizarConvenio($paciente->__get("id_convenio")));
            $stmt->bindValue(":numero_carteirinha", $paciente->__get("numero_carteirinha"));
            $stmt->bindValue(":validade_carteirinha", $this->normalizarData($paciente->__get("validade_carteirinha")));
            $stmt->bindValue(":status_paciente", $paciente->__get("status_paciente") ?: "Ativo");

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;

        } catch(PDOException $e) {
            return false;
        }
    }

    public function read() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        p.*,
                        c.nome_convenio
                    FROM " . $this->table_name . " p
                    LEFT JOIN convenios c ON p.id_convenio = c.id_convenio
                    ORDER BY 
                        CASE 
                            WHEN p.status_paciente = 'Ativo' THEN 1 
                            ELSE 2 
                        END,
                        p.nome ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readOne($id) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT 
                        p.*,
                        c.nome_convenio,
                        c.procedimentos_autorizados
                    FROM " . $this->table_name . " p
                    LEFT JOIN convenios c ON p.id_convenio = c.id_convenio
                    WHERE p.id_paciente = :id
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            return null;
        }
    }

    public function update(Paciente $paciente) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET 
                        nome = :nome,
                        cpf = :cpf,
                        data_nascimento = :data_nascimento,
                        telefone = :telefone,
                        endereco = :endereco,
                        alergias = :alergias,
                        historico_clinico = :historico_clinico,
                        tipo_sanguineo = :tipo_sanguineo,
                        id_convenio = :id_convenio,
                        numero_carteirinha = :numero_carteirinha,
                        validade_carteirinha = :validade_carteirinha,
                        status_paciente = :status_paciente
                    WHERE id_paciente = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome", $paciente->__get("nome"));
            $stmt->bindValue(":cpf", $paciente->__get("cpf"));
            $stmt->bindValue(":data_nascimento", $this->normalizarData($paciente->__get("data_nascimento")));
            $stmt->bindValue(":telefone", $paciente->__get("telefone"));
            $stmt->bindValue(":endereco", $paciente->__get("endereco"));
            $stmt->bindValue(":alergias", $paciente->__get("alergias"));
            $stmt->bindValue(":historico_clinico", $paciente->__get("historico_clinico"));
            $stmt->bindValue(":tipo_sanguineo", $paciente->__get("tipo_sanguineo"));
            $stmt->bindValue(":id_convenio", $this->normalizarConvenio($paciente->__get("id_convenio")));
            $stmt->bindValue(":numero_carteirinha", $paciente->__get("numero_carteirinha"));
            $stmt->bindValue(":validade_carteirinha", $this->normalizarData($paciente->__get("validade_carteirinha")));
            $stmt->bindValue(":status_paciente", $paciente->__get("status_paciente") ?: "Ativo");
            $stmt->bindValue(":id", $paciente->__get("id_paciente"));

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        if ($this->conn === null) {
            return false;
        }

        try {
            /*
             * Em sistema hospitalar, não é seguro apagar definitivamente um paciente,
             * pois ele pode possuir consultas, triagens, prontuários, exames,
             * prescrições, internações e faturamentos vinculados.
             *
             * Por isso, o método delete realiza uma inativação lógica.
             */
            $query = "UPDATE " . $this->table_name . " 
                    SET status_paciente = 'Inativo'
                    WHERE id_paciente = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id", $id);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function countInternados() {
        if ($this->conn === null) {
            return 0;
        }

        try {
            $query = "SELECT COUNT(*) AS total 
                    FROM internacoes 
                    WHERE status_internacao = 'Ativa' 
                    AND data_alta IS NULL";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['total'] ?? 0;

        } catch(PDOException $e) {
            return 0;
        }
    }
}
?>