<?php
class ProvidersController extends AppController {

	var $name = 'Providers';

	function index() {
		$this->Provider->recursive = 0;
		$this->set('providers', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'provider'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('provider', $this->Provider->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->Provider->create();
			if ($this->Provider->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'provider'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'provider'));
			}
		}
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'provider'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Provider->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'provider'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'provider'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Provider->read(null, $id);
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'provider'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Provider->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Provider'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Provider'));
		$this->redirect(array('action' => 'index'));
	}
}
?>