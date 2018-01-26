<?php
/*
	Chat Room plugin for Question2Anaswer
	Copyright (c) Scott Vivian

	Question2Answer (c) Gideon Greenspan
	http://www.question2answer.org/

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
	Plugin Name: Chat Room EXTENDED
	Plugin URI: https://github.com/q2apro/q2a-chat-extended
	Plugin Description: A chat room functionality for Q2A with special features
	Plugin Version: 3.0 q2apro
	Plugin Date: 2017-12-31
	Plugin Author: Scott Vivian + q2apro
	Plugin Author URI: http://codelair.co.uk
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5
	
	NOTE: This is an extended version of Scott's chat plugin by q2apro.
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}


qa_register_plugin_module('page', 'qa-chat.php', 'qa_chat', 'Chat Room');
qa_register_plugin_layer('qa-chat-layer.php', 'Chat Room layer');
qa_register_plugin_module('page', 'qa-chat-history.php', 'qa_chat_history_page', 'Chat History Page');
// event process for sending chat history
qa_register_plugin_module('event', 'qa-chat-mailadmin.php', 'qa_chat_mailadmin', 'Chat History Mail Admin');
// admin for settings 
qa_register_plugin_module('module', 'qa-chat-admin.php', 'qa_chat_admin', 'Chat Settings Admin');

// q2apro custom function
function q2apro_send_chat_to_admin($mailweekday=null)
{
	if(is_null($mailweekday))
	{
		$mailweekday = qa_opt('qa_chat_mailadmin_mailweekday');
	}
	// already sent today - this replaces a cronjob
	$date = date('Y-m-d');
	
	if($date != qa_opt('qa_chat_mailadmin_checkdate') && date('N', time()) == $mailweekday)
	{
		// remember date 
		qa_opt('qa_chat_mailadmin_checkdate', $date);
	
		require_once QA_INCLUDE_DIR.'qa-app-posts.php';
		require_once QA_INCLUDE_DIR.'qa-app-emails.php';
		
		// get all recent questions from within last 3 days
		$chatmessages = qa_db_read_all_assoc(
						qa_db_query_sub('SELECT p.postid, p.userid, u.handle AS username, p.message AS message,
									p.posted, DATE_FORMAT(p.posted, "%Y-%m-%dT%H:%i:%sZ") AS posted_utc
									FROM ^chat_posts p LEFT JOIN ^users u ON u.userid=p.userid
									WHERE posted BETWEEN DATE_SUB(NOW(), INTERVAL 1 WEEK) and NOW()
									ORDER BY p.posted DESC
									') );

		// init
		$emailbody = '';
		
		$emailbody .= '
			<div id="qa-chat-content">
				<a href="'.qa_opt('site_url').'/chat" style="margin-bottom:10px;text-align:right;font-size:13px;">go to Chat</a>
				
				<ul id="qa-chat-list">
		';
		
		$gmtoffset = 2;
		
		foreach($chatmessages as $m)
		{
			// convert URLs in chat text to links
			$m['message'] = substr(preg_replace('/([^A-Za-z0-9])((http|https|ftp):\/\/([^\s&<>"\'\.])+\.([^\s&<>"\']|&amp;)+)/i', '\1<a href="\2" target="_blank">\2</a>', ' '.$m['message'].' '), 1, -1);

			$emailbody .= '
			<li id="qa-chat-id-'.$m['postid'].'" class="qa-chat-item">
			  <div class="qa-chat-item-meta">
				<span class="qa-chat-item-who">
				  <a target="_blank" href="'.qa_opt('site_url').'/user/'.urlencode($m['username']).'">'.$m['username'].'</a>
				</span><br>
				<span class="qa-chat-item-when">'.date('d.m.Y H:i',strtotime($m['posted'])+$gmtoffset*60*60).'</span>
			  </div>
			  <div class="qa-chat-item-data">'.$m['message'].'</div>
			</li>
			';
		}
		$emailbody .= '
					</ul>
				</div> <!-- qa-chat-content -->
		';
		
		// styles 
		$emailbody .= '
			<style type="text/css">
				body { font-family: "Trebuchet MS", Tahoma, Arial, Helvetica, sans-serif; }
				#qa-chat-content { width: 728px; margin: 1em auto;}
				#qa-chat-list { width: 728px; margin: 1em auto; background:#FFF; border:1px solid #CCC;}
				#chat-hints { line-height:150%; margin-bottom:20px; } 
				#qa-chat-form { width:711px; padding:12px 10px; border-radius:5px; background: #4c9607; box-shadow:0px 1px 2px #595; } 
				.qa-chat-item img { max-width: 570px; border:1px solid #DDD; padding:8px; background:#FFF; } 
				.qa-chat-post { width: 600px; }
				.qa-chat-post:focus{box-shadow: 0 0 2px #007eff; -moz-box-shadow: 0 0 2px #007eff; -webkit-box-shadow: 0 0 2px #007eff; }
				#qa-chat-list, .qa-chat-item, #qa-chat-user-list, .qa-chat-user-item { display: block; list-style: none; margin: 0; padding: 0; font-size: 13px;  line-height: 1.4; }
				.qa-chat-item { overflow: hidden; padding: 4px 0; border-top: 1px solid #eee; padding:8px 0;}
				.qa-chat-item:first-child {border-top:0;}
				.qa-chat-item:nth-child(even) {background: #EEE; }
				.qa-chat-item:last-child { border-bottom: 1px solid #eee; }
				.qa-chat-item-meta { float: left; width: 110px; padding-right: 20px; font-size: 11px; color: #999; text-align: right; }
				.qa-chat-item-data { float: left; width: 598px; }
				.qa-chat-user-item { padding: 2px 4px; }
				.qa-chat-idle, .qa-chat-idle > a { color: #aaa; }
				.qa-chat-kick { float: right; cursor: pointer; width: 10px; height: 10px; border-radius: 10px; background: #999; margin-top: 5px; }
				.qa-chat-kick:hover { background: #f00; }
				.qa-chat-service { background: #fffae4; }
			</style>
		';
		
		$today = date('Y-m-d');
		$sevenago = date('Y-m-d', strtotime('-7 days'));
		$subject = 'Chat History: '.$sevenago.' - '.$today;
		
		q2apro_custom_mailer(
			array(
				'fromemail' => qa_opt('site_title'),
				'fromname'  => qa_opt('from_email'),
				'toemail'   => qa_opt('from_email'),
				'toname'    => qa_opt('site_title'),
				// 'touserid'  => $userid,
				// 'bcclist'   => $bcclist,
				'subject'   => $subject,
				'body'      => $emailbody,
				'html'      => true
			)
		);
		
	}
} // END q2apro_send_chat_to_admin



// thx to https://github.com/amiyasahu/q2a-email-notification/blob/master/qa-email-notifications-event.php#L144
function q2apro_custom_mailer($params)
{
	require_once QA_INCLUDE_DIR.'qa-class.phpmailer.php';

	$mailer = new PHPMailer();
	$mailer->CharSet = 'utf-8';

	$mailer->From = $params['fromemail'];
	$mailer->Sender = $params['fromemail'];
	$mailer->FromName = $params['fromname'];

	$usermail = isset($params['toemail']) ? $params['toemail'] : null;
	$touserid = isset($params['touserid']) ? $params['touserid'] : null;
	if(isset($usermail))
	{
		$mailer->AddAddress($usermail, $params['toname']);
	}
	else if(isset($touserid))
	{
		// get usermail from userid
		$usermail = qa_db_read_one_value(
						qa_db_query_sub('SELECT email FROM ^users
											WHERE userid = #',
											$touserid), true);
		if(isset($usermail))
		{
			$mailer->AddAddress($usermail, $params['toname']);
		}
		else {
			return false;
		}
	}
	else
	{
		// cannot send mail, no addressee
		return false;
	}

	$mailer->Subject = $params['subject'];
	$mailer->Body = $params['body'];
	if(isset($params['bcclist']))
	{
		foreach($params['bcclist'] as $email)
		{
			$mailer->AddBCC($email);
		}
	}

	if($params['html'])
	{
		$mailer->IsHTML(true);
	}

	if(qa_opt('smtp_active'))
	{
		$mailer->IsSMTP();
		$mailer->Host = qa_opt('smtp_address');
		$mailer->Port = qa_opt('smtp_port');

		if(qa_opt('smtp_secure'))
		{
			$mailer->SMTPSecure = qa_opt('smtp_secure');
		}

		if(qa_opt('smtp_authenticate'))
		{
			$mailer->SMTPAuth = true;
			$mailer->Username = qa_opt('smtp_username');
			$mailer->Password = qa_opt('smtp_password');
		}
	}

	// we could save the message into qa_mbmessages for documentation
	$eventid = null;
	$eventname = 'mail_sent';
	$senderid = isset($params['senderid']) ? $params['senderid'] : 1;
	$paramslog = array(
		// 'senderid'  => $senderid,
		'touserid'  => $touserid,
		// 'toname'    => $params['toname'],
		'subject'   => $params['subject'],
		'body'      => preg_replace('!\s+!', ' ', strip_tags($params['body'])) // merges whitespaces
	);
	// notrack flag
	if(!isset($params['notrack']))
	{
		booker_log_event($senderid, $eventid, $eventname, $paramslog);
	}

	return $mailer->Send();
} // end q2apro_custom_mailer
