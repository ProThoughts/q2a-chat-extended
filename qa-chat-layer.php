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
				h1, #chat-hints, #qa-chat-form { margin-left:10px;}
				#chat-hints {
					line-height:150%;
					margin-bottom:20px;
				}
				#qa-chat-form { 
					max-width:711px;
				    padding:12px 10px;
				    border-radius:5px;
					background: #4c9607;
					background: -moz-linear-gradient(top, #4c9607 0%, #69af07 100%);
					background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#4c9607), color-stop(100%,#69af07));
					background: -webkit-linear-gradient(top, #4c9607 0%,#69af07 100%);
					background: -o-linear-gradient(top, #4c9607 0%,#69af07 100%);
					background: -ms-linear-gradient(top, #4c9607 0%,#69af07 100%);
					background: linear-gradient(to bottom, #4c9607 0%,#69af07 100%);
					-webkit-box-shadow: 0px 1px 2px #595;
					-moz-box-shadow: 0px 1px 2px #595;
					box-shadow:0px 1px 2px #595;
				}
				.qa-chat-item img {
					max-width: 570px;
					border:1px solid #DDD;
					padding:8px;
					background:#FFF;
				}
				.qa-chat-post { width: 100%; }
				.qa-chat-post:focus{box-shadow: 0 0 2px #007eff; -moz-box-shadow: 0 0 2px #007eff; -webkit-box-shadow: 0 0 2px #007eff; }
				#qa-chat-list, .qa-chat-item, #qa-chat-user-list, .qa-chat-user-item { display: block; list-style: none; margin: 0; padding: 0; font-size: 13px;  line-height: 1.4; }
				#qa-chat-list { max-width: 728px; margin: 1em auto; background:#FFF; border:1px solid #CCC;}
				.qa-chat-item { overflow: hidden; padding: 4px 0; border-top: 1px solid #eee; padding:8px 0;}
				.qa-chat-item:first-child {border-top:0;}
				.qa-chat-item:nth-child(even) {background: #EEE; }
				.qa-chat-item:last-child { border-bottom: 1px solid #eee; }
				.qa-chat-item-meta { float: left; width: 110px; padding-right: 20px; font-size: 11px; color: #999; text-align: right; }
				.qa-chat-item-data { float: left; width: 598px; }
				.qa-chat-user-item { padding: 2px 4px; }
				/*.qa-chat-user-item:hover { background: rgba(255,255,255,0.4); }*/
				.qa-chat-idle, .qa-chat-idle > a { color: #aaa; }
				.qa-chat-kick { float: right; cursor: pointer; width: 10px; height: 10px; border-radius: 10px; background: #999; margin-top: 5px; }
				.qa-chat-kick:hover { background: #f00; }
				.qa-chat-service { background: #fffae4; }
				
				.qa-sidebar,#ytubeLink,#androidLink {display:none;}
				
				#admin_edit_chat { position:absolute;right:10px;top:35px; }
				#hideRecentChats { position:absolute;right:10px;top:75px; }
				#soundToggle { position:absolute;right:10px;top:110px; }
				
				/* smartphones */
				@media only screen and (max-width:480px) {
					.qa-main {
						width:96%;
						padding-left:3%;
					}
					h1, #chat-hints, #qa-chat-form {
						margin-left:0;
					}
					#hideRecentChats, #soundToggle {
						position:static;		
					}
					.qa-chat-item {
						padding:8px 5px 8px 5px;
					}
					.qa-chat-item-meta {
						width:auto;
						text-align:left;
					}
					.qa-chat-item-data {
						width:97%;		
					}
				}

			</style>';

			$this->output_raw( $chat_css );
		}
	}

}
