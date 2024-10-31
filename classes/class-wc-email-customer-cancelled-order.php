<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email_Customer_Cancelled_Order' ) ) :

/**
 * Cancelled Order Email.
 *
 * An email sent to the admin when an order is cancelled.
 *
 * @class       WC_Email_Customer_Processing_Order
 * @version     2.2.7
 * @package     WooCommerce/Classes/Emails
 * @author      WooThemes
 * @extends     WC_Email
 */
class WC_Email_Customer_Cancelled_Order extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id               = 'customer_cancelled_order';
		$this->title            = __( 'Customer Cancelled order', JEM_PDFLITE );
		$this->description      = __( 'Cancelled order emails are sent to chosen recipient(s) when orders have been marked cancelled (if they were previously processing or on-hold).', JEM_PDFLITE );
		$this->heading          = __( 'Cancelled order', JEM_PDFLITE );
		$this->subject          = __( '[{site_title}] Cancelled order ({order_number})', JEM_PDFLITE );
		$this->template_html    = 'emails/admin-cancelled-order.php';
		$this->template_plain   = 'emails/plain/admin-cancelled-order.php';
		$this->customer_email = true;

		// Triggers for this email
		add_action( 'woocommerce_order_status_pending_to_cancelled_notification', array( $this, 'trigger' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled_notification', array( $this, 'trigger' ) );

		// Call parent constructor
		parent::__construct();
	}

	/**
	 * Trigger.
	 *
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {

		if ( $order_id ) {

			$this->object                  = wc_get_order( $order_id );
			$this->recipient               = $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => false,
			'email'			=> $this
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => true,
			'email'			=> $this
		) );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', JEM_PDFLITE ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable this email notification', JEM_PDFLITE ),
				'default'       => 'yes'
			),
			'subject' => array(
				'title'         => __( 'Subject', JEM_PDFLITE ),
				'type'          => 'text',
				'description'   => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', JEM_PDFLITE ), $this->subject ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'heading' => array(
				'title'         => __( 'Email Heading', JEM_PDFLITE ),
				'type'          => 'text',
				'description'   => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', JEM_PDFLITE ), $this->heading ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'email_type' => array(
				'title'         => __( 'Email type', JEM_PDFLITE ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', JEM_PDFLITE ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options(),
				'desc_tip'      => true
			)
		);
	}
}

endif;

return new WC_Email_Customer_Cancelled_Order();