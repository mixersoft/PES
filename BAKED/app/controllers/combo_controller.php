<?php 
class ComboController extends AppController {
    var $name = 'Combo';
    var $uses = NULL;
    var $helpers = NULL;
    var $autoRender = false;
    var $layout = null;
    //    var $cacheAction = array('index/'=>21600, 'js/'=>21600);
    
    function beforeFilter() {
        $this->Auth->allow('*');
    }
    function beforeRender() {
    }    
    
    function js() {
    	$this->autorender = false;
		Configure::write('debug', 0);
        $qs = $_GET; // $this->params;
        $baseurl = @if_e($qs['baseurl'], '');
        $wwwroot = Configure::read('path.wwwroot');       
        unset($qs['url'], $qs['baseurl']);
        if ( empty($qs)) {
            header('Content-Type: application/x-javascript');
        } else {
            $scripts = array_keys($qs);
            
            $comboAsArray = array();
            foreach ($scripts as $script) {
                $i = strrpos($script, '_');
                $script = substr_replace($script, '.', $i, 1);
                $path = cleanpath($wwwroot.DS.$baseurl.DS.$script);
                $comboAsArray[] = file_get_contents($path);
            }
            $contentType = substr($script, $i + 1);


            /*
             * return combo scripts
             */
            switch (strtolower($contentType)) {
                case "js":
                    header('Content-Type: application/x-javascript');
					if (strpos($baseurl, 'svc/lib') === 0) {
						setExpiresHeader(24*3600);	// 1 day for /svc/lib
					} else setExpiresHeader(5*60);		// 5 min for combo loaded scripts
                    break;
                case "css":
                    header('Content-Type: text/css');
                    break;
            }
            //setExpiresHeader(3600*24*30);
            echo implode(' ', $comboAsArray);
        }
		return;
		exit(0);
    }
    /**
     * renders raw HTML markup templates for use in javascript
     * @param $name string - name of view file
     */
    function markup($name) {
$this->log("/combo/markup/{$name}", LOG_DEBUG);    	
		$viewFile = DS."combo".DS.$name;
    	$this->render(null, 'markup', $viewFile);
    }
}
?>
