<?php
/**
 * template for thrift service
 * looking for thrift service in the following location:
 * 		"$GLOBALS['THRIFT_ROOT']/packages/{$GLOBALS['THRIFT_SERVICE']['PACKAGE']}/{$GLOBALS['THRIFT_SERVICE']['NAME']}.php"
 * 			ex: 
 * maps to __FILE__ with the following path:
 * 		ROOT."/app/vendors/thrift/".Inflector::underscore('$GLOBALS['THRIFT_SERVICE']['NAME']'); 
 * 
 * 
 */
// $GLOBALS['THRIFT_SERVICE']['PACKAGE'] = 'Hello';
// $GLOBALS['THRIFT_SERVICE']['NAME'] = 'HelloService';		// use CamelCase
// $GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_tasks';


if (empty($GLOBALS['THRIFT_SERVICE'])) throw new Exception('Error: $GLOBALS[THRIFT_SERVICE] is not set');
if (!isset($GLOBALS['THRIFT_SERVICE']['VERSION'])) throw new Exception('Error: $GLOBALS[THRIFT_SERVICE][VERSION] is not set');

function bootstrap_THRIFT_SERVER () {
	debug("Thrift bootstrap for file=".__FILE__);
	$GLOBALS['THRIFT_ROOT'] = ROOT."/app/vendors/thrift/{$GLOBALS['THRIFT_SERVICE']['VERSION']}/THRIFT_ROOT";
	require_once $GLOBALS['THRIFT_ROOT'] . '/Thrift.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TPhpStream.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php';
	require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TFramedTransport.php';
	debug("Thrift bootstrap complete");
}

function load_THRIFT_SERVICE() {
	$GEN_DIR = $GLOBALS['THRIFT_ROOT'].'/packages';
	$service = $GLOBALS['THRIFT_SERVICE'];
	$thrift_service_file = $GEN_DIR . "/{$service['PACKAGE']}/{$service['NAME']}.php";
	debug("LOADING Thrift Service (compiled), file=".$thrift_service_file);
	require_once $thrift_service_file;
}
 
function process_THRIFT_SERVICE_REQUEST() {
	$service = $GLOBALS['THRIFT_SERVICE'];
	header('Content-Type', 'application/x-thrift');

	/*
	 * example
	 */ 
	// $handler   = new HelloServiceImpl();
	// $processor = new HelloServiceProcessor($handler);
	
	$handler_class = $service['NAME']."Impl";
	$handler_class = empty($service['NAMESPACE']) ? $handler_class : $service['NAMESPACE'].'_'.$handler_class;
	$handler   = new $handler_class();
	$processor_class = $service['NAME']."Processor";
	$processor = new $processor_class($handler);
	debug("processor_class=$processor_class");
		 
	$transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));
	 
	$protocol = new TBinaryProtocol($transport, true, true);
	 
	$transport->open();
	$processor->process($protocol, $protocol);
	$transport->close();
}

?>