<?php
class AppController {
	static $userid = null;
	static $ownerid = null;
	static $role = null;
}
// cake image_group twid=5013d07c-80fc-45d8-b3f4-245cf67883f5 infile=output-venice-244.json
class ImageGroupShell extends Shell {
	var $uses = array('User','Usershot', 'Asset');
	var  $relpath = '/gist/import/';
	function main() {
		$this->out("*************** ImageGroupShell ***********");
		$this->out("APP=".APP);
		$this->out(print_r($this->args, true));
		$this->hr();
		
		// parse args: infile: twid:
		foreach ($this->args as $i=>$arg) {
			list($name, $value) = explode('=', $arg);
			if ($name=='infile') $value = dirname(__FILE__).$this->relpath.$value;
			$extract[$name] = $value;
		}

		$this->args = $extract;
		$this->out(print_r($this->args, true));
		extract($extract);		// expecting $twid, $infile
		$this->hr();
		
		// use ROLE=SCRIPT, Usershot.priority=30
		$ScriptUser_options = array(
			'conditions'=>array(
				'primary_group_id'=>Configure::read('lookup.roles.SCRIPT'),
				'username'=>'image-group',
			)
		);
		$data = $this->User->find('first', $ScriptUser_options);
		print_r($data);
		

		// new AppController() to set $userid and $role for script
		App::import('Core', 'Controller'); 
		// App::import('Controller', 'App'); 
		AppController::$userid = $data['User']['id'];
		AppController::$role = 'SCRIPT'; 		// from conditions
		
		/*
		 * load GistComponent to get image-group output file as json
		 * 		URI = workorders/photos/[uuid]/raw:1/perpage:999/.json?debug=0
		 */
		App::import('Component', 'Gist');	// move to Task??
        $this->Controller =& new Controller(); 
		$this->Gist = loadComponent('Gist', $this->Controller); 
		
		
		// set WorkordersPermissionable 
		$this->Asset->Behaviors->attach('WorkorderPermissionable',
			array('type'=>'TasksWorkorder', 'uuid'=>$this->args['twid'])
		);
		$this->hr();


		// get assoc array from infile or castingCall
		if ($this->args['infile']) {
			$image_groups = json_decode(file_get_contents($this->args['infile']), true);	
		} else {
			// bind $script_owner to image-group runtime settings 
			// get castingCall using which AppController::$userid???
			$script_owner = empty($this->passedArgs['circle']) ? 'image-group' : 'image-group-circles';
			$preserveOrder = $script_owner == 'image-group';
			$image_groups = $this->Gist->getImageGroupFromCC($castingCall, $preserveOrder);	
		}
		
		
		
		// $this->out(print_r($image_groups, true));
		// import groups by script, using Usershot.priority=30
		$newShots = array();
		foreach($image_groups['Groups'] as $groupAsShot_aids) {
			
			// debug
			// if ($i > 5) break;
			
			if (count($groupAsShot_aids)==1) {
				unset($image_groups['Groups'][$i]); 
				continue;		// skip if only one uuid, group of 1
			}
			$result = $Usershot->groupAsShot($groupAsShot_aids, $force=true);
			if ($result['success']) {
				$newShots[] = array(
					'asset_ids'=>$groupAsShot_aids, 
					'shot'=>$result['response']['groupAsShot'],
				);
			} else {
				$newShots[] = array(
					'asset_ids'=>$groupAsShot_aids, 
					'shot'=>$result['response']['message'],
				);
			}
		}
		$this->hr();
		print_r($newShots);
	}
}
?>