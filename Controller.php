<?php

//controlador para requisição de páginas
class Controller {
    private $model;
    public $view;
    
    public function redirect($url) {
        header('location:'.BASE_URL.'/'.$url);
        exit;
    }
}

//controlador para requisição ajax
class Service extends Controller{
    protected $requireSession = true;
    
    function __construct() {
        if ($this->requireSession){
            if ( session_id()==='' ){
                session_start();
            }
            
            if ( !isset($_SESSION['USER_ID']) ){
                Response::error('Session not exists');
            }
        }
    }
}
