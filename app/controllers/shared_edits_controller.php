<?php
class SharedEditsController extends AppController {

	var $name = 'SharedEdits';

	function index() {
		$this->SharedEdit->recursive = 0;
		$this->set('sharedEdits', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'shared edit'));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('sharedEdit', $this->SharedEdit->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->SharedEdit->create();
			if ($this->SharedEdit->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'shared edit'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'shared edit'));
			}
		}
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'shared edit'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->SharedEdit->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'shared edit'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'shared edit'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->SharedEdit->read(null, $id);
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'shared edit'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->SharedEdit->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Shared edit'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Shared edit'));
		$this->redirect(array('action' => 'index'));
	}
}
?>