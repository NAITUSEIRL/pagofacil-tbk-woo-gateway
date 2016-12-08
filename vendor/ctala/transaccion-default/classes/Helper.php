<?php

namespace ctala\transaccion\classes;

/**
 * Description of Helper
 *
 * @author ctala
 */
class Helper {

    /**
     * Esta función permite la redirección para los pagos.
     * 
     * @param type $url
     * @param type $variables
     */
    public static function redirect($url, $variables) {
        foreach ($variables as $key => $value) {
            $args_array[] = '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        ?>
        <html>
            <head>
                <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
            </head> 
            <body style="background-image:url('https://webpay3g.transbank.cl/webpayserver/imagenes/background.gif')">
                <form name="WS1" id="WS1" action="<?= $url ?>" method="POST" onl>
                    <?php
                    foreach ($args_array as $arg) {
                        echo $arg;
                    }
                    ?> 
                    <input type="submit" id="submit_webpayplus_payment_form" style="visibility: hidden;"> 
                </form>
                <script>
                    $(document).ready(function () {
                        $("#WS1").submit();
                    });
                </script>
            </body>
        </html>

        <?php
    }

}
