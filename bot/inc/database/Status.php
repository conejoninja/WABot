<?php

    class Status extends DAO
    {
        private static $instance;

        public static function newInstance()
        {
            if( !self::$instance instanceof self ) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        function __construct()
        {
            parent::__construct();
            $this->setTableName('t_status');
            $this->setPrimaryKey('pk_i_id');
            $array_fields = array(
                'pk_i_id',
                's_game',
                's_status'
            );
            $this->setFields($array_fields);
        }

    }
?>