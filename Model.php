<?php

require_once __DIR__.'/../db/DB.php';

class Model {
    /**
     * @var DB
     */
    protected $db;
    
    function __construct($config) {
        $this->db = new DB($config);
    }
}
