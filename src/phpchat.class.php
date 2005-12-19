<?php

require_once dirname(__FILE__)."/phpchatconfig.class.php";
if (!class_exists("xajax")) require_once dirname(__FILE__)."/../lib/xajax_0_1_beta4/xajax.inc.php";

class phpChat
{
  var $chatconfig;
  var $xajax;
  
  function phpChat( $params = array() )
  {
    // start the session : session is used for locking purpose and cache purpose
    session_start();
    if (isset($_GET["init"])) session_destroy();

    $params["sessionid"] = session_id();

    $c =& phpChatConfig::Instance( $params );

    /*
    $fp = fopen("log", "w");
    ob_start();
    // Smarty way: Capture the Smarty output
    print_r($c);
    print_r($_SESSION);
    $data = ob_get_contents();
    ob_end_clean();
    fwrite($fp, $data);
    fclose($fp);
    */
    
    // Xajax doesn't support yet static class methode call
    // I use basic functions to wrap to my statics methodes
    function handleRequest($request, $shownotice = true)
    {
      $c =& phpChatConfig::Instance();
      $c->shownotice = $shownotice;
      return phpChat::HandleRequest($request);
    }
    // then init xajax engine
    $this->xajax = new xajax($c->server_file, $c->prefix);
    if ($c->debug)
      $this->xajax->debugOn();
    $this->xajax->registerFunction("handleRequest");
    $this->xajax->processRequests();
  }

  /**
   * usage: <?php $chat->printJavascript(); ?>
   */
  function printJavaScript()
  {
    $c =& phpChatConfig::Instance();
    
    $this->xajax->printJavascript();    
    
    if (!class_exists("Smarty")) require_once dirname(__FILE__)."/../lib/Smarty-2.6.7/libs/Smarty.class.php";
    $smarty = new Smarty();
    $smarty->left_delimiter  = "~[";
    $smarty->right_delimiter = "]~";
    $smarty->template_dir    = dirname(__FILE__).'/templates/';
    $smarty->compile_dir     = $c->cache_dir;    
    $smarty->compile_check   = true;
    $smarty->debugging       = false;
    $c->assignToSmarty($smarty);
    
    echo "<script type=\"text/javascript\">\n<!--\n";
    $smarty->display("javascript1.js.tpl");
    //echo $this->xajax->compressJavascript($js);
    echo "\n-->\n</script>\n";
  }

  /**
   * usage: <?php $chat->printChat(); ?>
   */
  function printChat()
  {
    $c =& phpChatConfig::Instance();
    
    if (!class_exists("Smarty")) require_once dirname(__FILE__)."/../lib/Smarty-2.6.7/libs/Smarty.class.php";
    $smarty = new Smarty();
    $smarty->left_delimiter  = "~[";
    $smarty->right_delimiter = "]~";
    $smarty->template_dir    = dirname(__FILE__).'/templates/';
    $smarty->compile_dir     = $c->cache_dir;    
    $smarty->compile_check   = true;
    $smarty->debugging       = false;
    $c->assignToSmarty($smarty);
    
    $smarty->display("chat.html.tpl");
  }
  
  /**
   * usage: <?php $chat->printStyle(); ?>
   */
  function printStyle()
  {
    $c =& phpChatConfig::Instance();
    
    if (!class_exists("Smarty")) require_once dirname(__FILE__)."/../lib/Smarty-2.6.7/libs/Smarty.class.php";
    $smarty = new Smarty();
    $smarty->left_delimiter  = "~[";
    $smarty->right_delimiter = "]~";
    $smarty->template_dir    = dirname(__FILE__).'/templates/';
    $smarty->compile_dir     = $c->cache_dir;
    $smarty->compile_check   = true;
    $smarty->debugging       = false;
    $c->assignToSmarty($smarty);
    
    echo "<style type=\"text/css\">\n<!--\n";
    $smarty->display("style.css.tpl");
    if ($c->css_file)
      $smarty->display($c->css_file);
    echo "\n-->\n</style>\n";
  }
  
  /**
   * return the chatconfig object
   */
  /*
  function &GetConfig( $chat_id = 0, $prefix = "" )
  {
    static $chatconfig;
    if (!isset($chatconfig))
      {
	if ($chat_id == 0 && $prefix == "")
	  return NULL;
	if (isset($_SESSION[$prefix."chatconfig_".$chat_id]))
	  $chatconfig = unserialize($_SESSION[$prefix."chatconfig_".$chat_id]);
	else
	  return NULL;
      }
    return $chatconfig;    
  }
  */

  /**
   * change the chatconfig object only if it is not allready saved
   */
  /*
  function &SetConfig( &$p, $force = false, $init = true )
  {
    if (is_a($p, 'phpChatConfig'))
    {
      // save config in session
      $_SESSION[$p->prefix."chatconfig_".$p->id] = serialize($p);
      return $p;
    }

    $chat_id = phpChatConfig::GetIdFromParams($p);
    $prefix  = phpChatConfig::GetPrefix();

    if ( !$force && isset($_SESSION[$prefix."chatconfig_".$chat_id]) )
      return phpChat::GetConfig( $chat_id, $prefix );
    
    $c = new phpChatConfig( $p );

    // initialize the chatobject if necessary    
    if ($init)
    {
      if (!$c->isInit() || isset($_GET["init"]))
        $c->init();
      if (!$c->isInit())
      {
        $errors = $c->getErrors();
        echo "<ul>"; foreach( $errors as $e ) echo "<li>".$e."</li>"; echo "</ul>";
        exit;
      }
    }

    // save the validated config in session
    $_SESSION[$c->prefix."chatconfig_".$c->id] = serialize($c);

    return phpChat::GetConfig( $chat_id, $prefix );
  }
  */
  
  function FilterNickname($nickname)
  {
    $c =& phpChatConfig::Instance();
    $nickname = substr($nickname, 0, $c->max_nick_len);
    $nickname = htmlspecialchars(stripslashes($nickname));
    return $nickname;
  }
  
  function FilterMsg($msg)
  {
    $c =& phpChatConfig::Instance();
    $msg  = substr($msg, 0, $c->max_text_len);
    $msg  = htmlspecialchars(stripslashes($msg));
    if ($msg[0] == "\n") $msg = substr($msg, 1); // delete the first \n generated by FF
    if (strpos($msg,"\n") > 0) $msg  = "<br/>".$msg;
    $msg  = str_replace("\r\n", "<br/>", $msg);
    $msg  = str_replace("\n", "<br/>", $msg);
    $msg  = str_replace("\t", "    ", $msg);
    $msg  = str_replace("  ", "&nbsp;&nbsp;", $msg);
    $msg  = str_replace(":)", "<img src=\"http://cybergifs.com/faces/smile3.gif\" alt=\"\" />", $msg);
    $msg  = str_replace(":D", "<img src=\"http://cybergifs.com/faces/smileysparkle.gif\" alt=\"\" />", $msg);
    $msg  = str_replace(":etoile:", "<img src=\"http://www.mentaljokes.com/images/star_guy.gif\" alt=\"\" />", $msg);
    $msg  = str_replace(":joint:", "<img src=\"http://smileyonline.free.fr/images/gif/mod%E9ration/vignette/thumbnails/blunt_gif.gif\" alt=\"\" />", $msg);
    $msg  = str_replace(":sante:", "<img src=\"http://smileyonline.free.fr/images/gif/mod%E9ration/vignette/thumbnails/trinque_gif.gif\" alt=\"\" />", $msg);
    $msg  = str_replace(":p", "<img src=\"http://www.tweenpix.net/blog/themes/tweenpix/smilies/tong.gif\" alt=\"\" />", $msg);
    
    
    $msg  = str_replace(phpChat::FilterNickname($c->nick), "<strong>".phpChat::FilterNickname($c->nick)."</strong>", $msg);

    return $msg;
  }
    
  function HandleRequest($request)
  {
    $xml_reponse = new xajaxResponse();
    
    //    $fp = fopen("log", "w");
    //    ob_start();
    
    if (preg_match("/\/([a-z]*)( (.*)|)/i", $request, $res))
    {
      $cmd   = "Cmd_".$res[1];
      $param = $res[3];
      // call the command
      phpChat::$cmd($xml_reponse, $param);
    }
    //    print_r($xml_reponse->getXML());
    //    $data = ob_get_contents();
    //    ob_end_clean();
    //    fwrite($fp, $data!="" ? $data : "");
    //    flush($fp);
    //    fclose($fp);
    
    return $xml_reponse->getXML();
  }
  
  function Cmd_update(&$xml_reponse)
  {
    $c =& phpChatConfig::Instance();
    phpChat::Cmd_updateMyNick($xml_reponse);
    phpChat::Cmd_getOnlineNick($xml_reponse);
    phpChat::Cmd_getNewMsg($xml_reponse);
    $xml_reponse->addScript($c->prefix."timeout_var = window.setTimeout('".$c->prefix."handleRequest(\\'/update\\')', ".$c->refresh_delay.");");
  }
  
  function Cmd_connect(&$xml_reponse)
  {
    $c =& phpChatConfig::Instance();
    $_SESSION[$c->prefix."from_id_".$c->id] = 0;
    $xml_reponse->addScript("var ".$c->prefix."timeout_var;");
    phpChat::Cmd_update($xml_reponse);
    if ($c->nick != "")
    {
      phpChat::Cmd_nick(&$xml_reponse, $c->nick);
      return;
      
      
      $xml_reponse->addAssign($c->prefix."handle", "value", $c->nick);
      //      $xml_reponse->addScript("document.getElementById('".$c->prefix."words').focus();");
      phpChat::Cmd_notice($xml_reponse, $c->nick." is connected");
    }
    else
    {
      $errors[$c->prefix."handle"] = "Please enter your nickname.";
      phpChat::Cmd_error($xml_reponse, $errors);      
      $xml_reponse->addScript("document.getElementById('".$c->prefix."handle').focus();");
    }
  }

  function Cmd_nick(&$xml_reponse, $newnick)
  {
    $c =& phpChatConfig::Instance();
    $oldnick = $c->nick;
    
    if ($newnick == "")
    {
      phpChat::Cmd_asknick($xml_reponse, "");
      //      $errors[$c->prefix."handle"] = "Please enter your nickname.";
      //      phpChat::Cmd_error($xml_reponse, $errors);
      return;
    }
    
    $container =& $c->getContainerInstance();
    $oldnickid = $container->getNickId($oldnick);
    
    if ($oldnickid == 0 || $oldnick == "")
    {
      // this is the first time the nick is assigned
      $container->changeNick($newnick, $c->sessionid);
      $c->nick = $newnick;
      $c->saveInSession();
      phpChat::Cmd_notice($xml_reponse, htmlspecialchars(stripslashes($newnick))." is connected");
      $xml_reponse->addAssign($c->prefix."handle", "value", $newnick);
    }
    else if ( $oldnickid == $c->sessionid )
    {
      if ($c->nick != $newnick)
      {
        // this is a real nick change
        $container->changeNick($newnick, $c->sessionid);
        $oldnick = $c->nick;
        $c->nick = $newnick;
        $c->saveInSession();
        phpChat::Cmd_notice($xml_reponse, htmlspecialchars(stripslashes($oldnick))." changes his nickname to ".htmlspecialchars(stripslashes($newnick)));
        $xml_reponse->addAssign($c->prefix."handle", "value", $newnick);
      }
      else
        $xml_reponse->addAssign($c->prefix."handle", "value", $newnick);
    }
    else
    {
      // the wanted nick is allready used
      // please change it
      phpChat::Cmd_asknick($xml_reponse, $newnick);
    }
     
    /*
    $newnick2 = $container->changeNick($newnick);
    if ($newnick2 != $newnick)
    {
      phpChat::Cmd_asknick($xml_reponse, $newnick);
      return;
    }
    $oldnick = $c->nick;
    $c->nick = $newnick;
    phpChat::SetConfig($c, true);
    phpChat::Cmd_getOnlineNick($xml_reponse);
    if ($oldnick != "")
      phpChat::Cmd_notice($xml_reponse, htmlspecialchars(stripslashes($oldnick))." changes his nickname to ".htmlspecialchars(stripslashes($newnick)));
    else
      phpChat::Cmd_notice($xml_reponse, htmlspecialchars(stripslashes($newnick))." is connected");
    $xml_reponse->addAssign($c->prefix."handle", "value", $newnick);
    //    $xml_reponse->addScript("document.getElementById('".$c->prefix."words').focus();");
    */
  }

  function Cmd_notice(&$xml_reponse, $msg)
  {
    $c =& phpChatConfig::Instance();
    if ($c->shownotice)
    {
      $container =& $c->getContainerInstance();
      $container->writeMsg("*", $msg);
      phpChat::Cmd_getNewMsg($xml_reponse);
    }
  }

  function Cmd_me(&$xml_reponse, $msg)
  {
    $c =& phpChatConfig::Instance();
    
  }
  
  function Cmd_quit(&$xml_reponse)
  {
    $c =& phpChatConfig::Instance();
    $container =& $c->getContainerInstance();
    if ($container->removeNick($c->nick))
      phpChat::Cmd_notice($xml_reponse, $c->nick." quit");
    else
      phpChat::Cmd_notice($xml_reponse, "error: ".$c->nick." can't quit");
  }
  
  function Cmd_getOnlineNick(&$xml_reponse)
  {
    $c =& phpChatConfig::Instance();
    $container =& $c->getContainerInstance();
    $disconnected_users = $container->removeObsoletNick();
    foreach ($disconnected_users as $u)
      phpChat::Cmd_notice($xml_reponse, $u." disconnected (timeout)");
    $users = $container->getOnlineNick();
    sort($users);
    $html = '<ul>'; foreach ($users as $u) $html .= '<li>'.htmlspecialchars(stripslashes($u)).'</li>'; $html .= '</ul>';
    $xml_reponse->addAssign($c->prefix."online", "innerHTML", $html);
  }

  function Cmd_updateMyNick(&$xml_reponse)
  {
    $c =& phpChatConfig::Instance();
    $container =& $c->getContainerInstance();
    $ok = $container->updateNick($c->nick);
    if (!$ok)
      phpChat::Cmd_error(&$xml_reponse, "Cmd_updateMyNick failed");
  }
  
  function Cmd_getNewMsg(&$xml_reponse)
  {
    // get params from config obj
    $c =& phpChatConfig::Instance();
    
    // check this methode is not being called
    if( isset($_SESSION[$c->prefix."lock_readnewmsg_".$c->id]) )
    {
      // kill the lock if it has been created more than 10 seconds ago
      $last_10sec = time()-10;
      $last_lock = $_SESSION[$c->prefix."lock_readnewmsg_".$c->id];
      if ($last_lock < $last_10sec) $_SESSION[$c->prefix."lock_".$c->id] = 0;
      if ( $_SESSION[$c->prefix."lock_readnewmsg_".$c->id] != 0 ) exit;
    }

    // create a new lock
    $_SESSION[$c->prefix."lock_readnewmsg_".$c->id] = time();
    
    $from_id = $_SESSION[$c->prefix."from_id_".$c->id];
    
    $container =& $c->getContainerInstance();
    $new_msg = $container->readNewMsg($from_id);
    $new_from_id = $new_msg["new_from_id"];
    $messages    = $new_msg["messages"];

    // transform new message in html format
    $html = '';
    foreach ($messages as $msg)
    {
      $html .= '<div id="'.$c->prefix.'msg'.$msg[0].'" class="'.$c->prefix.'message'.($from_id == 0 ? " ".$c->prefix."oldmsg" : "").'">';
      $html .= '<span class="'.$c->prefix.'date'.((isset($msg[1]) && date("d/m/Y") == $msg[1]) ? " ".$c->prefix."invisible" : "" ).'">'.(isset($msg[1]) ? $msg[1] : "").'</span> ';
      $html .= '<span class="'.$c->prefix.'heure">'.(isset($msg[2]) ? $msg[2] : "").'</span> ';
      $html .= '<span class="'.$c->prefix.'pseudo">'.(isset($msg[3]) ? $msg[3] : "").'</span> ';
      $html .= '<span class="'.$c->prefix.'words">'.(isset($msg[4]) ? $msg[4] : "").'</span><br/>';
      $html .= '</div>';
    }
  	
    if ($html != "") // do not send anything if there is no new messages to show
    {
      // store the new msg id
      $_SESSION[$c->prefix."from_id_".$c->id] = $new_from_id;
      // append new messages to chat zone
      $xml_reponse->addAppend($c->prefix."chat", "innerHTML", $html);
      // move the scrollbar from N line down
      $xml_reponse->addScript('var div_msg; var msg_height = 0;');
      foreach ($messages as $msg)
        $xml_reponse->addScript('div_msg = document.getElementById(\''.$c->prefix.'msg'.$msg[0].'\'); msg_height += div_msg.offsetHeight+2;');
      $xml_reponse->addScript('document.getElementById(\''.$c->prefix.'chat\').scrollTop += msg_height;');
    }

    // remove the lock
    $_SESSION[$c->prefix."lock_readnewmsg_".$c->id] = 0;
  }
  
  function Cmd_send(&$xml_reponse, $msg)
  {
    $c =& phpChatConfig::Instance();
        
    // check the nick is not allready known
    $nick = phpChat::FilterNickname($c->nick);
    $text = phpChat::FilterMsg($msg);
        
    $errors = array();
    if ($text == "") $errors[$c->prefix."words"] = "Text cannot be empty.";
    if ($nick == "") $errors[$c->prefix."handle"] = "Please enter your nickname.";
    if (count($errors) == 0)
    {
      $container =& $c->getContainerInstance();
      $container->writeMsg($nick, $text);
    	
      // a message has been posted so :
      // - read new messages
      // - clear "words" field to be ready to recieve the next message
      // - give focus to "words" field
      //$xml_reponse->addClear($c->prefix."words","value");  	
      $xml_reponse->addScript($c->prefix."ClearError(Array('".$c->prefix."words"."','".$c->prefix."handle"."'));");
      $xml_reponse->addScript("document.getElementById('".$c->prefix."words').focus();");
      phpChat::Cmd_getNewMsg($xml_reponse);
      //  		$xml_reponse->addScript('window.clearTimeout('.$c->prefix.'timeout_var); '.$c->prefix.'refreshChat();');
    }
    else
    {
      // an error occured, just ignore the message and display errors
      phpChat::Cmd_error($xml_reponse, $errors);
      if (isset($errors[$c->prefix."handle"])) // the nick is empty so give it focus
        $xml_reponse->addScript("document.getElementById('".$c->prefix."handle').focus();");
    }
  }
  
  function Cmd_join(&$xml_reponse, $newchat)
  {
    $c =& phpChatConfig::Instance();
  }
  
  function Cmd_error(&$xml_reponse, $errors)
  {
    $c =& phpChatConfig::Instance();
    if (is_array($errors))
    {
      $error_ids = ""; $error_str = "";
      foreach ($errors as $k => $e) { $error_ids .= ",'".$k."'"; $error_str.= $e." "; }
      $error_ids = substr($error_ids,1);
      $xml_reponse->addScript($c->prefix."SetError('".addslashes(stripslashes($error_str))."', Array(".$error_ids."));");
    }
    else
      $xml_reponse->addScript($c->prefix."SetError('".addslashes(stripslashes($errors))."', Array());");
  }

  function Cmd_asknick(&$xml_reponse, $nicktochange)
  {
    $c =& phpChatConfig::Instance();
    if ($nicktochange == "")
      $msg = "Please enter your nickname";
    else
      $msg = "'".$nicktochange."' is used, please choose another nickname.";
    $xml_reponse->addScript("var newpseudo = prompt('".addslashes($msg)."', '".addslashes($nicktochange)."'); ".$c->prefix."handleRequest('/nick ' + newpseudo);");
  }
  
}

?>
