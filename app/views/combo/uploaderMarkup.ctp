<div id="menu-sign-in-markup" class="xxmenu yui3-aui-overlaycontext-hidden hide">
	<div id='login'>
		<div class="grid_x">
			<div class='message hide'></div>
				<form id="UserLoginForm" method="post" action="" onsubmit="return false;" accept-charset="utf-8">
					<div style="display: none;"><input name="_method" value="POST" type="hidden"></div><div class="input text required"><label for="UserUsername">Username</label>
					<input class='postData' name="data[User][username]" maxlength="45" id="UserUsername" type="text"></div> 
					<div class="input password"><label for="UserPassword">Password</label>
					<input class='postData' name="data[User][password]" id="UserPassword" type="password"></div><div class="submit">
					<input value="Login" type="submit" class="orange" onclick="SNAPPI.AIR.XhrHelper.signIn();return false;"></div>
					<select class='postData hide' name="data[User][magic]" id="UserMagic">
						<option value="" selected="selected">select test accounts</option>
						<option class='hr' value=""></option>
						<option value="4c5f9050-d8bc-4baa-ad2d-03b0f67883f5">2010</option>
						<option value="12345678-1111-0000-0000-editor------">editor</option>
						<option value="12345678-1111-0000-0000-newyork-----">newyork</option>
						<option value="12345678-1111-0000-0000-paris-------">paris</option>
						<option value="12345678-1111-0000-0000-sardinia----">sardinia</option>
						<option value="12345678-1111-0000-0000-sfbay-------">sfbay</option>
						<option value="12345678-1111-0000-0000-123456789abc">snappi</option>
						<option value="12345678-1111-0000-0000-venice------">venice</option>
					</select>
				</form>
		</div>
	</div>
</div>
<div id="menu-uploader-folder-markup" class="menu yui3-aui-overlaycontext-hidden hide">
	<ul class='select'></ul>
</div>
<div id="menu-uploader-batch-markup" class="menu yui3-aui-overlaycontext-hidden hide">
	<ul class='select'></ul>
</div>			
<div id="contextmenu-photoroll-markup" class="menu yui3-aui-overlaycontext-hidden hide">
	<ul>
		<li action='cancel_selected' class='before-show'>Cancel Upload</li>
		<li action='retry_selected' class='before-show'>Retry Upload</li>
		<hr>
		<li action='remove_from_uploader_selected'>Remove Snap</li>
	</ul>
</div>
<div id="menu-select-all-markup" class="menu yui3-aui-overlaycontext-hidden hide">
	<ul>
		<li action='select_all'>Select All</li>
		<li action='select_all_pages' class='before-show'>Select All Pages</li>
		<li action='clear_all'>Clear All</li>
		<hr>
		<li action='retry_selected' class='before-show'>Retry Selected</li>
		<li action='cancel_selected' class='before-show'>Cancel Selected</li>
		<hr>
		<li action='remove_from_uploader_selected' title='Remove imported photo(s) from the Snaphappi Desktop Uploader.' >Delete...</li>
	</ul>
</div>	
<div class='empty-photo-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
	<h1>Snap Upload Gallery</h1>
	<p>This is where you will see the photos which have been added to the Desktop Uploader.
	Before you can see your photos online, you will have to perform the following steps:</p>
	<ul>
		<ol>1. Add folder(s) to the Desktop Uploader (the folders will be scanned for JPG photos), </ol>
		<ol>2. Sign up for an account at Snaphappi, and</ol>
		<ol>3. Click "Start Upload" to begin uploading your photos to Snaphappi.</ol>
	</ul>
	<p>You can simply close the Desktop Uploader at any time and for any reason. 
		When you restart the uploader, it will automatically continue from the last upload.</p>
	<ul class='inline' ><li class='btn orange rounded-5'><a onclick='SNAPPI.AIR.Flex_UploadAPI.selectFolder(); return false;'>Get started now.</a></li></ul>
</div></div>
<div class='empty-filtered-photo-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
	<p>There are no Snaps in this View.</p>
	<p>Currently showing Snaps with status='<b class='status'></b>' in folder='<b class='folder'>'</b>. Adjust your filter settings to change this view.</p>
</div></div>
<div class="hide"><div class="alert-import-complete container_x">
	<h1>Scan Complete (Yay!)</h1>
	<p>We found <b>{count}</b> files in the following folder(s):</p>
	<div class='wrap-v'><input type='text' class='copy-paste' onclick='this.select();' value='{folder}' /></div>
	<p class='added hide'>but only <b>{added}</b> new JPGs were added - the remaining files were either updated or skipped.</p>
	<p>Before you can see these photos online, you need to complete the following steps:</p>
	<div class='wrap-v'><ol>
		<li>Sign-in to your Snaphappi account, then</li>
		<li>Click "Start Upload" to begin uploading photos.</li>
	</ol></div>
	<p>You may also review your imported photos individually to cancel uploading or remove entirely.</p>
</div></div>
<div class="hide"><div class='alert-upload-complete'>
	<h1>Upload Complete (double Yay!)</h1>
	<p>Congratulations, your upload is complete. Go online to Snaphappi to see, organize and share your uploaded photos.</p>
	<ul class='inline center' >
		<li class='btn orange rounded-5'><a onclick="return SNAPPI.AIR.UIHelper.nav.openPage('my-photos');">Go Online to See My Photos.</a></li>
	</ul>
</div></div> 
<div class="hide"><div class='alert-newer-version'>
	<h1>Update Your Desktop Uploader</h1>
	<p>A newer version of the Snaphappi Desktop Uploader is now available.</p>
	<ul class='inline center' >
		<li class='btn orange rounded-5'><a onclick="return SNAPPI.AIR.UIHelper.nav.openPage('pages-downloads');">Get Latest Version.</a></li>
	</ul>
</div></div>
