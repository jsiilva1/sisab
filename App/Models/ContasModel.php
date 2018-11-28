<?php

namespace App\Models;

use App\Sisab\Conta;
use PDO;

class ContasModel extends \Core\Model {

    private static $db_instance;

    static public function getAll () {
        $db = static::getConnection();

        $sql = 'SELECT c.*, a.id_agencia, a.numero_agencia FROM contas c JOIN agencias a ON a.id_agencia = c.fk_id_agencia';

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();

            $contas_list = $stmt->fetchAll(PDO::FETCH_OBJ);

            return $contas_list;

            $db = null;
        } catch (\PDOException $e) {
            throw new \Exception("Erro model");
        }
    }

    static public function create (Conta $conta) {

        $db = static::getConnection();

        $sql = 'INSERT INTO contas (numero, saldo, limite, rendimento, tipo) VALUES (?, ?, ?, ?, ?)';

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $conta->getNumero(), PDO::PARAM_STR);
            $stmt->bindValue(2, $conta->getSaldo(), PDO::PARAM_STR);
            $stmt->bindValue(3, $conta->getLimite(), PDO::PARAM_STR);
            $stmt->bindValue(4, $conta->getRe(), PDO::PARAM_INT);

            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }

            $db = null;
        } catch (\PDOException $e) {
            throw new \Exception("Erro model");
        }
    }
}