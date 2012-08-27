<?php
class AssetsCollectionsController extends AppController {

	var $name = 'AssetsCollections';

	function index() {
		$this->AssetsCollection->recursive = 0;
		$this->set('assetsCollections', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'assets collection'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('assetsCollection', $this->AssetsCollection->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->AssetsCollection->create();
			if ($this->AssetsCollection->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'assets collection'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'assets collection'));
			}
		}
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'assets collection'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->AssetsCollection->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'assets collection'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'assets collection'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->AssetsCollection->read(null, $id);
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'assets collection'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->AssetsCollection->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Assets collection'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Assets collection'));
		$this->redirect(array('action' => 'index'));
	}
}
?>