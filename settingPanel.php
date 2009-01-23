<?php
function rmSettingCSS() {
    echo '<style type="text/css">
/*<![CDATA[*/
#rmWrap{width:650px;}
#rmWrap fieldset{margin:12px 0 0 0;padding:0;}
#rmWrap fieldset{-moz-border-radius:5px;-webkit-border-radius:5px;}
#rmWrap legend{margin-left:500px;color:#666;font-weight:bold;}
#rmWrap fieldset ol{list-style:none;margin:0;padding:0;}
#rmWrap fieldset li{margin:12px;}
#rmWrap label{display:block;float:left;width:150px;margin-right:12px;}
#rmWrap .textinput{width:320px;}
#rmWrap fieldset.submit{border-style:none;}
/*]]>*/
</style>';
}

function rmSettingJquery() {
    echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js" type="text/javascript"></script>';
    echo '<link rel="stylesheet" href="'.get_bloginfo('url').'/wp-content/plugins/replymail/tabs.flora.css" type="text/css" media="screen" title="Flora (Default)">';
    echo '<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.5.3/jquery-ui.min.js" type="text/javascript"></script>';
    echo '<script type="text/javascript">
$(document).ready(function(){
$("#rmWrap > ul").tabs();
});
</script>';
}
function rmSettingPage() {
    // If submit, collecting options data
    // serialize them and update database
    if($_POST['rmSubmitHidden'] === 'yes') {
        $options = rmCheckData();
        if ($options[0]){
            update_option('rmOptions', $options);
            unset($options);
?>
<div class="updated"><p><strong><?php _e('Options saved!', 'replymail');?></strong></p></div>
<?php
        }else{
?>
<div class="updated"><p><strong style="color:red;"><?php echo $options[1];?></strong></p></div>
<?php
        }
    }elseif($_POST['rmSubmitUninstall']==='yes'){
        delete_option('rmOptions');
?>
<div class="updated"><p><strong><?php _e('Options uninstalled! just go to <a href="plugins.php">plugins page</a> Deactivate this plugin', 'replymail');?></strong></p></div>
<?php
    }
    $options = get_option('rmOptions');
?>
<h2><?php _e('replyMail Setting');?></h2>
<div id="rmWrap">
    <ul>
        <li><a href="#setting"><span>Setting</span></a></li>
        <li><a href="#donate"><span>Donate</span></a></li>
        <li><a href="#uninstall"><span>Uninstall</span></a></li>
    </ul>
    <div id="setting">
        <form name="form1" method="post" action="<?php echo htmlentities(str_replace( '%7E', '~', $_SERVER['REQUEST_URI']),ENT_QUOTES,'UTF-8'); ?>">
            <fieldset>
                <legend><?php _e('Email From Information: ', 'replymail');?></legend>
                <ol>
                  <li>
                    <label for="fromEmail"><?php _e('Email: ', 'replymail');?></label>
                    <input id="fromEmail" name="fromEmail" type="text" tabindex="1" class="textinput" value="<?php echo $options[0];?>" />
                  </li>
                  <li>
                    <label for="fromName"><?php _e('From Name: ', 'replymail');?></label>
                    <input name="fromName" id="fromName" type="text" tabindex="2" class="textinput" value="<?php echo $options[1];?>" />
                  </li>
                </ol>
            </fieldset>
            <fieldset>
                <legend><?php _e('Email Template Setting: ', 'replymail');?></legend>
                <ol>
                  <li>
                    <label for="emailSubject"><?php _e('Subject: ', 'replymail');?></label>
                    <input name="emailSubject" id="emailSubject" type="text" tabindex="3" class="textinput" value="<?php echo $options[2];?>" />
                  </li>
                  <li>
                    <label for="emailContent"><?php _e('Email Content Template: ', 'replymail');?></label>
                    <textarea name="emailContent" id="emailContent" cols="60" rows="16" tabindex="4"><?php echo stripslashes(format_to_edit($options[3]));?></textarea>
                  </li>
                </ol>
            </fieldset>
            <fieldset class="submit">
                <input type="hidden" name="rmSubmitHidden" value="yes" />
                <input name="sumbit" id="sumbit" type="submit" value="<?php _e('Save Options', 'replymail');?>" tabindex="5" />
            </fieldset>
        </form>
        <ol>
            <strong><?php _e('Template Tags Help: ', 'replymail')?></strong>
            <li><em>{#blogName}</em> - <?php _e('The name of this blog. ONLY for Subject.', 'replymail')?></li>
            <li><em>{#postTitle}</em> - <?php _e('The title of the comment post. ONLY for Subject.', 'replymail')?></li>
            <li><em>{#oriCommentAuthor}</em> - <?php _e('The parent commenter\'s name. Can use both for Subject and Email Content.', 'replymail')?></li>
            <li><em>{#replyCommentAuthor}</em> - <?php _e('The reply commenter\'s name. Can use both for Subject and Email Content.', 'replymail')?></li>
            <li><em>{#blog}</em> - <?php _e('Clickable link for the blog. ONLY for Email Content.', 'replymail')?></li>
            <li><em>{#post}</em> - <?php _e('Clickable link for the comment post. ONLY for Email Content.', 'replymail')?></li>
            <li><em>{#replyContent}</em> - <?php _e('Reply comment content. ONLY for Email Content.', 'replymail')?></li>
            <li><em>{#oriContent}</em> - <?php _e('Parent comment content. ONLY for Email Content.', 'replymail')?></li>
            <li><?php _e('Also, your can use these HTML tags: ', 'replymail')?><?php echo allowed_tags();?></li>
        </ol>
    </div>
    <div id="donate">
        <strong id="donate"><?php _e('Donate', 'replymail');?></strong>
        <p><?php _e('Has This Plugin Helped You?', 'replymail')?><br />
        <?php _e('If so, we welcome your support. Click the Donate button and contribute. Thank you!', 'replymail')?><br />
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
            <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="">
            <img alt="" border="0" src="https://www.paypal.com/zh_XC/i/scr/pixel.gif" width="1" height="1">
        </form>
        </p>
    </div>
    <div id="uninstall">
        <strong><?php _e('UNINSTALL!', 'replymail')?></strong>
        <p><?php _e('Attention! UNINSTALL will delete options from database, and can not restore.', 'replymail')?></p>
        <form name="form2" method="post" action="<?php echo htmlentities(str_replace( '%7E', '~', $_SERVER['REQUEST_URI']),ENT_QUOTES,'UTF-8'); ?>">
            <fieldset class="submit">
                <input type="hidden" name="rmSubmitUninstall" value="yes" />
                <input name="sumbit" id="sumbit" type="submit" value="<?php _e('Uninstall', 'replymail');?>" tabindex="5" />
            </fieldset>
        </form>
    </div>
</div>
<?php
}
