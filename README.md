# Jcsp Social Sdk

Jscp Social Sdk Component

## Installation

Require this package, with composer, in the root directory of your project.

```bash
composer require jscp/social-sdk
```

Please note that this library requires at least PHP 7.2 installed.

## Usage

### Init the Client Instance

Use the bellow codes to init the client instance.

```php
// Set the client config
$config = [
    // The directory to store temporary files while sharing the resources. 
    // The default value is /tmp .
    'temp_storage_path' => "/tmp/temp_file", 
    
    // A full class name or an instance, it must implement the contract \Jcsp\SocialSdk\Contract\CacheInterface . 
    // The default value is \Jcsp\SocialSdk\Cache\Session::class .
    'cache' => CustomCache::class,
    
    // A full class name or an instance, it must implement the contract \Jcsp\SocialSdk\Contract\LoggerInterface. 
    // The default value is \Jcsp\SocialSdk\Log\NoLog::class .
    'logger' => CustomLogger::class;
];

// Set the real social media name
$socialMediaName = "Youtube";

// Set the auth config
$authConfig = new \Jcsp\SocialSdk\Model\AuthConfig();
$authConfig->setClientId("Client id");
$authConfig->setClientSecret("Client secret");
$authConfig->setRedirectUrl("Oauth callback url");

// Create client instance
$factory = new \Jcsp\SocialSdk\ClientFactory($config);
$client = $factory->createClient($socialMediaName);

// Init auth data
$client->initAuth($authConfig);
```

Some functions need to provide access token data, so must pass the `$accessToken` object.

```php
// Set the access token object
$accessToken = new \Jcsp\SocialSdk\Model\AccessToken();
$accessToken->setToken("Access token string");
$accessToken->setTokenSecret("Access token secret string");
$accessToken->setUserId("User id"); // optional, only used for some platforms
$accessToken->setRefreshToken("Refresh token string"); // optional, part of platforms support
$accessToken->setExpireTime($expireTimestramp); // optional, part of platforms support
$accessToken->setParams([]); // optional, for part of platforms

// Create client instance
$factory = new \Jcsp\SocialSdk\ClientFactory($config);
$client = $factory->createClient($socialMediaName);

// Init auth
$client->initAuth($authConfig, $accessToken);
```

### Authorization

#### Generate Authorization Url

```php
$authUrl = $client->generateAuthUrl();
```

#### Get Access Token

```php
$accessToken = $client->getAccessToken(array_merge($_GET, $_POST));
```

#### Refresh Access Token

```php
$allowRefreshToken = $client->allowRefreshToken();
$isAccessTokenExpired = $client->isAccessTokenExpired();
if $allowRefreshToken && $isAccessTokenExpired {
    $accessToken = $client->refreshAccessToken();
}
```

### User

#### Get User Profile

```php
// Need access token
$userProfile = $client->getUserProfile(); 
```

### Channel

Different platforms have different names for describing the area for sharing contents, like channel in Youtube, like page in Facebook, board in Pinterest, etc. 
For Convenient, here use the word channel to unity the name.

#### Get Channel List

```php
// Need access token
$channelList = $client->getShareChannelList();
```

### Share

#### Can share to user

```php
$canShareToUser = $client->canShareToUser();
```

#### Can share to channel

```php
$canShareToChannel = $client->canShareToChannel();
```

#### Share Video

```php
// Need access token

// If the access token has expired, try to refresh it.
if ($client->allowRefreshToken() && $client->isAccessTokenExpired()) {
    $client->refreshAccessToken();
}

// Share video
$params = new \Jcsp\SocialSdk\Model\VideoShareParams();
$params->setTitle("Share Title");
$params->setDescription("Share Description");
$params->setVideoUrl("Video Url");
$params->setThumbnailUrl("Thumbnail Url");
$params->setSocialId("User Id or Channel Id");
$params->setDisplayName("Username or Channel Name"); // Some platforms need social id, other use social display name.
$params->setAccessToken("Access Token String");
$params->setIsPostToChannel(false); // true: post to channel, false: post to user
$result = $client->shareVideo($params);
```

### Simulate Share

Use the other way to share content.

#### Create Simulate Client

```php
// Set the client config
$config = [
     // Specify these endpoint urls
    'simulate' => [
        'post_video_endpoint' => '',
        'query_post_task_endpoint' => '',
        'get_account_list_endpoint' => '',
    ],
    
    // A full class name or an instance, it must implement the contract \Jcsp\SocialSdk\Contract\LoggerInterface. 
    // The default value is \Jcsp\SocialSdk\Log\NoLog::class .
    'logger' => CustomLogger::class;
];

// Create client instance
$factory = new \Jcsp\SocialSdk\ClientFactory($this->config);
$client = $factory->createSimulateClient();
```

#### Get Account List

Get a list of the active accounts.

```php
$accountList = $client->getAccountList();
```

#### Make Simulate Video Post

To Make the simulate video post, in fact the simulate server would generate a task and return it. Save the task if which the method returned.

```php
// Make a task
$params = new \Jcsp\SocialSdk\Model\SimulateVideoPostParams();
$params->setVideoUrl("Video Url");
$params->setTitle("Title");
$params->setDescription("Description");
$params->setAccount("Account");
$params->setCallbackUrl("Public Url to handle Post Result Notice");
$params->setSocialMediaName("Full Platform Name, like Youtube");
$task = $client->simPostVideo($params);

// Get task info
$taskId = $task->getTaskId(); // Important, save it.
$taskStatus = $task->getTaskStatus();
$msg = $task->getMsg(); // Message for develop
$info = $task->getInfo(); // Message for operation staff
```

#### Handle Simulate Post Callback

After a simulate post finished, the simulate server would make post's type call the callback url which specified above, to notify the client server the result about post task.   

```php
$requestParams = array_merge($_GET, $_POST);
$task = $client->handleSimPostCallback($requestParams);
```

#### Query A Task Info

It's hard to guarantee the callback handler always runs stably, so here provide a method to query task info manually.

```php
$result = $client->queryTaskInfo("Task Id");
```

### Task Status

See the class `\Jcsp\SocialSdk\ModelSimulatePostTask`

## LICENSE

The Component is open-sourced software licensed under the [Apache license](LICENSE).
