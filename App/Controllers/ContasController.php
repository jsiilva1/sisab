<?php

namespace App\Controllers;

use App\Models\AgenciasModel;
use App\Models\ContasModel;
use App\Sisab\ContaCorrente;
use App\Sisab\ContaEspecial;
use App\Sisab\ContaPoupanca;
use App\Sisab\Exception\EstouroSaldoException;
use App\Sisab\Exception\ModelException;
use Core\Util\HttpHelpers;
use Core\View;

class ContasController extends \Core\Controller {

    public function indexAction () {
        $agencias = AgenciasModel::getAll();

        View::renderTemplate('Contas/index', [
            'agencias' => $agencias
        ]);
    }


    public function listar () {

        try {
            $contas = ContasModel::getAll();
        } catch (\PDOException $e) {
            $contas = null;
            $flashMessage = $e->getMessage();
            $alert = 'danger';
        }

        View::renderTemplate('Contas/listar', [
            'contas' => $contas,
            'quantidade' => ($contas) ? count($contas) : 'undefined',
            'notification' => [
                'newMessage' => true,
                'state' => true,
                'flashMessage' => isset($flashMessage) ? $flashMessage : false,
                'flashAlert' => isset($alert) ? $alert : false
            ]
        ]);
    }
    public function criarAction () {

        // Obtem os dados do formulário pela Query String do Request
        $numero = (isset($_REQUEST['numero'])) ? $_REQUEST['numero'] : null;
        $limite = (isset($_REQUEST['limite'])) ? $_REQUEST['limite'] : null;
        $rendimento = (isset($_REQUEST['rendimento'])) ? $_REQUEST['rendimento'] : null;
        $tipo = (isset($_REQUEST['tipo'])) ? $_REQUEST['tipo'] : 'CONTA_POUPANCA';
        $id_agencia = (isset($_REQUEST['agencia'])) ? $_REQUEST['agencia'] : 0;

        switch ($tipo) {
            case 'CONTA_POUPANCA':
                $conta = new ContaPoupanca($numero, $tipo, $id_agencia, $rendimento);
                break;

            case 'CONTA_CORRENTE':
                $conta = new ContaCorrente($numero, $tipo, $id_agencia);
                break;

            case 'CONTA_ESPECIAL':
                $conta = new ContaEspecial($numero, $tipo, $id_agencia, $limite);
                break;

            default:
                $conta = new ContaPoupanca($numero, $tipo, $id_agencia, $rendimento);
                $tipo = 'CONTA_POUPANCA';
        }

        try {
            $state = ContasModel::create($conta);

            // Controle as flash messages baseado no retorno do Model
            if ($state):
                $flashMessage = 'A conta foi criada com sucesso!';
                $alert = 'success';
            endif;

        } catch (ModelException $e) {
            $flashMessage = "OPA! Houve algum erro ao criar a conta";
            $alert = 'danger';
        }

        // Renderiza o template implantando as variáveis de controle
        View::renderTemplate('Contas/index', [
            'flashMessage' => $flashMessage,
            'flashAlert' => $alert,
            'newMessage' => true,
            'quantidade_agencias' => 'undefined'
        ]);
    }

    public function deletarAction () {

        $id_conta = HttpHelpers::getId($_SERVER['QUERY_STRING']);

        try {
            $state = ContasModel::delete($id_conta);

            $notification = [
                'newMessage' => true,
                'state' => true,
                'redirect' => true,
                'flashMessage' => 'A conta foi deletada com sucesso!',
                'flashAlert' => 'success'
            ];
        } catch (\PDOException $e) {
            $notification = [
                'newMessage' => true,
                'state' => false,
                'quantidade' => 'undefined',
                'redirect' => true,
                'flashMessage' => $e->getMessage(),
                'flashAlert' => 'danger'
            ];
        }

        // Renderiza o template implantando as variáveis de controle
        View::renderTemplate('Contas/listar', [
            'notification' => $notification,
            'quantidade' => 'undefined'
        ]);
    }

    public function editarAction () {

        // Obtem os dados do formulário pela Query String do Request
        $id_conta = (isset($_REQUEST['cid'])) ? $_REQUEST['cid'] : null;
        $numero = (isset($_REQUEST['numero'])) ? $_REQUEST['numero'] : null;
        $saldo = (isset($_REQUEST['saldo'])) ? $_REQUEST['saldo'] : null;
        $limite = (isset($_REQUEST['limite'])) ? $_REQUEST['limite'] : null;
        $rendimento = (isset($_REQUEST['rendimento'])) ? $_REQUEST['rendimento'] : null;
        $tipo = (isset($_REQUEST['tipo'])) ? $_REQUEST['tipo'] : null;

        $sign = (isset($_REQUEST['sign'])) ? $_REQUEST['sign'] : null;

        if (!isset($sign) || $sign != 'do') {
            View::renderTemplate("Contas/editar", [
                'id_conta' => $id_conta,
                'numero' => $numero,
                'saldo' => $saldo,
                'limite' => $limite,
                'rendimento' => $rendimento,
                'tipo' => $tipo
            ]);
        } else {

            $conta = new ContaCorrente($numero, $tipo);
            $conta->setId($id_conta);

            try {

                $state = ContasModel::update($conta);

                // Controle as flash messages baseado no retorno do Model
                if ($state):
                    $flashMessage = 'A conta foi atualizada com sucesso!';
                    $alert = 'success';
                else:
                    $flashMessage = 'OPA! Houve algum erro ao atualizar a conta!';
                    $alert = 'danger';
                endif;

                View::renderTemplate("Contas/listar", [
                    'flashMessage' => $flashMessage,
                    'flashAlert' => $alert,
                    'newMessage' => true
                ]);

            } catch (ModelException $e) {}

        }
    }

    public function depositoAction () {
        $contas = ContasModel::getAll();

        View::renderTemplate('Contas/deposito/index', [
            'contas' => $contas
        ]);
    }

    public function transferenciaAction () {
        $contas = ContasModel::getAll();

        View::renderTemplate('Contas/transferencia/index', [
            'contas' => $contas
        ]);
    }

    public function saqueAction () {
        $contas = ContasModel::getAll();

        View::renderTemplate('Contas/saque/index', [
            'contas' => $contas
        ]);
    }

    public function extratoAction () {

        $id = HttpHelpers::getId($_SERVER['QUERY_STRING']);

        try {
            $conta = ContasModel::getById($id);
        } catch (\PDOException $e) {
            $conta = null;
            $flashMessage = $e->getMessage();
            $alert = 'danger';
        }

        View::renderTemplate('Extrato/index', [
            'conta' => $conta,
            'notification' => [
                'newMessage' => true,
                'error' => ($conta == null) ? true : false,
                'flashMessage' => isset($flashMessage) ? $flashMessage : false,
                'flashAlert' => isset($alert) ? $alert : false
            ]
        ]);
    }

    public function operacaoAction () {

        // Obtem os dados do formulário pela Query String do Request
        $agencia = (isset($_REQUEST['agencia'])) ? $_REQUEST['agencia'] : false;
        $id_conta = (isset($_REQUEST['conta'])) ? $_REQUEST['conta'] : null;
        $valor = (isset($_REQUEST['valor'])) ? $_REQUEST['valor'] : 0;
        $operacao = (isset($_REQUEST['operacao'])) ? $_REQUEST['operacao'] : 0;
        $id_conta_origem = (isset($_REQUEST['conta_origem'])) ? $_REQUEST['conta_origem'] : 0;
        $id_conta_destino = (isset($_REQUEST['conta_destino'])) ? $_REQUEST['conta_destino'] : 0;

        switch ($operacao) {

            case 'deposito':

                try {
                    $state = ContasModel::deposito($id_conta, $valor);

                    // Controle as flash messages baseado no retorno do Model
                    if ($state):
                        $flashMessage = "O depósito de R$ ${valor} foi transacionado com sucesso!";
                        $alert = 'success';
                    endif;

                } catch (ModelException $e) {
                    $flashMessage = "Erro ao transacionar este depósito de R$ ${valor}. Tente novamente mais tarde!";
                    $alert = 'danger';
                }

                break;

            case 'saque':

                try {
                    $state = ContasModel::saque($id_conta, $valor);

                    // Controle as flash messages baseado no retorno do Model
                    if ($state):
                        $flashMessage = "O saque de R$ ${valor} foi transacionado com sucesso!";
                        $alert = 'success';
                    else:
                        $flashMessage = "Erro ao transacionar este saque de R$ ${valor}. Verifique seu saldo e tente novamente mais tarde!";
                        $alert = 'danger';
                    endif;

                } catch (\PDOException $e) {
                    $flashMessage = "Erro ao transacionar este saque de R$ ${valor}. Verifique seu saldo e tente novamente mais tarde!";
                    $alert = 'danger';
                } catch (EstouroSaldoException $e) {
                    $flashMessage = $e->getMessage();
                    $alert = 'danger';
                }

                break;

            case 'transferencia':

                if ($id_conta_origem == $id_conta_destino) {
                    $flashMessage = "Erro ao transacionar esta transferência de R$ ${valor}. A conta de origem não pode ser igual a conta de destino.";
                    $alert = 'danger';
                } else {
                    try {
                        $state = ContasModel::transferencia($id_conta_origem, $id_conta_destino, $valor);

                        // Controle as flash messages baseado no retorno do Model
                        if ($state):
                            $flashMessage = "A transferência de R$ ${valor} foi transacionada entre as contas com sucesso!";
                            $alert = 'success';
                        else:
                            $flashMessage = "Erro ao transacionar esta transferência de R$ ${valor}. Tente novamente mais tarde.";
                            $alert = 'danger';
                        endif;

                    } catch (\PDOException $e) {
                        $flashMessage = "Erro ao transacionar esta transferência de R$ ${valor}. Tente novamente mais tarde.";
                        $alert = 'danger';
                    }
                }

                break;

            default:
                echo 'Operação inválida!';
        }

        if ($operacao == 'deposito') {
            $view = 'Contas/deposito/index';
        } else if ($operacao == 'saque') {
            $view = 'Contas/saque/index';
        } else if ($operacao == 'transferencia') {
            $view = 'Contas/transferencia/index';
        }

        View::renderTemplate($view, [
            'flashMessage' => $flashMessage,
            'flashAlert' => $alert,
            'newMessage' => true,
            'tipo' => 'operacao',
            'operacao' => $operacao,
            'id_conta' => $id_conta,
            'quantidade_agencias' => 'undefined'
        ]);
    }
}