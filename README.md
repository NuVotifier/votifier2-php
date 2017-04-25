# votifier2-php

Votifier protocol v2 client for PHP.

## Example

```php
    $vote = new \Imaginarycode\Votifier2\Vote("tuxed", "127.0.0.1", "Test", NULL);
    $service = new \Imaginarycode\Votifier2\Server("127.0.0.1", 8192, "TOKEN");
    $service->sendVote($vote);
```