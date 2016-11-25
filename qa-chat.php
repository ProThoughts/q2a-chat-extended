<?php
/*
	Question2Answer Chat Room plugin, v1.5
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_chat
{
	private $directory;
	private $urltoroot;
	private $user;
	private $dates;
	private $optactive = 'chat_active';

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function suggest_requests() // for display in admin interface
	{
		return array(
			array(
				'title' => 'Chat Room',
				'request' => 'chat',
				'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}

	public function match_request( $request )
	{
		return $request == 'chat';
	}

	function init_queries( $tableslc )
	{
		$tbl1 = qa_db_add_table_prefix('chat_posts');
		$tbl2 = qa_db_add_table_prefix('chat_users');

		if ( in_array($tbl1, $tableslc) && in_array($tbl2, $tableslc) )
		{
			qa_opt( $this->optactive, '1' );
			return null;
		}

		return array(
			'CREATE TABLE IF NOT EXISTS ^chat_posts (
			  `postid` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `userid` int(10) unsigned NOT NULL,
			  `posted` datetime NOT NULL,
			  `message` varchar(800) NOT NULL,
			  PRIMARY KEY (`postid`),
			  KEY `posted` (`posted`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8',

			'CREATE TABLE IF NOT EXISTS ^chat_users (
			  `userid` int(10) unsigned NOT NULL,
			  `lastposted` datetime NOT NULL,
			  `lastpolled` datetime NOT NULL,
			  `kickeduntil` datetime NOT NULL DEFAULT "2012-01-01 00:00:00",
			  PRIMARY KEY (`userid`),
			  KEY `active` (`lastpolled`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',

			'CREATE TABLE IF NOT EXISTS ^chat_kicks (
			  `userid` int(10) unsigned NOT NULL,
			  `kickedby` int(10) unsigned NOT NULL,
			  `whenkicked` datetime NOT NULL,
			  PRIMARY KEY (`userid`,`kickedby`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);

	}

	/*
		MAIN function: display the chat room, or run an AJAX request
	*/
	public function process_request( $request )
	{
		// set up user
		$this->user = array(
			'id' => qa_get_logged_in_userid(),
			'handle' => qa_get_logged_in_handle(),
			'flags' => qa_get_logged_in_flags(),
			'level' => qa_get_logged_in_level(),
		);

		// check if user is banned (kicked)
		$sql = 'SELECT kickeduntil, (kickeduntil-NOW() > 0) AS iskicked FROM ^chat_users WHERE userid=#';
		$result = qa_db_query_sub( $sql, $this->user['id'] );
		$row = qa_db_read_one_assoc($result, true);
		$this->user['iskicked'] = @$row['iskicked'];
		$this->user['kickeduntil'] = @$row['kickeduntil'];

		// create dates for database
		$now = time();
		$this->dates = array(
			'posted' => gmdate( 'Y-m-d H:i:s', $now ),
			'posted_utc' => gmdate( 'Y-m-d\TH:i:s\Z', $now ),
		);

		// AJAX: someone posted a message
		$message = qa_post_text('ajax_add_message');
		if ( $message !== null )
		{
			if ( !$this->user_perms_post() )
			{
				echo "QA_AJAX_RESPONSE\n0\nYou are not allowed to post currently, sorry.";
				return;
			}

			// prevent just spaces
			$message = trim($message);
			if ( strlen($message) == 0 )
			{
				echo "QA_AJAX_RESPONSE\n0\nThe message you post must actually be something.";
				return;
			}

			$data = array(
				'userid' => $this->user['id'],
				'username' => $this->user['handle'],
				'posted' => $this->dates['posted'],
				'posted_utc' => $this->dates['posted_utc'],
				'message' => $message,
			);

			// save to database
			$data['postid'] = $this->post_message( $data );
			$this->update_activity(true);

			$data['username'] = qa_html( $data['username'] );
			$data['message'] = $this->format_message( $data['message'] );

			header('Content-Type: text/plain; charset=utf-8');
			echo "QA_AJAX_RESPONSE\n" . $this->user['id'] . "\n" . json_encode($data);
			return;
		}

		// AJAX: polling check; $lastid=0 on initial page load
		$lastid = qa_post_text('ajax_get_messages');
		if ( $lastid !== null )
		{
			if ( !$this->user_perms_view() )
			{
				echo "QA_AJAX_RESPONSE\n0\nYou don't appear to be logged in. Please reload the page.";
				return;
			}
			if ( $this->user_perms_kicked() )
			{
				echo "QA_AJAX_RESPONSE\n0\nYou have been kicked. Please reload the page.";
				return;
			}

			$this->update_activity( $lastid==0 );
			$messages = $this->get_messages( $lastid );
			$users = $this->users_online();

			header('Content-Type: text/plain; charset=utf-8');
			echo "QA_AJAX_RESPONSE\n" . $this->user['id'] . "\n" . json_encode($messages) . "\n" . json_encode($users);
			return;
		}

		// AJAX: request to kick user
		$kickuserid = qa_post_text('ajax_kick_userid');
		$kickhandle = qa_post_text('ajax_kick_username');
		if ( $kickuserid !== null )
		{
			// currently only mods/admins can kick users
			if ( $this->user['level'] < QA_USER_LEVEL_MODERATOR )
			{
				echo "QA_AJAX_RESPONSE\n0\nYou are not allowed to do that currently, sorry.";
				return;
			}

			$this->kick_user( $kickuserid, $kickhandle );

			header('Content-Type: text/plain; charset=utf-8');
			echo "QA_AJAX_RESPONSE\n" . $this->user['id'] . "\nGave 'em a right kickin'!";
			return;
		}



		// regular page request
		$qa_content = qa_content_prepare();
		$qa_content['title'] = 'Community-Chat';
		$qa_content['script_rel'][] = $this->urltoroot.'qa-chat.js?v=1.74';

		$admin_button = '';
		$dbeditlink = qa_opt('qa_chat_mailadmin_dbeditlink');
		if(!empty($dbeditlink) && qa_get_logged_in_level()>=QA_USER_LEVEL_SUPER) 
		{
			$admin_button = '<a target="_blank" class="qa-gray-button" id="admin_edit_chat" href="'.$dbeditlink.'">edit chat</a><br />';
		}

		// eetv: added
		$chathints = '
			<div id="chat-hints">
				'.$admin_button.'
				1. Der Chat ist eine Lounge für angenehme Gespräche.
				<br />
				2. Mathe-Aufgaben gehören <a target="_blank" href="'.qa_path('ask').'">ins Forum</a>, nicht in den Chat.
				<br />
				3. Ihr könnt euch über beliebige Dinge unterhalten. Postet Links zu Bildern, Videos, Musik etc.
			</div>
			';

		// allowed to enter
		if($this->user_perms_post())
		{
			$qa_content['custom_form'] = $chathints.'
				<p class="qa-gray-button" id="hideRecentChats" title="Leere die Liste, damit du später sofort erkennst, welche Nachrichten neu sind.">Liste leeren</p>
				<p class="qa-gray-button" id="soundToggle" title="Benachrichtigungston bei eingehender Chat-Nachricht. Hilfreich, wenn du gerade auf anderen Seiten surfst.">Sound ist aus</p>
				<form method="post" id="qa-chat-form">
					<input id="message" class="qa-form-tall-text qa-chat-post" type="text" name="ajax_add_message" autocomplete="off" maxlength="800">
					<input type="submit" class="qa-form-tall-button" value="Senden">
				</form>
				<ul id="qa-chat-list"></ul>
				';
			$qa_content['custom_item'] = '
			<audio id="chatAudio">
				<source src="'.$this->urltoroot.'notify.ogg" type="audio/ogg">
				<source src="'.$this->urltoroot.'notify.mp3" type="audio/mpeg">
			</audio>
			';
		}
		else if ( $this->user_perms_kicked() )
		{
			$ktil_utc = gmdate( 'Y-m-d\TH:i:s\Z', strtotime($this->user['kickeduntil']) );
			$qa_content['error'] =
				'Sorry, you have been kicked from chat temporarily. Take a few moments to chill.<br>' .
				'The ban expires <span id="qa_chat_kickeduntil" data-utc="' . $ktil_utc . '" title="' . $ktil_utc . '">soon</span>' .
				'<script>$("#qa_chat_kickeduntil").timeago();</script>';
		}
		else if ( $this->user_perms_view() )
		{
			$qa_content['error'] = 'Sorry, you are currently unable to post in chat. If you are new, you must confirm your email address.';
		}
		else
		{
			$qa_content['error'] = 'Bitte <a href="'.qa_path('login').'?to=chat">einloggen</a> oder <a href="'.qa_path('register').'?to=chat">registrieren</a>, um den Chat benutzen zu können.';
			$qa_content['custom_hint'] = '
			<div style="margin:30px 0 0 15px;font-size:14px;">
			<p>Im Chat kannst du die Experten treffen, die deine Mathefragen im Forum beantworten.<br /><br />Der Chat ist nicht auf Mathematik beschränkt, ihr dürft euch zu Themen wie Videos, Musik, Schule, Studium usw. austauschen. Loggt euch ein und macht einfach mit. Nette Leute sind immer da.</p>
			
			<p style="margin-top:50px;">Du hast eine Mathefrage? Stell sie kostenlos und ohne Anmeldung:</p>
				<form method="post" action="./ask">
					<table class="qa-form-tall-table" id="askboxtable">
						<tr style="vertical-align:middle;">
							<td class="qa-form-tall-label" style="width:150px;padding:0;white-space:nowrap;">
								<span style="margin-left:13px;">Deine Mathefrage:</span>
							</td>
							<td class="qa-form-tall-data" style="padding:8px 8px 8px 0;">
								<input NAME="title" TYPE="text" class="qa-form-tall-text" id="askboxin">
								<input class="ask-box-button" TYPE="submit" value="Frage stellen"> 
							</td>
					</tr>
				</table>
				<input type="hidden" name="doask1" value="1">
			</form>
			</div>';
		}

		return $qa_content;
	}



	// fetch all messages after given id
	private function get_messages( $lastid )
	{
		$sql =
			'SELECT p.postid, p.userid, u.handle AS username, p.message AS message,
			   p.posted, DATE_FORMAT(p.posted, "%Y-%m-%dT%H:%i:%sZ") AS posted_utc
			 FROM ^chat_posts p LEFT JOIN ^users u ON u.userid=p.userid
			 WHERE p.postid > #
			 ORDER BY p.posted DESC LIMIT 80';
		$result = qa_db_query_sub( $sql, $lastid );

		$messages = qa_db_read_all_assoc($result);

		foreach ( $messages as &$m )
		{
			$m['message'] = $this->format_message( $m['message'] );
			$m['username'] = qa_html( $m['username'] );
		}

		return $messages;
	}

	// save message to database
	private function post_message( $data )
	{
		$sql = 'INSERT INTO ^chat_posts (postid, userid, posted, message) VALUES (0, #, $, $)';
		qa_db_query_sub( $sql, $data['userid'], $data['posted'], $data['message'] );
		return qa_db_last_insert_id();
	}

	// update user activity
	private function update_activity( $posted=false )
	{
		if ( $posted )
			$sql = 'INSERT INTO ^chat_users (userid, lastposted, lastpolled) VALUES (#, NOW(), NOW()) ON DUPLICATE KEY UPDATE lastposted=NOW(), lastpolled=NOW()';
		else
			$sql = 'INSERT INTO ^chat_users (userid, lastpolled) VALUES (#, NOW()) ON DUPLICATE KEY UPDATE lastpolled=NOW()';

		qa_db_query_sub( $sql, $this->user['id'] );
	}

	// get recently active users
	private function users_online()
	{
		$sql =
			'SELECT u.userid, u.handle AS username, u.level, (c.lastposted < DATE_SUB(NOW(), INTERVAL 8 MINUTE)) AS idle, (c.kickeduntil > NOW()) AS kicked
			 FROM ^users u, ^chat_users c
			 WHERE u.userid=c.userid AND c.lastpolled > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
			 ORDER BY u.handle';
		$result = qa_db_query_sub( $sql );

		$users = qa_db_read_all_assoc($result);

		foreach ( $users as &$u )
		{
			$u['username'] = qa_html( $u['username'] );
			$kickable = $u['level'] < QA_USER_LEVEL_MODERATOR && $this->user['level'] >= QA_USER_LEVEL_MODERATOR;
			$u['kickable'] = $kickable ? '1' : '0';
		}

		return $users;
	}

	// votes to kick a user; mods/admins can kick users straight away
	private function kick_user( $kickuserid, $kickhandle )
	{
		$sql = 'INSERT INTO ^chat_kicks (userid, kickedby, whenkicked) VALUES (#, #, NOW()) ON DUPLICATE KEY UPDATE whenkicked=NOW()';
		qa_db_query_sub( $sql, $kickuserid, $this->user['id'] );

		$sql = 'UPDATE ^chat_users SET kickeduntil = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE userid=#';
		$result = qa_db_query_sub( $sql, $kickuserid );

		if ( $result )
		{
			$message = array(
				'userid' => '0',
				'posted' => $this->dates['posted'],
				'message' => qa_html($kickhandle) . ' has been kicked off chat for 10 minutes.',
			);
			$this->post_message( $message );
		}
	}

	// check user permissions for viewing page
	private function user_perms_view()
	{
		return $this->user['id'] > 0;
	}

	// check if user was kicked
	private function user_perms_kicked()
	{
		return $this->user['iskicked'] > 0;
	}

	// check user permissions for posting messages
	private function user_perms_post()
	{
		return qa_user_permit_error() === false && !$this->user['iskicked'] && (
			($this->user['level'] >= QA_USER_LEVEL_EXPERT) ||
			($this->user['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED)
		);
	}

	// format message
	private function format_message( $msg )
	{
		// censor bad words
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		$blockwordspreg = qa_get_block_words_preg();
		$msg = qa_block_words_replace( $msg, $blockwordspreg );

		$msg = qa_html( $msg );
		return qa_html_convert_urls($msg, true);
	}

}
