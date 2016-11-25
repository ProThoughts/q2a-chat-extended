<?php

/*
	Plugin Name: q2apro-ajax-usersearch
	Plugin URI: http://www.q2apro.com/plugins/usersearch
*/


	class qa_chat_admin
	{

		function init_queries($tableslc)
		{
		}

		// option's value is requested but the option has not yet been set
		function option_default($option)
		{
			switch($option)
			{
				case 'qa_chat_mailadmin_enabled':
					return 1;
				case 'qa_chat_mailadmin_checkdate':
					return 0;
				case 'qa_chat_mailadmin_checkdate':
					return '';
				case 'qa_chat_mailadmin_mailweekday':
					return 7; // 1 Mon - 7 Sun
				default:
					return null;
			}
		}
		
		function allow_template($template) 
		{
			return ($template!='admin');
		}
		
		function admin_form(&$qa_content)
		{                       
			// process the admin form if admin hit Save-Changes-button
			$ok = null;
			if (qa_clicked('qa_chat_save')) 
			{
				if(qa_post_text('qa_chat_mailadmin_domanual')=='1')
				{
					// clear checkdate first
					qa_opt('qa_chat_mailadmin_checkdate', '');
					// needs to set today's weekday
					q2apro_send_chat_to_admin(date('N', time()));
				}
				else
				{
					// only assign if not triggered manually 
					qa_opt('qa_chat_mailadmin_checkdate', (String)qa_post_text('qa_chat_mailadmin_checkdate'));						
				}
				qa_opt('qa_chat_mailadmin_enabled', (bool)qa_post_text('qa_chat_mailadmin_enabled')); // empty or 1
				qa_opt('qa_chat_mailadmin_mailweekday', (int)qa_post_text('qa_chat_mailadmin_mailweekday'));
				qa_opt('qa_chat_mailadmin_dbeditlink', (String)qa_post_text('qa_chat_mailadmin_dbeditlink'));
				$ok = qa_lang('admin/options_saved');
			}
			
			// form fields to display frontend for admin
			$fields = array();
			
			$fields[] = array(
				'type' => 'checkbox',
				'label' => 'Weekly email for chat history enabled',
				'tags' => 'name="qa_chat_mailadmin_enabled"',
				'value' => qa_opt('qa_chat_mailadmin_enabled'),
			);
			
			$fields[] = array(
				'type' => 'number',
				'label' => 'Weekday to send the email (1 Mon - 7 Sun):',
				'tags' => 'name="qa_chat_mailadmin_mailweekday"',
				'value' => qa_opt('qa_chat_mailadmin_mailweekday'),
			);
			
			$fields[] = array(
				'type' => 'text',
				'label' => 'Last checkdate:',
				'tags' => 'name="qa_chat_mailadmin_checkdate"',
				'value' => qa_opt('qa_chat_mailadmin_checkdate'),
			);
			
			$fields[] = array(
				'type' => 'checkbox',
				'label' => 'Trigger chat history mailing manually (tick checkbox, then hit save)',
				'tags' => 'name="qa_chat_mailadmin_domanual"',
				'value' => '',
			);
			
			$fields[] = array(
				'type' => 'text',
				'label' => 'Link to Phpmyadmin (table qa_chat_posts) to quickly edit messages (the admin will see an edit-Button within the chat):',
				'tags' => 'name="qa_chat_mailadmin_dbeditlink"',
				'value' => qa_opt('qa_chat_mailadmin_dbeditlink'),
			);
			
			return array(           
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'fields' => $fields,
				'buttons' => array(
					array(
						'label' => qa_lang_html('main/save_button'),
						'tags' => 'name="qa_chat_save"',
					),
				),
			);
		}
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/