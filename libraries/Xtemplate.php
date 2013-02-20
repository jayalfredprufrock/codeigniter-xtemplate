<?php
/**
 * @name        CodeIgniter Template Library
 * @author      Jens Segers
 * @link        http://www.jenssegers.be
 * @license     MIT License Copyright (c) 2012 Jens Segers
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

if (!defined("BASEPATH"))
    exit("No direct script access allowed");

class Xtemplate {
    
    /**
     * Construct with configuration array. Codeigniter will use the config file otherwise
     * @param array $config
     */
    public function __construct($config = array()) {
        
		$this->_ci = & get_instance();
        
        if (!empty($config)) {
            $this->initialize($config);
        }
        
        log_message('debug', 'XTemplate library initialized');
    }
    
    /**
     * Initialize with configuration array
     * @param array $config
     * @return Xtemplate
     */
    public function initialize($config = array()) {

		foreach ($config as $key => $val) {
            $this->{'_' . $key} = $val;
        }
      
        if (!class_exists('Template')) {
		
            $this->_ci->load->spark($this->_template_spark_path);
        }
		
		// load session library if not already loaded
		if (!isset($this->_ci->session))
		{
			$this->_ci->load->library('session');
		}
		
		return $this;
    }
    
    /**
     * Publish the template with the current partials
     * You can manually pass a template file with extra data, or use the default template from the config file
     * @param string $template
     * @param array $data
     */
    public function publish($template = FALSE, $data = array(), $return = FALSE) {

	   $template = $this->_ci->template->publish($template, $data, TRUE);
		   
	   $previous_state = $this->_ci->session->userdata('xtemplate');

	   if (!is_array($previous_state)){
			
			$previous_state = array('template'=> FALSE, 'partials' => array());
	   }
	   
	   $partials = $this->_ci->template->get_partials();
	   
	   $new_state = array('template' => $this->_ci->template->get_template(), 'partials' => array_map('crc32', $partials));
      
	   $this->_ci->session->set_userdata('xtemplate', $new_state);   
	   
	   $this->_ci->console->log($previous_state);
	   $this->_ci->console->log($new_state);
	   
	   if ($this->_ci->input->is_ajax_request()){
		   
		      	
		   $json = array('partials'=>array());	
			
		   //only include fully parsed template when template has changed	
		   if ($new_state['template'] !== $previous_state['template']){
			
				$json['template'] = $template;
	       }
		   
		   //compare previous partials with new partials, only returning new/changed partials
		   foreach($partials as $name=>$partial)
		   {			
				if (!isset($previous_state['partials'][$name]) || $previous_state['partials'][$name] != $new_state['partials'][$name])
				{
					$json['partials'][$name] = $partial->content();
				}
		   }   
		   
		   //figure out which partials have been added or removed
		   $json['removed_partials'] = array_keys(array_diff_key($previous_state['partials'], $new_state['partials']));
		   $json['new_partials'] = array_keys(array_diff_key($new_state['partials'], $previous_state['partials']));
			

		   return $return ? $json : exit(json_encode($json));
	   }
	   else {

			return $return ? $template :  $this->_ci->output->append_output($template);
		}	    
	}
    
}