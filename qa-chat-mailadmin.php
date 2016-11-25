<?php
/*
	Question2Answer Chat Room plugin, v1.5
	License: http://www.gnu.org/licenses/gpl.html
*/

	if (!defined('QA_VERSION')) 
	{
		header('Location: ../');
		exit;
	}

	class qa_chat_mailadmin
	{

		function init_queries($tableslc) 
		{
			// none
		}
		
		function option_default($option) 
		{
			// none
		}

		function process_event($event, $userid, $handle, $cookieid, $params)
		{
			if(qa_opt('qa_chat_mailadmin_enabled')==1 && date('Y-m-d') != qa_opt('qa_chat_mailadmin_checkdate'))
			{
				// do the newsletter sending on Q or A because user will accept to wait 1-2 seconds to have his answer posted ;-)
				// send on SUN (7), MON (1)
				$mailweekday = qa_opt('qa_chat_mailadmin_mailweekday');
				if(date('N', time()) == $mailweekday)
				{
					if($event == 'q_post' || $event == 'a_post')
					{
						q2apro_send_chat_to_admin();
					}
				}
			}
		}
	
	} // end qa_chat_mailadmin


/*
	Omit PHP closing tag to help avoid accidental output
*/