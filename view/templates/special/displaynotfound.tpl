<div id="exception" class="generic-page-wrapper">
	<h1>{{$l10n.title}}</h1>
	<p>{{$l10n.desc1}}</p>
	<p>{{$l10n.desc2}}</p>
	<ul>
{{foreach $l10n.reasons as $reason}}
		<li>{{$reason}}</li>
{{/foreach}}
	</ul>
</div>
