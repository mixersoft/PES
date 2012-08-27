<?php
echo sprintf(__d('users', 'Hello %s,', true), ucFirst($user['User']['username'])) . "\n";
echo "\n";
echo "To verify your email address, please follow the link below within the next 24 hours:\n";
echo "\n";
echo Router::url(array('admin' => false, 'plugin' => '', 'controller' => 'users', 'action' => 'verify', 'email', $user['Profile']['email_token']), true) . "\n";
echo "\n";
echo "If the link does not work, please copy and paste it into your browser address bar.\n";
echo "\n";
echo "Your Snaphappi account was created with the following details:\n";
echo "\n";
echo sprintf(__d('users', 'username: %s', true), $user['User']['username']) . "\n";
echo sprintf(__d('users', 'email: %s', true), $user['User']['email']) . "\n";
echo "\n";
echo "Thank you,\n";
echo "\n";
echo "The Snaphappi Team\n";
echo "\n";
?>

