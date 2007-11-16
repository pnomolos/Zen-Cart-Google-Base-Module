<?php
/**
 * @package admin
 * @version $Id: googlebase.php 0 Sep 5, 2007 3:38:26 PM pablif@gmail.com $
 */
 
  require('includes/application_top.php');
  
  if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
  }
  
  require_once(DIR_WS_MODULES.'googlebase/library/gb-http.php');
  require_once(DIR_WS_MODULES.'googlebase/googlebase.php');
  
  $action = (isset($_GET['action']) ? $_GET['action'] : '');
  $token = (isset($_GET['token']) ? $_GET['token'] : '');
  $gb = new googlebase();
  $is_authenticated = $gb->getOption('token') != null; //TODO: && is_valid_token

  if(!$is_authenticated && zen_not_null($action) && $action == 'auth'||
     zen_not_null($token)) {
    require_once(DIR_WS_MODULES.'googlebase/library/gb-authentication.php');
    if(zen_not_null($token)) {
      $gbhttp = googlebase::getGbaseHttpRequest();
      $response = gb_get_session_token($token, $gbhttp);
      if(!$response->hasErrors()) {
        $gb->setOption('token', $response->getParsedToken());
        $is_authenticated = true;
      } else {
        global $messageStack;
        $msg = "Authentication failed with error: <code>".$response->getConnectionError().'<br>'.strip_tags($response->getResponseBody).'</code>';
        $messageStack->add_session($msg, 'error');
      }
      $gbhttp->close();
    } else {
      zen_redirect(gb_get_authentication_url());
    }
  }
  
  if(zen_not_null($action)) {
    if($action == 'options') {
      $gb->editOptions();
    } else if($action == 'revoke' && $is_authenticated) {
      require_once(DIR_WS_MODULES.'googlebase/library/gb-authentication.php');
      $gbhttp = googlebase::getGbaseHttpRequest();
      gb_revoke_session_token($gb->getOption('token'), $gbhttp);
      $gbhttp->close();
      $gb->setOption('token', null);
      $is_authenticated = false;
    }
  }
  
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
  <title><?php echo TITLE; ?></title>
  <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
  <link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
  <script language="javascript" src="includes/menu.js"></script>
  <script language="javascript" src="includes/general.js"></script>
  <script type="text/javascript">
    <!--
    function init() {
      cssjsmenu('navbar');
      if (document.getElementById) {
        var kill = document.getElementById('hoverJS');
        kill.disabled = true;
      }
    }
    // -->
  </script>
</head>
<body onload="init()">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<div style="margin: 5px">
<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="2" class="pageHeading" style="padding: 12px 0;"><?php echo GB_HEADING_TITLE; ?></td> <!-- body_text //-->
  </tr>
  <tr>
    <td valign="top">
      <table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr class="dataTableHeadingRow">
          <td class="dataTableHeadingContent" width="10%" align="center"><?php echo GB_TABLE_HEADING_ACTION; ?></td>
          <td class="dataTableHeadingContent"><?php echo GB_TABLE_HEADING_DESCRIPTION; ?></td>
        </tr>
        <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo zen_href_link('googlebase.php'); ?>'">
          <td>&nbsp;</td>
          <td class="dataTableContent" align="right">
            <?php echo zen_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', '');?>
          </td>
        </tr>
        <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
          <?php if(!$is_authenticated): ?>
            <td align="center">
              <form method="get" action=""><input type="hidden" name="action" value="auth"><input type="submit" value="<?php echo GB_AUTH_BUTTON;?>"></form>
            </td>
            <td>
              <?php echo GB_AUTH_DESCRIPTION; ?>
            </td>
          <?php else: ?>
            <td align="center">
              <form method="get" action=""><input type="hidden" name="action" value="revoke"><input type="submit" value="<?php echo GB_REVOKE_BUTTON;?>"></form>
            </td>
            <td>
              <?php echo GB_REVOKE_DESCRIPTION; ?>
            </td>
          <?php endif; ?>
        </tr>
        <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
          <td align="center">
            <form method="get" action=""><input type="hidden" name="action" value="upload"><input type="submit" value="<?php echo GB_UPLOAD_BUTTON;?>" <?php echo $is_authenticated? '' : 'disabled="disabled"'; ?>></form>
          </td>
          <td>
            <?php echo GB_UPLOAD_DESCRIPTION; ?>
          </td>
        </tr>
        <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
          <td></td>
          <td></td>
        </tr>
        
      </table>
    </td>
    <td valign="top" width="25%">
      <table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr class="infoBoxHeading">
          <td><b><?php echo GB_OPTIONS_TITLE;?></b></td>
        </tr>
        <form method="post" action="?action=options">
          <tr class="infoBoxContent">
            <td>
              <input type="checkbox" name="enabled" <?php if($gb->getOption('enabled')) echo 'checked="checked"';?>>
              <? echo GB_OPTIONS_ENABLED;?>.
            </td>
          </tr>
          <tr class="infoBoxContent">
            <td>
              <input type="text" size="3" name="maxuploads" value="<?php echo $gb->getOption('maxuploads');?>">
              <?php echo GB_OPTIONS_MAX_UPLOADS; ?>
            </td>
          </tr>
          <tr class="infoBoxContent">
            <td>
              <?php echo GB_OPTIONS_AUTHOR_NAME;?> <br>
              <input type="text" name="authorname" value="<?php echo htmlspecialchars($gb->getOption('authorname'));?>">
            </td>
          </tr>
          <tr class="infoBoxContent">
            <td>
              <?php echo GB_OPTIONS_AUTHOR_EMAIL;?> <br>
              <input type="text" name="authoremail" value="<?php echo htmlspecialchars($gb->getOption('authoremail'));?>">
            </td>
          </tr>
          <tr class="infoBoxContent">
            <td>
              <input type="checkbox" name="draft" <?php if($gb->getOption('draft')) echo 'checked="checked"';?>>
              <? echo GB_OPTIONS_DRAFT;?>.
            </td>
          </tr>
          <tr class="infoBoxContent">
            <td>
              <input type="checkbox" name="upc" <?php if($gb->getOption('upc')) echo 'checked="checked"';?>>
              <? echo GB_OPTIONS_UPC;?>.
            </td>
          </tr>
          <!--
          <tr class="infoBoxContent">
            <td>
              custom google attributes
              <textarea name="attrspecs"><?php echo $gb->getOption('attrspecs');?></textarea>
            </td>
          </tr>
          -->
          <tr class="infoBoxContent">
            <td>
              <input type="submit" name="submit" value="<?php echo GB_OPTIONS_SUBMIT;?>">
            </td>
          </tr>
          </form>
        </tr>
      </table>
    </td>
  </tr>
</table>
</div>
<div>
  <?php
    if(zen_not_null($action) && $action == 'upload') {
      $gb->uploadProducts();
    }
  ?>
</div>

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
