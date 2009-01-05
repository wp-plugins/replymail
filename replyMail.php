<?php
/*
Plugin Name: replyMail
Plugin URI: http://wanwp.com
Description: Enhance the threaded comments system of WordPress 2.7.<br />
When someone reply to your comment, send a email to you.
Author: 冰古
Version: 1.0
Author URI: http://bingu.net
License: GNU General Public License 2.0 http://www.gnu.org/licenses/gpl.html
*/

$pluginDir = dirname(__FILE__);

// init replyMail Options
register_activation_hook(__FILE__, 'rmInitOptions');
function rmInitOptions() {
    $email = get_option('admin_email');
    $name = get_option('blogname');
    $options = array('email' => $email,
                     'name' => $name,
                     'subject' => "Someone on {$name} reply to your comment",
                     'content' => '1');
    add_option('rmOptions', $options,'' ,'no');
}

// Check and format data
function rmCheckData($nameLength=100, $subjectLength=150) {
    $email = rmCheckEmail($_POST['fromEmail']);
    if ($email[0]===false) return $email;
    $name = rmCheckName($_POST['fromName'], $nameLength);
    if ($name[0]===false) return $name;
    $subject = rmCheckName($_POST['emailSubject'], $subjectLength);
    if ($subject[0]===false) return $subject;
    $content = rmCheckContent($_POST['emailContent']);
    if ($content[0]===false) return $subject;
    return array($email, $name, $subject, $content);
}
function rmCheckEmail($email) {
    $email = trim($email);
    if (empty($email)) return array(false,__('Blank email address'));
    if (isset($email[100])) return array(false,__('Email address not allow longer than 100 byte'));
    $domain = substr($email, strpos($email, '@')+1);
    if (isset($domain[61])) return array(false, __('Domain name not allow longer than 60 byte'));
    $pattern = "/^\w+([-_+.]\w+)*\@\w+(-\w+)*(\.\w+(-\w+)*)*\.[a-z]{2,4}$/is";
    if (!preg_match($pattern, $email) || substr_count($email,'@') != 1) return array(false, __('Wrong email format'));
    return $email;
}
function rmCheckName($name, $length=100) {
    $name = trim($name);
    if (empty ($name)) return array(false, __('Blank name'));
    $name = htmlentities($name, ENT_COMPAT, "UTF-8");
    if (isset($name[$length])) return array(false, __("Name not allow longer than {$length} byte"));
    return $name;
}
function rmCheckContent($content) {
    if (isset($content[5120])) return array(false, __('Content not allow longer than 5120 byte'));
    $content = htmlspecialchars($content);
    return $content;
}

// Add replyMail's setting page to the Options menu
add_action('admin_menu', 'rmAddSettingPage');
add_action('admin_head', 'rmSettingCSS');
function rmAddSettingPage() {
    add_options_page('replyMail', 'replyMail Setting', 8, __FILE__, 'rmSettingPage');
}
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
<div class="updated"><p><strong><?php echo __('Options saved!');?></strong></p></div>
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
            <legend><?php echo __('Email From Information: ');?></legend>
            <ol>
              <li>
                <label for="fromEmail"><?php echo __('Email: ');?></label>
                <input id="fromEmail" name="fromEmail" type="text" tabindex="1" class="textinput" value="<?php echo $options[0];?>" />
              </li>
              <li>
                <label for="fromName"><?php echo __('From Name: ');?></label>
                <input name="fromName" id="fromName" type="text" tabindex="2" class="textinput" value="<?php echo $options[1];?>" />
              </li>
            </ol>
        </fieldset>
        <fieldset>
            <legend><?php echo __('Email Template Setting: ');?></legend>
            <ol>
              <li>
                <label for="emailSubject"><?php echo __('Subject: ');?></label>
                <input name="emailSubject" id="emailSubject" type="text" tabindex="3" class="textinput" value="<?php echo $options[2];?>" />
              </li>
              <li>
                <label for="emailContent"><?php echo __('Email Content Template: ');?></label>
                <textarea name="emailContent" id="emailContent" cols="60" rows="16" tabindex="4"><?php echo $options[3];?></textarea>
              </li>
            </ol>
        </fieldset>
        <fieldset class="submit">
            <input type="hidden" name="rmSubmitHidden" value="yes" />
            <input name="sumbit" id="sumbit" type="submit" value="<?php echo __('Save Options');?>" tabindex="5" />
        </fieldset>
    </form>
</div>
<?php
}

/**
 * Retrieves child comment data and the parent comment data
 * @param int $commentId child comment's ID
 * @return <type>
 */
function rmGetData($commentId) {
    // Retrieves child comment data
    $comment = get_comment($commentId);

    // If comment not approved or do not have a parent comment,
    // return and exit.
    if ($comment->comment_approved == 'spam'){
        return array(false, __('Not approved!'));
    }elseif ($comment->comment_parent == '0'){
        return array(false, __('No parent comment!'));
    }
    
    // Save child comment data to $renturnComment,
    // and then unset $comment
    $comments = array('postID' => $comment->comment_post_ID,
                      'childCommentAuthor' => $comment->comment_author,
                      'childCommentAuthorEmail' => $comment->comment_author_email,
                      'childCommentDate' => $comment->comment_date,
                      'childCommentContent' => $comment->comment_content,
                      'childCommentParent' => $comment->comment_parent);
    unset ($comment);

    // Retrieves parent comment data
    $comment = get_comment($comments['childCommentParent']);

    //Reply to own comment, do not send mail.
    if ($comment->comment_author_email == $comments['childCommentAuthorEmail']){
        return array(false,__('Reply to own comment'));
    }
    
    $comments['parentCommentAuthor'] = $comment->comment_author;
    $comments['parentCommentAuthorEmail'] = $comment->comment_author_email;
    $comments['parentCommentDate'] = $comment->comment_date;
    $comments['parentCommentContent'] = $comment->comment_content;
    
    unset ($comment);
    return $comments;
}

/**
 * Send mail
 * @param <type> $to
 * @param <type> $subject
 * @param <type> $content
 * @param <type> $header
 */
function rmSendingMail($to, $subject, $content, $header){
    wp_mail($to, $subject, $content, $header);
}

/**
 * send reply mail
 *
 * @uses $wpdb
 *
 * @param int $commentdata contain comment ID
 * @return
 */
function rmReplyMail($commentdata){
    $commentdata = rmGetData((int)$commentdata);
    if($commentdata[0]===false) return ;
    $options = get_option('rmOptions');
    $options = rmReplaceTemplate($commentdata, $options);
    rmSendingMail($commentdata['parentCommentAuthorEmail'],
                  $options[2],
                  $options[3],
                  "From: \"{$options[1]}\" <{$options[0]}>\nContent-Type: text/html; charset=\"UTF-8\"\n");

}

function rmReplaceTemplate($commentdata, $options) {
    // Retrieves the post data
    query_posts("p={$commentdata['postID']}");
    while (have_posts()) : the_post();
        $postTitle = the_title('','',false);
        $postPermalink = get_permalink();
    endwhile;

    $blogName = get_bloginfo('name');
    $blogURL = get_bloginfo('url');

    $pattern = array(0 => '{#blogName}',
                     1 => '{#postTitle}',
                     2 => '{#oriCommentAuthor}',
                     3 => '{#replyCommentAuthor}',
                     4 => '{#blog}',
                     5 => '{#post}',
                     6 => '{#replyContent}',
                     7 => '{#oriContent}');
    $replace = array(0 => $blogName,
                     1 => $postTitle,
                     2 => $commentdata['parentCommentAuthor'],
                     3 => $commentdata['childCommentAuthor'],
                     4 => "<a href=\"{$postPermalink}\">{$postTitle}</a>",
                     5 => "<a href=\"{$blogURL}\">{$blogName}</a>",
                     6 => $commentdata['parentCommentContent'],
                     7 => $commentdata['childCommentContent']);

    // Subject Template
    $options[2] = str_replace($pattern[0], $replace[0], $options[2]);
    $options[2] = str_replace($pattern[1], $replace[1], $options[2]);
    $options[2] = str_replace($pattern[2], $replace[2], $options[2]);
    $options[2] = str_replace($pattern[3], $replace[3], $options[2]);

    // Email Content Template
    $options[3] = str_replace("\r\n", '<br />', $options[3]);
    $options[3] = str_replace("\n", '<br />', $options[3]);
    $options[3] = str_replace($pattern[2], $replace[2], $options[3]);
    $options[3] = str_replace($pattern[3], $replace[3], $options[3]);
    $options[3] = str_replace($pattern[4], $replace[4], $options[3]);
    $options[3] = str_replace($pattern[5], $replace[5], $options[3]);
    $options[3] = str_replace($pattern[6], $replace[6], $options[3]);
    $options[3] = str_replace($pattern[7], $replace[7], $options[3]);

    return $options;
}
add_action('comment_post', 'rmReplyMail', 500);
?>
