<?php
/*
Plugin Name: WooCommerce CCAvenue MultiCurrency Gateway
Plugin URI: http://www.mrova.com/
Description: A WooCommerce Gateway for CCAvenue specifically written for India. Keeping in mind Indian Merchants wanting to accept payments from abroad in multiple currencies. 
Version: 0.0.1
Author: Jude Rosario
Author URI: http://www.juderosario.com/
    License: GNU General Public License v2.0
    License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


// Security Check
if ( ! defined('ABSPATH')) exit('Direct Access Restricted');



add_action( 'plugins_loaded', 'init_ccavenue_multicurrency' );


function init_ccavenue_multicurrency() {
	if( !class_exists( 'WC_Gateway_CCAvenue_MultiCurrency' ) ) :
	
	class WC_Gateway_CCAvenue_MultiCurrency extends WC_Payment_Gateway {
		 
		 public function __construct() {

            $this->id           = 'ccavenue_mcg';
            $this->method_title = __('CCAvenue MultiCurrency Gateway', 'bagc_ccavenue_mcg');
            $this->icon         =  plugins_url( 'images/logo.gif' , __FILE__ );
            $this->has_fields   = false;
            
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title            = $this->settings['mcg_title'];
            $this->description      = $this->settings['mcg_description'];
            $this->merchant_id      = $this->settings['mcg_merchant_id'];
            $this->working_key      = $this->settings['mcg_working_key'];
            $this->access_code      = $this->settings['mcg_access_code'];
            
            $this->liveurl  = 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
            $this->notify_url = str_replace( 'https:', 'http:', home_url( '/?wc-api%3Dwc_gateway_ccavenue_multicurrency' )  );


			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, 
						array( $this, 'process_admin_options' ) );
        }


        // Admin form fields
        function init_form_fields() {

            $this->form_fields = array(

                'mcg_enabled' => array(
                    'title' => __('Enable/Disable', 'bagc_ccavenue_mcg'),
                    'type' => 'checkbox',
                    'label' => __('Enable the CCAvenue MultiCurrency Payment GFateway.', 'bagc_ccavenue_mcg'),
                    'default' => 'no'),

                'mcg_title' => array(
                    'title' => __('Title:', 'bagc_ccavenue_mcg'),
                    'type'=> 'text',
                    'description' => __('The title which the user sees during checkout.', 'bagc_ccavenue_mcg'),
                    'default' => __('CCAvenue for International Users', 'bagc_ccavenue_mcg')),

                'mcg_description' => array(
                    'title' => __('Description:', 'bagc_ccavenue_mcg'),
                    'type' => 'textarea',
                    'description' => __('The description which the user sees during checkout.', 'bagc_ccavenue_mcg'),
                    'default' => __('Pay using either your Credit or Debit card or via Internet Banking 
                    	using Secure Servers powered by CCAvenue.', 'bagc_ccavenue_mcg')),

                'mcg_merchant_id' => array(
                    'title' => __('Merchant ID', 'bagc_ccavenue_mcg'),
                    'type' => 'text',
                    'description' => __('This ID (User ID) is available through the "Generate Working Key" 
                    	option under "Settings and Options" at the CCAvenue Web Portal.')),
                
                'mcg_working_key' => array(
                    'title' => __('Working Key', 'bagc_ccavenue_mcg'),
                    'type' => 'text',
                    'description' =>  __('Given to you by CCAvenue as part of the starter pack', 'bagc_ccavenue_mcg'),),
                
                'mcg_access_code' => array(
                    'title' => __('Access Code', 'bagc_ccavenue_mcg'),
                    'type' => 'text',
                    'description' =>  __('Given to you by CCAvenue as part of the starter pack', 'bagc_ccavenue_mcg'),
                    )
                );
			}


        // Options for the admin page
		public function admin_options() {
			ob_start();
				echo  ' ' ; 
				echo  '<h3>'.__('CCAvenue MultiCurrency Payment Gateway', 'bagc_ccavenue_mcg').'</h3>';
	            echo  '<p>'.__('CCAvenue is a major payment processor in India.');
                echo  __('This gateway enables merchants to sell to customers outside India in multiple currencies').'</p>';
	            echo  '<table class="form-table bagc-ccavenue-mcg-table">';
	            echo  $this -> generate_settings_html();
	            echo  '</table>';
            ob_end_flush();
        }


        function payment_fields() {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }
        }

        // Reciept page
        function receipt_page($order) {
            echo '<p>' . __('Thank you for your order, please click the button below to pay with you credit card using CCAvenue.', 'bagc_ccavenue_mcg') . '</p>';
            echo $this->generate_ccavenue_form($order);
        }


        // Process payment
        function process_payment($order_id) {
            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true),
            );
        }

        // Check server response
        function check_ccavenue_response() {
            global $woocommerce;

            $msg['class'] = 'error';
            $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined. 
            Please try again later, contact your bank if issue persists.";

            if (isset($_REQUEST['encResp'])) {

                $encResponse = $_REQUEST["encResp"];
                $rcvdString = decrypt($encResponse, $this->working_key);
                $decryptValues = array();
                parse_str($rcvdString, $decryptValues);

                $order_id_time = $decryptValues['order_id'];
                $order_id = explode('_', $decryptValues['order_id']);
                $order_id = (int) $order_id[0];

                if ($order_id != '') {
                    try {
                        $order = new WC_Order($order_id);
                        $order_status = $decryptValues['order_status'];
                        $transauthorised = false;
                        if ($order->status !== 'completed') {
                            if ($order_status == "Success") {
                                $transauthorised = true;
                                $msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                $msg['class'] = 'success';
                                if ($order->status != 'processing') {
                                    $order->payment_complete();
                                    $order->add_order_note('CCAvenue payment successful<br/>Bank Ref Number: ' . $decryptValues['bank_ref_no']);
                                    $woocommerce->cart->empty_cart();
                                }
                            } else if ($order_status === "Aborted") {
                                $msg['message'] = "Thank you for shopping with us. We will keep you posted regarding the status of your order through e-mail";
                                $msg['class'] = 'success';
                            } else if ($order_status === "Failure") {
                                $msg['class'] = 'error';
                                $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined
                                Please try again later, contact your bank if issue persists.";
                            } else {
                                $msg['class'] = 'error';
                                $msg['message'] = "Thank you for shopping with us. However, there was an error with the transaction. Please try again later.";
                            }
                            if ($transauthorised == false) {
                                $order->update_status('failed');
                                $order->add_order_note('Failed');
                                $order->add_order_note($this->msg['message']);
                            }
                        }
                    } catch (Exception $e) {
                        $msg['class'] = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, there was an error with the transaction. Please try again later.";
                    }
                }
            }

            if (function_exists('wc_add_notice')) {

                wc_add_notice($msg['message'], $msg['class']);
            
            } else {
                if ($msg['class'] == 'success') {
                    $woocommerce->add_message($msg['message']);
                } else {
                    $woocommerce->add_error($msg['message']);
                }
                $woocommerce->set_messages();
            }

            $redirect_url = get_permalink(woocommerce_get_page_id('myaccount'));

            wp_redirect($redirect_url);

            exit;
        }


        // Deprecated fuction
        function showMessage($content){
        return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
        







	}




	endif ; 
}



function add_bagc_ccavenue_mcg_gateway( $methods ) {
	$methods[] = 'WC_Gateway_CCAvenue_MultiCurrency'; 
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_bagc_ccavenue_mcg_gateway' );

// CCAvenue Security functions 

function encrypt($plainText,$key)
{
    $secretKey = hextobin(md5($key));
    $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
    $blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
    $plainPad = pkcs5_pad($plainText, $blockSize);
    if (mcrypt_generic_init($openMode, $secretKey, $initVector) != -1) 
    {
      $encryptedText = mcrypt_generic($openMode, $plainPad);
      mcrypt_generic_deinit($openMode);
  } 
  return bin2hex($encryptedText);
}
function decrypt($encryptedText,$key)
{
    $secretKey = hextobin(md5($key));
    $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $encryptedText=hextobin($encryptedText);
    $openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
    mcrypt_generic_init($openMode, $secretKey, $initVector);
    $decryptedText = mdecrypt_generic($openMode, $encryptedText);
    $decryptedText = rtrim($decryptedText, "\0");
    mcrypt_generic_deinit($openMode);
    return $decryptedText;
}

function pkcs5_pad ($plainText, $blockSize)
{
    $pad = $blockSize - (strlen($plainText) % $blockSize);
    return $plainText . str_repeat(chr($pad), $pad);
}

