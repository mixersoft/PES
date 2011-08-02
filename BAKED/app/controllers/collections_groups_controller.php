<?php
class CollectionsGroupsController extends AppController {

	var $name = 'CollectionsGroups';

	function index() {
		$this->CollectionsGroup->recursive = 0;
		$this->set('collectionsGroups', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'collections group'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('collectionsGroup', $this->CollectionsGroup->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->CollectionsGroup->create();
			if ($this->CollectionsGroup->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'collections group'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'collections group'));
			}
		}
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'collections group'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->CollectionsGroup->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'collections group'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'collections group'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->CollectionsGroup->read(null, $id);
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'collections group'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->CollectionsGroup->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Collections group'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Collections group'));
		$this->redirect(array('action' => 'index'));
	}
}
?>