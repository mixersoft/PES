<?php if (empty($shareLinks)): ?>
<h2>No links for that query</h2>
<?php else: ?>
<h2>Links for the query</h2>
<?php pr($shareLinks); ?>
<?php endif; ?>