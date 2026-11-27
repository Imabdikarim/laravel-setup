<?php

/*
|--------------------------------------------------------------------------
| CORS
|--------------------------------------------------------------------------
|
| Lets the website (running on another address, like http://localhost:5500)
| read the public projects endpoint. Only GET on /api/public/* is opened up,
| and no cookies are shared.
|
| For a real launch, replace '*' with your website's address, for example
| ['https://basiit.com'].
|
*/

return [
    'paths' => ['api/public/*'],
    'allowed_methods' => ['GET', 'OPTIONS'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];