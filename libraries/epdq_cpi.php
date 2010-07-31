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

		$this->epdq_cpi_url = 'https://secure2.epdq.co.uk/cgi-bin/CcxBarclaysEpdq.e';
		
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
	 * Creates encryption code from Barlcays ePDQ
	 *
	 * @access public
	 * @param  string
	 * @return string
	 */	
	
	function encryption($total = NULL)
	{
		
	}
	
	// --------------------------------------------------------------------
	
}
// END ePDQ CPI Class

/* End of file epdq_cpi.php */
/* Location: ./system/libraries/epdq_cpi.php */