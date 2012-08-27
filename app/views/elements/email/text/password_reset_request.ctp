<?php
/**
 * Copyright 2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
echo "To reset your password, please follow the link below within the next 24 hours:\n";
echo "\n";
echo Router::url(array('admin' => false, 'plugin' => '', 'controller' => 'users', 'action' => 'reset_password', $token), true). "\n";
echo "\n";
echo "If the link does not work, please copy and paste it into your browser address bar.\n";
echo "\n";
echo "Sincerely,\n";
echo "\n";
echo "The Snaphappi Team\n";
echo "\n";
?>