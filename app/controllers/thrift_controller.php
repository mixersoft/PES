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
	 * load thrift service, with prefix routing
	 */
	function service($service, $debug = 0) {
		try {
			Configure::write('debug', $debug);
			if (isset($this->passedArgs['api'])) {
				$version = $this->passedArgs['api'];
				$GLOBALS['THRIFT_SERVICE']['VERSION'] = $version; 
				App::import('Vendor', "thrift/{$version}/{$service}");
			} else {
				App::import('Vendor', "thrift/{$service}");
			}
		} catch(Exception $e) {
			$this->log("ERROR: Thrift service=app/vendors/thrift/{$version}/{$service}");
		}
		exit(0);		// return response over thrift transport
	}
}
?>
