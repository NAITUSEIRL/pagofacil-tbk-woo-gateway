<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cristian Tala Sánchez < http://cristiantala.cl >.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ctala\transaccion\classes;

use ctala\transaccion\classes\Transaccion;

/**
 * La Clase Response posee los mismos atributos de transacción más 
 * todos los necesarios de una transacción exitosa lo que no implica que lo sea.
 * 
 * Al igual que el objeto anterior, la firma debe de validarse
 *
 * @author ctala
 */
class Response extends Transaccion {

    public $ct_estado;
    public $ct_order_id_mall;
    public $ct_authorization_code;
    public $ct_payment_type_code;
    public $ct_card_number;
    public $ct_card_expiration_date;
    public $ct_shares_number;
    public $ct_accounting_date;
    public $ct_transaction_date;

    /*
     * VALORES QUE PUEDE TENER EL ESTADO DE UNA ORDEN
     */
    public $COMPLETADA = "COMPLETADA";
    public $FALLIDA = "FALLIDA";
    public $ANULADA = "ANULADA";
    public $PENDIENTE = "PENDIENTE";

    function __construct($ct_order_id, $ct_token_tienda, $ct_monto, $ct_token_service, $ct_estado, $ct_authorization_code, $ct_payment_type_code, $ct_card_number, $ct_card_expiration_date, $ct_shares_number, $ct_accounting_date, $ct_transaction_date, $ct_order_id_mall) {

        parent::__construct($ct_order_id, $ct_token_tienda, $ct_monto, $ct_token_service);

        $this->ct_estado = $ct_estado;
        $this->ct_authorization_code = $ct_authorization_code;
        $this->ct_payment_type_code = $ct_payment_type_code;
        $this->ct_card_number = $ct_card_number;
        $this->ct_card_expiration_date = $ct_card_expiration_date;
        $this->ct_shares_number = $ct_shares_number;
        $this->ct_accounting_date = $ct_accounting_date;
        $this->ct_transaction_date = $ct_transaction_date;
        $this->ct_order_id_mall = $ct_order_id_mall;
    }

    function getArray() {
        $resultado = [
            "ct_order_id" => $this->ct_order_id,
            "ct_token_tienda" => $this->ct_token_tienda,
            "ct_monto" => $this->ct_monto,
            "ct_token_service" => $this->ct_token_service,
            "ct_estado" => $this->ct_estado,
            "ct_authorization_code" => $this->ct_authorization_code,
            "ct_payment_type_code" => $this->ct_payment_type_code,
            "ct_card_number" => $this->ct_card_number,
            "ct_card_expiration_date" => $this->ct_card_expiration_date,
            "ct_shares_number" => $this->ct_shares_number,
            "ct_accounting_date" => $this->ct_accounting_date,
            "ct_transaction_date" => $this->ct_transaction_date,
            "ct_order_id_mall" => $this->ct_order_id_mall,
        ];

        ksort($resultado);
        return $resultado;
    }

}
