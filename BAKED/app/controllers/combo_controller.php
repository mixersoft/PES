<?php 
class ComboController extends AppController {
    var $name = 'Combo';
    var $uses = NULL;
    var $helpers = array('Cache');
    var $autoRender = false;
    var $layout = null;
    // var $cacheAction = array('js'=>86400);	// '1 day'
    // var $cacheAction = "1 hour";
    
    function beforeFilter() {
        $this->Auth->allow('*');
    }
    function beforeRender() {
    }    
    
    function js() {
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
			$output =  implode(' ', $comboAsArray);
			$this->set('output', $output);
					
            
            /*
             * return combo scripts
             */
			$contentType = substr($script, $i + 1);      
            switch (strtolower($contentType)) {
                case "js":
                    header('Content-Type: application/x-javascript');
					if (strpos($baseurl, 'svc/lib') === 0) {
						$this->cacheAction = '1 hour';
						setExpiresHeader(24*3600);	// 1 day for /svc/lib
					} else setExpiresHeader(5*60);		// 5 min for combo loaded scripts
                    break;
                case "css":
                    header('Content-Type: text/css');
					if (strpos($baseurl, 'svc/lib') === 0) {
						$this->cacheAction = '1 hour';
						setExpiresHeader(24*3600);	// 1 day for /svc/lib
					} else setExpiresHeader(5*60);		// 5 min for combo loaded scripts
                    break;
            }
        }
		$this->render('js', null);
		return;
    }
    /**
     * renders raw HTML markup templates for use in javascript
     * @param $name string - name of view file
     */
    function markup($name) {
    	// exports cookies with prefix 'SNAPPI_' to PAGE.Cookie
    	$this->layout='markup';
		$this->__setCookies();
		$this->autoRender = false;
		$viewFile = DS."combo".DS.$name;
    	$this->render(null, 'markup', $viewFile);
    }
}
?>
