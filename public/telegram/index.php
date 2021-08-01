<!DOCTYPE html>
<html>
<head>
	<title>GNU/Weeb Telegram Chat Archive</title>
	<link rel="stylesheet" type="text/css" href="/assets/css/telegram.css?w=<?= time() ?>"/>
</head>
<body>
<div class="main-cg">
	<h1>GNU/Weeb Telegram Chat Archive</h1>
	<div id="msg-stub" style="display:none;">
		<div id="msg-cg-{{msg_id}}" class="msg-cg">
			<div class="pp-cg"></div>
			<div class="content-cg">
				<div class="cg-inline name-cg"><b>{{user_id}}</b></div>
				<div class="cg-inline tg-date-cg"><b>{{tg_date}}</b></div>
				<div>{{content}}</div>
			</div>
		</div>
	</div>
	<div id="chat-cg">
	</div>
</div>
<script type="text/javascript" src="/assets/js/telegram.js?w=<?= time() ?>"></script>
</body>
</html>
