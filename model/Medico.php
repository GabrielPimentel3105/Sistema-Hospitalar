<?php

class Medico {
    private $conn;
    private $id_medico;
    private $nome;
    private $crm;
    private $especialidade;
    private $telefone;
    private $email;

    public function __construct($db = null) {
        $this->conn = $db;
    }

    public function __get($atributo) {
        return property_exists($this, $atributo) ? $this->$atributo : null;
    }

    public function __set($atributo, $valor) {
        $this->$atributo = $valor;
    }

    private function executarDAO($metodo, $argumentos = []) {
        $daoPath = __DIR__ . '/../dao/MedicoDAO.php';
        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('MedicoDAO')) {
            return false;
        }

        $dao = new MedicoDAO($this->conn);

        if (!method_exists($dao, $metodo)) {
            return false;
        }

        if (empty($argumentos)) {
            if (in_array($metodo, ['create', 'update'])) {
                return $dao->$metodo($this);
            }

            if ($metodo === 'delete') {
                return $dao->delete($this->__get('id_medico'));
            }

            if ($metodo === 'updateStatus') {
                return $dao->updateStatus($this->__get('id_medico'), $this->__get('status_leito'));
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

    public function readOne(...$argumentos) {
        return $this->executarDAO('readOne', $argumentos);
    }

    public function update(...$argumentos) {
        return $this->executarDAO('update', $argumentos);
    }

    public function delete(...$argumentos) {
        return $this->executarDAO('delete', $argumentos);
    }

}
?>
