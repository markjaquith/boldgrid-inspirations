<script id="theme-group-template" type="text/x-handlebars-template">
	<select id='theme_group'>
		<option disabled selected>Choose a group</option>
		{{#each this}}
			<option value='{{id}}'>{{title}}</option>
		{{/each}}
	</select>
</script>
