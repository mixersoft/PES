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
	 * @return aa of image-groups
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
			debug("WARNING: image-group doesn't work on win32, using sample output for workorder client=sardinia ***************");
			// Sample output
			$output = Array
				(
				    'Groups' => Array
				        (
				            '4' => Array
				                (
				                    '0' => '4bbb3907-b400-480f-b9b9-11a0f67883f5',
				                    '1' => '4bbb3907-7dd0-42d5-a285-11a0f67883f5',
				                    '2' => '4bbb3907-0088-4061-b834-11a0f67883f5',
				                ),
				            '12' => Array
				                (
				                    '0' => '4bbb3907-1d88-4f31-82e4-11a0f67883f5',
				                    '1' => '4bbb3907-82f4-4452-90a5-11a0f67883f5',
				                )
				        ),
				    'ID' => 1349150148,
				    'Timestamp' => 1349150148,
				    'count' => 22,
				    'elapsed (sec)' => '2.0446100234985',
				);
			return $output;
						
			// $stdin = substr($stdin, 0, 100).";";
			// $cmd = "echo {$stdin};"; // *** override ***********
			// // $cmd = "{$gist_bin}/image-group.cmd"; 
			// $errors = GistComponent::$Exec->exec($cmd, $options, $stdin, $output);
			// if ($errors) return $errors;
			// else return $output;
		} else {
			// unix, image-group only works on linux
			$cmd = "{$gist_bin}/image-group --base_path {$stageRoot} --preserve_order --pretty_print";
			$start = microtime(true);
			$errors = GistComponent::$Exec->exec($cmd, $options, $stdin, $output);
			$end = microtime(true);
			$elapsed = $end-$start;
			$this->log("GistComponent->image-group() processing, records={$castingCall['CastingCall']['Auditions']['Perpage']}, time={$elapsed} sec", LOG_DEBUG);
			if ($errors) return $errors;
			else {
				$output = json_decode($output, true);
				$output['count'] = $castingCall['CastingCall']['Auditions']['Perpage'];
				$output['elapsed (sec)'] = $elapsed;
				if (empty($output['Groups'])) $output['Groups'] = array();
				return $output;
			}
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
			$start = time();
			$errors = GistComponent::$Exec->exec($cmd, $options);
			$elapsed = time()-$start;
			$this->log("image-group processing for ", LOG_DEBUG);
			return $errors;
		}
	}
	
}
?>