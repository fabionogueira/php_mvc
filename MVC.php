<?php

/**
 * MVC.php
 * @author Fábio Nogueira
 * @version 0.0.4
 */

error_reporting(E_ALL & ~E_NOTICE);
define('MVC_VERSION', '0.0.4');

//carrega classes do core
require __DIR__.'/Session.php';    //{{Session.php}}
require __DIR__.'/View.php';       //{{View.php}}
require __DIR__.'/Controller.php'; //{{Controller.php}}

class MVC{
    private static $_config = array();
    private static $_url;
    
    private static function info(){
        Response::success(array(
            'version'   => MVC_VERSION,
            'copyright' => '(c) 2014, DTEC. All rights reserved.',
            'tostring'  => 'MVC Version '.MVC_VERSION . "\nCopyright (c) 2014, DTEC. All rights reserved."
        ));
    }
    private static function routerError($err_message){
        $templates = self::$_config['templates'];
        $name      = isset($templates["error"]) ? $templates["error"] : $templates["default"].'_error';
        
        if ( file_exists(ROOT.'template/'.$name.'.php') ){
            require ROOT.'template/'.$name.'.php';
            exit;
        }else{
            exit ("router error! <b>$err_message</b>" );
        }
        exit;
    }
    private static function splitUrl(){
        self::$_url = array();
        
        if (BASE_URL!=''){
            $arr = explode(BASE_URL, $_SERVER['REDIRECT_URL']);
            $url = $arr[1]!='' ? trim($arr[1], '/') : trim($arr[0], '/');
        }else{
            $url = trim($_SERVER['REDIRECT_URL'], '/');
        }
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $arr = explode('/', $url);
        
        // Put URL parts into according properties
        // By the way, the syntax here is just a short form of if/else, called "Ternary Operators"
        // @see http://davidwalsh.name/php-shorthand-if-else-ternary-operators
        self::$_url['controller']  = (isset($arr[0]) ? $arr[0] : null);
        self::$_url['action']      = (isset($arr[1]) ? $arr[1] : null);
        self::$_url['parameter_1'] = (isset($arr[2]) ? $arr[2] : null);
        self::$_url['parameter_2'] = (isset($arr[3]) ? $arr[3] : null);
        self::$_url['parameter_3'] = (isset($arr[4]) ? $arr[4] : null);
    }
    private static function controllerInstance($controllerClassName, $modelClassName=NULL){
        require ROOT . self::$_config['controllerPath'] . $controllerClassName.'.php';
        
        $viewName  = str_replace("Controller", "View", $controllerClassName);
        
        $instance = new $controllerClassName();
        if (!self::$_config['ajax']){
            $instance->view = new View(ROOT. 'view'. DIRECTORY_SEPARATOR . $viewName.'.php');
        }
        
        if (!is_null($modelClassName)){
            require ROOT.'model/'.$modelClassName.'.php';
            $instance->model = new $modelClassName(self::$_config["model"]);
        }
        
        return $instance;
    }
    private static function runController($instance, $actionName){
        $templates   = self::$_config['templates'];
        $parameter_1 = self::$_url['parameter_1'];
        $parameter_2 = self::$_url['parameter_2'];
        $parameter_3 = self::$_url['parameter_3'];
        $view        = $instance->view;
        $controller  = self::$_url['controller'];
        
        if (isset($instance->model)){
            $view->model = $instance->model;
        }
        
        //se não tem action, action será 'index'
        $actionName = ($actionName=='' ? 'index' : $actionName);
        
        //chama a action de acordo com a quantidade de parâmetros enviados
        if (method_exists($instance, $actionName)) {
            $output = $instance->{$actionName}($parameter_1, $parameter_2, $parameter_3);
            if (self::$_config['ajax']){
                Response::success($output);
            }
        }else{
            self::routerError('action not found');
        }
        
        //se a action não retornou o conteúdo da view, busca direto da view
        if (is_null($output) && !self::$_config['ajax']){
            $output = $view->output();
        }
        
        //carrega o template
        if (!self::$_config['ajax']){
            $name = isset($templates[$controller]) ? $templates[$controller] : $templates["default"];
            require ROOT.'template/'.$name.'.php';
        }
    }    
    
    public static function getParameter($index){
        return self::$_url['parameter_'.$index];
    }
    public static function getConfig(){
        return self::$_config;
    }
    public static function config($config){
        self::$_config = array_merge_recursive(self::$_config, $config);
    }
    public static function run(){
        $cfg = self::$_config;
        
        //define algumas constantes úteis
        define('TEMPLATE', $cfg['template']);
        define('BASE_URL', $cfg['url']['base']);
        
        //separa a url em controller, action e parâmetros
        self::splitUrl();
        
        if (self::$_url['controller']==='mvc-info'){
            self::info();
        }
        
        $controllerClassName = self::$_url['controller']=='' ? 'MainController' : ucfirst(self::$_url['controller'] . $cfg['controllerSufix']);
        $modelClassName      = self::$_url['controller']=='' ? 'MainModel' : ucfirst(self::$_url['controller'] . 'Model');
        $actionName          = self::$_url['action'];
        
        //se o arquivo do controller não existe
        if (!file_exists(ROOT.$cfg['controllerPath'] . $controllerClassName . '.php')) {
            self::routerError('controller not found');
        }else{
            if (!file_exists(ROOT.'model/' . $modelClassName . '.php')){
                $modelClassName = NULL;
            }
            
            if (!is_null($modelClassName)){
                require __DIR__.'/Model.php';
            }
            
            if ($cfg['ajax'] && function_exists("ajax_bootstrap") ){
                ajax_bootstrap();
            }
            
            $instance = self::controllerInstance($controllerClassName, $modelClassName);
            self::runController($instance, $actionName);
        }
    }
}

MVC::config(array(
    "controllerSufix" => "Controller",
    "controllerPath"  => "controller/"
));
