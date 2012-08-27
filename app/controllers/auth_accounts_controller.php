<?php
class AuthAccountsController extends AppController {

	var $name = 'AuthAccounts';

	function index() {
		$this->AuthAccount->recursive = 0;
		$this->set('authAccounts', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'auth account'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('authAccount', $this->AuthAccount->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->AuthAccount->create();
			if ($this->AuthAccount->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'auth account'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'auth account'));
			}
		}
		$users = $this->AuthAccount->User->find('list');
		$this->set(compact('users'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'auth account'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->AuthAccount->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'auth account'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'auth account'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->AuthAccount->read(null, $id);
		}
		$users = $this->AuthAccount->User->find('list');
		$this->set(compact('users'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'auth account'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->AuthAccount->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Auth account'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Auth account'));
		$this->redirect(array('action' => 'index'));
	}
}
?>