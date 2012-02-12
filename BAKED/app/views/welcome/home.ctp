<div class='one-col'>
    <div>
        <blockquote>
            <i>"I've got 1000s of photos on my computer and it's overwhelming. I don't know where to begin..."</i>
        </blockquote>
        <blockquote>
            <i>"The photos are coming too fast - I can't keep up with my life..."</i>
        </blockquote>
        <div id='welcome-copy' class='text'>
            <a href='http://gallery.snaphappi.com/gallery/page_gallery/venice' target='_blank'><img width="320" height="213" title='click here to see a sample Page Gallery in a new window' src='/img/welcome/page-gallery-sample.jpg'></a>
			<h3>Snaphappi Gallery</h3>
            <p>
                You've got some beautiful memories buried among 1000s of photos. Let us help you dig them out.
            </p>
            <p>
                Use our Gallery application to:
                <ul>
                    <li>
                        find and rate your favorite photos,
                    </li>
                    <li>
                        automatically make beautiful Page Galleries from your best shots,
                    </li>
                    <li>
                        and quickly share them with your family and friends.
                    </li>
                </ul>
            </p>
			<h3>Handcrafted Digital Photo-processing™</h3>
            <p>
                But that's not all. If you get stuck at any point, 
                you can (soon) call on our staff of professionally-trained photo editors
                to help you get started, or get it done. We have <u>real people</u> helping you make sense of your photos.
            </p>
            <p>
                Do more with your photos - and let us help you get it done.
            </p>
        </div>
        <div class='text'>
        	<h3>Quick Links (for DEV)</h3>
        	<ul class='inline'>
            <li><?php $userid = Session::read('Auth.User.id'); echo $this->Html->link('my account', "/users/home/{$userid}");?></li>
            <li><?php echo $this->Html->link('browse groups', "/groups");?></li>
            <li><?php echo $this->Html->link('check ACLs', "/users/checkPermission");?></li>         
            <li><?php echo $this->Html->link('logout', "/users/signout");?></li>     	
        	</ul>
        	<br>
        
        
        	<h3>Take a Sneak Peek</h3>
            <p>
                Preview the Snaphappi Gallery application now and see how easy it is to make and share beautiful Page Galleries.
            </p>
            <?php echo $this->Form->submit('Get Started', array('onclick'=>'location.href="/welcome/connect"'));?>

        </div>
    </div>
    <div class='coloredHref center'>
        <p>
            <?php // echo $html->link("Take a quick tour of Snaphappi", '/tour/home'); ?>
        </p>
        <br>
    </div>
</div>
<?php // echo $this->Layout->getFooterBar(); ?>
