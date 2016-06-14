<?php

/**
 * Description of Logger
 *
 * @author ctala
 */

namespace tbkaaswoogateway\classes;

class Logger {
    
    public static function log_me_wp($message, $sufijo = "") {
        if (WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($sufijo . "\t-> " . $message);
            }
        }
    }
    
}
