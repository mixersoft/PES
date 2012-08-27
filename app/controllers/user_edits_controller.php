<?php
class UserEditsController extends AppController {

	var $name = 'UserEdits';

	function index() {
		$this->UserEdit->recursive = 0;
		$this->set('userEdits', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'user edit'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('userEdit', $this->UserEdit->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->UserEdit->create();
			if ($this->UserEdit->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'user edit'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'user edit'));
			}
		}
		$owners = $this->UserEdit->Owner->find('list');
		$assets = $this->UserEdit->Asset->find('list');
		$this->set(compact('owners', 'assets'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'user edit'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->UserEdit->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'user edit'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'user edit'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->UserEdit->read(null, $id);
		}
		$owners = $this->UserEdit->Owner->find('list');
		$assets = $this->UserEdit->Asset->find('list');
		$this->set(compact('owners', 'assets'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'user edit'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->UserEdit->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'User edit'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'User edit'));
		$this->redirect(array('action' => 'index'));
	}
}
?>