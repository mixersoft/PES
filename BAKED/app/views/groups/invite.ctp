<?php
echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>
<?php  if (isset($invitation)) { // invite ?>
<div class="groups invite">
<div class='placeholder'>
<h2>Invite your friends to join this group.</h2>
<div></div>
<blockquote>(add a "Share this:" block for sharing by email, facebook,
twitter, etc)
<p /><?php echo $this->Html->link($invitation); ?>

</blockquote>
<br />
</div>
</div>
<?php } else { // accept, jump page ?>
<div class="groups invite">
<div class='placeholder'>
<h2>Accept Invitation to Join this Group.</h2>
<div></div>
<blockquote>
<p />
(jump page for accepting invitation)
<p />
<?php echo $this->Html->link('continue to join link', array('action'=>'join',AppController::$uuid)); ?>
<p />
</blockquote>
<br />
</div>
</div>
<?php } ?>
