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

//    var $codigo_comercio;
//    var $token_servicio;
//
//    public function __construct() {
//
//        Logger::log_me_wp("ENTANDO AL CONSTRUCTOR");
//
//        $this->id = 'tbkaas';
//        $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png';
//        
//        Logger::log_me_wp($this->icon);
//        
//        $this->has_fields = false;
//        $this->method_title = 'TBKAAS WS';
//
//        $this->method_description = __('Permite pagos con tarjeta de crédito y debido chilenas usando WebServices a través de TBKAAS ');
//
//
//
//        // Load the settings.
//        $this->init_form_fields();
//        $this->init_settings();
//
//        // Define user set variables
//        $this->title = $this->get_option('title');
//        $this->description = $this->get_option('description');
//
//        $this->codigo_comercio = $this->get_option("codigo_comercio");
//        $this->token_servicio = $this->get_option("token_servicio");
//
//
//        /*
//         * Actions
//         * woocommerce_receipt_tbkaas se ejecuta luego del checkout.
//         * woocommerce_thankyou_tbkaas se ejecuta al terminar la transacción.
//         * woocommerce_update_options_payment_gateways_tbkaas  guarda la configuración de la pasarela de pago.
//         */
//
//        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
//        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
//
//        // Payment listener/API hook
//        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'tbkaas_api_handler'));
//
//        Logger::log_me_wp("Saliendo AL CONSTRUCTOR");
//    }
    
    function __construct() {
        $this->id = 'WooPagosMP';
        $this->has_fields = false;
        $this->method_title = 'Mercado Pago Chile';
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->getDescription();
        $this->notification_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'WooPagosMP', home_url('/')));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_gateway_paypal', array($this, 'check_ipn_response'));
    }
    
    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Habilita Woocommerce Webpay Plus', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('', 'woocommerce'),
                'default' => __('Web Pay Plus WebService', 'woocommerce')
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
            'token_servicio' => array(
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
        if ($DOBLEVALIDACION === "yes") {
            $order = new WC_Order($order_id);
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
         */
        if (!add_post_meta($this->order_id, '_id_session', $id_session, true)) {
            update_post_meta($this->order_id, '_id_session', $id_session);
        }

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

}
