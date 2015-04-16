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

        }


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
                    'description' => __('This controls the title which the user sees during checkout.', 'bagc_ccavenue_mcg'),
                    'default' => __('CCAvenue for International Users', 'bagc_ccavenue_mcg')),

                'mcg_description' => array(
                    'title' => __('Description:', 'bagc_ccavenue_mcg'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'bagc_ccavenue_mcg'),
                    'default' => __('Pay securely using either your Credit or Debit card or via Internet banking using CCAvenue Secure Servers.', 'bagc_ccavenue_mcg')),

                'mcg_merchant_id' => array(
                    'title' => __('Merchant ID', 'bagc_ccavenue_mcg'),
                    'type' => 'text',
                    'description' => __('This ID (User ID) is available through the "Generate Working Key" option under "Settings and Options" at the CCAvenue Web Portal.')),
                
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


	}

	endif ; 
}

