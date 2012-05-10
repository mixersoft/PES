<?php
echo $this->Session->flash();
echo $this->Form->create('ShareLink', array('url' => array('action' => 'ask_password', $secretId)));
echo $this->Form->input('password');
echo $this->Form->end('Submit');