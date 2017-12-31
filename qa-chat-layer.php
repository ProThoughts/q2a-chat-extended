<?php
/*
	Question2Answer Chat Room plugin, v1.5
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_html_theme_layer extends qa_html_theme_base
{
	private $cssopt = 'chat_room_css';

	function head_custom()
	{
		parent::head_custom();

		if ( !in_array($this->template, ['plugin', 'custom']) || ($this->request != 'chat' && $this->request != 'chathistory') )
			return;

		$hidecss = qa_opt($this->cssopt) === '1';

		if ( !$hidecss )
		{
			$chat_css = '
			<style>
				#qa-chat-sidebar {
					padding:10px 20px;
				}
				h1, #chat-hints, #qa-chat-form { 
					margin-left:10px;
				}
				#chat-hints {
					line-height:150%;
					margin-bottom:20px;
				}
				#qa-chat-form { 
					max-width:711px;
				    padding:12px 10px 5px 10px;
				    border-radius:2px;
					background: #27b94f;
				}
				.qa-chat-item img {
					max-width: 570px;
					border:1px solid #DDD;
					padding:8px;
					background:#FFF;
				}
				.qa-chat-item img.chat-avatar-img {
					padding:0;
					background:transparent;
					margin-right:5px;
				}
				.qa-chat-post { 
					width: 85%; 
					margin:0 10px 0 0;
				}
				.qa-chat-post:focus{ box-shadow: 0 0 2px #007eff; }
				#qa-chat-list, .qa-chat-item, #qa-chat-user-list, .qa-chat-user-item { display: block; list-style:none; margin: 0; padding: 0; font-size: 13px; line-height: 1.4; }
				#qa-chat-user-list {
					list-style:square inside none;
					color:#000;
				}
				#qa-chat-list {
					display:table;
					width:100%;
					max-width: 728px; 
					margin: 1em 0 1em 10px; 
				}
				.qa-chat-item { 
					display:table-row;
					overflow: hidden; 
					border-top: 1px solid #eee; 
					/*padding:8px 0;*/
				}
				.qa-chat-item:first-child {border-top:0;}
				.qa-chat-item:nth-child(even) {background: #EEE; }
				.qa-chat-item:last-child { border-bottom: 1px solid #eee; }
				.qa-chat-item-avatar { 
					display:table-cell;
					width: 7%; 
					max-width: 35px; 
					vertical-align: top;
				}
				.qa-chat-item-meta { 
					display:table-cell;
					width: 15%; 
					max-width: 110px; 
					padding-right: 20px; 
					font-size: 11px; 
					color: #999; 
					/*text-align: right;*/ 
				}
				.qa-chat-item-data { 
					display:table-cell;
					width: 80%; 
					max-width: 598px; 
				}
				.qa-chat-item-avatar,
				.qa-chat-item-meta, 
				.qa-chat-item-data { 
					padding:5px 0;
				}
				.qa-chat-user-item { padding: 2px 4px; }
				/*.qa-chat-user-item:hover { background: rgba(255,255,255,0.4); }*/
				.qa-chat-idle, .qa-chat-idle > a { color: #aaa; }
				.qa-chat-kick { float: right; cursor: pointer; width: 10px; height: 10px; border-radius: 10px; background: #999; margin-top: 5px; }
				.qa-chat-kick:hover { background: #f00; }
				.qa-chat-service { background: #fffae4; }
				
				.qa-sidebar,#ytubeLink,#androidLink {display:none;}
				
				#admin_edit_chat { position:absolute;right:10px;top:35px; }
				#hideRecentChats { position:absolute;right:10px;top:75px; }
				#soundToggle { position:absolute;right:10px;top:115px; }
				
				/* smartphones */
				@media only screen and (max-width:480px) 
				{
					.qa-main {
						width:100%;
						padding-left:3%;
					}
					h1, #chat-hints, #qa-chat-form {
						margin-left:0;
					}
					#hideRecentChats, #soundToggle {
						position:static;		
					}
					.qa-chat-post { 
						width: 77%; 
					}
					.qa-chat-sendbutton { 
						padding:7px;
						height:auto;
						margin:0;
					}
					.qa-chat-item {
						padding:10px 5px 5px 5px;
					}
					.qa-chat-item-meta {
						width:auto;
						text-align:left;
					}
					.qa-chat-item-data {
						width:97%;		
					}
					#chat-hints {
						display:none;
					}
					#qa-chat-form {
						width:330px;
						padding:6px 5px 5px 6px;
					}
					
					#qa-chat-list, .qa-chat-item {
						display:block;
						margin-left:0;
					}
					.qa-chat-item-avatar, 
					.qa-chat-item-meta, 
					.qa-chat-item-data { 
						display:inline-block;
					}
					.qa-chat-item-avatar, .qa-chat-item-meta {
						padding:0;
					}
					.qa-chat-item-avatar {
						width:40px;
					}
					.qa-chat-item {
						border-top:0;
					}
				}

			</style>';

			$this->output_raw( $chat_css );
		}
	}

}
