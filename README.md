# Chat-API.com SDK

This library makes work with chat-api.com easier

### Deprecated
#### The library was made quite a while ago and may be out of date. Please make a fork if you want to use it.

# Installation

Just download chatapi.class.php or use Composer:

```
composer require mikechip/chatapi
```

# Create instance

```
  $api = new ChatApi(
        '_token_', // Chat-Api.com token
        'https://foo.chat-api.com/instance1234' // Chat-Api.com API url
  );
```

# Get QR code

Proxying via PHP:
```
header('Content-Type: image/png');
readfile( $api->getQRCode() );
```

Or show directly:
```
<img src="<?=$api->getQRCode();?>" />
```

# Send message

```
die(
    ($api->sendPhoneMessage('+12345', 'It works!') == true) ? 'Message sent' : 'Fail'
);
```

# Support
Use **Issues** to contact me
