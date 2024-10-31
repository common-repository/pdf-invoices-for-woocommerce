<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<div class="jem-pdf-container" style="page-break-after:always;">
    <div class="jem-pdf-sub-container">

    <div class="jem-pdf-inner-container">

        <div class="jem-pdf-in-row">

            <div class="jem-pdf-half head-f">
                <div class="jem-mini-r jem-pdf-customer-detail"><?php echo $buyer_detail; ?></div>
                <div class="jem-mini-r jem-pdf-customer-detail"><?php echo $invoice_detail; ?></div>
            </div>

            <div class="jem-pdf-half  head-s">
                <?php
                if($logo_id>0){
                ?>
                    <div class="invoice-logo">
                        <?php echo wp_get_attachment_image($logo_id,array(150,115)); ?>
                    </div>
                <?php } ?>
                <div class="jem-mini-r store-title"><?php echo $store_name; ?></div>
                <div class="jem-mini-r"><?php echo $store_address; ?></div>
            </div>
        </div>
    </div>

    <div class="jem-order-list">
        <div class="table">
            <div class="row">

                <?php if($template_settings['line_image']): ?>
                    <span class="thead cell">&nbsp;</span>
                <?php endif; ?>

                <span class="thead cell"><?php _e('Product',JEM_PDFLITE); ?></span>

                <?php if($template_settings['line_sku']): ?>
                    <span class="thead cell"><?php _e('Sku',JEM_PDFLITE); ?></span>
                <?php endif; ?>

                <?php if($template_settings['line_category']): ?>
                    <span class="thead cell"><?php _e('Category',JEM_PDFLITE); ?></span>
                <?php endif; ?>

                <?php if($template_settings['line_short_desc']): ?>
                    <span class="thead cell"><?php _e('Desc',JEM_PDFLITE); ?></span>
                <?php endif; ?>

                <span class="thead cell"><?php _e('Quantity',JEM_PDFLITE); ?></span>
                <span class="thead cell"><?php _e('Price',JEM_PDFLITE); ?></span>

                <?php if($template_settings['line_tax'] && get_option('woocommerce_calc_taxes')=='yes'): ?>
                    <span class="thead cell"><?php _e('Tax Rate(%)',JEM_PDFLITE); ?></span>
                <?php endif; ?>

                <?php if($template_settings['line_tax'] && get_option('woocommerce_calc_taxes')=='yes'): ?>
                    <span class="thead cell"><?php _e('Tax',JEM_PDFLITE); ?></span>
                <?php endif; ?>

                <span class="thead cell"><?php _e('Total',JEM_PDFLITE); ?></span>

            </div>

            <?php
            foreach( $order->get_items() as $item_id => $item ) {

                if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
                    return;
                }

                $product = $order->get_product_from_item( $item );
                $item_meta = $order->get_item_meta( $item_id );

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
                    $tax_data = maybe_unserialize( isset( $check_item['taxes'] ) ? $check_item['taxes'] : '' );
                } elseif ( $line_items_fee ) {
                    $check_item = current( $line_items_fee );
                    $tax_data   = maybe_unserialize( isset( $check_item['line_tax_data'] ) ? $check_item['line_tax_data'] : '' );
                }

                $legacy_order     = ! empty( $order_taxes ) && empty( $tax_data ) && ! is_array( $tax_data );
                $show_tax_columns = ! $legacy_order || sizeof( $order_taxes ) === 1;



                $is_visible = $product && $product->is_visible();
                echo '<div class="row item-row">';

                if($template_settings['line_image']):
                    echo '<span class="cell">'.$product->get_image(array(60,34)).'</span>';
                endif;

                echo '<span class="cell">';
                echo apply_filters( 'woocommerce_order_item_name', $is_visible ? sprintf( '%s', $item['name'] ) : $item['name'], $item, $is_visible );
                $order->display_item_meta( $item );
                $order->display_item_downloads( $item );
                echo '</span>';

                if($template_settings['line_sku']):
                    echo '<span class="cell">'.$product->sku.'</span>';
                endif;

                if($template_settings['line_category']):
                    echo '<span class="cell line_category">'.$product->get_categories(', ').'</span>';
                endif;

                if($template_settings['line_short_desc']):
                    echo '<span class="cell line_desc">'.$product->post->post_excerpt.'</span>';
                endif;

                echo '<span class="cell">'.$item['qty'].'</span>';

                echo '<span class="cell">';
                if ( isset( $item['line_total'] ) ) {
                    if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] != $item['line_total'] ) {
                        echo '<del>' . wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_order_currency() ) ) . '</del> ';
                    }
                    echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_order_currency() ) );
                }
                echo '</span>';



                if(wc_tax_enabled()) {
                    if ($template_settings['line_tax']):
                        $tax_rates = WC_Tax::get_rates($product->get_tax_class());
                        if (count($tax_rates) > 0 && $product->is_taxable()) {
                            echo '<span class="cell line_desc">';
                            foreach ($tax_rates as $rate) {
                                echo '<dd>'.$rate['label'] . ' - ' . round($rate['rate'], 2) . '%</dd>';
                            }
                            echo '</span>';
                        } else {
                            echo '<span class="cell line_desc">-</span>';
                        }
                    endif;
                }

                if(wc_tax_enabled()) {


                    if($template_settings['line_tax']){

                        echo '<span class="cell">';
                        if ( empty( $legacy_order ) ){
                            $line_tax_data = isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '';
                            $tax_data      = maybe_unserialize( $line_tax_data );

                            foreach ( $order_taxes as $tax_item ) {
                                $tax_item_id       = $tax_item['rate_id'];
                                $tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
                                $tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';

                                if ( '' != $tax_item_total ) {
                                    if ( isset( $tax_item_subtotal ) && $tax_item_subtotal != $tax_item_total ) {
                                        echo '<del>' . wc_price( wc_round_tax_total( $tax_item_subtotal ), array( 'currency' => $order->get_order_currency() ) ) . '</del> ';
                                    }

                                    echo '<dd>'.wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_order_currency() ) ).'</dd>';
                                } else {
                                    echo '&ndash;';
                                }

                                if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id ) ) {
                                    echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
                                }

                            }
                        }
                        echo '</span>';
                    }

                }


                echo '<span class="cell">'.$order->get_formatted_line_subtotal( $item ).'</span>';
                echo '</div>';
            }
            ?>

        </div>
    </div>



        <div class="jem-invoice-bottom">
            <div class="jem-mini-r jem-pdf-in-row">
                <div class="jem-pdf-half head-f">
                    <?php echo $shipping_detail; ?>
                </div>
                <div class="jem-pdf-half head-s">
                    <?php
                    $details = Jem_Pdf_Invoices()->get_order_item_totals($order);
                    if(!empty($details)){
                        foreach($details as $key => $total ) {
                            ?>
                            <div class="jem-mini-r jem-inner-bottom">
                                <label><?php echo $total['label']; ?></label>
                                <?php echo $total['value']; ?>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>
</div>