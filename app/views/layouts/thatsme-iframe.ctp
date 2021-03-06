<?php 
echo header('Pragma: no-cache');
echo header('Cache-control: no-cache');
echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 

$hostname = Configure::read('isLocal') ? 'thats-me' : 'thats-me.snaphappi.com';
$iframeHost = Configure::read('isLocal') ? 'snappi-dev' : 'dev.snaphappi.com';
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<style type="text/css">
		/* Generated by Font Squirrel (http://www.fontsquirrel.com) on January 18, 2013 */
		@font-face {
		    font-family: 'GeoSansLightRegular';
		    src: url('http://<?php   echo $hostname ; ?>/css/fonts/GeoSansLight/geosanslight-webfont.eot');
		    src: url('http://<?php   echo $hostname ; ?>/css/fonts/GeoSansLight/geosanslight-webfont.eot?#iefix') format('embedded-opentype'),
		         url('http://<?php   echo $hostname ; ?>/css/fonts/GeoSansLight/geosanslight-webfont.woff') format('woff'),
		         url('http://<?php   echo $hostname ; ?>/css/fonts/GeoSansLight/geosanslight-webfont.ttf') format('truetype'),
		         url('http://<?php   echo $hostname ; ?>/css/fonts/GeoSansLight/geosanslight-webfont.svg#geosanslightregular') format('svg');
		    font-weight: normal;
		    font-style: normal;
		}	
		@font-face {
		  font-family: 'Homemade Apple';
		  font-style: normal;
		  font-weight: 400;
		  src: local('Homemade Apple'), local('HomemadeApple'), url(http://themes.googleusercontent.com/static/fonts/homemadeapple/v3/yg3UMEsefgZ8IHz_ryz86Kmk7U3V72hKgu0Yds_deA8.woff) format('woff');
		}
		@font-face {
		  font-family: 'Roboto';
		  font-style: normal;
		  font-weight: 300;
		  src: local('Roboto Light'), local('Roboto-Light'), url(http://themes.googleusercontent.com/static/fonts/roboto/v7/Hgo13k-tfSpn0qi1SFdUfT8E0i7KZn-EPnyo3HZu7kw.woff) format('woff');
		}
		@font-face {
		  font-family: 'Roboto';
		  font-style: normal;
		  font-weight: 400;
		  src: local('Roboto Regular'), local('Roboto-Regular'), url(http://themes.googleusercontent.com/static/fonts/roboto/v7/2UX7WLTfW3W8TclTUvlFyQ.woff) format('woff');
		}
		@font-face {
		  font-family: 'Roboto';
		  font-style: normal;
		  font-weight: 700;
		  src: local('Roboto Bold'), local('Roboto-Bold'), url(http://themes.googleusercontent.com/static/fonts/roboto/v7/d-6IYplOFocCacKzxwXSOD8E0i7KZn-EPnyo3HZu7kw.woff) format('woff');
		}
		@font-face {
		  font-family: 'FontAwesome';
		  src: url(http://<?php   echo $hostname ; ?>/css/fonts/FontAwesome/fontawesome-webfont.eot?v=3.0.1);
		  src: url(http://<?php   echo $hostname ; ?>/css/fonts/FontAwesome/fontawesome-webfont.eot?#iefix&v=3.0.1) format('embedded-opentype'),
		    url(http://<?php   echo $hostname ; ?>/css/fonts/FontAwesome/fontawesome-webfont.woff?v=3.0.1) format('woff'),
		    url(http://<?php   echo $hostname ; ?>/css/fonts/FontAwesome/fontawesome-webfont.ttf?v=3.0.1) format('truetype');
		  font-weight: normal;
		  font-style: normal;
		}		
	</style>	
<?php	
	echo '<link href="http://'.$hostname.'/min/b=css&f=bootstrap.css,responsive.css,beachfront-2.css,beachfront.css,responsive-tablet.css,responsive-mobile.css,font-awesome.css" type="text/css" rel="stylesheet">';
	echo '<link href="http://'.$hostname.'/css/beachfront-less.css" type="text/css" rel="stylesheet">';	
	$this->Layout->output($this->viewVars['HEAD_for_layout']);
	if ($hostname == 'thats-me.snaphappi.com') echo	'<script type="text/javascript">document.domain="snaphappi.com"</script>';
?>	
	</head>
	<body>
<?php	  
	echo $content_for_layout;	
?>		
	</body>
</html>

