<?php

require_once __DIR__ . '/../model/Exame.php';

class ExameDAO {
    private $conn;
    private $table_name = "exames";

    public function __construct($db) {
        $this->conn = $db;
    }

    private function valorDecimal($valor) {
        if ($valor === null || $valor === '') {
            return 0.00;
        }

        $valor = str_replace(',', '.', $valor);
        return (float) $valor;
    }

    public function create(Exame $exame) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (
                        nome_exame,
                        resultado,
                        data_exame,
                        valor_exame,
                        id_prontuario,
                        status_exame
                    )
                    VALUES
                    (
                        :nome_exame,
                        :resultado,
                        :data_exame,
                        :valor_exame,
                        :id_prontuario,
                        :status_exame
                    )";

            $stmt = $this->conn->prepare($query);

            $nome_exame = htmlspecialchars(strip_tags($exame->__get("nome_exame")));
            $resultado = htmlspecialchars(strip_tags($exame->__get("resultado")));
            $data_exame = htmlspecialchars(strip_tags($exame->__get("data_exame")));
            $valor_exame = $this->valorDecimal($exame->__get("valor_exame"));
            $id_prontuario = htmlspecialchars(strip_tags($exame->__get("id_prontuario")));
            $status_exame = $exame->__get("status_exame") ?: "Solicitado";

            $stmt->bindValue(":nome_exame", $nome_exame);
            $stmt->bindValue(":resultado", $resultado);
            $stmt->bindValue(":data_exame", $data_exame);
            $stmt->bindValue(":valor_exame", $valor_exame);
            $stmt->bindValue(":id_prontuario", $id_prontuario);
            $stmt->bindValue(":status_exame", $status_exame);

            return $stmt->execute();

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
                        e.id_exame,
                        e.nome_exame,
                        e.resultado,
                        e.data_exame,
                        e.valor_exame,
                        e.id_prontuario,
                        e.status_exame,

                        pr.data_registro,

                        pac.id_paciente,
                        pac.nome AS nome_paciente,
                        pac.id_convenio,
                        pac.numero_carteirinha,
                        pac.validade_carteirinha,

                        conv.nome_convenio,

                        med.nome AS nome_medico
                    FROM " . $this->table_name . " e
                    LEFT JOIN prontuario pr ON e.id_prontuario = pr.id_prontuario
                    LEFT JOIN consultas c ON pr.id_consulta = c.id_consulta
                    LEFT JOIN pacientes pac ON c.id_paciente = pac.id_paciente
                    LEFT JOIN convenios conv ON pac.id_convenio = conv.id_convenio
                    LEFT JOIN medicos med ON c.id_medico = med.id_medico
                    ORDER BY e.data_exame DESC, e.id_exame DESC";

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
            $query = "SELECT 
                        id_exame,
                        nome_exame,
                        resultado,
                        data_exame,
                        valor_exame,
                        id_prontuario,
                        status_exame
                    FROM " . $this->table_name . "
                    WHERE id_exame = :id_exame
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_exame", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(Exame $exame) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET
                        nome_exame = :nome_exame,
                        resultado = :resultado,
                        data_exame = :data_exame,
                        valor_exame = :valor_exame,
                        id_prontuario = :id_prontuario,
                        status_exame = :status_exame
                    WHERE id_exame = :id_exame";

            $stmt = $this->conn->prepare($query);

            $id_exame = htmlspecialchars(strip_tags($exame->__get("id_exame")));
            $nome_exame = htmlspecialchars(strip_tags($exame->__get("nome_exame")));
            $resultado = htmlspecialchars(strip_tags($exame->__get("resultado")));
            $data_exame = htmlspecialchars(strip_tags($exame->__get("data_exame")));
            $valor_exame = $this->valorDecimal($exame->__get("valor_exame"));
            $id_prontuario = htmlspecialchars(strip_tags($exame->__get("id_prontuario")));
            $status_exame = $exame->__get("status_exame") ?: "Solicitado";

            $stmt->bindValue(":id_exame", $id_exame);
            $stmt->bindValue(":nome_exame", $nome_exame);
            $stmt->bindValue(":resultado", $resultado);
            $stmt->bindValue(":data_exame", $data_exame);
            $stmt->bindValue(":valor_exame", $valor_exame);
            $stmt->bindValue(":id_prontuario", $id_prontuario);
            $stmt->bindValue(":status_exame", $status_exame);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateStatus($id_exame, $status_exame) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $statusPermitidos = [
                "Solicitado",
                "Em análise",
                "Finalizado",
                "Cancelado"
            ];

            if (!in_array($status_exame, $statusPermitidos)) {
                return false;
            }

            $query = "UPDATE " . $this->table_name . "
                    SET status_exame = :status_exame
                    WHERE id_exame = :id_exame";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":status_exame", $status_exame);
            $stmt->bindValue(":id_exame", $id_exame);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function finalizarComResultado($id_exame, $resultado) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET 
                        resultado = :resultado,
                        status_exame = 'Finalizado'
                    WHERE id_exame = :id_exame";

            $stmt = $this->conn->prepare($query);

            $resultado = htmlspecialchars(strip_tags($resultado));
            $id_exame = htmlspecialchars(strip_tags($id_exame));

            $stmt->bindValue(":resultado", $resultado);
            $stmt->bindValue(":id_exame", $id_exame);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        /*
            Regra de negócio:
            O exame não será excluído fisicamente.
            Em vez disso, será marcado como Cancelado para manter o histórico clínico.
        */
        return $this->updateStatus($id, "Cancelado");
    }
}
?>