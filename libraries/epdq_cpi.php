<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ePDQ CPI class
 *
 * This CodeIgniter library provides a neat and simple method to forward to the
 * Barclays ePDQ CPI service http://www.barclaycardbusiness.co.uk/epdq_cpi/
 * 
 * @package   epdq_cpi
 * @version   0.9
 * @author    Ollie Rattue, Too many tabs <orattue[at]toomanytabs.com>
 * @copyright Copyright (c) 2010, Ollie Rattue
 * @license   http://www.opensource.org/licenses/mit-license.php
 * @link      http://github.com/ollierattue/codeigniter-epdq-cpi
 */

class epdq_cpi
{
	var $epdq_cpi_request_url; 		   // the Barclays ePDQ request url
	var $epdq_cpi_encryption_url; 	   // the Barclays ePDQ encyrption url
	var $collect_delivery_address;	   // bool: collect additional delivery address?
	var $return_url;				   // the url which CPI will return a user to
	var $merchant_display_name;		   // the company name that will appear on Barclays payment page
	
	var $cpi_logo;					   // location (URL) of a graphical file in GIF or JPG format
									   // WIDTH=500px HEIGHT=100px - shown on Barclays payment page

	var $currency_code;			       // The standard ISO code	(e.g. 826 for GBP) for the currency you 
									   // wish the transaction to be processed in. Please refer to Appendix A 
									   // for details of valid currency code values.
	var $clientid;
	var $passphrase;
	var $email_address;
	var $full_name;
	var $shipping_amount;
	var $tax_amount;
	var $order_id; // Optional
	var $submit_button; // Image/Form button
	
	/* Mandating the Card Security Code 

	By default the Card Security Code (CSC) field is not mandatory on the payment page. If you 
	wish to ensure that your customers enter their CSC then, this can be achieved by using the 
	variable “mandatecsc” in your integration similar to passing the total and oid values. 

	To activate the mandate for the CSC specify a value of ‘1’, or a value of ‘2’ to deactivate it. For 
	security purposes this needs to be passed in the string which is encrypted by the encryption 
	tool and returns the epdqdata string.  An example for the request string can be found below. 	
	*/
	
	var $mandatecsc;
	
	var $CI;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	
	public function __construct()
	{
		if (!isset($this->CI))
		{
			$this->CI =& get_instance();
		}
		
		$this->CI->load->helper('url');
		$this->CI->load->helper('form');
		$this->CI->load->config('epdq_cpi_config');

		$this->epdq_cpi_request_url = 'https://secure2.epdq.co.uk/cgi-bin/CcxBarclaysEpdq.e';
		$this->epdq_cpi_encyrption_url = 'https://secure2.epdq.co.uk/cgi-bin/CcxBarclaysEpdqEncTool.e';
		
		$this->clientid = $this->CI->config->item('epdq_clientid');
		$this->passphrase = $this->CI->config->item('epdq_passphrase');
		$this->mandatecsc = 1; // Card Security Code appears as a mandatory field on the CPI. 
		$this->currency_code =  826; // (For GBP Stores). See integration guide for other codes
		$this->merchant_display_name = $this->CI->config->item('epdq_merchant_display_name'); // MAX 25 characters
		$this->cpi_logo = $this->CI->config->item('epdq_cpi_logo');
		$this->collect_delivery_address = 0;
		$this->return_url = base_url(); // fallback setting
		$this->tax_amount = 0; // No tax charged by default
		$this->shipping_amount = 0; // No shipping charged by default
		$this->button('Proceed to payment');
		
		log_message('debug', "ePDQ CPI Class Initialized");
	}
	
	// --------------------------------------------------------------------
	
	/*
	1 indicates “show the  delivery information” (CPI normal behaviour) 
	Default is “0” indicates “do not show the delivery information” 
	*/
	
	function collect_delivery_address()
	{
		$this->collect_delivery_address = 1;
	}
	
	// --------------------------------------------------------------------
	
	// Optional
	// MAX 64 characters 
	
	function set_email_address($value = NULL) 
	{
		$this->email_address = $value;
	} 

	// --------------------------------------------------------------------
	
	function set_return_url($value = NULL)
	{
		$this->return_url = $value;
	}
	
	// --------------------------------------------------------------------
	
	// Optional
	// 2 decimal places
	
	function set_tax($amount = NULL)
	{
		$this->tax_amount = format_decimal_places($amount, 2);
	}
	
	// --------------------------------------------------------------------
	
	// Optional
	// 2 decimal places
	
	function set_shipping($amount = NULL)
	{
		$this->shipping_amount = format_decimal_places($amount, 2);
	}

	// --------------------------------------------------------------------
	
	/*  Optional 
	If you do not create a value for the Order ID, ePDQ will 
	do this for you. However, we do recommend creation of your own Order ID 
	to assist with order and authorisation response tracking. 
	
	MAX 36 characters
	*/
	
	function set_order_id($value = NULL)
	{
		$this->order_id = $value;
	}

	// --------------------------------------------------------------------
	
	function button($value = NULL)
	{
		// changes the default caption of the submit button
		$this->submit_button = form_submit('submit-button', $value);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Creates an eDPQ CPI form
	 *
	 * @access public
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return string
	 */	
	
	function form($total = NULL, $form_name = 'epdq-form')
	{
		/*  Optional additional fields
		
		<INPUT type=hidden name=baddr1 value="address line 1">  60 characters 
		<INPUT type=hidden name=baddr2 value="address line 2">  60 characters 
		<INPUT type=hidden name=baddr3 value="address line 3">  60 characters 
        <INPUT type=hidden name=bcity value="City"> 25 characters 
		<INPUT type=hidden name=bstate value="US State"> 25 characters
		<INPUT type=hidden name=bcountyprovince value="County"> 3 characters
		<INPUT type=hidden name=bpostalcode value="Postcode"> 9 characters 
		<INPUT type=hidden name=bcountry value="GB"> 2 characters 
		<INPUT type=hidden name=btelephonenumber value="01111 012345"> 30 characters
		<INPUT type=hidden name=email value="email@domain.extension"> 64 characters 
		
		Delivery information
		 
		<INPUT type=hidden name=saddr1 value="Address line 1"> 60 characters
		<INPUT type=hidden name=saddr2 value="Address line 2"> 60 characters 
		<INPUT type=hidden name=saddr3 value="Address line 3"> 60 characters
		<INPUT type=hidden name=scity value="City"> 25 characters
		<INPUT type=hidden name=sstate value="US State"> 25 characters
		<INPUT type=hidden name=scountyprovince value="County"> 3 characters
		<INPUT type=hidden name=spostalcode value="Postcode"> 9 characters
		<INPUT type=hidden name=scountry value="GB"> 2 characters
		<INPUT type=hidden name=stelephonenumber value="01111 012345"> 30 characters		
		
		*/
		
		$str = '<form action="'.$this->epdq_cpi_request_url.'" method="post" id="'.$form_name.'" name="'.$form_name.'">' . "\n";
		$str .= $this->encryption($total). "\n";
		$str .= form_hidden('returnurl', $this->return_url) . "\n";
		$str .= form_hidden('collectdeliveryaddress', $this->collect_delivery_address) . "\n";
		$str .= form_hidden('merchantdisplayname', $this->merchant_display_name) . "\n";
		
		if ($this->cpi_logo)
		{
			$str .= form_hidden('cpi_logo', $this->cpi_logo) . "\n";	
		}
		
		if ($this->email_address)
		{
			$str .= form_hidden('email', $this->email_address) . "\n";
		}
	
		if ($this->shipping_amount)
		{
			$str .= form_hidden('shipping', $this->shipping_amount) . "\n";
		}
				
		if ($this->tax_amount)
		{
			$str .= form_hidden('tax', $this->tax_amount) . "\n";
		}
		
		$str .= $this->submit_button;
		$str .= form_close() . "\n";

		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Creates an auto-forwading eDPQ CPI form
	 *
	 * Generates an entire HTML page consisting of a form with hidden elements
	 * which is submitted to Barlcays ePDQ via the BODY element's onLoad 
	 * attribute.  We do this so that you can validate any POST vars from you 
	 * custom form before submitting to Barclays ePDQ.  
	 *
	 * You'll have your own form which is submitted to your script to validate 
	 * the data, which in turn calls this function to create another hidden 
	 * form and submit to ePDQ.
	 *
	 * @access public
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return string
	 */	
	
	function auto_form($total = NULL)
	{
		$this->button('Click here if you\'re not automatically redirected...');

		echo '<html>' . "\n";
		echo '<head><title>Processing Payment...</title></head>' . "\n";
		echo '<body onLoad="document.forms[\'epdq_auto_form\'].submit();">' . "\n";
		echo '<p>Please wait, your order is being processed and you will be redirected to our payment partner.</p>' . "\n";
		echo $this->form($total, 'epdq_auto_form');
		echo '</body></html>';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Receives encrypted transaction data from Barlcays ePDQ
	 *
	 * @access public
	 * @param  string
	 * @return string
	 */	
	
	private function encryption($total = NULL)
	{
        $params = "clientid={$this->clientid}";
        $params .= "&password={$this->passphrase}"; // This is correct - the password in the post url is the passphrase in CPI config.
		$params .= "&chargetype=Auth";
		$params .= "&total={$total}";
		$params .= "&currencycode={$this->currency_code}";
		$params .= "&mandatecsc={$this->mandatecsc}";
		
		if ($this->order_id)
		{
			$params .= "&oid={$this->order_id}";
		}
		
		$user_agent = $_SERVER['HTTP_USER_AGENT'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $this->epdq_cpi_encyrption_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  // this line makes it work under https

        $result = curl_exec($ch);
        curl_close($ch);
		return $result;
	}
	
	// --------------------------------------------------------------------
	
	private function format_decimal_places($amount = NULL, $number_of_places = 2)
	{
		return number_format($amount, $number_of_places, '.', '');
	}
	
	// --------------------------------------------------------------------	
}

// END ePDQ CPI Class

/* End of file epdq_cpi.php */
/* Location: ./system/libraries/epdq_cpi.php */