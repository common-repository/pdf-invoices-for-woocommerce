<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists( 'Jem_Pdf_Dashboard_Settings' ) ) :
class Jem_Pdf_Dashboard_Settings{

    public function __construct(){

    }

    public static function get_selected($type,$invoice,$field,$opt,$return=false){
        global $jem_pdf;
        if(isset($jem_pdf[$type][$invoice][$field]) && $opt && $opt!=''){
            $selected = isset($jem_pdf[$type][$invoice][$field]) && $jem_pdf[$type][$invoice][$field]==$opt ? ' selected ' : '';
            if($return){
                return $selected;
            }
            else
            {
                echo $selected;
            }
        }
    }

    public static function get_checked($type,$invoice,$field,$opt){
        global $jem_pdf;
        if(isset($jem_pdf[$type][$invoice][$field]) &&  $opt && $opt!=''){
            $checked = isset($jem_pdf[$type][$invoice][$field]) && $jem_pdf[$type][$invoice][$field]==$opt ? ' checked ' : '';
            echo $checked;
        }
        elseif(isset($jem_pdf[$type][$field]) && $opt && $opt!=''){
            $checked = isset($jem_pdf[$type][$field]) && $jem_pdf[$type][$field]==$opt ? ' checked ' : '';
            echo $checked;
        }

    }

    public static function get_multi_checked($type,$invoice,$field,$opt){
        global $jem_pdf;
        if(isset($jem_pdf[$type][$invoice][$field]) && is_array($jem_pdf[$type][$invoice][$field]) && $opt && $opt!=''){
            $checked = isset($jem_pdf[$type][$invoice][$field]) && in_array($opt,$jem_pdf[$type][$invoice][$field]) ? ' checked ' : '';
            echo $checked;
        }
        elseif(isset($jem_pdf[$type][$field]) && is_array($jem_pdf[$type][$field]) && $opt && $opt!=''){
            $checked = isset($jem_pdf[$type][$field]) && in_array($opt,$jem_pdf[$type][$field]) ? ' checked ' : '';
            echo $checked;
        }

    }

    public static function get_value($type,$invoice,$field,$opt,$echo=true){
        global $jem_pdf;
        if(isset($jem_pdf[$type][$invoice][$field]) && $jem_pdf[$type][$invoice][$field]){
            $value = isset($jem_pdf[$type][$invoice][$field]) && $jem_pdf[$type][$invoice][$field]!='' ? $jem_pdf[$type][$invoice][$field] : '';
            if($echo){
                echo stripslashes($value);
            }
            else
            {
                return stripslashes($value);
            }

        }
        elseif(isset($jem_pdf[$type][$field]) && $jem_pdf[$type][$field]){
            $value = isset($jem_pdf[$type][$field]) && $jem_pdf[$type][$field]!='' ? $jem_pdf[$type][$field] : '';
            if($echo){
                echo stripslashes($value);
            }
            else
            {
            return stripslashes($value);
            }
        }

    }

    /**
     * Simply returns the string for Available in PRO messages, allows for easy customization!
     */
    public static function get_available_in_pro_message(){
        return "<br><i><a href='xxx.com'>Coming soon in our PRO version - click for details</a></i>";
    }

    public static function general_settings(){
        ?>
        <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <h3 class="title"><?php _e('General Settings',JEM_PDFLITE); ?></h3>

        <table class="form-table">

            <tr>
                <th>
                    <label><?php _e('View PDF Option',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('How an end customer will actually see the invoice. Either downloaded in their browser or opened in a new window.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <select name="view-invoice">
                        <option value="download"<?php Jem_Pdf_Dashboard_Settings::get_selected('general','options','view-invoice','download'); ?>><?php _e('Download the PDF',JEM_PDFLITE); ?></option>
                        <option value="new-window"<?php Jem_Pdf_Dashboard_Settings::get_selected('general','options','view-invoice','new-window'); ?>><?php _e('Open the PDF in a new Browser Window',JEM_PDFLITE); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <?php $tip1 =  __('Do NOT create an email if ALL the products on the order have a price of 0 and the total is also zero (including shipping).',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                    <label><?php _e('Disable Invoices for Free Products',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="disbale-invoice" value="1" <?php Jem_Pdf_Dashboard_Settings::get_checked('general','options','disbale-invoice',true); ?>>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('Select Template',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('This allows different templates to be used to generate the pdf Invoice.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <select name="template">
                        <option value="default" <?php Jem_Pdf_Dashboard_Settings::get_selected('general','options','template','default'); ?>><?php _e('Default Template',JEM_PDFLITE); ?></option>
                        <?php
                        $templates = Jem_Pdf_Invoices()->get_jem_pdf_templates();
                        if(!empty($templates)){
                            foreach($templates as $temp){
                                $template = get_file_data($temp,array('TemplateName'=>'Template Name'));
                                echo '<option value="'.$temp.'" '.Jem_Pdf_Dashboard_Settings::get_selected('general','options','template',$temp,1).'>'.$template['TemplateName'].'</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('Paper Size',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('This will be the paper size fed into the PDF engine.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <select name="paper-size">
                        <option value="A4"<?php Jem_Pdf_Dashboard_Settings::get_selected('general','options','paper-size','default'); ?>><?php _e('Default Paper Size',JEM_PDFLITE); ?></option>
                        <option value="letter"<?php Jem_Pdf_Dashboard_Settings::get_selected('general','options','paper-size','letter'); ?>><?php _e('Letter',JEM_PDFLITE); ?></option>
                        <option value="A4"<?php Jem_Pdf_Dashboard_Settings::get_selected('general','options','paper-size','A4'); ?>><?php _e('A4',JEM_PDFLITE); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('Date Format',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('Controls how dates appears. Should be valid date format, ex: DD/MM/YY ',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="text" name="date-format" value="<?php Jem_Pdf_Dashboard_Settings::get_value('general','options','date-format','date-format'); ?>">
                </td>
            </tr>

            </table>

            <h3 class="title"><?php _e('Regular Invoice',JEM_PDFLITE); ?></h3>
            <table class="form-table">

            <tr>
                <th>
                    <label><?php _e('Attach to Email',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('This specifies which email an invoice should be sent with.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <div class="jem-pdf-row"><input type="checkbox" name="attach-to-mail[invoice][]" value="new_order"<?php Jem_Pdf_Dashboard_Settings::get_multi_checked('general','general_invoice','attach-to-mail','new_order'); ?>> <span class="label"><?php _e('Admin New Order', JEM_PDFLITE); ?></span></div>
                    <div class="jem-pdf-row"><input type="checkbox" name="attach-to-mail[invoice][]" value="cancelled_order"<?php Jem_Pdf_Dashboard_Settings::get_multi_checked('general','general_invoice','attach-to-mail','cancelled_order'); ?>> <span class="label"><?php _e('Admin Cancelled Order', JEM_PDFLITE); ?></span></div>
                    <div class="jem-pdf-row"><input type="checkbox" name="attach-to-mail[invoice][]" value="customer_processing_order"<?php Jem_Pdf_Dashboard_Settings::get_multi_checked('general','general_invoice','attach-to-mail','customer_processing_order'); ?>> <span class="label"><?php _e('Customer Processing Order', JEM_PDFLITE); ?></span></div>
                    <div class="jem-pdf-row"><input type="checkbox" name="attach-to-mail[invoice][]" value="customer_completed_order"<?php Jem_Pdf_Dashboard_Settings::get_multi_checked('general','general_invoice','attach-to-mail','customer_completed_order'); ?>> <span class="label"><?php _e('Customer Completed Order', JEM_PDFLITE); ?></span></div>
                    <div class="jem-pdf-row"><input type="checkbox" name="attach-to-mail[invoice][]" value="customer_invoice"<?php Jem_Pdf_Dashboard_Settings::get_multi_checked('general','general_invoice','attach-to-mail','customer_invoice'); ?>> <span class="label"><?php _e('Customer Invoice', JEM_PDFLITE); ?></span></div>
                    <div class="jem-pdf-row"><input type="checkbox" name="attach-to-mail[invoice][]" value="customer_cancelled_order"<?php Jem_Pdf_Dashboard_Settings::get_multi_checked('general','general_invoice','attach-to-mail','customer_cancelled_order'); ?>> <span class="label"><?php _e('Customer Cancelled Order', JEM_PDFLITE); ?></span></div>
                    <div class="jem-pdf-row"><input type="checkbox" name="attach-to-mail[invoice][]" value="customer_refunded_order"<?php Jem_Pdf_Dashboard_Settings::get_multi_checked('general','general_invoice','attach-to-mail','customer_refunded_order'); ?>> <span class="label"><?php _e('Customer Refunded Order', JEM_PDFLITE); ?></span></div>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('Number Type',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('Controls if the WooCommerce order number is used or we use a Custom Invoice Number.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <select name="order-num-type[invoice]">
                        <option value="woocommerce-invoce"<?php Jem_Pdf_Dashboard_Settings::get_selected('general','general_invoice','order-num-type','woocommerce-invoce'); ?>><?php _e('Use WooCommerce Order Number',JEM_PDFLITE); ?></option>
                        <option value="custom-invoce"<?php Jem_Pdf_Dashboard_Settings::get_selected('general','general_invoice','order-num-type','custom-invoce'); ?>><?php _e('Custom Invoice Number',JEM_PDFLITE); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('Next Invoice Number',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('This number will be used for the next invoice and incremented by one',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="text" name="invoice-number[invoice]" value="<?php Jem_Pdf_Dashboard_Settings::get_value('general','general_invoice','invoice-number',''); ?>">
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('Invoice Prefix',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('You can enter a prefix for the invoice number. {{month}} or {{year}} will insert the order month and/or year.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="text" name="invoice-prefix[invoice]" value="<?php Jem_Pdf_Dashboard_Settings::get_value('general','general_invoice','invoice-prefix',''); ?>"><i>Use {{month}} and {{year}} for the current month and year</I>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('Invoice Suffix',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('can enter a suffix for the invoice number. {{month}} or {{year}} will insert the order month and/or year.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="text" name="invoice-suffix[invoice]" value="<?php Jem_Pdf_Dashboard_Settings::get_value('general','general_invoice','invoice-suffix',''); ?>"><i>Use {{month}} and {{year}} for the current month and year</I>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php _e('# Digits',JEM_PDFLITE); ?></label>
                    <?php $tip1 =  __('Integer that specifies how many digits the invoice number should be.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="text" name="invoice-digit[invoice]" value="<?php Jem_Pdf_Dashboard_Settings::get_value('general','general_invoice','invoice-digit',''); ?>"><i>The number of digits e.g. 6 would be 000123 for example</I>
                </td>
            </tr>

            </table>

            <h3 class="title"><?php _e('Misc. Settings',JEM_PDFLITE); ?></h3>
            <table class="form-table">

                <tr>
                    <th>
                        <label><?php _e('Temporary Directory',JEM_PDFLITE); ?></label>
                        <?php $tip1 =  __('The temp directory path.',JEM_PDFLITE);?>
                        <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                    </th>
                    <td>
                        <?php echo Jem_Pdf_Invoices()->jem_pdf_temp_path(); ?>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label><?php _e('Temporary Directory Writable',JEM_PDFLITE); ?></label>
                        <?php $tip1 =  __('Is it writable?',JEM_PDFLITE);?>
                        <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip1 ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                    </th>
                    <td>
                        <?php

                            $file = Jem_Pdf_Invoices()->jem_pdf_temp_path() . 'test.csv';
                            $ret = file_put_contents($file, "test data");

                            if($ret === false){
                                echo "FAILED";
                            } else {
                                unlink($file);
                                echo "SUCCESS";
                            }

                        ?>
                    </td>
                </tr>

            </table>

                <p class="submit">
                <input type="hidden" name="jem_pdf" value="save_jem_pdf_general">
                <input name="save" type="submit" class="button-primary" value="<?php _e('Save changes',JEM_PDFLITE); ?>"/>
            </p>

        </form>
        <?php
    }

    /**
     * Handles the HEADER & FOOTER tab
     */
    public static function template_settings(){
        global $jem_pdf;
        ?>
        <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <h3 class="title"><?php _e('Layout Settings',JEM_PDFLITE); ?></h3>
        <table class="form-table">

            <tr>
                <th>
                    <label><?php _e('Logo',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('Upload Invoice Header Logo.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <button class="button thickbox" id="jem_pdf_logo" type="button"><?php _e('Select Store Logo',JEM_PDFLITE); ?></button>
                    <span id="preview_jem_pdf_logo">
                        <?php
                        $logo_id = Jem_Pdf_Dashboard_Settings::get_value('header','','jem_pdf_logo','',false);
                        if(isset($logo_id) && $logo_id>0){
                            echo wp_get_attachment_image($logo_id,array(120,120));
                            echo '<a class="remove-bg" img-id="jem_pdf_logo" href="javascript:void(0);">&times;</a>';
                        }
                        ?>
                    </span>
                    <input type="hidden" name="jem_pdf_logo" id="imgid_jem_pdf_logo" value="<?php Jem_Pdf_Dashboard_Settings::get_value('header','','jem_pdf_logo',''); ?>">
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Remove Empty Lines',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If checked it will remove any lines that are blank e.g. buyer_address_2.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td> <?php Jem_Pdf_Dashboard_Settings::get_checked('header','empty-lines','',true); ?>
                    <input type="checkbox" name="empty-lines" value="1" <?php Jem_Pdf_Dashboard_Settings::get_checked('header','','empty-lines',true); ?>>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Store Name',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('Allows you to enter the name of the store.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="text" name="store-name" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('header','','store-name',''); ?>">
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Store Address',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('Allows you to enter the address of the store.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <textarea name="store-address" rows="4" class="jem-half"><?php Jem_Pdf_Dashboard_Settings::get_value('header','','store-address',''); ?></textarea>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Billing Details',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('Allows you to enter the buyer detail using placeholders.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <textarea name="buyer-detail" rows="4" class="jem-half" disabled><?php Jem_Pdf_Dashboard_Settings::get_value('header','','buyer-detail',''); ?></textarea>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Shipping Detail',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('Allows you to enter the shipping detail using placeholders.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <textarea name="shipping-detail" rows="4" class="jem-half" disabled><?php Jem_Pdf_Dashboard_Settings::get_value('header','','shipping-detail',''); ?></textarea>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Invoice Information',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('Allows you to enter the invoice info.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <textarea name="invoice-detail" rows="4" class="jem-half" disabled><?php Jem_Pdf_Dashboard_Settings::get_value('header','','invoice-detail',''); ?></textarea>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>

        </table>
        <p class="submit">
            <input type="hidden" name="jem_pdf" value="save_jem_pdf_header">
            <input name="save" type="submit" class="button-primary" value="<?php _e('Save changes',JEM_PDFLITE); ?>"/>
        </p>
        </form>

    <?php
    }

    /**
     * Handles the DETAILED CONTENT tab
     */
    public static function content_settings(){
        ?>
        <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <h3 class="title"><?php _e('Detailed Content Layout',JEM_PDFLITE); ?></h3>
        <table class="form-table">

        <tr>
            <th>
                <label><?php _e('Remove Empty Lines',JEM_PDFLITE); ?></label>
                <?php $tip =  __('If checked it will remove any lines that are blank.',JEM_PDFLITE);?>
                <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
            </th>
            <td>
                <input type="checkbox" name="line-remove-blank-lines" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','line-remove-blank-lines',true); ?>>
            </td>
        </tr>
        <tr>
                <th>
                    <label><?php _e('SKU',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If checked it will add sku in line item.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="checkbox" name="line-sku" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','line-sku',true); ?>>
                </td>
        </tr>
            <tr>
                <th>
                    <label><?php _e('Show Shipping if free',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If checked it will display Shipping if free.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input type="checkbox" name="shipping-if-free" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','shipping-if-free',true); ?>>
                </td>
            </tr>
        <tr>
                <th>
                    <label><?php _e('Product Category',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If checked it will add product category in line item.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input disabled type="checkbox" name="line-category" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','line-category',true); ?>>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
        <tr>
                <th>
                    <label><?php _e('Short Description',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If checked it will add product Short Description in line item.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input disabled type="checkbox" name="line-short-desc" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','line-short-desc',true); ?>>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
        <tr>
                <th>
                    <label><?php _e('Product Image',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If checked it will add product image in line item.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input disabled type="checkbox" name="line-image" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','line-image',true); ?>>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>

        </table>

        <h3 class="title"><?php _e('Tax Settings',JEM_PDFLITE); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label><?php _e('Display Tax Inline',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If selected will display tax rate, tax amount and net amount for each line item.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input disabled type="checkbox" name="line-tax" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','line-tax',true); ?>>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Display total excl. of tax',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('If enabled will display an EXTRA subtotal EXCLUSIVE of tax.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input disabled type="checkbox" name="line-excl-tax" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','line-excl-tax',true); ?>>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Display tax totals',JEM_PDFLITE); ?></label>
                    <?php $tip =  __('Controls if we display tax totals.',JEM_PDFLITE);?>
                    <img class="help_tip" data-tip="<?php echo wc_sanitize_tooltip( $tip ); ?>" src="<?php echo Jem_Pdf_Invoices()->jem_pdf_url().'/assets/images/help.png'; ?>" height="16" width="16" />
                </th>
                <td>
                    <input disabled type="checkbox" name="tax-totals" value="1"<?php Jem_Pdf_Dashboard_Settings::get_checked('content','','tax-totals',true); ?>>
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="hidden" name="jem_pdf" value="save_jem_pdf_content">
            <input name="save" type="submit" class="button-primary" value="<?php _e('Save changes',JEM_PDFLITE); ?>"/>
        </p>

        </form>
        <?php
        }

    /**
     * Handles the LOCALIZATION tab
     */
    public static function localization_settings(){
        ?>
        <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <h3 class="title"><?php _e('Localization Settings',JEM_PDFLITE); ?></h3>

        <table class="form-table">

        <tr>
            <th>
                <label><?php _e('Invoice',JEM_PDFLITE); ?></label>

            </th>
            <td>
                <input disabled type="text" name="invoice" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','invoice',''); ?>">
                <?php echo self::get_available_in_pro_message(); ?>
            </td>
        </tr>
        <tr>
            <th>
                <label><?php _e('Proforma Invoice',JEM_PDFLITE); ?></label>
            </th>
            <td>
                <input disabled type="text" name="invoice-proforma" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','invoice-proforma',''); ?>">
                <?php echo self::get_available_in_pro_message(); ?>
            </td>
        </tr>
            <tr>
                <th>
                    <label><?php _e('Invoice Date',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="invoice-date" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','invoice-date',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Tax',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="invoice-tax" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','invoice-tax',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Tax %',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="tax-per" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','tax-per',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Price',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="invoice-price" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','invoice-price',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Quantity',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="quantity" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','quantity',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Shipping',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="shipping" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','shipping',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Shipping Tax',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="shipping-tax" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','shipping-tax',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Subtotal',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="subtotal" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','subtotal',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Cart Discount',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="cart-discount" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','cart-discount',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Order Discount',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="order-discount" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','order-discount',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Total',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="total" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','total',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Order Notes',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="order-notes" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','order-notes',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Amount in words',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="amount-words" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','amount-words',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Download Invoice',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="download-invoice" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','download-invoice',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Download Proforma Invoice',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="download-p-invoice" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','download-p-invoice',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Invoice Number',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="invoice-number" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','invoice-number',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Order Number',JEM_PDFLITE); ?></label>
                </th>
                <td>
                    <input disabled type="text" name="order-number" class="jem-half" value="<?php Jem_Pdf_Dashboard_Settings::get_value('localization','','order-number',''); ?>">
                    <?php echo self::get_available_in_pro_message(); ?>
                </td>
            </tr>

        </table>

<!--            <p class="submit">-->
<!--                <input type="hidden" name="jem_pdf" value="save_jem_pdf_localization">-->
<!--                <input name="save" type="submit" class="button-primary" value="--><?php //_e('Save changes',JEM_PDFLITE); ?><!--"/>-->
<!--            </p>-->

        </form>
        <?php
    }

}
endif;

?>