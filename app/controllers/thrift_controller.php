<?php 
class ThriftController extends AppController {
    public $name = 'Thrift';
	public $uses = array();
	 
    public $helpers = array(
		// 'Time',
		// 'Text',
		// 'Layout',
	);

	public $autoRender = false;			// return response over thrift transport
    public $layout = false;

	public $components = array(
		// add components here
	);	
    
    function beforeFilter() {
    	parent::beforeFilter();
        $this->Auth->allow('*');
    }
   
	/**
	 * load thrift service
	 */
	function service($service, $debug = 0) {
		try {
			Configure::write('debug', $debug);
			App::import('Vendor', "thrift/{$service}");
		} catch(Exception $e) {
			$this->log("ERROR: Thrift service=app/vendors/thrift/{$service}");
		}
		exit(0);		// return response over thrift transport
	}
}
?>
