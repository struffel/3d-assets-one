<?php

namespace blocks;

class HeadBlock
{
	public static function render()
	{ ?>

		<head>
			<script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.8/dist/htmx.min.js" integrity="sha384-/TgkGk7p307TH7EXJDuUlgG3Ce1UVolAOFopFekQkkXihi5u/6OCvVKyz1W+idaz" crossorigin="anonymous"></script>
			<script src="https://unpkg.com/htmx-ext-remove-me@2.0.0/remove-me.js"></script>
			<title>3Dassets.one - The asset search engine</title>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="apple-touch-icon" sizes="180x180" href="/img/static/favicon/apple-touch-icon.png">
			<link rel="icon" type="image/png" sizes="32x32" href="/img/static/favicon/favicon-32x32.png">
			<link rel="icon" type="image/png" sizes="16x16" href="/img/static/favicon/favicon-16x16.png">
			<link rel="manifest" href="/img/static/favicon/site.webmanifest">
			<link rel="mask-icon" href="/img/static/favicon/safari-pinned-tab.svg" color="#5bbad5">
			<link rel="shortcut icon" href="/img/static/favicon/favicon.ico">
			<meta name="msapplication-TileColor" content="#2d89ef">
			<meta name="msapplication-config" content="/img/static/favicon/browserconfig.xml">
			<meta name="theme-color" content="#ffffff">
		</head>
<?php
	}
}
