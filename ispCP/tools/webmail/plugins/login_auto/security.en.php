<?php


   /*
    *  Login:Auto Plugin
    *  By Jay Guerette <JayGuerette@pobox.com>
    *  (c) 2001 (GNU GPL - see ../../COPYING)
    *
    *  If you need help with this, or see improvements that can be made, please
    *  email the SquirrelMail Plugins mailing list or try contacting me at
    *  the address above (note as of May 2003, Jay does not seem to be maintaining
    *  this plugin).  I definately welcome suggestions and comments.  This plugin,
    *  as is the case with all SquirrelMail plugins, is not directly supported
    *  by the developers.
    *
    *  View the INSTALL document for information on installing this.  Also view
    *  the README document and plugins/README.plugins for more information.
    *
    */

   define('SM_PATH','../../');
   include_once(SM_PATH . 'config/config.php');
   include_once(SM_PATH . 'plugins/login_auto/config.php');

?>
<HTML>
   <HEAD>
<?php
   if ($theme_css != '') {
?>
<LINK REL="stylesheet" TYPE="text/css" HREF="<?php echo $theme_css ?>">
<?php
   }
?>
<TITLE><?php echo $org_title ?> - Security Notice</TITLE>
</HEAD>
<BODY>
<!-- @modified by ispCP Omega -->
<div id="container" style="background: none;">
<!-- @modified by ispCP Omega END -->
<TABLE BGCOLOR="FFFFFF" BORDER="0" COLS="1" WIDTH="90%" CELLSPACING="0" 
CELLPADDING="2" ALIGN="CENTER">
  <TR>
    <TD>
	<b>What does "Remeber my
<?php
echo ($auto_user && !$auto_pass)?'Name" ':'Name & Password" ';
?>
	mean?
	</b>
    </TD>
  </TR>
  <TR>
    <TD>
When you sign in with your name<?php echo ($auto_pass)?' and password':''?>,
your browser can &quot;remember&quot; this information. Check the box
and you won't have to enter this information each time you come back.
If you don't log in for
<?php
global $auto_expire_days, $auto_expire_hours, $auto_expire_minutes;
$x=$auto_expire_days*86400 + $auto_expire_hours*3600 + $auto_expire_minutes*60;
if ($x<60) echo (int)$x." seconds.";
elseif ($x<3600) echo (int)($x/60)." minutes.";
elseif ($x<86400) echo (int)($x/3600)." hours.";
else echo (int)($x/86400)." days, ";
?>
this information will be &quot;forgotten&quot;.
    </TD>
  </TR>
  <TR>
    <TD>
	<br><b>Should I be concerned about security?</b>
    </TD>
  </TR>
  <TR>
    <TD>
If you are concerned that other people might
<?php
echo ($auto_user && !$auto_pass)?'attempt to ':' ';
?>
access your email account, do not check the "Remember my
<?php
echo ($auto_user && !$auto_pass)?'Name" ':'Name & Password" ';
?>
box.
<?php
echo ($auto_user && !$auto_pass)?' You may want ':' Be sure '
?>
to click &quot;Sign Out&quot; when you leave your computer, which ensures
that you will be asked for your login information the next time anyone 
accesses this webmail from your computer.<br>
<br>
If you use a shared computer (in a library, Internet cafe, university,
airport or other common area) <b>DO NOT</b> check the "Remember my
<?php
echo ($auto_user && !$auto_pass)?'Name" ':'Name & Password" ';
?>
checkbox.<br>
    </TD>
  </TR>
<?php
echo ($auto_user && !$auto_pass)?"
  <TR>
    <TD>
	<br><b>
	Is saving just my name really a security risk?
	</b>
    </TD>
  </TR>
  <TR>
    <TD>
By providing one-half of the key needed to access your account,
you are making it slightly easier for someone to attempt to gain
access. In most situations this option is safe; however you should think
about who else may have access to your computer, and be aware of the slightly 
increased risk.
    </TD>
  </TR>
":'';
?>
  <TR>
    <TD>
	<br><b>What if I change my mind?</b>
    </TD>
  </TR>
  <TR>
    <TD>
<?php
echo ($auto_user && !$auto_pass)?
	'When you login, just uncheck the &quot;Remember my Name&quot; checkbox. ':'';
?>
You can always sign out at any time by clicking the &quot;Sign Out&quot; link
at the top of each page. This will erase the stored login information. Once you've
signed out, you can sign in again and choose whether or not your browser should
&quot;remember&quot; your login information.
    </TD>
  </TR>
  <TR>
    <TD>
	<br><b>How does this work?</b>
    </TD>
  </TR>
  <TR>
    <TD>
We do this with something called persistent cookies. Cookies are pieces of
information generated by a Web server and stored in the user's computer by the
browser. By enabling "Remember my Name<?php echo ($auto_pass)?' & Password':''?>", 
you are instructing the server to store your information in an encrypted fashion on
your computer.  Each time you login to this mail server, your name
<?php
echo ($auto_user && !$auto_pass)?'is ':'and password are ';
?>
requested from your browser by the
server. Clicking the &quot;Sign Out&quot; link will erase these cookies.
    </TD>
  </TR>
</TABLE>
<!-- @modified by ispCP Omega -->
 <p><a href="../../src/login.php>back</a></p>
</div>
<!-- @modified by ispCP Omega END -->
</BODY>
</HTML>