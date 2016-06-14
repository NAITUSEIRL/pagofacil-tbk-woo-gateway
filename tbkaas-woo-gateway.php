<?php

namespace tbkaaswoogateway;

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

use tbkaaswoogateway\classes\Logger;
use tbkaaswoogateway\classes\WC_Gateway_TBKAAS;

//CONSTANTES
define("SERVER_TBKAAS", "http://dev-env.sv1.tbk.cristiantala.cl/tbk/v1");

//VARIABLES
//Funciones
function add_your_gateway_class($methods) {
    $methods[] = 'WC_Gateway_TBKAAS_HIJO';
    return $methods;
}

function init_your_gateway_class() {

    class WC_Gateway_TBKAAS_HIJO extends WC_Gateway_TBKAAS {
        
    }

}

//Acciones
add_action('plugins_loaded', 'tbkaaswoogateway\init_your_gateway_class');

//FILTROS
add_filter('woocommerce_payment_gateways', 'tbkaaswoogateway\add_your_gateway_class');
?>