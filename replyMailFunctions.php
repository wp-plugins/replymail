<?php
/* 
 * replyMail general functions
 */

/**
 *
 * Retrieves child comment data and its parent comment data
 *
 * @param int $commentId child comment's ID
 * @return array
 */
function rmGetData($commentdata) {
    // Retrieves child comment data
    global $comment_approved;
    global $comment_post_ID;
    global $comment_author;
    global $comment_author_email;
    global $comment_content;
    global $comment_parent;

    /*
     * if comment not approved, do not send email.
     */
    if (1 != $comment_approved) {
        $info = __('You comment is not approved now, not sending the notification email.', 'replymail');
        return array(false, 0, $info);
    }

    // If comment do not have a parent comment,
    // return and exit.
    if ($comment_parent == 0) {
        $info = __('No parent comment, do not send mail.', 'replymail');
        return array(false, 0, $info);
    }

    // Save child comment data to $comments,
    $comments = array(
                      'postID' => $comment_post_ID,
                      'childCommentAuthor' => $comment_author,
                      'childCommentAuthorEmail' => $comment_author_email,
                      'childCommentContent' => $comment_content,
                      'childCommentParent' => $comment_parent
                );

    // Retrieves parent comment data
    $comment = get_comment($comments['childCommentParent']);

    // Reply to own comment, do not send mail.
    // Reply to blog user, do not send mail too.
    if ($comment->comment_author_email == $comments['childCommentAuthorEmail']) {
        $info = __('Reply to own comment, do not send mail.', 'replymail');
        return array(false, 0, $info);
    } elseif ($comment->user_id != 0) {
        $info = __('Reply to blog user, do not send mail.', 'replymail');
        return array(false, 0, $info);
    }

    $comments['parentCommentAuthor'] = $comment->comment_author;
    $comments['parentCommentAuthorEmail'] = $comment->comment_author_email;
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
    global $rmDebug;
    $comments = rmGetData($commentdata);
    if ($comments[0]===false){
        $err = $comments[1];
    }else{
        $options = get_option('rmOptions');
        $options = rmReplaceTemplate($comments, $options, $commentdata);
        rmSendingMail($comments['parentCommentAuthorEmail'],
                      $options[2],
                      $options[3],
                      "From: \"{$options[1]}\" <{$options[0]}>\nContent-Type: text/html; charset=\"UTF-8\"\nX-Plugin: replyMail 1.1.4\n");
    }
    if ($rmDebug){
        var_dump($comments);
        echo '<br />';
        var_dump($options);
        echo '<br /><a href="', get_comment_link($commentdata), '">Redirect to comment</a>';
        exit();
    }
}

/**
 * Replace template tags to HTML tags.
 *
 * @global object $wpdb
 * @param array $comments
 * @param array $options
 * @param int $commentdata
 * @return array
 */
function rmReplaceTemplate($comments, $options,$commentdata) {
    global $wpdb, $table_prefix;
    // Retrieves the post/page's "title" & "permalink".
    $query = "SELECT `post_title`
              FROM `{$table_prefix}posts`
              WHERE ID = '".$comments['postID']."'";
    $postTitle = $wpdb->get_var($query);
    $postPermalink = get_permalink($comments['postID']). '#comment-' . $commentdata;

    // Retrieves the blog's "name" & "URL".
    $blogName = get_bloginfo('name');
    $blogURL = get_bloginfo('url');

    // Available Template TAGS.
    $pattern = array(0 => '{#blogName}',
                     1 => '{#postTitle}',
                     2 => '{#oriCommentAuthor}',
                     3 => '{#replyCommentAuthor}',
                     4 => '{#post}',
                     5 => '{#blog}',
                     6 => '{#oriContent}',
                     7 => '{#replyContent}');
    // End of Template TAGS.

    $replace = array(0 => $blogName,
                     1 => $postTitle,
                     2 => $comments['parentCommentAuthor'],
                     3 => $comments['childCommentAuthor'],
                     4 => "<a href=\"{$postPermalink}\">{$postTitle}</a>",
                     5 => "<a href=\"{$blogURL}\">{$blogName}</a>",
                     6 => $comments['parentCommentContent'],
                     7 => $comments['childCommentContent']);

    // Replace Subject Template TAGS to HTML tags.
    // $options[2] = str_replace('&quot;', "'", $options[2]);
    // $options[2] = str_replace('&ldquo;', '"', $options[2]);
    $options[2] = stripslashes(apply_filters('the_title',$options[2]));
    $options[2] = str_replace($pattern[0], $replace[0], $options[2]);
    $options[2] = str_replace($pattern[1], $replace[1], $options[2]);
    $options[2] = str_replace($pattern[2], $replace[2], $options[2]);
    $options[2] = str_replace($pattern[3], $replace[3], $options[2]);

    // Replace Email Content Template TAGS to HTML tags.
    $options[3] = stripslashes(apply_filters('comment_text',$options[3]));
    $options[3] = convert_smilies($options[3]);
    $options[3] = str_replace($pattern[2], $replace[2], $options[3]);
    $options[3] = str_replace($pattern[3], $replace[3], $options[3]);
    $options[3] = str_replace($pattern[4], $replace[4], $options[3]);
    $options[3] = str_replace($pattern[5], $replace[5], $options[3]);
    $options[3] = str_replace($pattern[6], $replace[6], $options[3]);
    $options[3] = str_replace($pattern[7], $replace[7], $options[3]);

    return $options;
}
/**
 * 
 * @param <type> $nameLength
 * @param <type> $subjectLength
 * @return <type> 
 */
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

/**
 *
 * @param <type> $email
 * @return <type>
 */
function rmCheckEmail($email) {
    $email = trim($email);
    if (empty($email)) return array(false,__('Blank email address', 'replymail'));
    if (isset($email[100])) return array(false,__('Email address not allow longer than 100 byte', 'replymail'));
    $domain = substr($email, strpos($email, '@')+1);
    if (isset($domain[61])) return array(false, __('Domain name not allow longer than 60 byte', 'replymail'));
    if (!is_email($email)) return array(false, __('Please fill in a real email'));
    return $email;
}

/**
 * 
 * @param <type> $name
 * @param <type> $length
 * @return <type>
 */
function rmCheckName($name, $length=100) {
    $name = trim($name);
    if (empty ($name)) return array(false, __('Blank name'));
    $name = htmlspecialchars(stripslashes($name));
    if (isset($name[$length])) return array(false, printf(__("Name not allow longer than %d byte", 'replymail'),$length));
    return $name;
}

/**
 * Check and filter the email content.
 * @param string $content
 * @return string
 */
function rmCheckContent($content) {
    if (isset($content[5120])) return array(false, __('Content not allow longer than 5120 byte', 'replymail'));
    $content = wp_filter_kses($content);
    return $content;
}

/* EOF replyMailFunctions.php */
/* ./wp-content/plugins/replymail/replyMailFunctions.php */