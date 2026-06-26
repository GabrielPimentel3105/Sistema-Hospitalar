<?php

class Prontuario {
    private $conn;
    private $id_prontuario;
    private $evolucao_clinica;
    private $observacoes;
    private $data_registro;
    private $id_consulta;

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
        $daoPath = __DIR__ . '/../dao/ProntuarioDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('ProntuarioDAO')) {
            return false;
        }

        $dao = new ProntuarioDAO($this->conn);

        if (!method_exists($dao, $metodo)) {
            return false;
        }

        if (empty($argumentos)) {
            if (in_array($metodo, ['create', 'update'])) {
                return $dao->$metodo($this);
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

    public function buscarPaciente(...$argumentos) {
        return $this->executarDAO('buscarPaciente', $argumentos);
    }

    public function buscarConsulta(...$argumentos) {
        return $this->executarDAO('buscarConsulta', $argumentos);
    }

    public function buscarOuCriarPorConsulta(...$argumentos) {
        return $this->executarDAO('buscarOuCriarPorConsulta', $argumentos);
    }

    public function listarExames(...$argumentos) {
        return $this->executarDAO('listarExames', $argumentos);
    }

    public function listarPrescricoes(...$argumentos) {
        return $this->executarDAO('listarPrescricoes', $argumentos);
    }

    public function listarMedicamentos(...$argumentos) {
        return $this->executarDAO('listarMedicamentos', $argumentos);
    }

    public function solicitarExame(...$argumentos) {
        return $this->executarDAO('solicitarExame', $argumentos);
    }

    public function emitirPrescricao(...$argumentos) {
        return $this->executarDAO('emitirPrescricao', $argumentos);
    }

    public function verificarAlertaMedicamento(...$argumentos) {
        return $this->executarDAO('verificarAlertaMedicamento', $argumentos);
    }
}
?>