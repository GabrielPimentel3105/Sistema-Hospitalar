<?php

class Internacao {
    private $conn;
    private $id_internacao;
    private $data_entrada;
    private $data_alta;
    private $status_internacao;
    private $id_paciente;
    private $id_leito;

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
        $daoPath = __DIR__ . '/../dao/InternacaoDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('InternacaoDAO')) {
            return false;
        }

        $dao = new InternacaoDAO($this->conn);

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

    public function read(...$argumentos) {
        return $this->executarDAO('read', $argumentos);
    }

    public function readAtivas(...$argumentos) {
        return $this->executarDAO('readAtivas', $argumentos);
    }

    public function darAlta(...$argumentos) {
        return $this->executarDAO('darAlta', $argumentos);
    }

    public function transferir(...$argumentos) {
        return $this->executarDAO('transferir', $argumentos);
    }

    public function cancelar(...$argumentos) {
        return $this->executarDAO('cancelar', $argumentos);
    }

    public function countAtivas(...$argumentos) {
        return $this->executarDAO('countAtivas', $argumentos);
    }
}
?>