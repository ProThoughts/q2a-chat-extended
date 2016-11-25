
Chat Room Extended (Question2Answer plugin)
=================================================

A plugin for [Question2Answer](http://www.question2answer.org) that allows users to live-chat. [Original source](https://github.com/svivian/q2a-chat-room) by Scott, extended by q2apro. 

Added features: 

 - number of new messages in tab title
 - sound notification
 - empty chat button for each user
 - weekly chat history email to admin
 - manual chat history triggered by admin
 

Installation & Usage
-------------------------------------------------

1. Download and extract the files to your plugins folder (e.g. `qa-plugins/chat`).

2. Go to Admin > Plugin and click the link to set up the database tables.

3. Go to Admin > Pages and add a link to to the chat room in your menu.

4. By default the plugin uses the URL `example.com/chat`. You can change this to something else using Q2A's QA_CONST_PATH_MAP feature. First, change the "URL of link" field from `chat` to your preferred URL, e.g. `chinwag`. Then add this to your `qa-config.php` file:

		$QA_CONST_PATH_MAP=array(
			'chat' => 'chinwag',
		);


Pay What You Like (to the original dev. Scott)
-------------------------------------------------

Most of my code is released under the open source GPL license, and provided with a 'Pay What You Like' approach. Feel free to download and modify the plugins/themes to suit your needs, and I hope you value them enough to donate a few dollars.

### [Donate here](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4R5SHBNM3UDLU)
