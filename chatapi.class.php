<?php
	/**
	 * Chat-Api.com SDK
	 */

	class ChatApi
	{
		protected $token = '';
		protected $url = '';

		/**
		 * Create instance
		 * @param string $token
		 * @param string $url
		 * @return void
		 */
		public function __construct($token, $url = 'https://foo.chat-api.com')
		{
			$this->token = $token;
			$this->url = $url;
		}

		/**
		 * Generate query URL
		 * @param string $method
		 * @return string
		 */
		public function createUrl($method)
        	{
            		return $this->url.'/'.$method.'?token=' . $this->token;
        	}

		/**
		 * Send chat-api query
		 * @param string $method
		 * @param null|array $args
		 * @param string $qmethod
		 * @return bool|string
		 */
		public function query($method, $args = null, $qmethod = 'POST')
		{
			$url = $this->createUrl($method);

			if(isset($args) && is_array($args)) {
				$json = json_encode($args);

				$options = stream_context_create(['http' => [
					'method' => $qmethod,
					'header' => 'Content-type: application/json',
					'content' => $json
				]]);
			}

			return file_get_contents($url, false, isset($options) ? $options : null);
		}

		/**
         	 * Get all inbox messages (doesn't include queue)
		 * @return null|array
		 */
		public function getInbox()
		{
			return array_reverse(json_decode($this->query('messages'), 1)['messages']);
		}

		/**
		 * Get status (logged in / error / loading) for current instance
		 * @return string
		 */
		public function getStatus()
		{
		    $js = json_decode($this->query('status'), 1);

		    if(isset($js['accountStatus']))
			return $js['accountStatus'];
		    else
			return 'error';
		}

		/**
		 * Get all messages from chat by ID
		 * @param string $author
		 * @return array
		 */
		public function getChatMessages($author)
		{
			$ib = $this->getInbox();
			$msgs = [];

			foreach($ib as $message) {
				if(isset($author) && $author == $message['chatId'])
					$msgs[] = $message;
			}

			return $msgs;
		}

		/**
		 * Send message to phone number
		 * @param string $chat
		 * @param string $text
		 * @return boolean
		 */
		public function sendPhoneMessage($chat, $text)
		{
			return json_decode($this->query('sendMessage', ['phone' => $chat, 'body' => $text]), 1)['sent'];
		}

		/**
		 * Log out current instance
		 * @return null|array
		 */
		public function logout()
		{
		    return json_decode($this->query('logout', []));
		}

		/**
		 * Send reboot signal to instance
		 * @return null|array
		 */
		public function reboot()
		{
		    return json_decode($this->query('reboot', []));
		}

		/**
		 * Generate QR-code direct link
		 * @return string
		 */
		public function getQRCode()
		{
		    return $this->createUrl('qr_code');
		}

		/**
		 * Send message to chat (by not phone but chat ID)
		 * @param string $chat
		 * @param string $text
		 * @return boolean
		 */
		public function sendMessage($chat, $text)
		{
			return json_decode($this->query('sendMessage', ['chatId' => $chat, 'body' => $text]), 1)['sent'];
		}

		/**
		 * Generate dialogues list from messages
		 * @return array
		 */
		public function getDialogs()
		{
			$ib = $this->getInbox();
			$contacts = [];

			foreach($ib as $message) {
				$contacts[ $message['chatId'] ] = [
				    'name' => $message['senderName'],
                    'last' => mb_substr($message['body'], 0, 50),
                    'num' => '+' . (int)filter_var($message['chatId'], FILTER_SANITIZE_NUMBER_INT)
                ];
			}

			return $contacts;
		}
	}
