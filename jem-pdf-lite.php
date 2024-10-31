<?php
/*
Plugin Name: WooCommerce Invoices & Packing Slips Plugin
Plugin URI: http://www.jem-products.com/woocommerce-pdf-invoices
Description: JEM PDF Invoices for woocommerce.
Author: JEM Products
Version: 1.0.3
WC tested up to: 3.5.5
Author URI: https://jem-products.com
Text Domain: jem-pdf-lite
*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//FREEMIUS
if ( ! function_exists( 'jempdf_fs' ) ) {
    // Create a helper function for easy SDK access.
    function jempdf_fs() {
        global $jempdf_fs;

        if ( ! isset( $jempdf_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $jempdf_fs = fs_dynamic_init( array(
                'id'                  => '3024',
                'slug'                => 'pdf-invoices-for-woocommerce',
                'type'                => 'plugin',
                'public_key'          => 'pk_f2918fb9e0c7042d962dcfcdb531a',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'jem-pdf-pro',
                    'account'        => false,
                    'contact'        => false,
                    'support'        => false,
                    'parent'         => array(
                        'slug' => 'woocommerce',
                    ),
                ),
            ) );
        }

        return $jempdf_fs;
    }

    // Init Freemius.
    jempdf_fs();
    // Signal that SDK was initiated.
    do_action( 'jempdf_fs_loaded' );}

//END FREEMIUS

function jempdf_fs_custom_connect_message(
    $message,
    $user_first_name,
    $plugin_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
        __( 'Hey %1$s' ) . ',<br>' .
        __( ' never miss an important update -- opt-in to our security and feature updates notifications, and non-sensitive diagnostic tracking with freemius.com.', 'pdf-invoices-for-woocommerce' ),
        $user_first_name,
        '<b>' . $plugin_title . '</b>',
        '<b>' . $user_login . '</b>',
        $site_link,
        $freemius_link
    );
}

jempdf_fs()->add_filter('connect_message', 'jempdf_fs_custom_connect_message', 10, 6);

class Jem_Pdf_Invoices_WooCommerce
{

    //The temporary path
    const JEM_PDF_TEMP_PATH_CONST = 'jem_pdf_temp';

    protected static $_instance = null;
    public $find = array();
    public $replace = array();
    public $invoice_num = 0;
    public $invoice_date = '';
    public $invoice_date_formatted = '';

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function __construct()
    {
        define('JEM_PDFLITE', 'jem-pdf-lite');
        define('JEM_PDFLITE_VERSION',1.0);
        register_activation_hook(__FILE__, array($this,'jem_pdf_activation'));
        register_deactivation_hook(__FILE__, array($this,'jem_pdf_deactivation'));
        add_action('plugins_loaded',array($this,'jem_pdf_languages'));
        add_action('admin_menu', array($this, 'jem_pdf_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'jem_pdf_admin_scripts'));
        add_action('admin_init', array($this, 'jem_pdf_loader'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'jem_pdf_action_links'), 10, 1);
        add_action('init', array($this, 'jem_pdf_options'));
        add_action('wp_ajax_jem_pdf_output', array($this, 'jem_pdf_output'));
        add_action('wp_ajax_jem_pdf_output_packing_slip', array($this, 'jem_pdf_output_packing_slip'));
        add_action('wp_ajax_jem_pdf_update_invoice', array($this, 'jem_pdf_update_invoice'));
        add_filter('woocommerce_email_attachments', array($this, 'jem_pdf_email_general_attachment'), 999, 3);
        add_action('woocommerce_admin_order_actions_end', array($this, 'jem_pdf_order_action_admin'), 10, 1);
        add_action('woocommerce_new_order', array($this, 'jem_pdf_new_order'), 999, 1);
        add_action('woocommerce_new_order', array($this, 'jem_pdf_sequential_order_number'), 999, 1);
        add_filter('woocommerce_order_number', array($this, 'jem_pdf_order_number'), 999, 2);


        add_action('add_meta_boxes', array($this, 'jem_pdf_register_meta_box'));
        add_action('admin_head', array($this, 'jem_pdf_confirmation_msg'));
        add_action('wp_ajax_jem_pdf_order_invoice', array($this, 'jem_pdf_order_invoice'));
        add_action('woocommerce_my_account_my_orders_actions', array($this, 'jem_pdf_my_account_actions'), 999, 2);
        add_action('admin_notices',array($this,'jem_pdf_admin_notice'));
        add_action('jem_pdf_event',array($this,'jem_pdf_clear_temp'));
        add_filter('woocommerce_email_classes',array($this,'jem_pdf_email_classes'),10,1);
        add_filter('woocommerce_resend_order_emails_available',array($this,'jem_pdf_emails_available'),10,1);
        add_filter('gettext',array($this,'jem_pdf_strings'),999,3);

        if(is_admin()){
            add_filter('woocommerce_shop_order_search_fields',array($this,'jem_pdf_search_order'));
            add_filter('wc_pre_orders_search_fields',array( $this,'jem_pdf_search_order'));
            add_filter('request',array( $this,'jem_pdf_order_orderby'),999);
            add_filter('wc_pre_orders_edit_pre_orders_request',array($this,'jem_pdf_custom_orderby'),999,1);
        }
    }

    function jem_pdf_activation(){
        wp_schedule_event(time(),'daily','jem_pdf_event');
    }

    function jem_pdf_deactivation(){
        wp_clear_scheduled_hook('jem_pdf_event');
    }

    function jem_pdf_languages(){
        load_plugin_textdomain('jem-pdf-pro',false,plugin_basename(dirname( __FILE__ )).'/languages');
    }

    function jem_pdf_emails_available($actions){
        $actions[]='customer_cancelled_order';
        return $actions;
    }

    function jem_pdf_email_classes($classes){
        $classes['WC_Email_Customer_Cancelled_Order'] = include('classes/class-wc-email-customer-cancelled-order.php' );
        return $classes;
    }


    function jem_pdf_clear_temp() {

        $files = array();

        if( is_dir( $this->jem_pdf_temp_path() ) ) {
            $files = glob( $this->jem_pdf_temp_path() . "*.pdf");
            if(!empty($files)){
                foreach($files as $file){
                    @unlink($file);
                }
            }
        }
    }

    function jem_pdf_strings($translated_text, $text, $domain){

        $opts = get_option('_jem_pdf_opts');
        if(isset($domain) && $domain=='jem-pdf-pro'){
            switch($text){
                case 'Invoice' :
                    $translated_text = isset($opts['localization']['invoice']) && $opts['localization']['invoice']!='' ? $opts['localization']['invoice'] : $translated_text;
                    break;
                case 'Proforma Invoice' :
                    $translated_text = isset($opts['localization']['invoice-proforma']) && $opts['localization']['invoice-proforma']!='' ? $opts['localization']['invoice-proforma'] : $translated_text;
                    break;
                case 'Invoice Date' :
                    $translated_text = isset($opts['localization']['invoice-date']) && $opts['localization']['invoice-date']!='' ? $opts['localization']['invoice-date'] : $translated_text;
                    break;
                case 'Tax' :
                    $translated_text = isset($opts['localization']['invoice-tax']) && $opts['localization']['invoice-tax']!='' ? $opts['localization']['invoice-tax'] : $translated_text;
                    break;
                case 'Tax %' :
                    $translated_text = isset($opts['localization']['tax-per']) && $opts['localization']['tax-per']!='' ? $opts['localization']['tax-per'] : $translated_text;
                    break;
                case 'Price' :
                    $translated_text = isset($opts['localization']['invoice-price']) && $opts['localization']['invoice-price']!='' ? $opts['localization']['invoice-price'] : $translated_text;
                    break;
                case 'Quantity' :
                    $translated_text = isset($opts['localization']['quantity']) && $opts['localization']['quantity']!='' ? $opts['localization']['quantity'] : $translated_text;
                    break;
                case 'Shipping' :
                    $translated_text = isset($opts['localization']['shipping']) && $opts['localization']['shipping']!='' ? $opts['localization']['shipping'] : $translated_text;
                    break;
                case 'Shipping Tax' :
                    $translated_text = isset($opts['localization']['shipping-tax']) && $opts['localization']['shipping-tax']!='' ? $opts['localization']['shipping-tax'] : $translated_text;
                    break;
                case 'Subtotal' :
                    $translated_text = isset($opts['localization']['subtotal']) && $opts['localization']['subtotal']!='' ? $opts['localization']['subtotal'] : $translated_text;
                    break;
                case 'Cart Discount' :
                    $translated_text = isset($opts['localization']['cart-discount']) && $opts['localization']['cart-discount']!='' ? $opts['localization']['cart-discount'] : $translated_text;
                    break;
                case 'Order Discount' :
                    $translated_text = isset($opts['localization']['order-discount']) && $opts['localization']['order-discount']!='' ? $opts['localization']['order-discount'] : $translated_text;
                    break;
                case 'Total' :
                    $translated_text = isset($opts['localization']['total']) && $opts['localization']['total']!='' ? $opts['localization']['total'] : $translated_text;
                    break;
                case 'Order Notes' :
                    $translated_text = isset($opts['localization']['order-notes']) && $opts['localization']['order-notes']!='' ? $opts['localization']['order-notes'] : $translated_text;
                    break;
                case 'Amount in words' :
                    $translated_text = isset($opts['localization']['amount-words']) && $opts['localization']['amount-words']!='' ? $opts['localization']['amount-words'] : $translated_text;
                    break;
                case 'Download Invoice' :
                    $translated_text = isset($opts['localization']['download-invoice']) && $opts['localization']['download-invoice']!='' ? $opts['localization']['download-invoice'] : $translated_text;
                    break;
                case 'Download Proforma Invoice' :
                    $translated_text = isset($opts['localization']['download-p-invoice']) && $opts['localization']['download-p-invoice']!='' ? $opts['localization']['download-p-invoice'] : $translated_text;
                    break;
                case 'Invoice Number' :
                    $translated_text = isset($opts['localization']['invoice-number']) && $opts['localization']['invoice-number']!='' ? $opts['localization']['invoice-number'] : $translated_text;
                    break;
                case 'Order Number' :
                    $translated_text = isset($opts['localization']['order-number']) && $opts['localization']['order-number']!='' ? $opts['localization']['order-number'] : $translated_text;
                    break;
            }
        }
        return stripslashes($translated_text);
    }

    function jem_pdf_search_order( $search_fields ) {
        array_push( $search_fields, '_jem_pdf_sequential_order_number' );
        return $search_fields;
    }

    function jem_pdf_order_orderby( $vars ) {
        global $typenow, $wp_query;

        if ( 'shop_order' === $typenow ) {
            return $vars;
        }
        return $this->jem_pdf_custom_orderby( $vars );
    }

    function jem_pdf_custom_orderby($args){

        if ( isset( $args['orderby'] ) && 'ID' == $args['orderby'] ) {
            $args = array_merge( $args, array(
                'meta_key' => '_order_number',
                'orderby'  => 'meta_value_num',
            ) );
        }
        return $args;
    }


    function jem_pdf_register_meta_box()
    {
        add_meta_box('jem-pdf-pro', __('JEM PDF Invoices', JEM_PDFLITE), array($this, 'jem_pdf_display_box'), 'shop_order', 'side');
    }

    function jem_pdf_confirmation_msg()
    {

        global $post;
        if(isset($post) && is_object($post)){
            ?>
            <script type="text/javascript">
                //<![CDATA[
                jem_download_in = '<?php _e('This invoice has not been created yet – are you sure you want to create it?',JEM_PDFLITE); ?>';
                jem_proforma_in = '<?php _e('This invoice has not been created yet – are you sure you want to create it?',JEM_PDFLITE); ?>';
                jem_delete_in = '<?php _e('Are you sure ? you want to delete invoice.',JEM_PDFLITE); ?>';
                jem_delete_pr = '<?php _e('Are you sure ? you want to delete proforma invoice.',JEM_PDFLITE); ?>';
                jem_invoice_generated = <?php echo get_post_meta($post->ID,'_jem_invoice_generated_'.$post->ID,true)==1 ? 1 : 0; ?>;
                jem_proforma_generated = <?php echo get_post_meta($post->ID,'_jem_proforma_generated__'.$post->ID,true)==1 ? 1 : 0; ?>;
                //]]>
            </script>
            <?php
        }
    }

    //Shows the Meta Box in the admin's view order screen
    function jem_pdf_display_box()
    {

        global $jem_pdf;
        global $post;
        $order = wc_get_order($post->ID);

        $this->invoice_num = get_post_meta($post->id,'_jem_pdf_general_order_number',true) && get_post_meta($post->id,'_jem_pdf_general_order_number',true) != '' ? get_post_meta($post->id,'_jem_pdf_general_order_number',true) : wc_get_order($order)->get_order_number();
        $this->invoice_date = get_post_meta($post->id,'_jem_invoice_generated_time'.$post->id,true) && get_post_meta($post->id,'_jem_invoice_generated_time'.$post->id,true) != '' ? get_post_meta($post->id,'_jem_invoice_generated_time'.$post->id,true) : date('Y-m-d');
        $this->invoice_date_formatted = $this->jem_get_order_date(strtotime($this->invoice_date) );

        $url1 = wp_nonce_url(admin_url('admin-ajax.php?action=jem_pdf_output&order=' . $post->ID . '&type=download&gen=true&screen=order'), 'admin_invoice', 'jem_pdf');
        $packingSlipURL = wp_nonce_url(admin_url('admin-ajax.php?action=jem_pdf_output_packing_slip&order=' . $post->ID . '&type=output-packing-slip&screen=order'), 'admin_packing_slip', 'jem_pdf');
        $updateURL = wp_nonce_url(admin_url('admin-ajax.php?action=jem_pdf_update_invoice&order=' . $post->ID . '&type=update-invoice&screen=order'), 'update_invoice', 'jem_pdf');

        ?>

        <div class="overlay">
            <div id="loading-img"></div>
        </div>

        <div class="jem-order-action">

            <form id="jem-update-invoice" method="post">
            <div class="jem-meta-info">
                Invoice Date:
            </div>
            <div class="jem-clearfix"></div>
            <div class="jem-meta-info">
                <input type="text" class="date-picker-field" name="jem-invoice-date" id="jem-invoice-date" value="<?php echo date_i18n( 'Y-m-d', strtotime($this->invoice_date) )?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])">
            </div>
            <div class="jem-clearfix"></div>
            <div class="jem-meta-info">
                Invoice Number:
            </div>
            <div class="jem-clearfix"></div>
            <div class="jem-meta-info">
                <input type="text" name="jem-invoice-number" id="jem-invoice-number" value="<?php echo $this->invoice_num; ?>">
            </div>
            <div class="jem-clearfix"></div>

            <div class="jem-button ">
                <a href="<?php echo $updateURL; ?>" class="jem-pdf-update-invoice button "><?php _e('Update Invoice',JEM_PDFLITE); ?></a>
            </div>

            <div class="jem-button">
                <a href="<?php echo $url1; ?>" class="jem-in-download button"><?php _e('View Invoice',JEM_PDFLITE); ?></a>
            </div>

            <?php if (!get_post_meta($post->ID, 'jem_invoice_deleted_' . $post->ID, true)) { ?>
                <div class="jem-button">
                    <a href="<?php echo $packingSlipURL; ?>" class="button" target="_blank"><?php _e('View Packing Slip',JEM_PDFLITE); ?></a>
                </div>
            <?php } ?>
            </form>
        </div>
        <?php
    }

    function jem_pdf_my_account_actions($actions, $order)
    {
        global $jem_pdf;
        $show_invoice = get_post_meta($order->id, '_jem_invoice_generated_' . $order->id, true) && get_post_meta($order->id, '_jem_invoice_generated_' . $order->id, true) == 1 ? true : false;


        if ($show_invoice && $order->has_status(array('completed'))) {
            $actions['download_invoice'] = array('url' => wp_nonce_url(admin_url('admin-ajax.php?action=jem_pdf_output&order='.$order->id.'&type=download'), 'admin_invoice', 'jem_pdf'), 'name' => __('Invoice', JEM_PDFLITE));
        }
        return $actions;

    }

    //Called via AJAX
    function jem_pdf_order_invoice()
    {

        $order = sanitize_text_field($_GET['order']);

        if (isset($_GET['jem_pdf']) && wp_verify_nonce($_GET['jem_pdf'], 'admin_invoice')) {


            if (isset($_GET['type']) && $_GET['type'] == 'delete-invoice') {
                update_post_meta($order, '_jem_invoice_generated_' . $order, 0);
            } elseif (isset($_GET['type']) && $_GET['type'] == 'delete-proforma') {
                update_post_meta($order, '_jem_proforma_generated_' . $order, 0);
            }

        }
        wp_redirect(admin_url('post.php?post='. $order .'&action=edit&deleted='.$_GET['type']));
        exit;
    }

    function jem_pdf_admin_notice(){

        if(isset($_GET['deleted']) && $_GET['deleted']=='delete-invoice'){
                $class = 'notice notice-success';
                $message = __( 'Invoice deleted successfully.',JEM_PDFLITE);
                printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
        }
        elseif(isset($_GET['deleted']) && $_GET['deleted']=='delete-proforma'){
            $class = 'notice notice-success';
            $message = __( 'Proforma Invoice deleted successfully.',JEM_PDFLITE);
            printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
        }

    }




    function jem_pdf_library_loader()
    {

        if (!class_exists('DOMPDF')) {
            require_once($this->jem_pdf_path("/lib/dompdf/dompdf_config.inc.php"));
        }
        $pdf = new Dompdf();
        return $pdf;

    }

    function get_jem_pdf_template($type = 'proforma')
    {

        global $jem_pdf;


        if ($type === 'packing-slip'){
            $template = $this->jem_get_packing_slip_template();
            if (!file_exists($template) || $template == '') {
                $template = $this->jem_pdf_template_path() . "default-packing-slip.php";
            }
        } else {
            $template = $this->jem_get_general_invoice_template();
            if (!file_exists($template) || $template == '') {
                $template = $this->jem_pdf_template_path() . "default-invoice.php";
            }

        }
        return $template;
    }


    function get_jem_pdf_templates()
    {

        $files = array();
        if (is_dir(get_template_directory() . '/jem-pdf-pro')) {
            $files = glob(get_template_directory() . '/jem-pdf-pro/' . "*.php");
        }
        return $files;
    }

    function jem_pdf_template_path()
    {
        return $this->jem_pdf_path("/templates/");
    }

    function get_jem_file_name($id = '', $type)
    {

        $type = isset($type) && $type!='' ? $type : 'jem';

        if (isset($_GET['order']) && $_GET['order'] > 0) {
            $id = 'Invoice-' . $type . '-' . $_GET['order'];
        } elseif (isset($id) && $id > 0) {
            $id = 'Invoice-' . $type . '-' . $id;
        } else {
            $id = 'Invoice';
        }
        return $id . '.pdf';
    }

    //get's called when a new order is created
    //Creates the things we don't want to change, basically invoice number etc
    function jem_pdf_new_order($id)
    {

        global $wpdb;


        //Update the invoice created date
        update_post_meta($id, '_jem_invoice_generated_time'.$id, time());


        //Custom invoice number?
        $general_order_number = $this->jem_get_general_invoice_num_type();
        if (isset($general_order_number) && $general_order_number == 'custom-invoce') {

            $last_id = is_numeric(get_option('_jem_pdf_next_in_no')) && get_option('_jem_pdf_next_in_no')>0 ? get_option('_jem_pdf_next_in_no') : 1;
            if(!$last_id) {
                $last_id = $this->jem_get_general_invoice_start();
            }

            $jem_pdf_opts = get_option('_jem_pdf_opts');
            $jem_pdf_opts['general']['general_invoice']['invoice-number']=$last_id+1;
            update_option('_jem_pdf_opts',$jem_pdf_opts);
            update_option('_jem_pdf_next_in_no',$last_id+1);


            update_post_meta($id, '_jem_pdf_gn_order_number', $last_id);
            $general_invoice_num = str_pad($last_id, (int)$this->jem_get_general_invoice_num_digit(), '0', STR_PAD_LEFT);

            //generate invoice numbers and replace prefix/suffix
            $pre = $this->jem_get_general_invoice_prefix() != '' ? $this->jem_get_general_invoice_prefix()  : '';
            $pre = $this->jem_replace_invoice_num($pre);

            $suf = $this->jem_get_general_invoice_suffix() != '' ? $this->jem_get_general_invoice_suffix()  : '';
            $suf = $this->jem_replace_invoice_num($suf);

            //update the saved order number
            update_post_meta($id, '_jem_pdf_general_order_number', $pre . $general_invoice_num . $suf);


        }


    }

    /**
     * This is called by ajax and updates the invoice details, in particular invoice number and date
     * it uses whatever the client has entered on the Order screen
     */
    function jem_pdf_update_invoice(){


        if (!isset($_GET['jem_pdf']) || !wp_verify_nonce($_GET['jem_pdf'], 'update_invoice')){
            wp_send_json_error();
            return;
        }

        $date = sanitize_text_field( $_GET['invoice-date'] );
        $number = sanitize_text_field( $_GET['invoice-number'] );
        $order_id = sanitize_text_field( $_GET['order'] );

        //Any error checking we want to do here???
        update_post_meta($order_id,'_jem_pdf_general_order_number', $number);
        update_post_meta($order_id,'_jem_invoice_generated_time'.$order_id, $date);

        wp_send_json_success();

    }

    /**
     * Does the replace for the invoice prefix / suffix
     * Put it in a single place so we can easily expand/change over time
     * @param $num
     * @return mixed
     */
    function jem_replace_invoice_num($num){

        $year = date("Y");
        $month = date("d");

        $num = str_replace("{{year}}", $year, $num);
        $num = str_replace("{{month}}", $month, $num);
        return $num;
    }
    function jem_pdf_sequential_order_number($id){
        global $wpdb;
        $last_id = $wpdb->get_var("SELECT IF( MAX( CAST( meta_value as UNSIGNED ) ) IS NULL, 0, MAX( CAST( meta_value as UNSIGNED ) ) + 1 ) as order_id FROM {$wpdb->postmeta} WHERE meta_key='_jem_pdf_sequential_order_number'");
        if(!$last_id){
            update_post_meta($id,'_jem_pdf_sequential_order_number',$id);
        }
        else
        {
            update_post_meta($id,'_jem_pdf_sequential_order_number',$last_id);
        }
    }

    function jem_pdf_order_number($id, $obj)
    {

        $gn_order_num = get_post_meta($id, '_jem_pdf_sequential_order_number', true);
        if ($gn_order_num == "") {
            return $id;
        } else {
            return $gn_order_num;
        }

    }

    function jem_pdf_do_find_replace($order, $content)
    {

        $this->find = array(
            '{{buyer_first_name}}',
            '{{buyer_last_name}}',
            '{{buyer_company}}',
            '{{buyer_address_1}}',
            '{{buyer_address_2}}',
            '{{buyer_city}}',
            '{{buyer_state}}',
            '{{buyer_postcode}}',
            '{{buyer_country}}',
            '{{invoice_number}}',
            '{{invoice_date}}',
            '{{order_id}}',
            '{{order_date}}',
            '{{payment_method}}',
            '{{shipping_first_name}}',
            '{{shipping_last_name}}',
            '{{shipping_company}}',
            '{{shipping_address_1}}',
            '{{shipping_address_2}}',
            '{{shipping_city}}',
            '{{shipping_state}}',
            '{{shipping_postcode}}',
            '{{shipping_country}}',
            '{{billing_first_name}}',
            '{{billing_last_name}}',
            '{{billing_company}}',
            '{{billing_address_1}}',
            '{{billing_address_2}}',
            '{{billing_city}}',
            '{{billing_state}}',
            '{{billing_postcode}}',
            '{{billing_country}}',
            '{{billing_email}}',
            '{{billing_phone}}',
            '{{shipping_method}}',
            '{{order_note}}',
        );
        $this->replace = array(
            $order->get_billing_first_name(),
            $order->get_billing_last_name(),
            $order->get_billing_company(),
            $order->get_billing_address_1(),
            $order->get_billing_address_2(),
            $order->get_billing_city(),
            $order->get_billing_state(),
            $order->get_billing_postcode(),
            $order->get_billing_country(),
            $this->invoice_num,
            $this->jem_get_order_date($this->invoice_date),
            $order->get_order_number(),
            $this->jem_get_order_date(strtotime($order->get_date_created())),
            $order->get_payment_method_title(),
            $order->get_shipping_first_name(),
            $order->get_shipping_last_name(),
            $order->get_shipping_company(),
            $order->get_shipping_address_1(),
            $order->get_shipping_address_2(),
            $order->get_shipping_city(),
            $order->get_shipping_state(),
            $order->get_shipping_postcode(),
            $order->get_shipping_country(),
            $order->get_billing_first_name(),
            $order->get_billing_last_name(),
            $order->get_billing_company(),
            $order->get_billing_address_1(),
            $order->get_billing_address_2(),
            $order->get_billing_city(),
            $order->get_billing_state(),
            $order->get_billing_postcode(),
            $order->get_billing_country(),
            $order->get_billing_email(),
            $order->get_billing_phone(),
            $order->get_shipping_method( ),
            $order->get_customer_note(),
        );

        return preg_replace($this->find, $this->replace, $content);
    }

    function get_jem_buyer_details($order)
    {
        global $jem_pdf;
        if (isset($jem_pdf['header']['buyer-detail']) && $jem_pdf['header']['buyer-detail'] != '') {
            return $this->jem_pdf_do_find_replace($order, $jem_pdf['header']['buyer-detail']);
        }
    }

    function get_jem_shipping_details($order)
    {
        global $jem_pdf;
        if (isset($jem_pdf['header']['shipping-detail']) && $jem_pdf['header']['shipping-detail'] != '') {
            return $this->jem_pdf_do_find_replace($order, $jem_pdf['header']['shipping-detail']);
        }
    }

    function get_jem_invoice_details($order)
    {
        global $jem_pdf;
        if (isset($jem_pdf['header']['invoice-detail']) && $jem_pdf['header']['invoice-detail'] != '') {
            return $this->jem_pdf_do_find_replace($order, $jem_pdf['header']['invoice-detail']);
        }
    }

    function get_jem_invoice_details_packing_slip($order)
    {
        global $jem_pdf;
        if (isset($jem_pdf['header']['invoice-detail-packing-slip']) && $jem_pdf['header']['invoice-detail-packing-slip'] != '') {
            return $this->jem_pdf_do_find_replace($order, $jem_pdf['header']['invoice-detail-packing-slip']);
        }
    }

    /**
     *
     * @param $order
     * @return mixed
     */
    function get_jem_invoice_num($order)
    {
        global $jem_pdf;
        if (isset($jem_pdf['header']['invoice-num']) && $jem_pdf['header']['buyer-detail'] != '') {
            return $this->jem_pdf_do_find_replace($order, $jem_pdf['header']['buyer-detail']);
        }
    }

    function jem_format_lines($content)
    {

        global $jem_pdf;
        if (isset($jem_pdf['header']['empty-lines']) && $jem_pdf['header']['empty-lines']) {
            $content = preg_replace("#(<br\s*/?>\s*)+#", "<br />", $content);
        }

        return $content;

    }

    /**
     * Generate the HTML invoice for an order
     *
     * @param $id
     * @param string $type
     */
    function get_jem_pdf_invoice_html($id, $type = '')
    {

        global $jem_pdf;
        $order = wc_get_order($id);

        //These fields are used in the INCLUDE template file
        $template_settings = $this->get_template_settings();
        $buyer_detail = $this->jem_format_lines(str_replace("\r\n", "<br />", $this->get_jem_buyer_details($order)));
        $shipping_detail = $this->jem_format_lines(str_replace("\r\n", "<br />", $this->get_jem_shipping_details($order)));

        if($type === 'packing-slip'){
            $invoice_detail = $this->jem_format_lines(str_replace("\r\n", "<br />", $this->get_jem_invoice_details_packing_slip($order)));
        } else {
            $invoice_detail = $this->jem_format_lines(str_replace("\r\n", "<br />", $this->get_jem_invoice_details($order)));
        }

        $logo_id = isset($jem_pdf['header']['jem_pdf_logo']) ? $jem_pdf['header']['jem_pdf_logo'] : '';
        $customer_name = $this->get_jem_pdf_customer_name($order);
        $store_name = isset($jem_pdf['header']['store-name']) ? $jem_pdf['header']['store-name'] : '';
        $store_address = isset($jem_pdf['header']['store-address']) ? str_replace("\r\n", "<br />", $jem_pdf['header']['store-address']) : '';
        include($this->get_jem_pdf_template($type));
    }


    function get_template_settings()
    {

        global $jem_pdf;
        $template_settings = array();
        $template_settings['blank_line_remove'] = isset($jem_pdf['content']['line-remove-blank-lines']) ? $jem_pdf['content']['line-remove-blank-lines'] : '';
        $template_settings['line_sku'] = isset($jem_pdf['content']['line-sku']) ? $jem_pdf['content']['line-sku'] : '';
        $template_settings['line_category'] = isset($jem_pdf['content']['line-category']) ? $jem_pdf['content']['line-category'] : '';
        $template_settings['line_short_desc'] = isset($jem_pdf['content']['line-short-desc']) ? $jem_pdf['content']['line-short-desc'] : '';
        $template_settings['line_image'] = isset($jem_pdf['content']['line-image']) ? $jem_pdf['content']['line-image'] : '';
        $template_settings['shipping_if_free'] = isset($jem_pdf['content']['shipping-if-free']) ? $jem_pdf['content']['shipping-if-free'] : '';
        $template_settings['line_tax'] = isset($jem_pdf['content']['line-tax']) ? $jem_pdf['content']['line-tax'] : '';
        $template_settings['line_excl_tax'] = isset($jem_pdf['content']['line-excl-tax']) ? $jem_pdf['content']['line-excl-tax'] : '';
        $template_settings['tax_total'] = isset($jem_pdf['content']['tax-totals']) ? $jem_pdf['content']['tax-totals'] : '';
        return $template_settings;
    }

    function jem_pdf_tax_display_cart(){
        global $jem_pdf;
        $tex_display = isset($jem_pdf['content']['line-excl-tax']) && $jem_pdf['content']['line-excl-tax']==1 ? 'excl' : 'incl';
        return $tex_display;
    }

    function jem_pdf_tax_totals(){
        global $jem_pdf;
        $tax_total = isset($jem_pdf['content']['tax-totals']) && $jem_pdf['content']['tax-totals']==1 ? 1 : 0;
        return $tax_total;
    }


    /**
     * Gathers the relevent totals for this order
     * @param $order
     * @return mixed
     */
    function get_order_item_totals($order)
    {

        $template_settings = $this->get_template_settings();
        $tax_display = $this->jem_pdf_tax_display_cart();


        $total_rows = array();

        //if ($subtotal = $order->get_subtotal_to_display(false,$tax_display)){
        if ($subtotal = $order->get_subtotal_to_display(false,false)){
            $total_rows['cart_subtotal'] = array(
                'label' => __('Subtotal', JEM_PDFLITE).':',
                'value' => $subtotal
            );
        }

        if ($order->get_total_discount() > 0) {
            $total_rows['discount'] = array(
                'label' => __('Discount', JEM_PDFLITE).':',
                'value' => '-' . $order->get_discount_to_display(false)
            );
        }

        //if ($order->get_shipping_method() && $order->order_shipping>0) {
        if ($order->get_shipping_method() && $order->get_shipping_total()>0) {
            $total_rows['shipping'] = array(
                'label' => __('Shipping', JEM_PDFLITE).':',
                'value' => $order->get_shipping_to_display(false)
            );
        }

        if($template_settings['shipping_if_free'] && $order->get_shipping_total() == 0) {

            $total_rows['shipping'] = array(
                'label' => __('Shipping', JEM_PDFLITE).':',
                'value' => __('Free', JEM_PDFLITE),
            );

        }

        if ($fees = $order->get_fees()) {
            foreach ($fees as $id => $fee) {

                if (apply_filters('woocommerce_get_order_item_totals_excl_free_fees', $fee['line_total'] + $fee['line_tax'] == 0, $id)) {
                    continue;
                }

                if ('excl' == $tax_display) {

                    $total_rows['fee_' . $id] = array(
                        'label' => ($fee['name'] ? $fee['name'] : __('Fee', JEM_PDFLITE)) . ':',
                        //'value' => wc_price($fee['line_total'], array('currency' => $order->get_order_currency()))
                        'value' => wc_price($fee['line_total'], array('currency' => $order->get_currency()))
                    );

                } else {

                    $total_rows['fee_' . $id] = array(
                        'label' => $fee['name'] . ':',
                        //'value' => wc_price($fee['line_total'] + $fee['line_tax'], array('currency' => $order->get_order_currency()))
                        'value' => wc_price($fee['line_total'] + $fee['line_tax'], array('currency' => $order->get_currency()))
                    );
                }
            }
        }


        //Total Tax
        $total_rows['tax'] = array(
            'label' => WC()->countries->tax_or_vat() . ':',
            //'value' => wc_price($order->get_total_tax(), array('currency' => $order->get_order_currency()))
            'value' => wc_price($order->get_total_tax(), array('currency' => $order->get_currency()))
        );

        // Tax for tax exclusive prices.
//        if ($this->jem_pdf_tax_totals()) {
//
//            if (get_option('woocommerce_tax_total_display') == 'itemized' && $template_settings['tax_total']) {
//
//                foreach ($order->get_tax_totals() as $code => $tax) {
//
//                    $total_rows[sanitize_title($code)] = array(
//                        'label' => $tax->label . ':',
//                        'value' => $tax->formatted_amount
//                    );
//                }
//
//            } elseif ($template_settings['tax_total']) {
//
//                $total_rows['tax'] = array(
//                    'label' => WC()->countries->tax_or_vat() . ':',
//                    'value' => wc_price($order->get_total_tax(), array('currency' => $order->get_order_currency()))
//                );
//            }
//        }
//
//        if ($order->get_total() > 0 && $order->payment_method_title) {
//            $total_rows['payment_method'] = array(
//                'label' => __('Payment Method', JEM_PDFLITE).':',
//                'value' => $order->payment_method_title
//            );
//        }


        if ($refunds = $order->get_refunds()) {
            foreach ($refunds as $id => $refund) {
                $total_rows['refund_' . $id] = array(
                    'label' => $refund->get_refund_reason() ? $refund->get_refund_reason() : __('Refund', JEM_PDFLITE) . ':',
                    //'value' => wc_price('-' . $refund->get_refund_amount(), array('currency' => $order->get_order_currency()))
                    'value' => wc_price('-' . $refund->get_refund_amount(), array('currency' => $order->get_currency()))
                );
            }
        }

        $total_rows['order_total'] = array(
            'label' => __('Total', JEM_PDFLITE).':',
            'value' => $order->get_formatted_order_total($tax_display)
        );

        return apply_filters('woocommerce_get_order_item_totals', $total_rows, $order);
    }

    /**
     * Produces the actual invoice, called from an AJAX call
     * TODO - from the admin screen only???
     */
    function jem_pdf_output()
    {

        //Make sure the nonce is good


        if (isset($_GET['jem_pdf']) && wp_verify_nonce($_GET['jem_pdf'], 'admin_invoice')) {


            $type = isset($_GET['invoice']) ? strtolower($_GET['invoice']) : '';


            $order = sanitize_text_field($_GET['order']);

            if (isset($_GET['screen']) && $_GET['screen'] == 'order' && !(int)get_post_meta($_GET['order'], '_jem_invoice_generated_' . $_GET['order'], true)) {
                update_post_meta($order, '_jem_invoice_generated_time' . $order, time());
                update_post_meta($order, '_jem_invoice_generated_' . $order, true);
            }
            update_post_meta($order, '_jem_invoice_generated_' . $order, 1);
            $this->invoice_num = get_post_meta($order, '_jem_pdf_general_order_number', true) && get_post_meta($order, '_jem_pdf_general_order_number', true) != '' ? get_post_meta($order, '_jem_pdf_general_order_number', true) : wc_get_order($order)->get_order_number();
            $this->invoice_date = get_post_meta($order, '_jem_invoice_generated_time' . $order, true) && get_post_meta($order, '_jem_invoice_generated_time' . $order, true) != '' ? get_post_meta($order, '_jem_invoice_generated_time' . $order, true) : time();
            $this->invoice_date_formatted = $this->jem_get_order_date($this->invoice_date);

            //This is where we generate the actual invoice.
            $dompdf = $this->jem_pdf_library_loader();
            ob_start();
            $this->get_jem_pdf_invoice_html($_GET['order'], $type);
            $pdf_html = ob_get_clean();
            //FOR TESTING PURPOSES
            //TODO - put this in a setting
            //echo $pdf_html;
            //return;

            $dompdf->load_html($pdf_html);
            $dompdf->set_paper($this->jem_get_paper_size());
            $dompdf->render();
            $pdf = $dompdf->output();
            $name = "invoice-" . $this->invoice_num . ".pdf";
            if (isset($_GET['type']) && $_GET['type'] == 'view') {
                header('Content-type: application/pdf');
                header('Content-Disposition: inline; filename="' . $name . '"');
                echo $pdf;
            } else {
                file_put_contents($this->jem_pdf_temp_path() . $name, $pdf);
                $dompdf->stream($name);
            }

        }

    }

    /**
     * Produces the packing slip
     * TODO - we should combine w/the invoice output
     */
    function jem_pdf_output_packing_slip()
    {

        //Make sure the nonce is good
        if (isset($_GET['jem_pdf']) && wp_verify_nonce($_GET['jem_pdf'], 'admin_packing_slip')) {

            $order = sanitize_text_field($_GET['order']);

            $this->invoice_num = get_post_meta($order, '_jem_pdf_general_order_number', true) && get_post_meta($order, '_jem_pdf_general_order_number', true) != '' ? get_post_meta($order, '_jem_pdf_general_order_number', true) : wc_get_order($order)->get_order_number();

            //This is where we generate the actual packing slip
            $dompdf = $this->jem_pdf_library_loader();
            ob_start();
            $this->get_jem_pdf_invoice_html($_GET['order'], 'packing-slip');
            $pdf_html = ob_get_clean();
            //FOR TESTING PURPOSES
            //echo $pdf_html;
            //return;

            $dompdf->load_html($pdf_html);
            $dompdf->set_paper($this->jem_get_paper_size());
            $dompdf->render();
            $pdf = $dompdf->output();

            $name = "packing-slip-" . $this->invoice_num. ".pdf";

            if (isset($_GET['type']) && $_GET['type'] == 'view') {
                header('Content-type: application/pdf');
                header('Content-Disposition: inline; filename="' . $name . '"');
                echo $pdf;
            } else {
                file_put_contents($this->jem_pdf_temp_path(). $name, $pdf);
                $dompdf->stream($name);
            }


        }

    }


    function jem_pdf_email_general_attachment($attachments, $id, $object)
    {

        global $jem_pdf;
        $attach = false;

        if(in_array($id,$this->jem_get_general_invoice_emails()) || ($id=='customer_partially_refunded_order' && in_array('customer_refunded_order',$this->jem_get_general_invoice_emails())) ){
            $attach = true;
        }
        if(!$attach){
            return $attachments;
        }
        if (isset($jem_pdf['general']['options']['disbale-invoice']) && $jem_pdf['general']['options']['disbale-invoice'] && $object->order_total == 0) {
            return $attachments;
        }

        $type = '';

        if(!(int)get_post_meta($object->id,'_jem_invoice_generated_'.$object->id,true)){
            update_post_meta($object->id,'_jem_invoice_generated_time'.$object->id,time());
            update_post_meta($object->id, '_jem_invoice_generated_' . $object->id, 1);
        }

        $this->invoice_date = get_post_meta($object->id, '_jem_invoice_generated_time'.$object->id,true) && get_post_meta($object->id,'_jem_invoice_generated_time'.$object->id,true) != '' ? get_post_meta($object->id,'_jem_invoice_generated_time'.$object->id,true) : time();
        $this->invoice_date_formatted = $this->jem_get_order_date($this->invoice_date );

        $this->invoice_num = get_post_meta($object->id, '_jem_pdf_general_order_number', true) && get_post_meta($object->id, '_jem_pdf_general_order_number', true) != '' ? get_post_meta($object->id, '_jem_pdf_general_order_number', true) : wc_get_order($object->id)->get_order_number();

        $dompdf = $this->jem_pdf_library_loader();
        ob_start();
        $this->get_jem_pdf_invoice_html($object->id,$type);
        $pdf_html = ob_get_clean();
        $dompdf->load_html($pdf_html);
        $dompdf->set_paper($this->jem_get_paper_size());
        $dompdf->render();
        $pdf = $dompdf->output();
        $name = $this->get_jem_file_name($object->id,$type);
        file_put_contents($this->jem_pdf_temp_path() . $name, $pdf);
        $attachments[] = $this->jem_pdf_temp_path() . $name;
        return $attachments;

    }


    function jem_get_general_invoice_start()
    {
        global $jem_pdf;
        $invoice_num = isset($jem_pdf['general']['general_invoice']['invoice-number']) && $jem_pdf['general']['general_invoice']['invoice-number'] > 0 ? $jem_pdf['general']['general_invoice']['invoice-number'] : 1;
        return $invoice_num;
    }


    function jem_get_general_invoice_prefix()
    {
        global $jem_pdf;
        $prefix = isset($jem_pdf['general']['general_invoice']['invoice-prefix']) && $jem_pdf['general']['general_invoice']['invoice-prefix'] != '' ? $jem_pdf['general']['general_invoice']['invoice-prefix'] : '';
        return $prefix;
    }

    function jem_get_general_invoice_suffix()
    {
        global $jem_pdf;
        $suffix = isset($jem_pdf['general']['general_invoice']['invoice-suffix']) && $jem_pdf['general']['general_invoice']['invoice-suffix'] != '' ? $jem_pdf['general']['general_invoice']['invoice-suffix'] : '';
        return $suffix;
    }

    function jem_get_paper_size()
    {
        global $jem_pdf;
        $paper_size = isset($jem_pdf['general']['options']['paper-size']) && $jem_pdf['general']['options']['paper-size'] != '' ? $jem_pdf['general']['options']['paper-size'] : 'A4';
        return $paper_size;
    }

    function jem_get_general_invoice_emails()
    {

        global $jem_pdf;
        $emails = isset($jem_pdf['general']['general_invoice']['attach-to-mail']) && is_array($jem_pdf['general']['general_invoice']['attach-to-mail']) ? $jem_pdf['general']['general_invoice']['attach-to-mail'] : array();
        return $emails;
    }

//    function jem_get_proforma_invoice_emails()
//    {
//
//        global $jem_pdf;
//        $emails = isset($jem_pdf['general']['proforma_invoice']['attach-to-mail']) && is_array($jem_pdf['general']['proforma_invoice']['attach-to-mail']) ? $jem_pdf['general']['proforma_invoice']['attach-to-mail'] : array();
//        return $emails;
//    }

    //Turns the date into the format specified in the settings
    function jem_get_order_date($order_date)
    {

        global $jem_pdf;
        $date_format = isset($jem_pdf['general']['options']['date-format']) && $jem_pdf['general']['options']['date-format'] != '' ? str_replace(array('YY', 'MM', 'DD'), array('Y', 'm', 'd'), $jem_pdf['general']['options']['date-format']) : 'Y-m-d';
        //return date('d-m-Y H:i:s',$order_date);
        return date($date_format,$order_date);
    }

    function jem_get_general_invoice_template()
    {

        global $jem_pdf;
        $template = isset($jem_pdf['general']['options']['template']) && $jem_pdf['general']['options']['template'] != '' ? $jem_pdf['general']['options']['template'] : $this->jem_pdf_template_path() . "default-invoice.php";
        return $template;
    }

    function jem_get_packing_slip_template()
    {

        global $jem_pdf;
        $template = isset($jem_pdf['general']['options']['packing-slip-template']) && $jem_pdf['general']['options']['packing-slip-template'] != '' ? $jem_pdf['general']['options']['packing-slip-template'] : $this->jem_pdf_template_path() . "default-packing-slip.php";
        return $template;
    }

    function jem_get_general_invoice_num_digit()
    {

        global $jem_pdf;
        $digit = isset($jem_pdf['general']['general_invoice']['invoice-digit']) && $jem_pdf['general']['general_invoice']['invoice-digit'] != '' ? $jem_pdf['general']['general_invoice']['invoice-digit'] : 0;
        return $digit;
    }

//    function jem_get_proforma_invoice_num_digit()
//    {
//
//        global $jem_pdf;
//        $digit = isset($jem_pdf['general']['proforma_invoice']['invoice-digit']) && $jem_pdf['general']['proforma_invoice']['invoice-digit'] != '' ? $jem_pdf['general']['proforma_invoice']['invoice-digit'] : 0;
//        return $digit;
//    }

    function jem_get_general_invoice_num_type()
    {

        global $jem_pdf;
        $type = isset($jem_pdf['general']['general_invoice']['order-num-type']) && $jem_pdf['general']['general_invoice']['order-num-type'] != '' ? $jem_pdf['general']['general_invoice']['order-num-type'] : 'woocommerce-invoce';
        return $type;
    }
//
//    function jem_get_proforma_invoice_num_type()
//    {
//
//        global $jem_pdf;
//        $type = isset($jem_pdf['general']['proforma_invoice']['order-num-type']) && $jem_pdf['general']['proforma_invoice']['order-num-type'] != '' ? $jem_pdf['general']['proforma_invoice']['order-num-type'] : 'woocommerce-invoce';
//        return $type;
//    }
//
//    function jem_get_proforma_invoice_prefix()
//    {
//        global $jem_pdf;
//        $prefix = isset($jem_pdf['general']['proforma_invoice']['invoice-prefix']) && $jem_pdf['general']['proforma_invoice']['invoice-prefix'] != '' ? $jem_pdf['general']['proforma_invoice']['invoice-prefix'] : '';
//        return $prefix;
//    }
//
//    function jem_get_proforma_invoice_suffix()
//    {
//        global $jem_pdf;
//        $suffix = isset($jem_pdf['general']['proforma_invoice']['invoice-suffix']) && $jem_pdf['general']['proforma_invoice']['invoice-suffix'] != '' ? $jem_pdf['general']['proforma_invoice']['invoice-suffix'] : '';
//        return $suffix;
//    }

    /**
     * This is where we add the icons on the Order summary screen
     * @param $order
     */
    function jem_pdf_order_action_admin($order)
    {
        global $jem_pdf;

        $id = $order->get_id();


        //are we downloading or viewing
        if (isset($jem_pdf['general']['options']['view-invoice']) && $jem_pdf['general']['options']['view-invoice'] == 'download') {
            $action = 'download';
           $target = '';
        } else {
            $action = 'view';
            $target = '_blank';

        }

        //First the invoice
        $url = admin_url('admin-ajax.php?action=jem_pdf_output&order=' . $id . '&type=' . $action . '&screen=list');
        $target = $target;
        $name = __('PDF Invoice', JEM_PDFLITE);
        $actions['jem_invoice_download'] = array(
            'url' => wp_nonce_url($url, 'admin_invoice', 'jem_pdf'),
            'name' => $name,
            'img' => '<img src="' . Jem_Pdf_Invoices()->jem_pdf_url() .'/assets/images/pdf.jpg" width="16"> ',
            'action' => "jem_pdf_output",
            'class' => "jem_pdf_output"
        );

        //Now the packing slip
        $url = admin_url('admin-ajax.php?action=jem_pdf_output_packing_slip&order=' . $id . '&type=' . $action . '&screen=list');
        $name = __('Packing Slip', JEM_PDFLITE);
        $target = $target;
        $actions['jem_packing_slip_download'] = array(
            'url' => wp_nonce_url($url, 'admin_packing_slip', 'jem_pdf'),
            'name' => $name,
            'img' => '<img src="' . Jem_Pdf_Invoices()->jem_pdf_url() .'/assets/images/list.jpg" width="16"> ',
            'action' => "jem_pdf_output_packing_slip",
            'class' => "jem_pdf_output jem_pdf_output_packing_slip"
        );


        if(!empty($actions)){
            foreach ($actions as $action) {
                printf('<a target="%s" class="button tips %s" href="%s" data-tip="%s">%s</a>', $target, esc_attr($action['class']), esc_url($action['url']), $action['name'], $action['img']);
            }
        }

    }

    /**
     * This handles all the saving of the options etc for the plugin
     *It also sets up some defaults
     */
    function jem_pdf_options()
    {

        global $jem_pdf;

        if (isset($_POST['jem_pdf']) && $_POST['jem_pdf'] == 'save_jem_pdf_general') {

            //**************************************************
            //We are saving the GENERAL SETTINGS TAB  here!
            //**************************************************
            update_option('_jem_pdf_next_in_no', sanitize_text_field($_POST['invoice-number']['invoice']) );

            //sanitize the array of which email sto attach to
            $emails = isset( $_POST['attach-to-mail']['invoice'] ) ? (array) $_POST['attach-to-mail']['invoice'] : array();
            $emails = array_map( 'sanitize_text_field', $emails );

            $jem_pdf_options['general'] =
                array(
                    'options' =>
                        array(
                            'view-invoice' => sanitize_text_field($_POST['view-invoice']),
                            'disbale-invoice' => isset($_POST['disbale-invoice']) ? sanitize_text_field($_POST['disbale-invoice']) : '',
                            'template' => sanitize_text_field($_POST['template']),
                            'paper-size' => sanitize_text_field($_POST['paper-size']),
                            'date-format' => sanitize_text_field($_POST['date-format']),
                            'enable-proforma-invoice' => isset($_POST['enable-proforma-invoice']) ? sanitize_text_field($_POST['enable-proforma-invoice']) : ''
                        ),
                    'general_invoice' =>
                        array(
                            'attach-to-mail' => $emails,
                            'order-num-type' => sanitize_text_field($_POST['order-num-type']['invoice']),
                            'invoice-number' => sanitize_text_field($_POST['invoice-number']['invoice']),
                            'invoice-prefix' => sanitize_text_field($_POST['invoice-prefix']['invoice']),
                            'invoice-suffix' => sanitize_text_field($_POST['invoice-suffix']['invoice']),
                            'invoice-digit' => sanitize_text_field($_POST['invoice-digit']['invoice']),
                        ),

                );
        }
        elseif (isset($_POST['jem_pdf']) && $_POST['jem_pdf'] == 'save_jem_pdf_header') {

            //**************************************************
            //We are saving the Header & Footer section here!
            //**************************************************

            $jem_pdf_options['header'] =
                array(
                    'jem_pdf_logo' => sanitize_text_field($_POST['jem_pdf_logo']),
                    'empty-lines' => isset($_POST['empty-lines']) ? sanitize_text_field($_POST['empty-lines']) : '',
                    'store-name' => sanitize_text_field($_POST['store-name']),
                    'store-address' => sanitize_text_field($_POST['store-address'])
                );
        }
        elseif (isset($_POST['jem_pdf']) && $_POST['jem_pdf'] == 'save_jem_pdf_content') {
            //**************************************************
            //We are saving the DETAILED CONTENT TAB here!
            //**************************************************

            $jem_pdf_options['content'] =
                array(
                    'line-remove-blank-lines' => isset($_POST['line-remove-blank-lines']) ? sanitize_text_field($_POST['line-remove-blank-lines']) : '',
                    'line-sku' => isset($_POST['line-sku']) ? sanitize_text_field($_POST['line-sku']) : '',
                    'line-category' => isset($_POST['line-category']) ? sanitize_text_field($_POST['line-category']) : '',
                    'line-short-desc' => isset($_POST['line-short-desc']) ? sanitize_text_field($_POST['line-short-desc']) : '',
                    'line-image' => isset($_POST['line-image']) ? sanitize_text_field($_POST['line-image']) : '',
                    'shipping-if-free' => isset($_POST['shipping-if-free']) ? sanitize_text_field($_POST['shipping-if-free']) : '',
                    'line-tax' => isset($_POST['line-tax']) ? sanitize_text_field($_POST['line-tax']) : '',
                    'line-excl-tax' => isset($_POST['line-excl-tax']) ? sanitize_text_field($_POST['line-excl-tax']) : '',
                    'tax-totals' => isset($_POST['tax-totals']) ? sanitize_text_field($_POST['tax-totals']) : ''
                );

        }


        $defaults['general'] =
            array(
                'options' =>
                    array(
                        'view-invoice' => 'download',
                        'download-invoice' => 'if-created',
                        'disbale-invoice' => false,
                        'template' => 'default',
                        'paper-size' => 'default',
                        'date-format' => 'YY-MM-DD',
                        'enable-proforma-invoice' => false
                    ),
                'general_invoice' =>
                    array(
                        'attach-to-mail' => '',
                        'order-num-type' => 'woocommerce-invoce',
                        'invoice-number' => 1,
                        'invoice-prefix' => '',
                        'invoice-suffix' => '',
                        'invoice-digit' => 1,
                    ),
                'proforma_invoice' =>
                    array(
                        'attach-to-mail' => '',
                        'order-num-type' => 'woocommerce-invoce',
                        'invoice-number' => 1,
                        'invoice-prefix' => '',
                        'invoice-suffix' => '',
                        'invoice-digit' => 1,
                    ),
            );
        $defaults['header'] =
            array(
                'jem_pdf_logo' => '',
                'empty-lines' => true,
                'store-name' => get_bloginfo('name'),
                'store-address' => '',
                'buyer-detail' => '<b>Billing Details</b>
{buyer_first_name} {buyer_last_name}
{buyer_company}
{buyer_address_1}
{buyer_address_2}
{buyer_city}
{buyer_state} {buyer_postcode}
{buyer_country}',
                'shipping-detail' => '<b>Shipping Details</b>
{shipping_first_name} {shipping_last_name}
{shipping_company}
{shipping_address_1}
{shipping_address_2}
{shipping_city}
{shipping_state} {shipping_postcode}
{shipping_country}',
                'invoice-detail' => 'Order Number: {order_id}
Order Date: {order_date}
Payment Method: {payment_method}',
                'invoice-detail-packing-slip' => 'Order Number: {order_id}
Order Date: {order_date}
Shipping Method: {shipping_method}'
            );
        $defaults['content'] =
            array(
                'line-remove-blank-lines' => true,
                'line-sku' => true,
                'line-category' => false,
                'line-short-desc' => false,
                'line-image' => true,
                'shipping-if-free' => true,
                'line-tax' => true,
                'line-excl-tax' => false,
                'tax-totals' => false
            );
        $defaults['localization'] =
            array(
                'invoice' => 'Invoice',
                'invoice-proforma' => 'Invoice Proforma',
                'invoice-date' => 'Invoice Date',
                'invoice-tax' => 'Invoice Tax',
                'tax-per' => 'Tax %',
                'invoice-price' => 'Price',
                'quantity' => 'Qty',
                'shipping' => 'Shipping',
                'shipping-tax' => 'Shipping Tax',
                'subtotal' => 'SubTotal',
                'cart-discount' => 'Cart Discount',
                'order-discount' => 'Order Discount',
                'total' => 'Total',
                'order-notes' => 'Order Notes',
                'amount-words' => 'Amount in words',
                'download-invoice' => 'Download Invoice',
                'download-p-invoice' => 'Download Proforma Invoice',
                'invoice-number' => 'Invoice Number',
                'order-number' => 'Order Number'
            );


        //Insert defaults if appropriate
        //We use our own recursive parse arges so we can have nested defaults
        $jem_pdf_opts = get_option('_jem_pdf_opts', array());

        if (empty($jem_pdf_opts) && count($jem_pdf_opts) < 1) {
            $jem_pdf = $defaults;
        } else {
            $jem_pdf = self::parse_args_r($jem_pdf_opts, $defaults);
        }
        if (isset($_POST['jem_pdf'])) {
            $args = self::parse_args_r($jem_pdf_options, $jem_pdf);
            update_option('_jem_pdf_opts', $args);
            $jem_pdf = get_option('_jem_pdf_opts');
        }

    }

    function jem_pdf_admin_menu()
    {
        add_submenu_page('woocommerce', __('JEM PDF Invoices', JEM_PDFLITE), __('JEM PDF Invoices', JEM_PDFLITE), 'manage_options', 'jem-pdf-pro', array($this, 'jem_pdf_dashboard'));
    }

    function jem_pdf_admin_scripts()
    {

        wp_enqueue_media();
        wp_enqueue_script('thickbox');
        wp_enqueue_script('jquery-tiptip', $this->jem_pdf_url() . '/assets/js/jquery.tipTip.min.js', array('jquery'), JEM_PDFLITE_VERSION, true);
        wp_enqueue_script('jem_pdf_admin_script', $this->jem_pdf_url() . '/assets/js/admin.js', array('jquery', 'jquery-tiptip'), JEM_PDFLITE_VERSION);
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
        wp_enqueue_style('jem_pdf_admin_styles', $this->jem_pdf_url() . '/assets/css/admin.css', array(), JEM_PDFLITE_VERSION);

    }

    function jem_pdf_action_links($links)
    {
        $jem_pdf_link = array(
            '<a href="' . admin_url('admin.php?page=jem-pdf-pro') . '">Settings</a>',
        );
        return array_merge($jem_pdf_link, $links);
    }

    function jem_pdf_loader()
    {

        spl_autoload_register(function ($class) {
            $this->jem_pdf_include('/classes/' . $class . '.class.php');
        });

    }

    public function jem_pdf_url($url = '')
    {
        return untrailingslashit(plugins_url('/', __FILE__)) . $url;
    }

    function jem_pdf_path($dir = '')

    {
        return untrailingslashit(plugin_dir_path(__FILE__)) . $dir;
    }

    //creates the temp directory if it doesn't exist
    function jem_pdf_create_temp(){

        $path = $this->jem_pdf_temp_path();

        if ( !is_dir( $path ) ){
            wp_mkdir_p( $path );
        }
    }

    /**
     * Gets the temporary path
     * @return string
     */
    function jem_pdf_temp_path()

    {
        $uploads = wp_upload_dir();

        return $uploads['basedir'] . '/' . self::JEM_PDF_TEMP_PATH_CONST;
        //return untrailingslashit(plugin_dir_path(__FILE__)) . self::JEM_PDF_TEMP_PATH_CONST;
    }


    function jem_pdf_include($file)
    {

        if (is_file($this->jem_pdf_path($file))) {
            include_once($this->jem_pdf_path($file));
        }

    }

    function jem_pdf_dashboard()
    {
        $this->jem_pdf_include('/includes/dashboard.php');
    }

    function get_jem_pdf_customer_name($order)
    {
//        $fname = isset($order->billing_first_name) && $order->billing_first_name != '' ? $order->billing_first_name : $order->shipping_first_name;
//        $lname = isset($order->billing_last_name) && $order->billing_last_name != '' ? $order->billing_last_name : $order->shipping_last_name;
//        return $fname . ' ' . $lname;

        return $order->get_formatted_billing_full_name();
    }

    function get_jem_billing_address($order)
    {

//        $address = apply_filters('woocommerce_order_formatted_billing_address', array(
//            'company' => $order->billing_company,
//            'address_1' => $order->billing_address_1,
//            'address_2' => $order->billing_address_2,
//            'city' => $order->billing_city,
//            'state' => $order->billing_state,
//            'postcode' => $order->billing_postcode,
//            'country' => $order->billing_country
//        ), $order);
//
//        return WC()->countries->get_formatted_address($address);

        return $order->get_formatted_billing_address();


    }

    function parse_args_r( &$a, $b ) {
        $a = (array) $a;
        $b = (array) $b;
        $r = $b;
        foreach ( $a as $k => &$v ) {
            if ( is_array( $v ) && isset( $r[ $k ] ) ) {
                $r[ $k ] = self::parse_args_r( $v, $r[ $k ] );
            } else {
                $r[ $k ] = $v;
            }
        }
        return $r;
    }


}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function Jem_Pdf_Invoices()
    {
        return Jem_Pdf_Invoices_WooCommerce::instance();
    }

    Jem_Pdf_Invoices();
}
?>