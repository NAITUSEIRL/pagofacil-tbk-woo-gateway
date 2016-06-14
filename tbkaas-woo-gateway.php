<?php



/*
  Plugin Name: tbkaas-woo-gateway
  Plugin URI:  https://github.com/ctala/tbkaas-woo-gateway
  Description: Pasarela de Pagos para Woocommerce y Transbank usando WebPayPlus Webservices a través de TBKAAS
  Version:     0.1
  Author:      Cristian Tala Sánchez
  Author URI:  http://www.cristiantala.cl
  License:     MIT
  License URI: http://opensource.org/licenses/MIT
  Domain Path: /languages
  Text Domain: ctala-text_domain
 */

include_once 'vendor/autoload.php';

//use tbkaaswoogateway\classes\Logger;
use tbkaaswoogateway\classes\WC_Gateway_TBKAAS;

//CONSTANTES
define("SERVER_TBKAAS", "http://dev-env.sv1.tbk.cristiantala.cl/tbk/v1/initTransaction");
define("SERVER_TBKAAS_VERIFICAR", "http://dev-env.sv1.tbk.cristiantala.cl/tbk/v1/estadoOrden");

//VARIABLES
//Funciones
add_action('plugins_loaded', 'init_TBKAAS');
function init_TBKAAS() {
    if (!class_exists('WC_Payment_Gateway'))
        return;
    class WC_Gateway_TBKAAS_Chile extends WC_Gateway_TBKAAS {
         
    }
}
function add_your_gateway_class($methods) {
    $methods[] = 'WC_Gateway_TBKAAS_Chile';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_your_gateway_class');
?>