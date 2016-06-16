<?php

$fields = array(
    "Código de autorización" => get_post_meta($order_id, "_authorization_code", true),
    "Tipo de Pago" => get_post_meta($order_id, "_payment_type_code", true),
    "Monto" => get_post_meta($order_id, "_amount", true),
    "Final Tarjeta" => get_post_meta($order_id, "_card_number", true),
    "Cuotas" => get_post_meta($order_id, "_shares_number", true),
    "Fecha Contable" => get_post_meta($order_id, "_accounting_date", true),
    "Fecha Transacción" => get_post_meta($order_id, "_transaction_date", true),
);

\tbkaaswoogateway\classes\Logger::log_me_wp($fields);
?>
<h2><?php "Detalles de la Transacción"; ?></h2>
<table class="shop_table order_details">
    <thead>
        <tr>
            <th class="product-name"><?php echo 'Atributo'; ?></th>
            <th class="product-total"><?php echo 'Valor'; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($fields as $field => $key) {

            echo "<tr>";
            echo "<td>$field</td>";
            echo "<td>$key</td>";
            echo "</tr>";
        }
        ?>

    </tbody>
    <tfoot>

    </tfoot>
</table>