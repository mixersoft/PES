<?php
class ExecComponent extends Object
{
	var $name='Exec';
	var $controller;
	var $components;
	var $uses = array();
	static $OS;
	

	/*
	 * Constants
	 */

	function __construct() {
		parent::__construct();
		if (substr(php_uname(), 0, 7) == "Windows"){
				ExecComponent::$OS = "win32";
		} else {		
				ExecComponent::$OS = "unix";
		}
	}

	function startup(& $controller)
	{
		$this->controller = $controller;
	}
	
	function getOS() {
		return ExecComponent::$OS;
	}
	
	function shell($commands, $options=array())
	{
		if (ExecComponent::$OS == "win32"){
//			$shell = "%windir%\System32\CMD.exe ";
			$shell = "CMD.exe ";
			if (is_string($commands)) $commands = array($commands);
			if (!@empty($options['path'])) array_unshift($commands, "set PATH={$options['path']};%PATH%;");
			$commands[]='exit';
			$commands[]='exit';
			
//			unset($options['title']);
			$shellpath = @if_e(Configure::read('bin.shell'), 'C:/windows/system32');
			$options['env'] = array('path'=>$shellpath);
			$error = $this->win32exec($shell, $options, $commands);
		} else {		
			$shell = "/bin/bash";
			if (!@empty($options['path'])) array_unshift($commands, 'PATH='.$options['path'].':$PATH ; export PATH');
			if (is_array($commands)) {
				$commands[]='exit';
				$commands[]='exit';
			}			
			$error = $this->unixExec($shell, $options, $commands);
		}
		if($error) return $error;
	}
				
	function exec($cmd, $options=NULL, $stdin=NULL)
	{
		if (ExecComponent::$OS == "win32"){
			$return = $this->win32exec($cmd, $options, $stdin);
		} else {		
			$return = $this->unixExec($cmd,$options, $stdin);
		}
		return $return;
	}
	
	
	
	/**
	 * exec in Win32 shell
	 * @param $cmd - dos shell cmd
	 * @param $path - default to Configure::read('bin.imagemagick')
	 * @return String error message or 0 if no errors 
	 */
	function win32exec($cmd, $options=array(), $stdin=array()){
//		debug($stdin); 
//		debug($cmd);
		// Win32 defaults
		$default=array(
			'title'=>null
			,'cwd'=>'W:\\'
			,'env'=>array()
//			,'other_options'=>array('bypass_shell'=>true)
			,'asynch'=>false
		);
		$options = array_merge($default, $options);
		
		$descriptors = array(
			0 => array('pipe', 'r'), // stdin
			1 => array('pipe', 'w'), // stdout
			2 => array('pipe', 'w')  // stderr
		);
		// show Win32 commmand window for long running processes
		if ($options['title']!==null) 
		{
			$cmd = "start \"{$options['title']}\" ".$cmd;
//			$options['other_options']=array('bypass_shell'=>false);	
			// $this->log($options, LOG_DEBUG);		
		}
// $this->log($options, LOG_DEBUG);		
		return $this->realExec($cmd, $options, $stdin);
	}
	

	function unixExec($cmd, $options=NULL, $stdin=array()) 
	{

		// unix defaults
		$default=array(
			'cwd'=>'/tmp'
			,'env'=>array()
			,'asynch'=>false
		);
		$options = array_merge($default, $options);		
		
				$cwd = '/tmp';
		$env = array();
		if ($options) extract($options, EXTR_OVERWRITE);
		$descriptors = array(
			0 => array('pipe', 'r'), // stdin
			1 => array('pipe', 'w'), // stdout
			2 => array('pipe', 'w')  // stderr
		);		
		
		return $this->realExec($cmd, $options, $stdin);	
	}
	
	function realExec($cmd, $options, $stdin)
	{
		extract($options);
		
		$descriptors = array(
			0 => array('pipe', 'r'), // stdin
			1 => array('pipe', 'w'), // stdout
			2 => array('pipe', 'w')  // stderr
		);
		if ($stdin && isset($title) && $title=='deliver')
		{
			$this->log($cmd, LOG_DEBUG);
			$this->log($stdin, LOG_DEBUG);
		} 
// $this->log($env, LOG_DEBUG);

		/*
		 * Session close to keep Apache from hanging 
		 * see: http://bugs.php.net/bug.php?id=22526
		 */
		if (!empty($_REQUEST))	session_write_close();
		
		if ($asynch!==false) 
		{
			$h = proc_open($cmd, $descriptors, $pipes, $cwd, $env);
			$result = proc_close($h);
		} 
		else 
		{
			if ($h = proc_open($cmd, $descriptors, $pipes, $cwd, $env))
			{
	//			debug(implode("\n", $stdin));
				if ($stdin) fwrite($pipes[0], implode("\n", $stdin));
				
	//			$output=''; $errors='';
	//			while ($line = fgets($pipes[1]))	$output .= $line;
				$output = stream_get_contents($pipes[1]);
	
	//			while ($line = fgets($pipes[2]))	$errors .= $line;
				$errors = stream_get_contents($pipes[2]);

				
//		// wait until completion
//		$process = proc_get_status($h);
//		$pid = $process['pid'];
//		// do something with pid
//		echo("\n  process status".print_r($process, true));
//		while (isset($process['running']) && $process['running']) {
//			sleep(5);
//			$process = proc_get_status($h);
//		};				
//				
				
				
				foreach($pipes as $pipe) fclose($pipe);
				$result = proc_close($h);
			} else {
				return "proc_open() failed, cmd=$cmd";
			}
		}
		
		/*
		 * Session Start: have to buffer output to restart session
		 */	
//		session_start();
		
//if (strpos($cmd,'jhead')===false) 
$loc = "ExecComponent::realExec";
$this->log(compact('loc','result','cmd','output','errors'),LOG_DEBUG);		
		if ($result) return $errors;
		else return $result;		
	}
	
}
?>
