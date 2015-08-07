<?php
 
class View {
    protected $_file;
    protected $_data = array();
    protected $_withTemplate = true;

    public $content = "";
    
    public function __construct($file) {
        $this->_file = $file;
    }
    
    public function set($key, $value) {
        $this->_data[$key] = $value;
    }
    
    public function get($key) {
        return isset($this->_data[$key]) ? $this->_data[$key] : '';
    }
    
    public function withTemplate($value){
        $this->_withTemplate = $value;
    }
    
    public function output(){
        if ($this->_withTemplate){
            if (!file_exists($this->_file)){
                throw new Exception("Template " . $this->_file . " doesn't exist.");
            }
        }
        
        extract($this->_data);
        
        ob_start();
            if ($this->_withTemplate){
                $view = $this;
                include($this->_file);
            }
            $this->content = ob_get_contents();
        ob_end_clean();
        
        return $this->content;
    }
}
