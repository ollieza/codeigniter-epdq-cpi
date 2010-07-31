<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ePDQ CPI class
 *
 * This CodeIgniter library provides a neat and simple method to forward to the
 * Barclays ePDQ CPI service http://www.barclaycardbusiness.co.uk/epdq_cpi/
 * 
 * @package   epdq_cpi
 * @version   0.5
 * @author    Ollie Rattue, Too many tabs <orattue[at]toomanytabs.com>
 * @copyright Copyright (c) 2010, Ollie Rattue
 * @license   http://www.opensource.org/licenses/mit-license.php
 * @link      http://github.com/ollierattue/codeigniter-epdq-cpi
 */

class epdq_cpi
{
	var $epdq_cpi_request_url; 		   // the Barclays ePDQ request url
	var $collect_delivery_address;	   // bool: collect additional delivery address?
	var $return_url;				   // the url which CPI will return a user to
	var $merchant_display_name;		   // the company name that will appear on Barclays payment page
	var $cpi_logo;					   // location (URL) of a graphical file in GIF or JPG format
									   // WIDTH=500px HEIGHT=100px - shown on Barclays payment page
	
	/* Mandating the Card Security Code 

	By default the Card Security Code (CSC) field is not mandatory on the payment page. If you 
	wish to ensure that your customers enter their CSC then, this can be achieved by using the 
	variable “mandatecsc” in your integration similar to passing the total and oid values. 

	To activate the mandate for the CSC specify a value of ‘1’, or a value of ‘2’ to deactivate it. For 
	security purposes this needs to be passed in the string which is encrypted by the encryption 
	tool and returns the epdqdata string.  An example for the request string can be found below. 

	clientid=[clientid]&password=[password]&oid=[oid]&chargetype=Auth&total=1.00&currency 
	code=826&mandatecsc=1 
	
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
		
		log_message('debug', "ePDQ CPI Class Initialized");
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
	
	function form($total = NULL, $form_name = NULL, $email_address = NULL, $full_name = NULL)
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
		
		Tax and shipping
		
		<INPUT name=epdqdata type=hidden value="otx7cGHs8od9G3ZAsjO7gw3fJeTJ3O"> 
		(includes the encrypted Total Value of £10) 
		<input type=”hidden” name=”tax” value=”2.00”>  - requires 2 decimal places
		<input type=”hidden” name=”shipping” value=”2.00”> - requires 2 decimal places
		
		
		*/
		
		$str = '';
		$str .= '<form action="'.$this->epdq_cpi_request_url.'" method="post" id="'.$form_name.'" name="'.$form_name.'">' . "\n";
		$str .=  $this->epdq_encryption($total). "\n";
		$str .= '<input type="hidden" name="returnurl" value="'.base_url().'status/" />' . "\n";
		$str .= '<input type="hidden" name="collectdeliveryaddress" value="0" />' . "\n";
		$str .= '<input type="hidden" name="merchantdisplayname" value="IHOUSEU Limited" />' . "\n";
		$str .= '<input type="hidden" name="cpi_logo" value="'.image_url().'ihouseu-logo-barclays-epdq-100x500.jpg" /> ' . "\n";
		
		if ($email_address)
		{
			$str .= '<input type="hidden" name="email" value="'.$email_address.'">' ."\n";
		}
		
		if ($full_name)
		{
			$str .= '<input type="hidden" name="email" value="'.$full_name.'">' ."\n";
		}
		
		$str .= '<!-- <input type="hidden" name="tax" value="0" /> -->' . "\n";
		$str .= '<p>'. form_submit('submit-button', "Click here if you're not automatically redirected..."). '</p>';
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
	
	function auto_form($total = NULL, $form_name = NULL, $email_address = NULL, $full_name = NULL)
	{
		
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Receives encrypted transaction data from Barlcays ePDQ
	 *
	 * @access public
	 * @param  string
	 * @return string
	 */	
	
	function encryption($total = NULL)
	{
		$clientid = $this->CI->config->item('epdq_clientid'));
		$passphrase = $this->CI->config->item('epdq_passphrase'));
		
		// This is correct - the password in the post url is the passphrase in CPI config.
        $params = "clientid=$clientid&password=$passphrase&oid=$orderid&chargetype=Auth&total=$total&currencycode=826"; 
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $this->epdq_cpi_request_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  // this line makes it work under https

        $result=curl_exec($ch);
        curl_close($ch);
        return $result;
	}
	
	// --------------------------------------------------------------------
	
}
// END ePDQ CPI Class

/* End of file epdq_cpi.php */
/* Location: ./system/libraries/epdq_cpi.php */