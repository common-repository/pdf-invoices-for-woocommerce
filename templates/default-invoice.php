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
	<title>JEM PDF Invoice</title>
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
						<?php
						if ( $logo_id > 0 ) {
							?>
							<div class="invoice-logo">
								<?php echo wp_get_attachment_image( $logo_id, array( 150, 115 ) ); ?>
							</div>
						<?php } ?>

					</td>
					<td width="50%" class="jem-text-align-right">
						<div class="store-title"><?php echo $store_name; ?><BR></div>
						<?php echo $store_address; ?>

					</td>
				</tr>
			</table>
		</div>

		<!-- INVOICE # AND DATE -->
		<div class="jem-pdf-inner-container solid-header">
			<table width="100%">
				<tr>
					<td style="padding-left:5px;">
						<?php
						_e( 'Invoice # ', JEM_PDFLITE ); echo $this->invoice_num; ?>

					</td>
					<td class="jem-text-align-right" style="padding-right:5px;">
						<?php echo  $this->invoice_date_formatted; //$this->jem_ date("F j, Y", $this->invoice_date); ?>

					</td>
				</tr>
			</table>
		</div>

		<!-- BILL TO, SHIP TO & BASIC DETAILS -->
		<div class="jem-pdf-inner-container">
			<table class="jem-invoice-summary">
				<tr>
					<td class="jem-pdf-third">
						<?php echo $buyer_detail; ?>
					</td>
					<td class="jem-pdf-third">
						<?php echo $shipping_detail; ?>
					</td>
					<td class="jem-pdf-third">
						<?php echo $invoice_detail; ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="clearfix"></div>


		<table class="jem-order-list">
			<thead>
				<tr>
					<th class="jem-product"><?php _e( 'Product', JEM_PDFLITE ); ?></th>
					<th class="jem-quantity"><?php _e( 'Quantity', JEM_PDFLITE ); ?></th>
					<th class="jem-price"><?php _e( 'Price', JEM_PDFLITE ); ?></th>
					<th class="jem-total"><?php _e( 'Total', JEM_PDFLITE ); ?></th>
				</tr>
			</thead>
			<tbody>

			<?php
			foreach ( $order->get_items() as $item_id => $item ) {

				if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					return;
				}

				//get the product
//				$product   = $order->get_product_from_item( $item );
				$product_id = $item->get_product_id();
				$product = wc_get_product($item['product_id']);

				//$item_meta = $order->get_item_meta( $item_id );

				$line_items          = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );
				$line_items_fee      = $order->get_items( 'fee' );
				$line_items_shipping = $order->get_items( 'shipping' );

				$order_taxes         = $order->get_taxes();
				$tax_classes         = WC_Tax::get_tax_classes();
				$classes_options     = array();
				$classes_options[''] = __( 'Standard', 'jem-pdf-pro' );

				if ( ! empty( $tax_classes ) ) {
					foreach ( $tax_classes as $class ) {
						$classes_options[ sanitize_title( $class ) ] = $class;
					}
				}

				// Older orders won't have line taxes so we need to handle them differently :(
				$tax_data = '';
				if ( $line_items ) {
					$check_item = current( $line_items );
					$tax_data   = maybe_unserialize( isset( $check_item['line_tax_data'] ) ? $check_item['line_tax_data'] : '' );
				} elseif ( $line_items_shipping ) {
					$check_item = current( $line_items_shipping );
					$tax_data   = maybe_unserialize( isset( $check_item['taxes'] ) ? $check_item['taxes'] : '' );
				} elseif ( $line_items_fee ) {
					$check_item = current( $line_items_fee );
					$tax_data   = maybe_unserialize( isset( $check_item['line_tax_data'] ) ? $check_item['line_tax_data'] : '' );
				}

				$legacy_order     = ! empty( $order_taxes ) && empty( $tax_data ) && ! is_array( $tax_data );
				$show_tax_columns = ! $legacy_order || sizeof( $order_taxes ) === 1;


				$is_visible = $product && $product->is_visible();
				echo '<tr class="jem-line-item">';

				echo '<td >';
				echo apply_filters( 'woocommerce_order_item_name', $is_visible ? sprintf( '%s', $item['name'] ) : $item['name'], $item, $is_visible );

				if( $product->get_sku() != ""){
					echo "<BR><small>SKU: " . $product->get_sku() . "</small>";
				}
				echo '</td>';


				echo '<td class="cell">' . $item['qty'] . '</td>';

				echo '<td class="cell">';
				if ( isset( $item['line_total'] ) ) {
					if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] != $item['line_total'] ) {
						echo '<del>' . wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_currency() ) ) . '</del> ';
					}
					echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_currency() ) );
				}
				echo '</td>';





				echo '<td class="cell">' . $order->get_formatted_line_subtotal( $item ) . '</td>';
				echo '</tr>';
			}
			?>

			<!-- SUBTOTALS AND CUSTOMER NOTES -->
			<tr class="jem-subtotals">

				<td class="jem-cust-notes" colspan="2">
					<?php
						if( $order->get_customer_note()){
							echo '<br><b>Customer Notes</b><br>';
							echo $order->get_customer_note();
						}
					?>
				</td>

				<td colspan="2">
					<table class="jem-subtotals-items">
						<?php
						$details = Jem_Pdf_Invoices()->get_order_item_totals( $order );
						if ( ! empty( $details ) ) {
							foreach ( $details as $key => $total ) {
								?>
								<tr>

									<td class="clear-cell jem-text-align-right">
											<B><label><?php echo $total['label']; ?></label></B>
									</td>
									<td class="clear-cell">
											<?php echo $total['value']; ?>
									</td>
								</tr>
								<?php
							}
						}
						?>

					</table>
				</td>
			</tr>
			</tbody>
		</table>



	</div>
</div>

</body>
</html>