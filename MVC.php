<?php

error_reporting(E_ALL & ~E_NOTICE);
define('MVC_VERSION', '0.9.2');

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
        $template = self::$_config['template'];
        
        if ( file_exists(ROOT.'template/'.$template.'_error.php') ){
            require ROOT.'template/'.$template.'_error.php';
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
    
    public static function getParameter($index){
        return self::$_url['parameter_'.$index];
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
        $template    = self::$_config['template'];
        $parameter_1 = self::$_url['parameter_1'];
        $parameter_2 = self::$_url['parameter_2'];
        $parameter_3 = self::$_url['parameter_3'];
        $view        = $instance->view;
        
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
            require ROOT.'template/'.$template.'.php';
        }
    }
    
    public static function getConfig(){
        return self::$_config;
    }
    
    public static function config($config, &$level=NULL){
        foreach ($config as $key=>$value){
            if (is_array($value)){
                self::$_config[$key] = (isset(self::$_config[$key]) ? self::$_config[$key] : array());
                self::config($value, self::$_config[$key]);
            }else{
                if (is_null($level)){
                    self::$_config[$key] = $value;
                }else{
                    if (!is_array($level)){
                        $level = array();
                    }
                    $level[$key] = $value;
                }
            }
        }
    }
    
    public static function run(){
        //define algumas constantes úteis
        define('TEMPLATE', self::$_config['template']);
        define('BASE_URL', self::$_config['url']['base']);
        
        //separa a url em controller, action e parâmetros
        self::splitUrl();
        
        if (self::$_url['controller']==='mvc-info'){
            self::info();
        }
        
        //carrega classes do core
        require __DIR__.'/Model.php';
        require __DIR__.'/View.php';
        require __DIR__.'/Controller.php';
        
        $controllerClassName = self::$_url['controller']=='' ? 'MainController' : ucfirst(self::$_url['controller'] . self::$_config['controllerSufix']);
        $modelClassName      = self::$_url['controller']=='' ? 'MainModel' : ucfirst(self::$_url['controller'] . 'Model');
        $actionName          = self::$_url['action'];
        
        //se o arquivo do controller não existe
        if (!file_exists(ROOT. self::$_config['controllerPath'] . $controllerClassName . '.php')) {
            self::routerError('controller not found');
        }else{
            if (!file_exists(ROOT.'model/' . $modelClassName . '.php')){
                $modelClassName = NULL;
            }
            
            if (self::$_config['ajax'] && function_exists("ajax_bootstrap") ){
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
