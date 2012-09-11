<?php

if (!defined('THRIFT_LIB_BASEPATH')) define('THRIFT_LIB_BASEPATH', 'W:/www-dev/');
$GLOBALS['THRIFT_ROOT'] = THRIFT_LIB_BASEPATH.'app/vendors/THRIFT_ROOT/';
$GEN_DIR = $GLOBALS['THRIFT_ROOT'].'packages';
 
require_once $GLOBALS['THRIFT_ROOT'] . '/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TPhpStream.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TFramedTransport.php';
 
require_once $GEN_DIR . '/Hello/HelloService.php';

/*
 * bootstrap cakephp, adapted from console bootstrap
 */
// print("\r\n<br> cakephp shell bootstrap ");				
$argv[] = __FILE__;
$argv[] = '-working';
$argv[] = THRIFT_LIB_BASEPATH.'/app';
$argv[] = 'test';				
require_once THRIFT_LIB_BASEPATH."app/vendors/thrift/thrift-cake-bootstrap.php";
// print("\r\n<br> bootstrap complete;");

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
                $hellos = array();
                for($i=0;$i<$times;$i++) {
                        $hellos[] = "$i Hello World!";
                }
                return $hellos;
        }
 
        public function say_hello() {
        	error_log("HelloServiceImpl->say_hello()");
			if (0) {
				$argv[] = '-app';
				$argv[] = 'W:\www-dev\app';
				$argv[] = '-working';
				$argv[] = 'W:\www-dev\app';
				$argv[] = 'test';
	        	$dispatcher = new ShellDispatcher($argv);
				$shell = $dispatcher->Shell;
				$options = array(
					'fields'=>array("User.id, User.username"),
				);
				$data = $shell->User->find('first', $options);
				print($data[0]);
				return($data[0]);
        	}
            return "Hello World!!!!!!!!";
        }
}
 
header('Content-Type', 'application/x-thrift');
 
$handler   = new HelloServiceImpl();
$processor = new HelloServiceProcessor($handler);
 
$transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));
 
$protocol = new TBinaryProtocol($transport, true, true);
 
$transport->open();
$processor->process($protocol, $protocol);
$transport->close();
?>