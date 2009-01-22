<?php
function rmSettingCSS() {
    echo '<style type="text/css">
/*<![CDATA[*/
.rmWrap{width:650px;}
.rmWrap fieldset{margin:12px 0 0 0;padding:0;}
.rmWrap fieldset{-moz-border-radius:5px;-webkit-border-radius:5px;}
.rmWrap legend{margin-left:500px;color:#666;font-weight:bold;}
.rmWrap fieldset ol{list-style:none;margin:0;padding:0;}
.rmWrap fieldset li{margin:12px;}
.rmWrap label{display:block;float:left;width:150px;margin-right:12px;}
.rmWrap .textinput{width:320px;}
.rmWrap fieldset.submit{border-style:none;}
/*]]>*/
</style>';
}
function rmSettingPage() {
    // If submit, collecting options data
    // serialize them and update database
    if($_POST['rmSubmitHidden'] == 'yes') {
        $options = rmCheckData();
        if ($options[0]){
            update_option('rmOptions', $options);
            unset($options);
?>
<div class="updated"><p><strong><?php echo __('Options saved!', 'replymail');?></strong></p></div>
<?php
        }else{
?>
<div class="updated"><p><strong style="color:red;"><?php echo $options[1];?></strong></p></div>
<?php
        }
    }
    $options = get_option('rmOptions');
?>
<div class="rmWrap">
    <h2><?php echo __('replyMail Setting');?></h2>
    <form name="form1" method="post" action="<?php echo htmlentities(str_replace( '%7E', '~', $_SERVER['REQUEST_URI']),ENT_QUOTES,'UTF-8'); ?>">
        <fieldset>
            <legend><?php echo __('Email From Information: ', 'replymail');?></legend>
            <ol>
              <li>
                <label for="fromEmail"><?php echo __('Email: ', 'replymail');?></label>
                <input id="fromEmail" name="fromEmail" type="text" tabindex="1" class="textinput" value="<?php echo $options[0];?>" />
              </li>
              <li>
                <label for="fromName"><?php echo __('From Name: ', 'replymail');?></label>
                <input name="fromName" id="fromName" type="text" tabindex="2" class="textinput" value="<?php echo $options[1];?>" />
              </li>
            </ol>
        </fieldset>
        <fieldset>
            <legend><?php echo __('Email Template Setting: ', 'replymail');?></legend>
            <ol>
              <li>
                <label for="emailSubject"><?php echo __('Subject: ', 'replymail');?></label>
                <input name="emailSubject" id="emailSubject" type="text" tabindex="3" class="textinput" value="<?php echo $options[2];?>" />
              </li>
              <li>
                <label for="emailContent"><?php echo __('Email Content Template: ', 'replymail');?></label>
                <textarea name="emailContent" id="emailContent" cols="60" rows="16" tabindex="4"><?php echo stripslashes(format_to_edit($options[3]));?></textarea>
              </li>
            </ol>
        </fieldset>
        <fieldset class="submit">
            <input type="hidden" name="rmSubmitHidden" value="yes" />
            <input name="sumbit" id="sumbit" type="submit" value="<?php echo __('Save Options', 'replymail');?>" tabindex="5" />
        </fieldset>
    </form>
    <ul>
        <strong><?php echo __('Template Tags Help: ', 'replymail')?></strong>
        <li><em>{#blogName}</em> - <?php echo __('The name of this blog. ONLY for Subject.', 'replymail')?></li>
        <li><em>{#postTitle}</em> - <?php echo __('The title of the comment post. ONLY for Subject.', 'replymail')?></li>
        <li><em>{#oriCommentAuthor}</em> - <?php echo __('The parent commenter\'s name. Can use both for Subject and Email Content.', 'replymail')?></li>
        <li><em>{#replyCommentAuthor}</em> - <?php echo __('The reply commenter\'s name. Can use both for Subject and Email Content.', 'replymail')?></li>
        <li><em>{#blog}</em> - <?php echo __('Clickable link for the blog. ONLY for Email Content.', 'replymail')?></li>
        <li><em>{#post}</em> - <?php echo __('Clickable link for the comment post. ONLY for Email Content.', 'replymail')?></li>
        <li><em>{#replyContent}</em> - <?php echo __('Reply comment content. ONLY for Email Content.', 'replymail')?></li>
        <li><em>{#oriContent}</em> - <?php echo __('Parent comment content. ONLY for Email Content.', 'replymail')?></li>
        <li><?php echo __('Also, your can use these HTML tags: ', 'replymail')?><?php echo allowed_tags();?></li>
    </ul>
</div>
<?php
}
