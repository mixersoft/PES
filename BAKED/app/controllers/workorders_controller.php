<?php
class WorkordersController extends AppController {

	public $name = 'Workorders';
	public $layout = 'snappi-guest';

	function test() {
		// $this->Workorder->TEST_createWorkorder();
		
		$GROUP_WO = '4fb35286-526c-4973-ab0e-09fcf67883f5';
		$this->Workorder->TEST_createWorkorder($GROUP_WO);
		
		$this->render('/elements/dumpSQL');
	}

}
?>