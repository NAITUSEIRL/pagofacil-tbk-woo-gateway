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

/**
 * Description of Transaccion
 *
 * @author ctala
 */
class Transaccion {
    /*
     * Estas son las variables básicas de una transaccion.
     */

    public $ct_order_id;
    //Esta variable es la que usaremos como "idSession" de transbank
    public $ct_token_tienda;
    public $ct_monto;
    public $ct_token_service;
    public $ct_email;
    public $ct_firma;
    //Esta es la variable con la que firmaremos el mensaje
    private $ct_token_secret;

    function __construct($ct_order_id, $ct_token_tienda, $ct_monto, $ct_token_service, $ct_email = NULL) {
        $this->ct_order_id = $ct_order_id;
        $this->ct_token_tienda = $ct_token_tienda;
        $this->ct_monto = $ct_monto;
        $this->ct_token_service = $ct_token_service;
        $this->ct_email = $ct_email;
    }

    function setCt_token_secret($ct_token_secret) {
        $this->ct_token_secret = $ct_token_secret;
    }

    function getArrayResponse() {
        $arreglo = $this->getArray();
        $arreglo["ct_firma"] = $this->firmarArreglo($arreglo);
        return $arreglo;
    }

    function getArray() {
        $resultado = [
            "ct_order_id" => $this->ct_order_id,
            "ct_token_tienda" => $this->ct_token_tienda,
            "ct_monto" => $this->ct_monto,
            "ct_token_service" => $this->ct_token_service
        ];

        if ($this->ct_email !== NULL) {
            $resultado["ct_email"] = $this->ct_email;
        }

        ksort($resultado);
        return $resultado;
    }

    function firmarArreglo($arreglo) {

        //Ordeno Arreglo
        ksort($arreglo);
        //Concateno Arreglo
        $mensaje = $this->concatenarArreglo($arreglo);

        //Firmo Mensaje
        $mensajeFirmado = $this->firmarMensaje($mensaje, $this->ct_token_secret);

        //Guardo y retorno el mensaje firmado
        $this->ct_firma = $mensajeFirmado;
        return $mensajeFirmado;
    }

    function firmarMensaje($mensaje, $claveCifrado) {
        $mensajeFirmado = hash_hmac('sha256', $mensaje, $claveCifrado);
        return $mensajeFirmado;
    }

    public function concatenarArreglo($arreglo) {
        $resultado = "";

        foreach ($arreglo as $field => $value) {
            $resultado .= $field . $value;
        }

        return $resultado;
    }

}
