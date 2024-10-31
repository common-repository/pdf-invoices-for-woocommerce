<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/*
Template Name: JEM Default Invoice Layout
*/
global $jem_pdf;
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title>JEM PDF Packing Slip</title>
	<link rel='stylesheet' href='<?php echo Jem_Pdf_Invoices()->jem_pdf_url( '/templates/css/style.css' ); ?>'
	      type='text/css' media='all'/>
</head>
<body>

<div class="jem-pdf-container">
	<div class="jem-pdf-sub-container">

		<!-- store logo & name, addr -->
		<div class="jem-pdf-inner-container">
			<table width="100%">
				<tr>
					<td width="50%">
						Packing Slip
					</td>
					<td width="50%" class="jem-text-align-right">
						<div class="store-title"><?php echo $store_name; ?><BR></div>
						<?php echo $store_address; ?>

					</td>
				</tr>
			</table>
		</div>


		<!-- BILL TO, SHIP TO & BASIC DETAILS -->
		<div class="jem-pdf-inner-container">
			<table class="jem-invoice-summary">
				<tr>
					<td width="50%">
						<?php echo $shipping_detail; ?>
					</td>
					<td width="50%" style="vertical-align: top;">
						<?php echo $invoice_detail; ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="clearfix"></div>


		<table class="jem-order-list">
			<thead>
				<tr>
					<th width="75%"><?php _e( 'Product', JEM_PDFLITE ); ?></th>
					<th width="25%"><?php _e( 'Quantity', JEM_PDFLITE ); ?></th>
				</tr>
			</thead>
			<tbody>

			<?php
			foreach ( $order->get_items() as $item_id => $item ) {

				if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					return;
				}

				$product   = $order->get_product_from_item( $item );
				$item_meta = $order->get_item_meta( $item_id );

				$line_items          = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );




				$is_visible = $product && $product->is_visible();
				echo '<tr class="jem-line-item">';

				echo '<td >';
				echo apply_filters( 'woocommerce_order_item_name', $is_visible ? sprintf( '%s', $item['name'] ) : $item['name'], $item, $is_visible );

				if( isset($product->sku) && $product->sku != ""){
					echo "<BR><small>SKU: " . $product->sku . "</small>";
				}
				echo '</td>';


				echo '<td class="cell">' . $item['qty'] . '</td>';

				echo '</tr>';
			}
			?>

			<!-- SUBTOTALS AND CUSTOMER NOTES -->
			<tr class="jem-subtotals">

				<td colspan="2">
					<?php
						if( $order->customer_message){
							echo '<br><b>Customer Notes</b><br>';
							echo $order->customer_message;
						}
					?>
				</td>

			</tr>
			</tbody>
		</table>



	</div>
</div>

</body>
</html>