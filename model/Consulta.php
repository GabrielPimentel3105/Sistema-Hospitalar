<?php

class Consulta {
    private $conn;
    private $id_consulta;
    private $data_consulta;
    private $horario;
    private $status_consulta;
    private $id_paciente;
    private $id_medico;
    private $id_sala;
    private $id_convenio;

    public function __construct($db = null) {
        $this->conn = $db;
    }

    public function __get($atributo) {
        return property_exists($this, $atributo) ? $this->$atributo : null;
    }

    public function __set($atributo, $valor) {
        if (property_exists($this, $atributo)) {
            $this->$atributo = $valor;
        }
    }

    private function executarDAO($metodo, $argumentos = []) {
        $daoPath = __DIR__ . '/../dao/ConsultaDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('ConsultaDAO')) {
            return false;
        }

        $dao = new ConsultaDAO($this->conn);

        if (!method_exists($dao, $metodo)) {
            return false;
        }

        if (empty($argumentos)) {
            if (in_array($metodo, ['create', 'update'])) {
                return $dao->$metodo($this);
            }

            if ($metodo === 'delete') {
                return $dao->delete($this->__get('id_consulta'));
            }
        }

        return call_user_func_array([$dao, $metodo], $argumentos);
    }

    public function __call($metodo, $argumentos) {
        return $this->executarDAO($metodo, $argumentos);
    }

    public function create(...$argumentos) {
        return $this->executarDAO('create', $argumentos);
    }

    public function update(...$argumentos) {
        return $this->executarDAO('update', $argumentos);
    }

    public function read(...$argumentos) {
        return $this->executarDAO('read', $argumentos);
    }

    public function readAgendamentos(...$argumentos) {
        return $this->executarDAO('readAgendamentos', $argumentos);
    }

    public function readFilaEspera(...$argumentos) {
        return $this->executarDAO('readFilaEspera', $argumentos);
    }

    public function readTriagens(...$argumentos) {
        return $this->executarDAO('readTriagens', $argumentos);
    }

    public function readHistoricoLiberados(...$argumentos) {
        return $this->executarDAO('readHistoricoLiberados', $argumentos);
    }

    public function readProximasConsultas(...$argumentos) {
        return $this->executarDAO('readProximasConsultas', $argumentos);
    }

    public function readAlertasManchester(...$argumentos) {
        return $this->executarDAO('readAlertasManchester', $argumentos);
    }

    public function countPendentes(...$argumentos) {
        return $this->executarDAO('countPendentes', $argumentos);
    }

    public function countConsultasHoje(...$argumentos) {
        return $this->executarDAO('countConsultasHoje', $argumentos);
    }

    public function createTriagem(...$argumentos) {
        return $this->executarDAO('createTriagem', $argumentos);
    }

    public function liberarPaciente(...$argumentos) {
        return $this->executarDAO('liberarPaciente', $argumentos);
    }

    public function existeConflito(...$argumentos) {
        return $this->executarDAO('existeConflito', $argumentos);
    }
}
?>