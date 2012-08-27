<?php 
class MeanshiftComponent extends Object {
    var $name = 'Meanshift';
    var $controller;
    var $uses = array();
    
    static $Exec;
    static $PATH2EXE;
    static $PATH2TMP;
    static $OS;
    static $OPTIONS;
    
    var $meanshift_options = '--bandwidthForSequence 12 -gpr 3.0';
    
    /*
     usage: mean-shift
     -b,--bandwidth <arg>               Bandwidth for event clustering
     -bs,--bandwidthForSequence <arg>   Bandwidth forsequence clustering
     -c,--config <arg>                  Name of configuration file. Default
     conf.cfg
     -dec,--do-event-clustering         Do event clustering
     -dev,--devide-event-value <arg>    Value for dividing to groups for event
     clustering
     -dsc,--do-seq-clustering           Do sequence clustering
     -dsv,--devide-seq-value <arg>      Value for dividing to groups for
     sequence clustering
     -fev,--filter-event-value <arg>    Value for filtering photos by score in
     event clustering
     -h,--help                          Print help for this application
     -i,--input <arg>                   Name of input file. Default ex.xml
     -o,--output <arg>                  Name of output file. Default out.xml
     */

    
    /*
     * Constants
     */
    
    function __construct() {
        parent::__construct();
        if ( empty(MeanshiftComponent::$Exec)) {
            App::import('Component', 'Exec');
            MeanshiftComponent::$Exec = & new ExecComponent();
            MeanshiftComponent::$PATH2EXE = cleanPath(Configure::read('bin.meanshift'));
            MeanshiftComponent::$PATH2TMP = cleanPath(Configure::read('path.meanshift_tmp'));
            MeanshiftComponent::$OPTIONS = array('cwd'=>MeanshiftComponent::$PATH2EXE);
            MeanshiftComponent::$OS = MeanshiftComponent::$Exec->getOS();
        }
    }
    
    function startup(&$controller) {
        $this->controller = $controller;
        if (!file_exists(MeanshiftComponent::$PATH2TMP)) {
            mkdir(MeanshiftComponent::$PATH2TMP, 2775, true);
        }
    }
    
    function getGroupings($in = 'in.xml', $out = null) {
        if (!file_exists(MeanshiftComponent::$PATH2TMP.DS.$in)) {
        
            // set session for tempfile prefix
            if (!$this->controller->Session->check('Meanshift.SessionKey')) {
                $this->controller->Session->write('Meanshift.SessionKey', time().'-'.rand());
            }
            $session = $this->controller->Session->read('Meanshift.SessionKey');
            
            /*
             * $in== is an XML file as string, write to tmp file
             */
            $content = $in;
            
            $in = MeanshiftComponent::$PATH2TMP.DS."{$session}.in.xml";
            $out = $out ? $out : MeanshiftComponent::$PATH2TMP.DS."{$session}.out.xml";
            $Handle = fopen($in, 'w');
            fwrite($Handle, $content);
            fclose($Handle);
        }
        
        $in_option = $in ? "-i {$in}" : '';
        $out_option = $out ? "-o {$out}" : '';
        $runtime_options = ($this->meanshift_options) ? $this->meanshift_options : '';
        $cmd = "java -jar meanshift.jar  {$runtime_options} {$in_option} {$out_option}";
        debug($cmd);
        $options = MeanshiftComponent::$OPTIONS;
        $errors = MeanshiftComponent::$Exec->exec($cmd, $options);
        return array('errors'=>$errors, 'out'=>$out);
    }

    
}
?>
