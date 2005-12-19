<?php

require_once "../src/phpchat.class.php";

$params = array();
$params["nick"] = "testcase user";
//$params["connect"] = false;
$chat = new phpChat( $params );
$c =& phpChatConfig::Instance();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>phpChat demo</title>

<?php $chat->printJavascript(); ?>
<?php $chat->printStyle(); ?>

	</head>
	<body>
<p><code>Cmd_send</code> testcase : <strong>'this is an text'</strong> message should be sent to channel</p>
<?php $chat->printChat(); ?>

  <script type="text/javascript">
  <?php echo $c->prefix."handleRequest('/send ".addslashes("'this is an text'")."');"; ?>
  </script>
        
	</body>
</html>