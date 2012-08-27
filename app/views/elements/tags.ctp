<div id='tag=form'>
	<form id="TagHomeForm" method="post" action="/tags/add" accept-charset="utf-8">
		<input name="_method" value="POST" type="hidden">
		<label for="TagStrTags"></label>
		<input name="data[Tag][strTags]" value="Enter tags" class="help" onclick='this.value=""; this.className="";' id="TagStrTags" type="text">
		<input class="orange" value="Go" type="submit">
		<input name="data[Tag][foreignKey]" value="<?php echo $uuid; ?>" id="TagForeignKey" type="hidden">
		<input name="data[Tag][class]" value="<?php echo $model; ?>" id="TagClass" type="hidden">
	</form>	
</div>
	