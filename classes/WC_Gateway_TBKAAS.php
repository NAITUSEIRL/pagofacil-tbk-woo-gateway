<?php

/*
 * The MIT License
 *
 * Copyright 2016 ctala.
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

namespace tbkaaswoogateway\classes;

use tbkaaswoogateway\classes\Logger;
use WC_Order;
use ctala\transaccion\classes\Transaccion;
use ctala\transaccion\classes\Response;

/**
 * Description of WC_Gateway_TBKAAS
 *
 * @author ctala
 */
class WC_Gateway_TBKAAS extends \WC_Payment_Gateway {

    var $notify_url;
    var $tbkaas_base_url;
    var $token_service;
    var $token_secret;

    function __construct() {
        $this->id = 'tbkaas';
        $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/../assets/images/logo.png';
        $this->has_fields = false;
        $this->method_title = 'PagoFácil.org - WebpayPlus';
        $this->notify_url = WC()->api_request_url('WC_Gateway_TBKAAS');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $modo_desarrollo = $this->get_option('desarrollo');
        if ($modo_desarrollo === "yes") {
            $this->tbkaas_base_url = SERVER_DESARROLLO;
        } else {
            $this->tbkaas_base_url = SERVER_PRODUCCION;
        }

        $this->token_service = $this->get_option('token_service');
        $this->token_secret = $this->get_option('token_secret');

        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        //Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'tbkaas_api_handler'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'tbkaas_thankyou_page'));
    }

    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Habilita PagoFácil - WebpayPlus', 'woocommerce'),
                'default' => 'yes'
            ),
            'desarrollo' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Habilita el modo de pruebas', 'woocommerce'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('', 'woocommerce'),
                'default' => __('WebpayPlust', 'woocommerce')
            ),
            'description' => array(
                'title' => __('Customer Message', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Mensaje que recibirán los clientes al seleccionar el medio de pago'),
                'default' => __('Sistema de pago con tarjetas de crédito y débito chilenas.'),
            ),
            'token_service' => array(
                'title' => "Token Servicio",
                'type' => 'text',
                'description' => "El token asignado al servicio creado en PagoFacil.org.",
                'default' => "",
            ),
            'token_secret' => array(
                'title' => "Token Secret",
                'type' => 'text',
                'description' => "Con esto codificaremos la información a enviar.",
                'default' => "",
            ),
            'redirect' => array(
                'title' => __('Redirección Automática'),
                'type' => 'checkbox',
                'label' => __('Si / No'),
                'default' => 'yes'
            ),
            'trade_name' => array(
                'title' => __('Nombre del Comercio', 'woocommerce'),
                'type' => 'text',
                'description' => __('Trade Name like : EmpresasCTM', 'woocommerce'),
                'default' => __('EmpresasCTM', 'woocommerce')
            ),
            'url_commerce' => array(
                'title' => __('URL Comercio', 'woocommerce'),
                'type' => 'text',
                'description' => __('Url Commerce like : http://www.empresasctm.cl', 'woocommerce'),
                'default' => __('http://www.empresasctm.cl', 'woocommerce')
            ),
        );
    }

    /*
     * Esta función es necesaria para poder generar el pago.
     */

    function process_payment($order_id) {
        $sufijo = "[TBKAAS - PROCESS - PAYMENT]";
        Logger::log_me_wp("Iniciando el proceso de pago para $order_id", $sufijo);

        $order = new WC_Order($order_id);
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    /*
     * Esta función es necesaria por parte de la herencia del Gateway para redireccionar a la página de pago.
     * Si la doble validación está activa se revisará en este punto también para no iniciar el proceso
     * de no ser necesario
     */

    function receipt_page($order_id) {
        $sufijo = "[RECEIPT]";
        $DOBLEVALIDACION = $this->get_option('doblevalidacion');
        $order = new WC_Order($order_id);
        if ($DOBLEVALIDACION === "yes") {

            log_me("Doble Validación Activada / " . $order->status, $sufijo);
            if ($order->status === 'processing' || $order->status === 'completed') {
                Logger::log_me_wp("ORDEN YA PAGADA (" . $order->status . ") EXISTENTE " . $order_id, "\t" . $sufijo);
                // Por solicitud muestro página de fracaso.
//                $this->paginaError($order_id);
                return false;
            }
        } else {
            Logger::log_me_wp("Doble Validación Desactivada / " . $order->status, $sufijo);
        }

        echo '<p>' . __('Gracias! - Tu orden ahora está pendiente de pago. Deberías ser redirigido automáticamente a la página de transbank.') . '</p>';
        echo $this->generate_TBKAAS_form($order_id);
    }

    function generate_TBKAAS_form($order_id) {



        $formPostAddress = $this->tbkaas_base_url . SERVER_TBKAAS;
        Logger::log_me_wp($formPostAddress);

        $SUFIJO = "[WEBPAY - FORM]";

        $order = new WC_Order($order_id);


        /*
         * Este es el token que representará la transaccion.
         */
        $token_tienda = (bin2hex(random_bytes(30)));

        /*
         * Agregamos el id de sesion la OC.
         * Esto permitira que validemos el pago mas tarde
         * Este valor no cambiara para la OC si est que ya está Creado
         * 
         */
        $token_tienda_db = get_post_meta($order_id, "_token_tienda", true);
        Logger::log_me_wp($token_tienda_db);
        if (is_null($token_tienda_db) || $token_tienda_db == "") {
            Logger::log_me_wp("No existe TOKEN, lo agrego");
            add_post_meta($order_id, '_token_tienda', $token_tienda, true);
        } else {
            Logger::log_me_wp("Existe session");
            $token_tienda = $token_tienda_db;
        }

        $monto = round($order->order_total);

        //_billing_email
        $email = $order->billing_email;
        $transaccion = new Transaccion($order_id, $token_tienda, $monto, $this->token_service, $email);
        $transaccion->setCt_token_secret($this->token_secret);

        $pago_args = $transaccion->getArrayResponse();


        Logger::log_me_wp($pago_args, $SUFIJO);

        foreach ($pago_args as $key => $value) {
            $webpayplus_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }


        $AUTOREDIRECT = $this->get_option('redirect');
        Logger::log_me_wp("Redirección Automática : " . $AUTOREDIRECT, $SUFIJO);

        if ($AUTOREDIRECT === "yes") {


            /*
             * Esto hace que sea enviada automáticamente el formulario.
             */
            wc_enqueue_js('
            $.blockUI({
                            message: "' . esc_js(__('Gracias por tu orden. Estamos redireccionando a Transbank')) . '",
                            baseZ: 99999,
                            overlayCSS:
                            {
                                    background: "#fff",
                                    opacity: 0.6
                            },
                            css: {
                                    padding:        "20px",
                                    zindex:         "9999999",
                                    textAlign:      "center",
                                    color:          "#555",
                                    border:         "3px solid #aaa",
                                    backgroundColor:"#fff",
                                    cursor:         "wait",
                                    lineHeight:		"24px",
                            }
                    });
            jQuery("#submit_tbkaas_payment_form").click();
		');
        }

        /*
         * La variable resultado tiene el formulario que es enviado a transbank. ( Todo el <FORM> )
         */
        $resultado = '<form action="' . esc_url($formPostAddress) . '" method="post" id="tbkaas_payment_form" target="_top">';
        $resultado .= implode('', $webpayplus_args_array);
        $resultado .= '<!-- Button Fallback -->
                        <div class="payment_buttons">
                        <input type="submit" class="button alt" id="submit_tbkaas_payment_form" value="' . __('Pago via WebpayPlus') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
                        </div>';
        if ($AUTOREDIRECT === "yes") {

            $resultado .= '
                                <script type="text/javascript">
					jQuery(".payment_buttons").hide();
				</script>';
            $resultado .= '</form>';
        } else {
            $resultado .= '</form>';
            wc_enqueue_js(
                    "$('#submit_tbkaas_payment_form').click(function(){
                       $('#submit_tbkaas_payment_form').attr('disabled', true);	
                   });"
            );
        }

        return $resultado;
    }

    /*
     * Proceso el post desde TBKAAS
     * Obtenemos order_id
     * Obtenemos el session_id
     */

    function tbkaas_api_handler() {

        /*
         * Si llegamos por post verificamos, si no redireccionamos a error.
         */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            /*
             * Revisamos si es callback
             */

            $esCallback = isset($_GET["callback"]);
            if ($esCallback) {
                $this->procesarCallback(INPUT_POST);
            } else {
                $this->procesoCompletado(INPUT_POST);
            }
        } else {
            $helper = new HTTPHelper();
            $helper->my_http_response_code(405);
        }
    }

    function tbkaas_thankyou_page($order_id) {
        Logger::log_me_wp("Entrando a Pedido Recibido de $order_id");
        $order = new WC_Order($order_id);

        if ($order->status === 'processing' || $order->status === 'complete') {
            include( plugin_dir_path(__FILE__) . '../templates/order_recibida.php');
        } else {
            $order_id_mall = get_post_meta($order_id, "_order_id_mall", true);
            include( plugin_dir_path(__FILE__) . '../templates/orden_fallida.php');
        }
    }

    private function procesoCompletado($POST) {
        Logger::log_me_wp("Iniciando el proceso completado ");
        /*
         * Revisamos si existe el parámetro DUPLICADA
         */
        $duplicada = filter_input(INPUT_POST, "DUPLICADA");

        if ($duplicada) {
            //Si llegamos acá se intentó pagar una orden duplicada.
            //Mostramos la página de rechazo.
            Logger::log_me_wp("Se intentó el pago de una OC duplicada");
            get_header();
            include( plugin_dir_path(__FILE__) . '../templates/orden_fallida.php');
            get_footer();
            exit();
        }

        $order_id = filter_input($POST, "ct_order_id");
        $order_id_mall = filter_input($POST, "ct_order_id_mall");
        $order_estado = filter_input($POST, "ct_estado");


        Logger::log_me_wp("ORDER _id = $order_id");
        Logger::log_me_wp("ORDER _id_mall = $order_id_mall");
        Logger::log_me_wp("ORDER _estado = $order_estado");

        Logger::log_me_wp($_POST);

        //Verificamos que la orden exista 
        $order = new WC_Order($order_id);
        if (!($order)) {
            return;
        }



        //Revisamos si ya está completada, si lo está no acemos nada.

        if ($order->status != "completed") {
            $this->procesarCallback($POST, FALSE);
        }

        //Si no aparece completada y el resultado es COMPLETADA cambiamos el estado y agregamos datos.

        /*
         * Redireccionamos.
         */
        $order_received = $order->get_checkout_order_received_url();
        wp_redirect($order_received);
        exit;
    }

    private function procesarCallback($POST, $return = true) {
        $http_helper = new HTTPHelper();
        $order_id = filter_input($POST, "ct_order_id");
        //Verificamos que la orden exista 
        $order = new WC_Order($order_id);
        if (!($order)) {
            if ($return) {
                $http_helper->my_http_response_code(404);
            }

            return;
        }

        //Si la orden está completada no hago nada.
        if ($order->status === 'completed') {
            if ($return) {
                $http_helper->my_http_response_code(400);
            }
            return;
        }


        $response = $this->getResponseFromPost($POST, $order_id);
        $ct_firma = filter_input($POST, "ct_firma");
        $ct_estado = filter_input($POST, "ct_estado");


        $response->setCt_token_secret($this->token_secret);

        $arregloFirmado = $response->getArrayResponse();

        Logger::log_me_wp("Arreglo Firmado : ");
        Logger::log_me_wp($arregloFirmado);
        Logger::log_me_wp("Accounting Date = " . $response->ct_accounting_date);

        if ($arregloFirmado["ct_firma"] == $ct_firma) {
            Logger::log_me_wp("Firmas Corresponden");
            /*
             * Si el mensaje está validado verifico que la orden sea haya completado.
             * Si se completó la marco como completa y agrego los meta datos
             */
            $ct_estado = $response->ct_estado;
            Logger::log_me_wp("ESTADO DE LA ORDEN : $ct_estado");

            if ($ct_estado == "COMPLETADA") {
                //Marcar Completa
                $order->payment_complete();
                //Agregar Meta
                $this->addMetaFromResponse($response, $order_id);
                Logger::log_me_wp("Orden $order_id marcada completa");
                if ($return) {
                    $http_helper->my_http_response_code(200);
                }
            } else {
                $order->update_status('failed', "El pago del pedido no fue exitoso.");
                add_post_meta($order_id, '_order_id_mall', $response->ct_order_id_mall, true);
                if ($return) {
                    $http_helper->my_http_response_code(200);
                }
            }
        } else {
            Logger::log_me_wp("Firmas NO Corresponden");
            if ($return) {
                $http_helper->my_http_response_code(400);
            }
        }
    }

    private function addMetaFromResponse(Response $response, $order_id) {
        add_post_meta($order_id, '_order_id_mall', $response->ct_order_id_mall, true);
        add_post_meta($order_id, '_authorization_code', $response->ct_authorization_code, true);
        add_post_meta($order_id, '_payment_type_code', $response->ct_payment_type_code, true);
        add_post_meta($order_id, '_amount', $response->ct_monto, true);
        add_post_meta($order_id, '_card_number', $response->ct_card_number, true);
        add_post_meta($order_id, '_shares_number', $response->ct_shares_number, true);
        add_post_meta($order_id, '_accounting_date', $response->ct_accounting_date, true);
        add_post_meta($order_id, '_transaction_date', $response->ct_transaction_date, true);
    }

    private function getResponseFromPost($POST, $order_id) {
        $ct_order_id = $order_id;
        $ct_token_tienda = filter_input($POST, "ct_token_tienda");
        $ct_monto = filter_input($POST, "ct_monto");
        $ct_token_service = filter_input($POST, "ct_token_service");
        $ct_estado = filter_input($POST, "ct_estado");
        $ct_authorization_code = filter_input($POST, "ct_authorization_code");
        $ct_payment_type_code = filter_input($POST, "ct_payment_type_code");
        $ct_card_number = filter_input($POST, "ct_card_number");
        $ct_card_expiration_date = filter_input($POST, "ct_card_expiration_date");
        $ct_shares_number = filter_input($POST, "ct_shares_number");
        $ct_accounting_date = filter_input($POST, "ct_accounting_date");
        $ct_transaction_date = filter_input($POST, "ct_transaction_date");
        $ct_order_id_mall = filter_input($POST, "ct_order_id_mall");


        $response = new Response($ct_order_id, $ct_token_tienda, $ct_monto, $ct_token_service, $ct_estado, $ct_authorization_code, $ct_payment_type_code, $ct_card_number, $ct_card_expiration_date, $ct_shares_number, $ct_accounting_date, $ct_transaction_date, $ct_order_id_mall);
        return $response;
    }

}
