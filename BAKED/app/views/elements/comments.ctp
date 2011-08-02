<h3><?php __('Comments'); ?> </h3>
<?php $commentWidget->options(array('allowAnonymousComment' => false));?>
<?php echo $commentWidget->display(array('subtheme'=>'discussion'));?>