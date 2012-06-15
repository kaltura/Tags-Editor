<html>
	<head>
		<?php
			$partnerId = $_REQUEST['partnerid'];
			$entryId = $_REQUEST['entryid'];
			$uiConfId = '4289612';//$_REQUEST['uiconfid'];
		?>
		<script type="text/javascript" src="http://www.kaltura.com/p/<?php echo $partnerId; ?>/sp/<?php echo $partnerId; ?>00/embedIframeJs/uiconf_id/<?php echo $uiConfId; ?>/partner_id/<?php echo $partnerId; ?>"></script>
	</head>
	<body style="background-color:black;">
		<object style="margin:10px;background-color:black;" id="kaltura_player" name="kaltura_player" 
			type="application/x-shockwave-flash" allowFullScreen="true" allowNetworking="all" allowScriptAccess="always" 
			height="333" width="400" bgcolor="#000000" xmlns:dc="http://purl.org/dc/terms/" xmlns:media="http://search.yahoo.com/searchmonkey/media/"
			rel="media:video" resource="http://www.kaltura.com/index.php/kwidget/wid/_<?php echo $partnerId; ?>/uiconf_id/<?php echo $uiConfId; ?>/entry_id/<?php echo $entryId; ?>" 
			data="http://www.kaltura.com/index.php/kwidget/wid/_<?php echo $partnerId; ?>/uiconf_id/<?php echo $uiConfId; ?>/entry_id/<?php echo $entryId; ?>">
				<param name="allowFullScreen" value="true" />
				<param name="allowNetworking" value="all" />
				<param name="allowScriptAccess" value="always" />
				<param name="bgcolor" value="#000000" />
				<param name="movie" value="http://www.kaltura.com/index.php/kwidget/wid/_<?php echo $partnerId; ?>/uiconf_id/<?php echo $uiConfId; ?>/entry_id/<?php echo $entryId; ?>" />
		</object>
	</body>
</html>