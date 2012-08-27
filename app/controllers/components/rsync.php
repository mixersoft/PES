<?php
class RsyncComponent extends Object
{
    var $name = 'Rsync';
    var $controller;
    var $components = array ('Exec');
    var $uses = array ();

    static $Exec;
    static $PATH2EXE;
    static $TARGET = null;
    static $TARGET_BASEPATH = null;


    /*
     * Constants
     */

    function __construct() {
        parent::__construct();
        if ( empty(RsyncComponent::$Exec))
        {
            App::import('Component', 'Exec');
            RsyncComponent::$Exec = & new ExecComponent();
        }
        RsyncComponent::$PATH2EXE = cleanPath(Configure::read('Config.rsync_home').DS.'bin');
        $remote = Configure::read('Remote');
        if (@isne($remote['host']))RsyncComponent::$TARGET = $remote['host'];
        if (@isne($remote['basepath']))RsyncComponent::$TARGET_BASEPATH = $remote['basepath'];
    }

    function startup( & $controller)
    {
        $this->controller = $controller;
    }

    function rsync($path) {
        $target_host = RsyncComponent::$TARGET;
        $target_basepath = RsyncComponent::$TARGET_BASEPATH;

        $config = Configure::read('Local');
        $basepath = cleanPath($config['preview']['fileroot'], 'unix');
        $relpath = str_replace($basepath.'/', '', cleanPath($path, 'unix'));

        // use -R relative switch, -rtvhR
        $options = array ('cwd'=>cleanPath(Configure::read('Config.rsync_script_home'), 'win32')
        , 'title'=>"rsync {$relpath}"
        );
        $win32_basepath = cleanPath($basepath, 'win32');
        switch(env('HTTP_HOST')) {
            case 'outpost-gallery':
            case 'snaphappi.com':
                $script = "gallery-rsync.cmd";
                break;
            case 'outpost-bp':
            default:
                $script = "bella-rsync.cmd";
                break;
        }
        $cmd = "$script --cwd \"{$win32_basepath}\" --src \"{$relpath}\"  --dest \"{$target_host}:{$target_basepath}/\" ";


        //		/**
        //		 * Archive.snaphappi.com test server
        //		 */
        //		$target_host = 'bellapictures@archive.snaphappi.com';
        //		$target_basepath = '/home/bellapictures/rsync-test';
        //		$options = array('cwd'=>cleanPath(Configure::read('Config.rsync_script_home'),'win32'));
        //		$win32_basepath = cleanPath($basepath, 'win32');
        //		$cmd = "bella-rsync.cmd --cwd \"{$win32_basepath}\" --src \"{$relpath}\"  --dest \"{$target_host}:{$target_basepath}/\" ";
        $this->log($cmd, LOG_DEBUG);
        $errors = RsyncComponent::$Exec->exec($cmd, $options);
        return $errors;
    }


    function uploadFromFiles() {
        // load Workorder model
        $target_host = RsyncComponent::$TARGET;
        $target_basepath = Configure::read('Remote.original_basepath');

        $config = Configure::read('Local');
        $basepath = cleanPath($config['original']['fileroot'], 'unix');
        $basepath = cleanPath($config['original']['fileroot']);
        $options = array ('cwd'=>cleanPath(Configure::read('Config.rsync_script_home'), 'win32')
        , 'title'=>"rsync uploadSTE"
        );
        $win32_basepath = cleanPath($basepath, 'win32');
        $cmd = "bella-rsync-from-file --cwd \"{$win32_basepath}\" --src . --dest \"{$target_host}:{$target_basepath}/\" ";

        $this->log($cmd, LOG_DEBUG);
        $errors = RsyncComponent::$Exec->exec($cmd, $options);
        return $errors;
    }

}
?>
