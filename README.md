# Chat-API.com SDK

Simple class makes work with chat-api.com easier

# Create instance

<pre>
  $api = new ChatApi(
        '_token_', // Chat-Api.com token
        'https://foo.chat-api.com/instance1234' // Chat-Api.com API url
  );
</pre>

# Get QR code

Server example:
<pre>
if(isset($_GET['qr']) {
  header('Content-Type: image/png');
  readfile( $api->getQRCode() );
}
</pre>

Client example:
<pre>
  <img id="qrcode">

  <script>
  $.get('index.php', {'qr': '1'}, function(i) {
      switch(i)
      {
          case 'init':
              text = 'Клиент не инициализирован';
              break;

          case 'error':
              text = 'Ошибка: Chat-Api не отвечает';
              break;

          case 'loading':
              text = 'Идёт загрузка с WhatsApp';
              break;

          case 'got qr code':
              text = 'Получен QR-код';
              $('#qrcode')[0].src = 'index.php?act=qr';
              break;

          case 'authenticated':
              text = 'Онлайн';
              break;
      }

      alert(text);
  });
</script>
</pre>

# Send message

<pre>
	die(
		($api->sendPhoneMessage('+12345', 'It works!') == true) ? 'Message sent' : 'Fail'
	);
</pre>
