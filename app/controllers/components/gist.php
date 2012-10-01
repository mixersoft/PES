<?php 
class GistComponent extends Object
{
	var $name='Gist';
	var $controller;
//	var $components = array('Exec');
	var $uses = array();
	
	static $Exec;
	static $PATH2EXE ;
	static $OPTIONS;
	static $OS ;
	

	/*
	 * Constants
	 */

	function __construct() {
		parent::__construct();
		if (empty(GistComponent::$Exec)) 
		{
			App::import('Component','Exec');
			GistComponent::$Exec = & new ExecComponent();
			$gist_bin = cleanPath(Configure::read('bin.gist'));
			GistComponent::$PATH2EXE = $gist_bin;
			GistComponent::$OPTIONS = array(
				'cwd'=>$gist_bin
//				, 'env'=>array( 'PATH'=>$gist_bin )
			);
			
			GistComponent::$OS = GistComponent::$Exec->getOS();
		}
	}

	function startup(& $controller)
	{
		$this->controller = & $controller;
	}
	/**
	 * get image-group output JSON from castingCall
	 * 	- assumes the method is called from Workorder or TasksWorkorder Controller 
	 * @param $castingCall aa, output $this->CastingCall->getCastingCall() 
	 */
	function getImageGroupFromCC($castingCall)
	{
		// config path
		$gist_bin = Configure::read('bin.gist');
		$stageRoot = Stagehand::$stage_basepath.DS;
		$imageGroupRoot = APP."vendors/shells/gist/import";

		// config proc_open
		$cmd = "{$gist_bin}/image-group --base_path {$stageRoot} --preserve_order --pretty_print";
		$options = GistComponent::$OPTIONS;
		$options['title'] = 'image-group';
		
		// config output
		$stdin['response']['castingCall'] = $castingCall;
		$stdin = json_encode($stdin);
		$output = true;		
		
		if (GistComponent::$OS=='win32') {
			debug("WARNING: image-group doesn't work on win32 ***************");			
			$stdin = substr($stdin, 0, 100).";";
			$cmd = "echo {$stdin};"; // *** override ***********
			// $cmd = "{$gist_bin}/image-group.cmd"; 
			$errors = GistComponent::$Exec->exec($cmd, $options, $stdin, $output);
			if ($errors) return $errors;
			else return $output;
		} else {
			// unix, image-group only works on linux
			$cmd = "{$gist_bin}/image-group --base_path {$stageRoot} --preserve_order --pretty_print";
			if (0 && Configure::read('isDev')) {	// snappi-cn testing, only
				$cat = "cat /www-dev/app/vendors/shells/gist/import/venice.json | ";
				$stdin = null;
				$cmd="{$cat} {$cmd}";
				debug("** Stdin is not closing properly. testing with cat | ");
			}
			debug("Perpage={$castingCall['CastingCall']['Auditions']['Perpage']}");
			$errors = GistComponent::$Exec->exec($cmd, $options, $stdin, $output);
			if ($errors) return $errors;
			else return $output;
		}
	}
		
	/**
	 * @param $type String = [Workorder | TasksWorkorder]
	 * @param $uuid workorder_id or tasks_workorder_id
	 * @param $userid AppController::$userid for ROLE=MANAGER|OPERATOR|SCRIPT
	 */
	function getImageGroup($type, $uuid, $userid)
	{
		$input_json = "";
		$stageRoot = Stagehand::$stage_basepath.DS;
		$imageGroupRoot = APP."vendors/shells/gist/import";
		$cmd = "cat $input_json | {$gist_bin}/image-group --base_path {$stageRoot} --preserve_order --pretty_print > {$imageGroupRoot}/output-ordered.json";
		
		if (GistComponent::$OS=='win32') {
			// just debug the cmd
debug($cmd);
			$return;
		} else {
			// unix, image-group only works on linux
			$options = GistComponent::$OPTIONS;
			$options['title'] = 'image-group';
			$errors = GistComponent::$Exec->exec($cmd, $options);
			return $errors;
		}
	}
	
}
?>