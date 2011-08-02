<?php
class CollectionsController extends AppController {

	var $name = 'Collections';

	function index() {
		$this->Collection->recursive = 0;
		$this->set('collections', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'collection'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('collection', $this->Collection->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->Collection->create();
			if ($this->Collection->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'collection'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'collection'));
			}
		}
		$users = $this->Collection->User->find('list');
		$assets = $this->Collection->Asset->find('list');
		$groups = $this->Collection->Group->find('list');
		$this->set(compact('users', 'assets', 'groups'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'collection'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Collection->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'collection'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'collection'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Collection->read(null, $id);
		}
		$users = $this->Collection->User->find('list');
		$assets = $this->Collection->Asset->find('list');
		$groups = $this->Collection->Group->find('list');
		$this->set(compact('users', 'assets', 'groups'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'collection'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Collection->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Collection'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Collection'));
		$this->redirect(array('action' => 'index'));
	}
}
?>