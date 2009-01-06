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
    $content = <<<CONTENT
{#oriCommentAuthor}:

{#replyCommentAuthor} reply to your comment on {#post}. Below is the comment content:
{#replyContent}

And your original comment is:
{#oriContent}
CONTENT;
    $options = array(0 => $email,
                     1 => $name,
                     2 => "Someone on '{$name}' reply to your comment",
                     3 => $content);
    add_option('rmOptions', $options);
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
    if (empty($email)) return array(false,__('Blank email address', 'replymail'));
    if (isset($email[100])) return array(false,__('Email address not allow longer than 100 byte', 'replymail'));
    $domain = substr($email, strpos($email, '@')+1);
    if (isset($domain[61])) return array(false, __('Domain name not allow longer than 60 byte', 'replymail'));
    $pattern = "/^\w+([-_+.]\w+)*\@\w+(-\w+)*(\.\w+(-\w+)*)*\.[a-z]{2,4}$/is";
    if (!preg_match($pattern, $email) || substr_count($email,'@') != 1) return array(false, __('Wrong email format', 'replymail'));
    return $email;
}
function rmCheckName($name, $length=100) {
    $name = trim($name);
    if (empty ($name)) return array(false, __('Blank name'));
    $name = htmlspecialchars(stripslashes($name));
    if (isset($name[$length])) return array(false, printf(__("Name not allow longer than %d byte", 'replymail'),$length));
    return $name;
}
function rmCheckContent($content) {
    if (isset($content[5120])) return array(false, __('Content not allow longer than 5120 byte', 'replymail'));
    $content = apply_filters('wp_filter_kses', $content);
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
                <textarea name="emailContent" id="emailContent" cols="60" rows="16" tabindex="4"><?php echo format_to_edit($options[3]);?></textarea>
              </li>
              <li>
                <ul>
                    <strong>Template Tags: </strong>
                    <li><em>{#blogName}</em> - The name of this blog. ONLY for Subject.</li>
                    <li><em>{#postTitle}</em> - The title of the comment post. ONLY for Subject.</li>
                    <li><em>{#oriCommentAuthor}</em> - The parent commenter's name. Can use both for Subject and Email Content.</li>
                    <li><em>{#replyCommentAuthor}</em> - The reply commenter,s name. Can use both for Subject and Email Content.</li>
                    <li><em>{#blog}</em> - Clickable link for the blog. ONly for Email Content.</li>
                    <li><em>{#post}</em> - Clickable link for the comment post. ONly for Email Content.</li>
                    <li><em>{#replyContent}</em> - Reply comment content. ONly for Email Content.</li>
                    <li><em>{#oriContent}</em> - Parent comment content. ONly for Email Content.</li>
                    <li>Also, your can use these HTML tags: <?php echo allowed_tags();?></li>
                </ul>
              </li>
            </ol>
        </fieldset>
        <fieldset class="submit">
            <input type="hidden" name="rmSubmitHidden" value="yes" />
            <input name="sumbit" id="sumbit" type="submit" value="<?php echo __('Save Options', 'replymail');?>" tabindex="5" />
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
        return array(false, __('Not approved!', 'replymail'));
    }elseif ($comment->comment_parent == '0'){
        return array(false, __('No parent comment!', 'replymail'));
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
        return array(false,__('Reply to own comment', 'replymail'));
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
                     6 => $commentdata['childCommentContent'],
                     7 => $commentdata['parentCommentContent']);

    // Subject Template
    $options[2] = str_replace('&quot;', "'", $options[2]);
    $options[2] = str_replace('&ldquo;', '"', $options[2]);
    $options[2] = str_replace($pattern[0], $replace[0], $options[2]);
    $options[2] = str_replace($pattern[1], $replace[1], $options[2]);
    $options[2] = str_replace($pattern[2], $replace[2], $options[2]);
    $options[2] = str_replace($pattern[3], $replace[3], $options[2]);

    // Email Content Template
    $options[3] = apply_filters('comment_text',$options[3]);
    $options[3] = str_replace($pattern[2], $replace[2], $options[3]);
    $options[3] = str_replace($pattern[3], $replace[3], $options[3]);
    $options[3] = str_replace($pattern[4], $replace[4], $options[3]);
    $options[3] = str_replace($pattern[5], $replace[5], $options[3]);
    $options[3] = str_replace($pattern[6], $replace[6], $options[3]);
    $options[3] = str_replace($pattern[7], $replace[7], $options[3]);

    return $options;
}
add_action('comment_post', 'rmReplyMail', 500);
/**
 * TODO
 * add a selectable languages options
 */
/*$locale = trim(get_option('rmLocale'));
if (empty($locale)) $locale = 'default';
$mofile = $pluginDir.'/lang/'.$locale.'.mo';
load_textdomain('replymail', $mofile);*/
?>