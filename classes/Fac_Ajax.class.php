<?php

class Fac_Ajax extends Agp_AjaxAbstract {
    /**
     * The single instance of the class 
     * 
     * @var object
     */
    protected static $_instance = null;    
    
	/**
	 * Main Instance
	 *
     * @return object
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}    
    
	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
    }        
    
    /**
     * Get constructor element parameters
     */
    public function getElementParameters($data) {
        $result = array();
        if (isset($data['key'])) {
            $result['content'] = Fac()->getTemplate('admin/constructor/constructor', array('key' => $data['key']));    
            $result['preview'] = '<p>' . Fac()->doShortcode(array(), '', $data['key']) . '</p>';
        }
        
        $return = json_encode($result);
        header('Content-Length: '.strlen($return)); 
        header('Content-type: application/json');            
        echo $return;                    
        die();
        
        //return $result;                    
    }
    
    public function getPreview($data) {
        $result = array();
        if (isset($data['key'])) {
            $atts = !empty($data['params']) ? $data['params'] : array();
            $result['preview'] = '<p>' . Fac()->doShortcode($atts, '', $data['key']) . '</p>';
        }
        
        $return = json_encode($result);
        header('Content-Length: '.strlen($return)); 
        header('Content-type: application/json');            
        echo $return;                    
        die();
        
        //return $result;                    
    }    

    public function getShortcode($data) {
        $result = array();
        if (isset($data['key'])) {
            $atts = !empty($data['params']) ? $data['params'] : array();
            $s = '';
            foreach ($atts as $key => $value) {
                if (!empty($value)) {
                    if (is_string($value)) {
                        $s .= ' ' . $key . '="' . $value . '"';    
                    } else {
                        $s .= ' ' . $key . '=' . $value;    
                    }
                }
            }
            
            $result['shortcode'] = '[' . $data['key'] . $s . ']';   
        }
        
        $return = json_encode($result);
        header('Content-Length: '.strlen($return)); 
        header('Content-type: application/json');            
        echo $return;                    
        die();
        
        //return $result;                    
    }        
    
}
