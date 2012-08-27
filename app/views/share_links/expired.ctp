<h2>The link is expired.</h2>

<h3>Submit the following form to ask a link renewal</h3>

<?php
$url = array('controller' => 'share_links', 'action' => 'ask_renewal', $secretKey);
echo $this->Form->create('ShareLink', array('url' => $url));
echo $this->Form->input('renewal_comment');
echo $this->Form->end(__('Submit', true));
?>