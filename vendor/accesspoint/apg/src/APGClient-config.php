<?php
    class APGClientConfiguration {

        public $WSDL = __DIR__ . '/service.wsdl'; // Physical path to WSDL file. Should be in a non web-accessible directory
        public $secureDOTapgPath = ""; // Physical path to secure.apg file. Should be in a non web-accessible directory
        public $NameSpace = "https://pgws.alert.com.mt/"; // Namespace. Do not modify.

        public function __construct()
        {
            $arguments = func_get_args();
            $numberOfArguments = func_num_args();
    
            if (method_exists($this, $function = '__construct'.$numberOfArguments)) {
                call_user_func_array(array($this, $function), $arguments);
            }
        }

        function __construct1($secureAPGPath){
            $this->secureDOTapgPath = $secureAPGPath;
        }

        function __construct0(){
            return;
        }

    }
?>