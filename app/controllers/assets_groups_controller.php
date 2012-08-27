<?php
class AssetsGroupsController extends AppController {

	var $name = 'AssetsGroups';

	function index() {
		$this->AssetsGroup->recursive = 0;
		$this->set('assetsGroups', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'assets group'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('assetsGroup', $this->AssetsGroup->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->AssetsGroup->create();
			if ($this->AssetsGroup->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'assets group'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'assets group'));
			}
		}
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'assets group'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->AssetsGroup->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'assets group'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'assets group'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->AssetsGroup->read(null, $id);
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'assets group'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->AssetsGroup->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Assets group'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Assets group'));
		$this->redirect(array('action' => 'index'));
	}
}
?>