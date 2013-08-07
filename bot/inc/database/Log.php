<?php

    class Log extends DAO
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
            $this->setTableName('t_log');
            $this->setPrimaryKey('s_id');
            $array_fields = array(
                'fk_i_chat_id',
                's_author',
                's_id',
                's_nickname',
                'i_timestamp',
                's_message'
            );
            $this->setFields($array_fields);
        }

        function isProcessed($id) {
            $msg = $this->findByPrimaryKey($id);
            return isset($msg['s_id']);
        }

    }
?>