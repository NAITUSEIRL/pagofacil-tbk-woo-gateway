<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ctala\transaccion\classes;

/**
 * Description of Resultado
 *
 * @author ctala
 */
class Resultado {
    /* @var $response Response */

    public $response;
    public $order_id;
    public $order_id_mall;
    public $fields;

    function __construct() {
        
    }

    function setResponse($response) {
        $this->response = $response;
        $this->fields = array(
            "Orden de compra Mall" => $this->response->ct_order_id_mall,
            "Código de autorización" => $this->response->ct_authorization_code,
            "Tipo de Pago" => $this->response->ct_payment_type_code,
            "Monto" => $this->response->ct_monto,
            "Final Tarjeta" => $this->response->ct_shares_number,
            "Cuotas" => $this->response->ct_shares_number,
            "Fecha Contable" => $this->response->ct_accounting_date,
            "Fecha Transacción" => $this->response->ct_transaction_date,
        );
        $this->setOrder_id($this->response->ct_order_id);
        $this->setOrder_id_mall($this->response->ct_order_id_mall);
        
    }

    function setOrder_id($order_id) {
        $this->order_id = $order_id;
    }

    function setOrder_id_mall($order_id_mall) {
        $this->order_id_mall = $order_id_mall;
    }

    public function pedidoRecibidoResponse() {
        $fields = $this->fields;
        include __DIR__.'/../templates/orden_recibida.php';
    }

    public function pedidoFallido() {
        $order_id = $this->order_id;
        $order_id_mall = $this->order_id_mall;

        include __DIR__.'/../templates/orden_fallida.php';
    }

    function setFields($fields) {
        $this->fields = $fields;
    }

}
