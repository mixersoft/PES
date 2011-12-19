<?php 	$this->Layout->blockStart('itemHeader');  ?>
<section class='item-header container_16'>
	<div class='wrap'>
		<h1 class='grid_16'>Awww Snap.</h1>
	</div>
</section>
<?php 	$this->Layout->blockEnd();  ?>

<section class="prefix_4 grid_8 suffix_4">
	<div class='related-content blue rounded-5'>
		<h2>Your web browser is not yet supported</h2>
		<p>Sorry, your browser is currently not supported.</p>
		<br />
		<p>Snaphappi is developed and tested on the Firefox, Google Chrome, and Safari.
		Please copy the link below and try again from one of these web browsers.</p>
		<div class="link">
			<?php  
				$target = Session::read('browser_unsupported_redirect');
				if ($target) {
					Session::delete('browser_unsupported_redirect');
					$tokens['linkTo'] = Router::url($target, true);
					echo String::insert("<input style='width:98%;' type='text' class='copy-paste' onclick='this.select();' value=':linkTo' />", $tokens);
				}
			?>
			</div>
		<div class="wrap-v">
			<div class="alpha prefix_1 grid_1"><a href='http://www.spreadfirefox.com/?q=affiliates&id=0&t=68'><img src='/img/providers/firefox.png'></a></div>	
			<div class="prefix_1 grid_1"><a href='http://www.google.com/chrome'><img src='/img/providers/chrome.png'></a></div>
			<div class="prefix_1 grid_1 "><a href='http://www.apple.com/safari/download/'><img src='/img/providers/safari.png'></a></div>	
		</div>
	</div>
</section>