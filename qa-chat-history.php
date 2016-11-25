<?php

	class qa_chat_history_page {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		// for display in admin interface under admin/pages
		function suggest_requests() 
		{	
			return array(
				array(
					'title' => 'Chat History Page', // title of page
					'request' => 'chathistory', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='chathistory') {
				return true;
			}

			return false;
		}

		function process_request($request)
		{
		
			/* start */
			$qa_content = qa_content_prepare();

			$qa_content['title'] = 'Chat History';

			// return if not admin!
			$level=qa_get_logged_in_level(); // NULL if not logged in
			if (is_null($level)) {
				// $qa_content['custom0'] = '<div>Zugriff nicht erlaubt</div>';
				$qa_content['error'] = qa_insert_login_links( 'Bitte ^1einloggen^2 oder ^3registrieren^4, um die History des Chats aufrufen zu können.', $request );
				return $qa_content;
			}
			
			// counter for custom html output
			$c = 2;
			
			
			// we received POST data, user has chosen a day
			// $chosenDay = '';
			$chosenDay = (string)date('Y-m-d');
			if( qa_post_text('request') ) {
				$chosenDay = qa_post_text('request');
				// sanitize string, keep only 0-9 and -
				$chosenDay = preg_replace("/[^0-9\-]/i", '', $chosenDay);
			}
			/*else if ($level>=QA_USER_LEVEL_ADMIN) {
				// admin gets delivered today instead of empty (Datum wählen)
				$chosenDay = date('Y-m-d');
			}*/

			// date input field
			$qa_content['custom'.++$c] = '<FORM METHOD="POST" ACTION="'.qa_self_html().'" id="datepick" style="display:block;margin-bottom:20px;margin-left:10px;">
											<input style="font-size:14px;width:130px;" value="'.$chosenDay.'" id="datepicker" name="request" placeholder="Datum wählen" type="text">
										  </FORM>';
			
			// datepicker script and styles
			$qa_content['custom'.++$c] = '<link rel="stylesheet" type="text/css" href="'.qa_path('tools').'/zebra_datepicker/metallic.css">';
			$qa_content['custom'.++$c] = '<style type="text/css">
											#parseMediaLinks {
												position:absolute;
												right:10px;
												top:70px;
											}
										  </style>';
			$qa_content['custom'.++$c] = '<script type="text/javascript" src="'.qa_path('tools').'/zebra_datepicker/zebra_datepicker.js"></script>';
			$qa_content['custom'.++$c] = '<script type="text/javascript">
				$(document).ready(function() {
					var date = new Date();
					$("#datepicker").Zebra_DatePicker({
						direction: ["2013-05-05", new Date( date.getTime() + 0 * 60 * 60 * 1000 ).toISOString().substring(0,10)], // until today
						// direction: ["2013-05-05", false],
						lang_clear_date: "", 
						months: ["Januar", "Februar", "Maerz", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
						days: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
						days_abbr: false,
						onSelect: function(view, elements) {
							$("form#datepick").submit();
						}
					});

					$("#parseMediaLinks").tipsy( {gravity: "s", offset:10 });
					
					parse_url_to_img();
					
					$("#parseMediaLinks").click( function(){
						parse_youtube_links();
						parse_soundcloud_links();
					});
					
					// parse img-URL to img tag
					function parse_url_to_img() {
						$(".qa-chat-item-data a").each( function() { 
							var regexr = "(https?:\/\/.*\.(?:png|jp?g|gif))";
							if( $(this).attr("href").match(regexr) != null ) {
								// $(this).empty();
								$(this).replaceWith("<img src=\'" + $(this).attr(\'href\') + "\' />"); // append
							}
						});
					}
					function ytVidId(a){var b=/^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/;return(a.match(b))?RegExp.$1:false};
					function parse_youtube_links(){$(".qa-chat-item-data a").each(function(){if(ytVidId($(this).attr("href"))){$(this).replaceWith(\'<br /><iframe width="420" height="345" src="http://www.youtube.com/embed/\'+ytVidId($(this).attr("href"))+\'" frameborder="0" allowfullscreen></iframe>\')}})};
					function parse_soundcloud_links(){$(\'a[href*="soundcloud.com"]\').each(function(){var a=$(this);$.getJSON("http://soundcloud.com/oembed?format=js&url="+a.attr("href")+"&iframe=true&callback=?",function(b){a.replaceWith(b.html)})})};
				});
			</script>';
			
			// query if date has been chosen
			if($chosenDay!='') {
				$queryHistory = qa_db_query_sub('SELECT p.postid, p.userid, u.handle AS username, p.message AS message,
											   p.posted, DATE_FORMAT(p.posted, "%Y-%m-%dT%H:%i:%sZ") AS posted_utc
											 FROM ^chat_posts p LEFT JOIN ^users u ON u.userid=p.userid
											 WHERE YEAR(posted) = #
											 AND MONTH(posted) = #
											 AND DAY(posted) = #
											 ORDER BY p.posted DESC', // LIMIT 80
											 substr($chosenDay,0,4), substr($chosenDay,5,2), substr($chosenDay,8,2) ); // UNIX_TIMESTAMP(posted)
				$messages = qa_db_read_all_assoc($queryHistory);

				// output parse elements
				$qa_content['custom'.++$c] = '<p class="qa-gray-button" id="parseMediaLinks" title="Wandelt alle Medien-Links in der History in abspielbare Videos und Musik um.">Medien-Links umwandeln</p>';
				
				// output chat history list
				$qa_content['custom'.++$c] = '<ul id="qa-chat-list">';
				$allChatsThatDay = '';
				
				foreach ( $messages as &$m ) {
					$m['message'] = $this->format_message( $m['message'] );
					$m['username'] = qa_html( $m['username'] );
					$allChatsThatDay .= '<li id="qa-chat-id-' . $m['postid'] . '" class="qa-chat-item">';
					$allChatsThatDay .= '  <div class="qa-chat-item-meta">';
					$allChatsThatDay .= '    <span class="qa-chat-item-who">';
					$allChatsThatDay .= '      <a class="qa-user-link" target="_blank" href="'.qa_path('user').'/' . urlencode($m['username']) . '">' . $m['username'] . '</a>';
					$allChatsThatDay .= '    </span><br>';
					$allChatsThatDay .= '    <span class="qa-chat-item-when" data-utc="' . $m['posted_utc'] . '" title="' . $m['posted_utc'] . '">' . date('H:i',strtotime($m['posted'])+2*60*60) . '</span>';  // +2*60*60 to do correct times for GMT+2 _ Y-m-d H:i:s
					$allChatsThatDay .= '  </div>';
					$allChatsThatDay .= '  <div class="qa-chat-item-data">' . $m['message'] . '</div>';
					$allChatsThatDay .= '</li>';
				}
				$qa_content['custom'.++$c] = $allChatsThatDay;
				$qa_content['custom'.++$c] = '</ul>';

				// debug: $qa_content['custom'.++$c] = "messages: " . http_build_query($messages);
				
			}
			
			return $qa_content;
		}
		
		// format message
		private function format_message( $msg )
		{
			// censor bad words
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
			$blockwordspreg = qa_get_block_words_preg();
			$msg = qa_block_words_replace( $msg, $blockwordspreg );

			$msg = qa_html( $msg );
			return qa_html_convert_urls($msg,true);
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/