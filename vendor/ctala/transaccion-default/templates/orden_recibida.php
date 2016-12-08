<?php

switch ($fields["Tipo de Pago"]) {
    case "VD":
        $fields["Tipo de Pago"] = "Débito";
        $fields["Tipo de Cuotas"] = "Venta Débito";
        break;
    case "VN":
        $fields["Tipo de Pago"] = "Crédito";
        $fields["Tipo de Cuotas"] = "Sin Cuotas";
        break;
    case "VC":
        $fields["Tipo de Pago"] = "Crédito";
        $fields["Tipo de Cuotas"] = "Cuotas normales";
        break;
    default:
        $fields["Tipo de Pago"] = "Crédito";
        $fields["Tipo de Cuotas"] = "Sin interés";
        break;
}
?>
<h2><?php echo "Detalles de la Transacción"; ?></h2>
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