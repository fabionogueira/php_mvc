<?php

class Session {
    private static $started = false;

    private static function start(){
        if (self::$started) return;

        if ( !(is_array($_SESSION) && isset($_SESSION['__timer'])) ) {
            session_start();
        }
        
        self::$started = true;
    }
    public static function create($data, $timer=0){ 
        self::start();
        
        if (!is_array($data)){
            exit ('Session::create() error! data deve ser do tipo array.');
        }
        
        $_SESSION = $data;
        $_SESSION['__timer'] = $timer;
        $_SESSION["__timer_started"] = time();
    }
    public static function destroy(){
        self::start();
        session_destroy();
    }
    public static function expired($return=true){
        self::start();
        
        $expired = false;
        
        if ( isset($_SESSION["__timer"]) && $_SESSION["__timer"]>0 ){
            $time = time() - $_SESSION["__timer_started"];

            if ( $time > $_SESSION["__timer"] ){
                $expired = true;
            }else{
                $_SESSION["__timer_started"] = time();
            }
        }
        
        if (!$return){
            exit ('Sua sessão expirou!');
        }
        
        return $expired;
    }
    public static function authorized($operations=''){
        if (!Session::expired()){
            if ($operations!=''){
                if (isset($_SESSION['operations'])){
                    if (is_array($operations)){
                        for ($i=0; $i<count($operations); $i++){
                            $op = $operations[$i];
                            if (!isset($_SESSION['operations'][$op])){
                                return false;
                            }
                        }
                    }else{
                        if (!isset($_SESSION['operations'][$operations])){
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
    public static function exist(){
        self::start();        
        return isset($_SESSION["__timer"]);
    }
    public static function item($item){
        self::start();
        return isset($_SESSION[$item]) ? $_SESSION[$item] : false;
    }
}
