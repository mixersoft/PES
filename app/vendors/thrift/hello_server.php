<?php
/*
 * 
 * Template for implementing Thrift Service Classes compiled from [Service].thrift
 * 
 * 
 *  1. set the global for the compiled thrift service, for 0.8.0 should be found in packages/
 * 		example: $GLOBALS['THRIFT_ROOT']/packages/Hello/HelloService.php"
 */  
$GLOBALS['THRIFT_SERVICE']['PACKAGE'] = 'Hello';
$GLOBALS['THRIFT_SERVICE']['NAME'] = 'HelloService';		// use CamelCase

/*
 * 2. bootstrap Thrift from Cakephp
 */ 
require_once ROOT.'/app/vendors/thrift/bootstrap_thrift_server.php';
bootstrap_THRIFT_SERVER();
load_THRIFT_SERVICE();

/*
 * 3. Implement the compiled thrift service interface here
 * 		example: class HelloServiceImpl implements HelloServiceIf() 
 * 
 * 
 * 		AND DON'T FORGET TO 
 * 4. Process_THRIFT_SERVICE_REQUEST() at the END of this file (see below)
 * 
 * 
 */ 

function check_cakephp($shell){
	/*
	 *  test Cakephp Model reference
	 */
	$options = array(
		'fields'=>array("User.id, User.username"),
	);
	$data = ClassRegistry::init('User')->find('first', $options);
	
	if ($_SERVER['REQUEST_METHOD']=="GET") {
		debug("Cake Model->query(): result=".print_r($data, true));
	} else error_log("Cake Model->query(): result=".print_r($data, true));	
		
	return $data;
}

class HelloServiceImpl implements HelloServiceIf {
        public function say_foreign_hello($language) {
                switch($language) {
                        case HelloLanguage::ENGLISH:
                                return "Hello World!";
                        break;
                        case HelloLanguage::FRENCH:
                                return "Bonjour tout le monde!";
                        break;
                        case HelloLanguage::SPANISH:
                                return "Hola Mundo!";
                        break;
                        default:
                                return "You didn't specify a valid language!";
                        break;
                }
        }
        public function say_hello_repeat($times) {
			if (1) {
				try {
					$data = check_cakephp('test');
					return $data['User'];
				} catch (Exception $e) {
					throw new Exception("Error: something wrong when we boostrapped cakephp");
				}
        	}        	
            $hellos = array();
            for($i=0;$i<$times;$i++) {
                    $hellos[] = "$i Hello World!";
            }
            return $hellos;
        }
 
        public function say_hello() {
        	error_log("HelloServiceImpl->say_hello()");
            return "Hello World!!!!!!!!";
        }
}


/*
 * 4. process the Thrift service request
 */
process_THRIFT_SERVICE_REQUEST();

?>