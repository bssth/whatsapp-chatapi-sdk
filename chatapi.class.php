<?php
    namespace Mike4ip;

    /**
     * Class ChatApi
     * @package Mike4ip
     */
    class ChatApi
    {
        protected $token = '';
        protected $url = '';
        protected $instance_url = 'https://us-central1-app-chat-api-com.cloudfunctions.net/';
        protected $instance_key = '12345';
        protected $_mem = [];

        /**
         * ChatApi constructor.
         * @param $token
         * @param string $url
         */
        public function __construct($token, $url = 'https://foo.chat-api.com')
        {
            $this->token = $token;
            $this->url = $url;
        }

        /**
         * Construct query URL
         * @param $method
         * @param array $args
         * @return string
         */
        public function createUrl($method, $args = [])
        {
            $args['token'] = $this->token;
            return $this->url.'/'.$method.'?'.http_build_query($args);
        }

        /**
         * Send chat-api query
         * @param string $method
         * @param null|array $args
         * @param string $qmethod
         * @return bool|string
         */
        public function query($method, $args = null, $qmethod = 'GET')
        {
            $url = $this->createUrl($method);

            if($qmethod == "POST" && isset($args) && is_array($args)) {
                $json = json_encode($args);

                $options = stream_context_create(['http' => [
                    'method' => $qmethod,
                    'header' => 'Content-type: application/json',
                    'content' => $json
                ]]);
            } elseif($qmethod == "GET" && isset($args) && is_array($args)) {
                $url = $this->createUrl($method, $args);

                $options = stream_context_create(['http' => [
                    'method' => $qmethod,
                    'header' => 'Content-type: application/json',
                ]]);
            }

            return file_get_contents($url, false, isset($options) ? $options : null);
        }

        /**
         * Recursively get all inbox messages
         * @return array|mixed|null
         */
        public function getFullInbox()
        {
            $inbox = [];

            while(true) {
                $inb = json_decode($this->query('messages', isset($offset) ? ['lastMessageNumber' => $offset] : ['last' => 1], "GET"), true);

                if(!is_array($inb) || !isset($inb['messages']) || !count($inb['messages'])) {
                    var_dump($inb);
                    break;
                }

                $offset = $inb['lastMessageNumber'];
                $inbox = array_merge($inbox, $inb['messages']);
            }

            return $inbox;
        }

        /**
         * @return false|string
         */
        public function createInstance()
        {
            $options = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => json_encode(['uid' => $this->instance_key, 'type' => 'whatsapp'])
            ]]);

            return json_decode(file_get_contents($this->instance_url.'newInstance', false, isset($options) ? $options : null), true);
        }

        /**
         * @param int $id
         * @return mixed
         */
        public function deleteInstance(int $id)
        {
            $options = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => json_encode(['uid' => $this->instance_key, 'instanceId' => (string)$id])
            ]]);

            return json_decode(file_get_contents($this->instance_url.'deleteInstance', false, isset($options) ? $options : null), true);
        }

        /**
         * Get all inbox messages (+ queue)
         * @return null|array
         */
        public function getInbox($offset = 0)
        {
            if(isset($this->_mem[$offset]) && is_array($this->_mem[$offset]))
                return $this->_mem[$offset];

            $inbox = json_decode($this->query('messages', ($offset > 0) ? ['lastMessageNumber' => $offset] : ['last' => 1]), 1);

            $newOffset = $inbox['lastMessageNumber'];
            $mess = $inbox['messages'];
            $inbox = [];

            foreach($mess as $val) {
                $val['offset'] = $newOffset;
                $inbox[] = $val;
            }

            if($offset < 50) {
                $que = json_decode($this->query('showMessagesQueue'), 1)['first100'];

                if (is_array($que)) {
                    foreach ($que as $k => $v) {
                        $inbox[] = [
                            'id' => $v['id'],
                            'body' => $v['body'],
                            'type' => $v['type'],
                            'senderName' => '',
                            'fromMe' => true,
                            'queue' => true,
                            'author' => $v['chatId'],
                            'time' => $v['last_try'],
                            'chatId' => $v['chatId']
                        ];
                    }
                }
            }

            usort($inbox, function ($a, $b) {
                if ($a['time'] == $b['time'])
                    return 0;
                return ($a['time'] < $b['time']) ? -1 : 1;
            });

            $this->_mem[$offset] = $inbox;
            return $inbox;
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
         * Gets currently installed webhook
         * @return null|array
         */
        public function getWebhook()
        {
            return json_decode($this->query('webhook', []));
        }

        /**
         * Installs new webhook
         * @return null|array
         */
        public function setWebhook($url = 'https://requestb.in/1f9aj261')
        {
            return json_decode($this->query('webhook', ['webhookUrl' => $url]));
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
         * Get screenshot URL of instance
         * @return string
         */
        public function getScreenshot()
        {
            return $this->createUrl('screenshot');
        }

        /**
         * Send file to chat
         * @param string $chat
         * @param string $body
         * @param string $filename
         * @return boolean
         */
        public function sendFile($chat, $body, $filename)
        {
            return json_decode($this->query('sendFile', ['chatId' => $chat, 'filename' => $filename, 'body' => $body]), 1)['sent'];
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
         * Generate conversations (dialogues-like) list from messages
         * @param int $offset
         * @return array
         */
        public function getDialogs($offset = 0)
        {
            $ib = $this->getInbox($offset);
            $contacts = [];

            foreach($ib as $message) {
                $contacts[ $message['chatId'] ] = [
                    'name' => $message['senderName'],
                    'answered' => $message['fromMe'],
                    'offset' => isset($message['offset']) ? $message['offset'] : 0,
                    'last' => mb_substr($message['body'], 0, 50),
                    'lastFull' => $message['body'],
                    'chatId' => $message['chatId'],
                    'num' => '+' . (int)filter_var($message['chatId'], FILTER_SANITIZE_NUMBER_INT)
                ];
            }

            return $contacts;
        }
    }
