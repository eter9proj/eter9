{{* Custom content from hook will replace everything. *}}
<div class="row">
{{if $content != '' }}
	<div class="col-sm-12">
		<div class="panel panel-default login-panel-content">
			<div class="panel-body">
				{{$content nofilter}}
			</div>
		</div>
	</div>
{{else}}
	<div class="col-md-5 col-sm-12 login-form">
	{{$login nofilter}}
	</div>
{{/if}}
</div>