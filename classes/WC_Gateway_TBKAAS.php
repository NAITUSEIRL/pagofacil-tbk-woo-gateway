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

/**
 * Description of WC_Gateway_TBKAAS
 *
 * @author ctala
 */
class WC_Gateway_TBKAAS extends \WC_Payment_Gateway {

    var $notify_url;

    function __construct() {
        $this->id = 'tbkaas';
        $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png';
        $this->has_fields = false;
        $this->method_title = 'Transbank As A Service';
        $this->notify_url = WC()->api_request_url('WC_Gateway_TBKAAS');
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');



        $this->method_description = '<ul>'
                . '<li>URL CALLBACK : ' . $this->notify_url . '</b></i></li>'
                . '<li>URL FINAL : ' . $this->notify_url . '</b></i></li>'
                . '</ul>';




        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        //Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'tbkaas_api_handler'));
    }

    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Habilita Transbank As A Service', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('', 'woocommerce'),
                'default' => __('Transbank As A Service ( WebpayPlus PST )', 'woocommerce')
            ),
            'description' => array(
                'title' => __('Customer Message', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Mensaje que recibirán los clientes al seleccionar el medio de pago'),
                'default' => __('Sistema de pago con tarjetas de crédito y debito chilenas.'),
            ),
            'codigo_comercio' => array(
                'title' => "Código de Comercio",
                'type' => 'text',
                'description' => "El número de comercio con el cual tienes la cuenta PST",
                'default' => "",
            ),
            'token_service' => array(
                'title' => "Token Servicio",
                'type' => 'text',
                'description' => "Token dado por TBKAAS para la conexión",
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
        $formPostAddress = SERVER_TBKAAS;

        Logger::log_me_wp($formPostAddress);

        $SUFIJO = "[WEBPAY - FORM]";

        $order = new WC_Order($order_id);
        $id_session = uniqid("", true);

        /*
         * Agregamos el id de sesion la OC.
         * Esto permitira que validemos el pago mas tarde
         * Este valor no cambiara para la OC
         */
        add_post_meta($order_id, '_id_session', $id_session, true);

        $pago_args = array(
            'monto' => round($order->order_total),
            'order_id' => $order_id,
            'codigo_comercio' => $this->get_option("codigo_comercio"),
            'token_service' => $this->get_option("token_service"),
            'id_session' => $id_session,
        );
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
        $resultado.=implode('', $webpayplus_args_array);
        $resultado.='<!-- Button Fallback -->
                        <div class="payment_buttons">
                        <input type="submit" class="button alt" id="submit_tbkaas_payment_form" value="' . __('Pago via WebpayPlus') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
                        </div>';
        if ($AUTOREDIRECT === "yes") {

            $resultado.='
                                <script type="text/javascript">
					jQuery(".payment_buttons").hide();
				</script>';
            $resultado.='</form>';
        } else {
            $resultado.='</form>';
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
            $order_id = filter_input(INPUT_POST, "order_id");
            //Verificamos que la orden exista 
            $order = new WC_Order($order_id);
            if (!($order)) {
                return;
            }
            Logger::log_me_wp("Order $order_id existente, continuamos");

            /*
             * Si la orden no está pagada verificamos.
             */


            $verificado = $this->verificarOrden($order, $order_id);

            /*
             * Si la orden esta completa cambiamos estado
             * Si no redireccionamos
             */

            if ($verificado) {
                //Completamos la orden
                $order->payment_complete();
            }

            /*
             * Redireccionamos.
             */
            $order_received = $order->get_checkout_order_received_url();
            wp_redirect($order_received);
            exit;
        } else {
            
        }
    }

    private function verificarOrden($order, $order_id) {

        Logger::log_me_wp("ENTRANDO AL API POR POST");

        $id_session_db = get_post_meta($order_id, "_id_session", true);
        Logger::log_me_wp("ID SEssion en DB : $id_session_db");

        //Si existe le preguntamos al servidor su estado
        $fields = array(
            'codigo_comercio' => $this->get_option("codigo_comercio"),
            'token_service' => $this->get_option("token_service"),
            'order_id' => $order_id,
            'monto' => round($order->order_total),
            'id_session' => $id_session_db
        );

        $resultado = $this->executeCurl($fields, SERVER_TBKAAS_VERIFICAR);

        Logger::log_me_wp("RESULTADO : $resultado");

        if ($resultado == "COMPLETADA") {
            Logger::log_me_wp("COMPLETADA");
            return true;
        } else {
            Logger::log_me_wp("NO COMPLETADA");
            return false;
        }
    }

    private function executeCurl(array $fields, $url) {

        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        //open connection
        $ch = \curl_init();

        //set the url, number of POST vars, POST data
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_POST, count($fields));
        \curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        $result = curl_exec($ch);

        Logger::log_me_wp("Resultado Verificacion : " . $result);

        //close connection
        curl_close($ch);
        return $result;
    }

}
