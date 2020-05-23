# STDOUT MVC API

Table of contents:

- [About](#about)
- [Configuration](#configuration)
- [Execution](#execution)
    - [Initialization](#initialization)
    - [Binding Events](#binding-events)
    - [Configuring Shared Variables](#configuring-shared-variables)
    - [Handling](#handling)
- [Installation](#installation)
- [Unit Tests](#unit-tests)
- [Reference Guide](#reference-guide)

## About

This API was created to efficiently handle web requests into server responses using a MVC version where views and models are expected to be independent while controllers mediate between the two based on user request. Designed with modularity, efficiency and simplicity at its foundation, API is both object and event oriented: similar to JavaScript, it allows developers to bind logic that will be executed when predefined events are reached while handling.

![diagram](https://www.lucinda-framework.com/public/images/svg/stdout-mvc-api.svg)

API does nothing more than standard MVC logic, so in real life it expects a web framework to be built on top to add further features (eg: DB connectivity). In order to use it, following steps are required from developers:

- **[configuration](#configuration)**: setting up an XML file where this API is configured
- **[initialization](#initialization)**: instancing [Lucinda\STDOUT\FrontController](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/FrontController.php), a [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php) able to handle requests into responses later on based on above two
- **[binding events](#binding-events)**: setting up [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php) classes that will be instanced and *run* when predefined events are reached during handling process
- **[configuring shared variables](#configuring-shared-variables)**: extend [Lucinda\STDOUT\Attributes](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Attributes.php) class to encapsulate variables specific to your project, to be shared between event listeners and controllers
- **[handling](#handling)**: calling *run* method @ [Lucinda\STDOUT\FrontController](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/FrontController.php)  to finally handle requests into responses, triggering events above (if any)

API is fully PSR-4 compliant, only requiring PHP7.1+ interpreter and SimpleXML extension. To quickly see how it works, check:

- **[installation](#installation)**: describes how to install API on your computer, in light of steps above
- **[reference guide](#reference-guide)**: describes all API classes, methods and fields relevant to developers
- **[unit tests](#unit-tests)**: API has 100% Unit Test coverage, using [UnitTest API](https://github.com/aherne/unit-testing) instead of PHPUnit for greater flexibility
- **[example](https://github.com/aherne/php-servlets-api/tree/v3.0.0/tests/FrontController.php)**: shows a deep example of API functionality based on [Lucinda\STDOUT\FrontController](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/FrontController.php) unit test

## Configuration

To configure this API you must have a XML with following tags inside:

- **[application](#application)**: (mandatory) configures your application on a general basis
- **[formats](#formats)**: (mandatory) configures formats in which your application is able to resolve responses to
- **[routes](#routes)**: (mandatory) configures routes that bind requested resources to controllers and views
- **[session](#session)**: (optional) configures options to use automatically when creating sessions
- **[cookies](#cookies)**: (optional) configures options to use automatically when setting cookies

### Application

Maximal syntax of this tag is:

```xml
<application default_format="..." default_page="..." version="...">
	<paths controllers="..." resolvers="..." validators="..." views="..."/>
</application>
```

Where:

- **application**: (mandatory) holds settings to configure your application
    - *default_format*: (mandatory) defines default display format (extension) for your application. Must match a *name* attribute @ **[formats](#formats)**! Example: "html"
    - *default_page*: (mandatory) defines implicit page when your site is invoked with none (eg: http://www.example.com). Must match a *url* attribute @ **[routes](#routes)**! Example: "index"
    - *version*: (optional) defines your application version, to be used in versioning static resources. Example: "1.0.0"
    - **paths**: (optional) holds where core components used by API are located based on attributes:
        - *controllers*: (optional) holds folder in which user-defined controllers will be located. Each controller must be a [Lucinda\STDOUT\Controller](#abstract-class-controller) instance!  
        - *resolvers*: (mandatory) holds folder in which user-defined view resolvers will be located. Each resolver must be a [Lucinda\STDOUT\ViewResolver](#abstract-class-viewresolver) instance!
        - *validators*: (optional) holds folder in which user-defined parameter validators will be located. Each validator must be a [Lucinda\STDOUT\EventListeners\Validators\ParameterValidator](#abstract-class-parametervalidator) instance!
        - *views*: (optional) holds folder in which user-defined views will be located (if HTML).

Tag example:

```xml
<application default_format="html" default_page="index" version="1.0.1">
	<paths controllers="application/controllers" resolvers="application/resolvers" validators="application/validators" views="application/views"/>
</application>
```

### Formats

Maximal syntax of this tag is:

```xml
<formats>
	<format name="..." content_type="..." class="..." {OPTIONS}/>
	...
</formats>
```
Where:

- **formats**: (mandatory) holds settings to resolve views based on response format (extension). Holds a child for each format supported:
    - **format**: (mandatory) configures a format-specific view resolver based on attributes:
        - *name*: (mandatory) defines display format (extension) handled by view resolver. Example: "html"
        - *content_type*: (mandatory) defines content type matching display format above. Example: "text/html"
        - *class*: (mandatory) name of user-defined class that will resolve views (including namespace or subfolder), found in folder defined by *resolvers* attribute of **paths** tag @ **[application](#application)**. Must be a [Lucinda\STDOUT\ViewResolver](#abstract-class-viewresolver) instance!
        - {OPTIONS}: a list of extra attributes necessary to configure respective resolver identified by *class* above            

Tag example:

```xml
<formats>
    <format name="html" content_type="text/html" class="ViewLanguageRenderer" charset="UTF-8"/>
    <format name="json" content_type="application/json" class="JsonRenderer" charset="UTF-8"/>
</formats>
```

### Routes

Maximal syntax of this tag is:

```xml
<routes>
    <route url="..." controller="..." view="..." format="..." method="...">
        <parameter name="..." validator="..." mandatory="..."/>
        ...
    </route>
    ...
</routes>
```

Where:

- **routes**: (mandatory) holds routing rules for handled requests
    - **route**: (optional) holds routing rules specific to a requested URI based on attributes:
        - *url*: (mandatory) requested requested resource url without trailing slash. Can be an exact url (eg: *foo/bar*) or a url pattern (eg: *user/(id)*). If pattern is used, each variable must be named and enclosed in parenthesis!
        - *controller*: (optional) holds user-defined controller (including namespace or subfolder) that will mitigate requests and responses based on models, found in folder defined by *controllers* attribute of **paths** tag @ **[application](#application)**. Must be a [Lucinda\STDOUT\Controller](#abstract-class-controller) instance!
        - *view*: (optional) holds user-defined template file that holds the recipe of response for request. Example: "homepage"
        - *format*: (optional) holds response format, if different from *default_format* @ [application](#application). Must match a *name* attribute @ **[formats](#formats)**! Example: "json"
        - *method*: (optional) holds single HTTP method by which resource MUST be requested with. If request comes with a different method, a [Lucinda\STDOUT\MethodNotAllowedException](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/MethodNotAllowedException.php) is thrown!

Each **route** tag can hold one or more rules to validate values of request and path parameters that came along with request. Each parameter corresponds to a **parameter** tag, where validation is configurable based on attributes:

- *name*: (mandatory) name of request or path parameter you want to validate. Examples:
    - *foo*, if request was GET and came with query-string *?foo=bar*
    - *id*, if route url is *user/(id)*
- *validator*: (mandatory) holds user-defined class (including namespace or subfolder) that validates value of parameter, found in folder defined by *validators* attribute of **paths** tag @ **[application](#application)**. Must be a [Lucinda\STDOUT\EventListeners\Validators\ParameterValidator](#abstract-class-parametervalidator) instance!
- *mandatory*: (optional) holds whether or not parameter is mandatory (value can be 0 or 1). If none, mandatory (1) is assumed!

**^ If parameter names collide, path parameters take precedence over request parameters!**

Tag example:

```xml
<routes>
    <route url="index" controller="HomepageController" view="index"/>
    <route url="user/(id)" controller="UserInfoController" view="user-info" method="GET">
        <parameter name="id" validator="UserNameValidator"/>
    </route>
</routes>
```

### Session

Maximal syntax of this tag is:

```xml
<session save_path="..." name="..." expired_time="..." expired_on_close="..." https_only="..." headers_only="..." referrer_check="..." handler="..." auto_start="...">
```

Where:

- *save_path*: (optional) absolute path in which sessions are saved on server. Example: "/tmp/sessions/"
- *name*: (optional) name of the session which is used as cookie name (default: PHPSESSID). Example: "SESSID"
- *expired_time*: (optional) number of seconds after which data will be garbage collected. Example: "60"
- *expired_on_close*: (optional) number of seconds session cookie is expected to survive in client browser after close. Example: "120"
- *https_only*: (optional) marks session cookie as accessible over secure HTTPS connections. Value: "1"
- *headers_only*: (optional) marks session cookie as accessible only through the HTTP protocol. Value: "1"
- *referrer_check*: (optional) substring you want to check each HTTP Referer for in order to validate session cookie. Example: "Chrome"
- *handler*: (optional) user-defined session handler (including namespace or subfolder) that implements [\SessionHandlerInterface](https://www.php.net/manual/en/class.sessionhandlerinterface.php). Example: "application/models/RedisHandler"
- *auto_start*: (optional) signals session to be started automatically for each request. Value: "1"

Tag example:

```xml
<session save_path="/tmp/sessions/" name="SESSID" expired_time="60" expired_on_close="120" https_only="1" headers_only="1" referrer_check="Chrome" handler="application/models/RedisHandler" auto_start="1">
```

### Cookies

Maximal syntax of this tag is:

```xml
<cookies path="..." domain="..." https_only="..." headers_only="...">
```

Where:

- *path*: (optional) path on the server in which the cookie will be available on. Example: "/foo/"
- *domain*: (optional) the (sub)domain that the cookie is available to. Example: "www.example.com"
- *https_only*: (optional) signals cookie should only be transmitted over a secure HTTPS connection from the client. Value: "1"
- *headers_only*: (optional) signals cookie should be made accessible only through the HTTP protocol. Value: "1"

Tag example:

```xml
<cookies path="/" domain="example.com" https_only="1" headers_only="1">
```

#### How to set path

Table below shows the effects of *path* attribute:

| Value | Effect |
| --- | --- |
|  |  the cookie will be available in the current directory that the cookie is being set in (default) |
| / | the cookie will be available within the entire domain (recommended) |
| /foo/ | the cookie will only be available within the /foo/ directory and all sub-directories such as /foo/bar/ of domain |

#### How to set domain

Table below shows the effects of *domain* attribute:

| Value | Effect |
| --- | --- |
|  | makes cookie available to current subdomain |
| www.example.com | makes cookie available to that subdomain and all other sub-domains of it (i.e. w2.www.example.com) |
| example.com | makes cookie available to the whole domain (including all subdomains of it) |

## Execution

### Initialization

Now that developers have finished setting up XML that configures the API, they are finally able to initialize it by instantiating [Lucinda\STDOUT\FrontController](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/FrontController.php).

Apart of method *run* required by [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php) interface it implements, class comes with following public method:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string $documentDescriptor, [Lucinda\STDOUT\Attributes](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Attributes.php) $attributes | void | Records user defined XML and attributes for later handling |

Where:

- *$documentDescriptor*: relative location of XML [configuration](#configuration) file. Example: "configuration.xml"
- *$attributes*: see **[configuring shared variables](#configuring-shared-variables)**.

### Binding Events

As mentioned above, API allows developers to bind listeners to handling lifecycle events. Each event  type corresponds to a abstract [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php) class:

| Type | Class | Description |
| --- | --- | --- |
| start | [Lucinda\STDOUT\EventListeners\Start](#abstract-class-eventlisteners-start) | Ran before [configuration](#configuration) XML is read |
| application | [Lucinda\STDOUT\EventListeners\Application](#abstract-class-eventlisteners-application) | Ran after [configuration](#configuration) XML is read into [Lucinda\STDOUT\Application](#class-application) |
| request | [Lucinda\STDOUT\EventListeners\Request](#abstract-class-eventlisteners-request) | Ran after user request is read into [Lucinda\STDOUT\Request](#class-request), [Lucinda\STDOUT\Session](#class-session) and [Lucinda\STDOUT\Cookies](#class-cookies) objects |
| response | [Lucinda\STDOUT\EventListeners\Response](#abstract-class-eventlisteners-response) | Ran after [Lucinda\STDOUT\Response](#class-response) body is compiled but before it's rendered |
| end | [Lucinda\STDOUT\EventListeners\End](#abstract-class-eventlisteners-end) | Ran after [Lucinda\STDOUT\Response](#class-response) was rendered back to caller  |

Listeners must extend matching event class and implement required *run* method holding the logic that will execute when event is triggered. It is required for them to be registered BEFORE **[handling](#handling)** via [Lucinda\STDOUT\FrontController](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/FrontController.php) method:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| addEventListener | string $type, string $className | void | Binds a listener to an event type |

Where:

- *$type*: event type (see above) encapsulated by enum [Lucinda\STDOUT\EventType](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/EventType.php)
- *$className*: listener *class name*, including namespace and subfolder, found in *folder* defined when [Lucinda\STDOUT\Attributes](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Attributes.php) was instanced.

To better understand how *folder* and *$className* above play together in locating event listener later on, let's take a look at table below:

| folder | $className | File Loaded | Class Instanced |
| --- | --- | --- | --- |
| application/events | TestEvent | application/events/TestEvent.php | TestEvent |
| application/events | foo/TestEvent | application/events/foo/TestEvent.php | TestEvent |
| application/events | \Foo\TestEvent | application/events/TestEvent.php | \Foo\TestEvent |
| application/events | foo/\Bar\TestEvent | application/events/foo/TestEvent.php | \Bar\TestEvent |

### Configuring Shared Variables

API allows event listeners to set variables that are going to be made available to subsequent event listeners and controllers. For each variable there is a:

- *setter*: to be ran once by a event listener
- *getter*: to be ran by subsequent event listeners and controllers

API comes with [Lucinda\STDOUT\Attributes](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Attributes.php), which holds the foundation every site must extend in order to set up its own variables. Class comes with following generic methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string $eventsFolder | void | Sets folder in which user-defined event listeners are located |
| getEventsFolder | void | string | Gets folder in which user-defined event listeners are located |

While handling request to response, it needs to add its own [Lucinda\STDOUT\EventListeners\RequestValidator](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/EventListeners/RequestValidator.php) in order to bind route requested to a [route](#routes) XML tag. Results of this binding are saved into [Lucinda\STDOUT\Attributes](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Attributes.php) via setters and made available via following getters:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getValidPage | void | string | Gets value of *url* attribute of matching [route](#routes) XML tag found either explicitly (via *url* attribute @ [route](#routes)) or implicitly (via *default_page* attribute @ [application](#application)) |
| getValidFormat | void | string | Gets value of *name* attribute of matching [format](#formats) XML tag found either explicitly (via *format* attribute @ [route](#routes)) or implicitly (via *default_format* attribute @ [application](#application)) |
| getPathParameters | void | array | Gets all path parameters detected from parameterized *url* attribute of matching [route](#routes) XML tag |
| getPathParameters | string $name | string | Gets value of a path parameter detected by its name. Returns NULL if not existing! |
| getValidParameters | void | array | Gets all parameter validation results by parameter name and validation result |
| getValidParameters | string $name | string | Gets parameter validation result by parameter name. Returns NULL if not existing! |

Unless your site is extremely simple, it will require developers to extend this class and add further variables, for whom setters and getters must be defined!

### Handling

Once above steps are done, developers are finally able to handle requests into responses via *run* method of [Lucinda\STDOUT\FrontController](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/FrontController.php), which:

- detects [Lucinda\STDOUT\EventListeners\Start](#abstract-class-eventlisteners-start) listeners and executes them in order they were registered
- encapsulates [configuration](#configuration) XML file into [Lucinda\STDOUT\Application](#class-application) object
- detects [Lucinda\STDOUT\EventListeners\Application](#abstract-class-eventlisteners-application) listeners and executes them in order they were registered
- encapsulates request information based on $\_SERVER superglobal into [Lucinda\STDOUT\Request](#class-request) object
- encapsulates session information based on $\_SESSION superglobal as well as operations available into [Lucinda\STDOUT\Session](#class-session) object
- encapsulates cookie operations and variables based on $\_COOKIE  superglobal as well as operations available into [Lucinda\STDOUT\Cookie](#class-cookie) object
- detects [Lucinda\STDOUT\EventListeners\Request](#abstract-class-eventlisteners-request) listeners and executes them in order they were registered
- initializes empty [Lucinda\STDOUT\Response](#class-response) based on information detected above from request or XML
- locates [Lucinda\STDOUT\Controller](#abstract-class-controller) based on information already detected and, if found, executes it in order to bind models to views
- locates [Lucinda\STDOUT\ViewResolver](#abstract-class-viewresolver) based on information already detected and executes it in order to feed response body based on view
- detects [Lucinda\STDOUT\EventListeners\Response](#abstract-class-eventlisteners-response) listeners and executes them in order they were registered
- sends [Lucinda\STDOUT\Response](#class-response) back to caller, containing headers and body
- detects [Lucinda\STDOUT\EventListeners\End](#abstract-class-eventlisteners-end) listeners and executes them in order they were registered

All components that are in developers' responsibility ([Lucinda\STDOUT\Controller](#abstract-class-controller), [Lucinda\STDOUT\ViewResolver](#abstract-class-viewresolver), along with event listeners themselves, implement [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php) interface, which only comes with a single method:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| run | void | void | Executes component's logic |

## Installation

First choose a folder, then write this command there using console:

```console
composer require lucinda/mvc
```

Rename folder above to DESTINATION_FOLDER then create an .htaccess file there with following content:

```console
RewriteEngine on
Options -Indexes
ErrorDocument 404 default
RewriteCond %{REQUEST_URI} !^/public
RewriteRule ^(.*)$ index.php
```

Then create a *configuration.xml* file holding configuration settings (see [configuration](#configuration) above) and a *index.php* file (see [initialization](#initialization) above) in project root with following code:

```php
require(__DIR__."/vendor/autoload.php");

$controller = new Lucinda\STDOUT\FrontController("configuration.xml", new Lucinda\STDOUT\Attributes("application/events"));
// TODO: add event listeners here
$controller->run();
```

## Unit Tests

For tests and examples, check following files/folders in API sources:

- [test.php](https://github.com/aherne/php-servlets-api/tree/v3.0.0/test.php): runs unit tests in console
- [unit-tests.xml](https://github.com/aherne/php-servlets-api/tree/v3.0.0/unit-tests.xml): sets up unit tests and mocks "loggers" tag
- [tests](https://github.com/aherne/php-servlets-api/tree/v3.0.0/tests): unit tests for classes from [src](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src) folder

## Reference Guide

These classes are fully implemented by API:

- [Lucinda\STDOUT\Application](#class-application): reads [configuration](#configuration) XML file and encapsulates information inside
- [Lucinda\STDOUT\Request](#class-request): encapsulates user request based on information in superglobals: $\_SERVER, $\_POST, $\_GET
    - [Lucinda\STDOUT\Request\Client](#class-request-client): encapsulates client information detected from request
    - [Lucinda\STDOUT\Request\Server](#class-request-server): encapsulates server information detected from request
    - [Lucinda\STDOUT\Request\URI](#class-request-uri): encapsulates uri information detected from request
    - [Lucinda\STDOUT\Request\UploadedFiles\File](#class-request-uploadedfile): encapsulates information about an uploaded file
- [Lucinda\STDOUT\Session](#class-session): encapsulates operations to perform with a http session mapped to $\_SESSION superglobal
- [Lucinda\STDOUT\Cookies](#class-cookies): encapsulates operations to perform with a http cookie mapped to $\_COOKIE superglobal
- [Lucinda\STDOUT\Response](#class-response): encapsulates response to send back to caller
    - [Lucinda\STDOUT\Response\Status](#class-response-status): encapsulates response HTTP status
    - [Lucinda\STDOUT\Response\View](#class-response-view): encapsulates view template and data that will be bound into a response body

Apart of classes mentioned in **[binding events](#binding-events)**, following abstract classes require to be extended by developers in order to gain an ability:

- [Lucinda\STDOUT\Controller](#abstract-class-controller): encapsulates binding [Lucinda\STDOUT\Request](#class-request) to [Lucinda\STDOUT\Response](#class-response) based on user request and XML info
- [Lucinda\STDOUT\ViewResolver](#abstract-class-viewresolver): encapsulates conversion of [Lucinda\STDOUT\Response\View](#class-response-view) into a [Lucinda\STDOUT\Response](#class-response) body

### Class Application

Class [Lucinda\STDOUT\Application](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Application.php) encapsulates information detected from XML and defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getVersion | void | string | Gets application version based on *version* attribute @ [application](#application) XML tag |
| getTag | string $name | [\SimpleXMLElement](https://www.php.net/manual/en/class.simplexmlelement.php) | Gets a pointer to a custom tag in XML root |

### Class Request

Class [Lucinda\STDOUT\Request](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Request.php) encapsulates information detected about user request based on superglobals ($\_SERVER, $\_GET, $\_POST, $\_FILES) and defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getClient | void | [Lucinda\STDOUT\Request\Client](#class-request-client) | Gets client information detected from request. |
| getInputStream | void | string | Gets access to input stream for binary requests. |
| getMethod | void | string | Gets request HTTP method (REQUEST_METHOD @ $\_SERVER). |
| getProtocol | void | string | Gets request protocol (HTTPS @ $\_SERVER)
| getServer | void | [Lucinda\STDOUT\Request\Server](#class-request-server) | Gets server information detected from request. ||
| getUri | void | [Lucinda\STDOUT\Request\URI](#class-request-uri) | Gets path information detected from request. |
| headers | void | array | Gets all request headers received from client by standard ISO name |
| headers | string $name | string | Gets value of request header by name or NULL if not found. |
| parameters | void | array | Gets all request parameters received from client matching current request method ($\_GET, $\_POST, etc). |
| parameters | string\|int $name | mixed | Gets value of request parameter by name or NULL if not found. |
| uploadedFiles | void | array | Gets all uploaded files received from client, each encapsulated as [Lucinda\STDOUT\Request\UploadedFiles\File](#class-request-uploadedfile) based on $\_FILES |
| uploadedFiles | string\|int $name | mixed | Gets [Lucinda\STDOUT\Request\UploadedFiles\File](#class-request-uploadedfile) received by name or NULL if not found. |

#### How are uploaded files processed

Unlike $\_FILES superglobal, like parameters sent by $\_GET or $\_POST, API preserves structure sent in form, so:

```html
<input type="file" name="asd[fgh]"/>
```

Once posted, *uploadedFiles* method will return:

```php
[
  "asd"=>["fgh"=>object]
]
```

Where *object* is a [Lucinda\STDOUT\Request\UploadedFiles\File](#class-request-uploadedfile)!  

### Class Request Client

Class [Lucinda\STDOUT\Request\Client](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Request/Client.php) encapsulates client information detected from request based on $\_SERVER superglobal  and defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getName | void | string | Gets client server name (REMOTE_HOST @ $\_SERVER) |
| getIP | void | string | Gets client ip (REMOTE_ADDR @ $\_SERVER) |
| getPort | void | int | Gets client port (REMOTE_PORT @ $\_SERVER) |

### Class Request Server

Class [Lucinda\STDOUT\Request\Server](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Request/Server.php) encapsulates web server information detected from request based on $\_SERVER superglobal  and defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getName | void | string | Gets server name (SERVER_NAME @ $\_SERVER) |
| getIP | void | string | Gets server ip (SERVER_ADDR @ $\_SERVER) |
| getPort | void | int | Gets server port (SERVER_PORT @ $\_SERVER) |
| getEmail | void | int | Gets server admin email (SERVER_ADMIN @ $\_SERVER) |
| getSoftware | void | int | Gets server software info (SERVER_SOFTWARE @ $\_SERVER) |

### Class Request URI

Class [Lucinda\STDOUT\Request\URI](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Request/URI.php) encapsulates path information detected from request based on $\_SERVER superglobal  and defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getContextPath | void | string | Gets context path based on requested URI (DOCUMENT_ROOT & SCRIPT_FILENAME @ $\_SERVER) |
| getPage | void | string | Gets resource (page) requested based on requested URI (REQUEST_URI @ $\_SERVER) |
| getQueryString | void | string | Gets query string that came with URI (QUERY_STRING @ $\_SERVER) |
| parameters | void | array | Gets query string parameters that came with URI ($\_GET) |
| parameters | string\|int $name | mixed | Gets value of query string parameter by name or NULL if not found. |

#### How is requested uri processed

API breaks down requested URI (value of REQUEST_URI param @ $\_SERVER) into relevant components based on following algorithm:

- it first detects **context path** by stripping SCRIPT_FILENAME from DOCUMENT_ROOT. Normally, context path is empty since sites are deployed to a specific hostname, but there are cases in which they are deployed on *localhost* directly, so when REQUEST_URI is *http://localhost/mySite/foo/bar*, context path will be *mySite*
- it strips context path and QUERY_STRING from REQUEST_URI in order to detect **requested page**. If no specific page was requested as in *http://www.example.com*, homepage is assumed, so requested page will be an empty string
- it records query string and its array representation based on QUERY_STRING param @ $\_SERVER. Recording an array representation of query string parameters separate from that returned by *parameters* method of [Lucinda\STDOUT\Request](#class-request) is justified when a non-GET request is received.

### Class Request UploadedFile

Class [Lucinda\STDOUT\Request\UploadedFiles\File](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Request/UploadedFiles\File.php) encapsulates information about a single file uploaded based on $\_FILES superglobal  and defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getName | void | string | Gets name of file uploaded. |
| getLocation | void | string | Gets temporary location of file uploaded. |
| getContentType | void | string | Gets content type of file uploaded. |
| getSize | void | int | Gets size of file uploaded.|

To process file uploaded, two methods were added for developers:


| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| move | string $destinaton | boolean | Moves file uploaded to a final location and returns whether or not operation was successful |
| delete | void | boolean | Deletes file uploaded and returns whether or not operation was successful |

### Class Session

Class [Lucinda\STDOUT\Session](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Session.php) encapsulates operations to perform with a http session via $\_SESSION superglobal and defines following public methods, all relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| start | void | void | Starts session, using settings defined in [session](#session) XML tag |
| isStarted | void | bool | Checks if session was started |
| set | string $key, $value | void | Sets session parameter by key and value |
| get | string $key | mixed | Gets value of session parameter by key |
| contains | string $key | bool | Checks if session contains parameter by key |
| remove | string $key | void | Deletes session parameter by key, if any |
| destroy | void | void | Destroys session, clearing of all parameters. |

### Class Cookies

Class [Lucinda\STDOUT\Cookies](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Cookies.php) encapsulates operations to perform with a http cookie via $\_COOKIE superglobal and defines following public methods, all relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| set | string $name, $value, int $expiration | void | Sets cookie parameter by name and value, lasting for expiration seconds from now, using settings defined in [cookie](#cookie) XML tag |
| get | string $name | mixed | Gets value of cookie by name |
| contains | string $name | bool | Checks if a cookie exists by name |
| remove | string $name | void | Deletes cookie by name |

### Class Response

Class [Lucinda\STDOUT\Response](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Response.php) encapsulates operations to be used in generating response. It defines following public methods relevant to developers:


| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getBody | void | string | Gets response body saved by method below. |
| setBody | string $body | void | Sets response body. |
| getStatus | void | [Lucinda\STDOUT\Response\Status](#class-response-status) | Gets response http status based on code saved by method below. |
| setStatus | int $code | void | Sets response http status code. |
| headers | void | array | Gets all response http headers saved by methods below. |
| headers | string $name | ?string | Gets value of a response http header based on its name. If not found, null is returned! |
| headers | string $name, string $value | void | Sets value of response http header based on its name. |
| redirect | string $location, bool $permanent=true, bool $preventCaching=false | void | Redirects caller to location url using 301 http status if permanent, otherwise 302. |
| view | void | [Lucinda\STDOUT\Response\View](#class-response-view) | Gets a pointer to view encapsulating data based on which response body will be compiled |

When API completes handling, it will call *commit* method to send headers and response body back to caller!

### Class Response Status

Class [Lucinda\STDOUT\Response\Status](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Response/Status.php) encapsulates response HTTP status and defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getDescription | void | string | Gets response http status code description (eg: "not modified"). |
| getId | void | int | Sets response http status code. |

### Class Response View

Class [Lucinda\STDOUT\Response\View](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Response/View.php) implements [\ArrayAccess](https://www.php.net/manual/en/class.arrayaccess.php) and encapsulates template and data that will later be bound to a response body. It defines following public methods relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getFile | void | string | Gets location of template file saved by method below. |
| setFile | string | int | Sets location of template file to be used in generating response body. |
| getData | void | array | Gets all data that will be bound to template when response body will be generated. |

By virtue of implementing [\ArrayAccess](https://www.php.net/manual/en/class.arrayaccess.php), developers are able to work with this object as if it were an array:

```php
$this->response->view()["hello"] = "world";
```

### Abstract Class EventListeners Start

Abstract class [Lucinda\STDOUT\EventListeners\Start](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/EventListeners/Start.php) implements [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php)) and listens to events that execute BEFORE [configuration](#configuration) XML is read.

Developers need to implement a *run* method, where they are able to access following protected fields injected by API via constructor:

| Field | Type | Description |
| --- | --- | --- |
| $attributes | [Lucinda\STDOUT\Attributes](#class-attributes) | Gets access to object encapsulating data where custom attributes should be set. |

A common example of a START listener is the need to set start time, in order to benchmark duration of handling later on:

```php
$this->attributes->setStartTime(microtime(true));
```

### Abstract Class EventListeners Application

Abstract class [Lucinda\STDOUT\EventListeners\Application](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/EventListeners/Application.php) implements [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php)) and listens to events that execute AFTER [configuration](#configuration) XML is read.

Developers need to implement a *run* method, where they are able to access following protected fields injected by API via constructor:

| Field | Type | Description |
| --- | --- | --- |
| $application | [Lucinda\STDOUT\Application](#class-application) | Gets application information detected from XML. |
| $attributes | [Lucinda\STDOUT\Attributes](#class-attributes) | Gets access to object encapsulating data where custom attributes should be set. |

A common example of a APPLICATION listener is the need to set database credentials for later use in connection:

```php
$this->attributes->setDataSource(object);
```

### Abstract Class EventListeners Request

Abstract class [Lucinda\STDOUT\EventListeners\Request](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/EventListeners/Request.php) implements [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php)) and listens to events that execute AFTER [Lucinda\STDOUT\Request](#class-request), [Lucinda\STDOUT\Session](#class-session) and [Lucinda\STDOUT\Cookies](#class-cookies) objects are created.

Developers need to implement a *run* method, where they are able to access following protected fields injected by API via constructor:

| Field | Type | Description |
| --- | --- | --- |
| $application | [Lucinda\STDOUT\Application](#class-application) | Gets application information detected from XML. |
| $request | [Lucinda\STDOUT\Request](#class-request) | Gets request information detected from superglobals. |
| $session | [Lucinda\STDOUT\Session](#class-session) | Gets pointer to class encapsulating operations on http session. |
| $cookies | [Lucinda\STDOUT\Cookies](#class-cookies) | Gets pointer to class encapsulating operations on http cookies. |
| $attributes | [Lucinda\STDOUT\Attributes](#class-attributes) | Gets access to object encapsulating data where custom attributes should be set. |

A common example of a REQUEST listener is the need to authorize request:

```php
if (!$this->isAllowed($this->attributes->getValidPage())) {
    throw new PageNotAllowedException($this->attributes->getValidPage());
}
```

### Abstract Class EventListeners Response

Abstract class [Lucinda\STDOUT\EventListeners\Response](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/EventListeners/Response.php) implements [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php)) and listens to events that execute AFTER [Lucinda\STDOUT\Response](#class-response) body was set but before it's committed back to caller.

Developers need to implement a *run* method, where they are able to access following protected fields injected by API via constructor:

| Field | Type | Description |
| --- | --- | --- |
| $application | [Lucinda\STDOUT\Application](#class-application) | Gets application information detected from XML. |
| $request | [Lucinda\STDOUT\Request](#class-request) | Gets request information detected from superglobals. |
| $session | [Lucinda\STDOUT\Session](#class-session) | Gets pointer to class encapsulating operations on http session. |
| $cookies | [Lucinda\STDOUT\Cookies](#class-cookies) | Gets pointer to class encapsulating operations on http cookies. |
| $response | [Lucinda\STDOUT\Response](#class-response) | Gets access to object based on which response can be manipulated. |
| $attributes | [Lucinda\STDOUT\Attributes](#class-attributes) | Gets access to object encapsulating data where custom attributes should be set. |

A common example of a RESPONSE listener is applying HTTP caching:

```php
if ($this->cacheIsFresh()) {
    $this->response->setStatus(304);
    $this->response->setBody("");
}
```

### Abstract Class EventListeners End

Abstract class [Lucinda\STDOUT\EventListeners\End](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/EventListeners/End.php) implements [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php)) and listens to events that execute AFTER [Lucinda\STDOUT\Response](#class-response) was rendered back to caller.

Developers need to implement a *run* method, where they are able to access following protected fields injected by API via constructor:

| Field | Type | Description |
| --- | --- | --- |
| $application | [Lucinda\STDOUT\Application](#class-application) | Gets application information detected from XML. |
| $request | [Lucinda\STDOUT\Request](#class-request) | Gets request information detected from superglobals. |
| $session | [Lucinda\STDOUT\Session](#class-session) | Gets pointer to class encapsulating operations on http session. |
| $cookies | [Lucinda\STDOUT\Cookies](#class-cookies) | Gets pointer to class encapsulating operations on http cookies. |
| $response | [Lucinda\STDOUT\Response](#class-response) | Gets access to object based on which response can be manipulated. |
| $attributes | [Lucinda\STDOUT\Attributes](#class-attributes) | Gets access to object encapsulating data where custom attributes should be set. |

A common example of a START listener is the need to set end time, in order to benchmark duration of handling:

```php
$this->attributes->setEndTime(microtime(true));
```

### Abstract Class Controller

Abstract class [Lucinda\STDOUT\Controller](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Controller.php) implements [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php)) to set up response (views in particular) by binding information detected beforehand to models. It defines following public method relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| run | void | void | Inherited prototype to be implemented by developers to set up response based on information saved by constructor |

Developers need to implement *run* method for each controller, where they are able to access following protected fields injected by API via constructor:

| Field | Type | Description |
| --- | --- | --- |
| $application | [Lucinda\STDOUT\Application](#class-application) | Gets application information detected from XML. |
| $request | [Lucinda\STDOUT\Request](#class-request) | Gets request information detected from superglobals. |
| $session | [Lucinda\STDOUT\Session](#class-session) | Gets pointer to class encapsulating operations on http session. |
| $cookies | [Lucinda\STDOUT\Cookies](#class-cookies) | Gets pointer to class encapsulating operations on http cookies. |
| $response | [Lucinda\STDOUT\Response](#class-response) | Gets access to object based on which response can be manipulated. |
| $attributes | [Lucinda\STDOUT\Attributes](#class-attributes) | Gets access to object encapsulating data set by event listeners beforehand. |

By far the most common operation a controller will do is sending data to view via *view* method of [Lucinda\STDOUT\Response](#class-response). Example:

```php
$this->response->view()["hello"] = "world";
```

To better understand how *controllers* attribute @ [application](#application) and *class* attribute @ [exception](#exceptions) matching requested route play together in locating [Lucinda\STDOUT\Controller](#abstract-class-controller) that will mitigate requests and responses later on, let's take a look at table below:

| controllers | class | File Loaded | Class Instanced |
| --- | --- | --- | --- |
| application/controllers | UsersController | application/controllers/UsersController.php | UsersController |
| application/controllers | foo/UsersController | application/controllers/foo/UsersController.php | UsersController |
| application/controllers | \Foo\UsersController | application/controllers/UsersController.php | \Foo\UsersController |
| application/controllers | foo/\Bar\UsersController | application/controllers/foo/UsersController.php | \Bar\UsersController |

Example of controller for */users* route:

```php
class UsersController extends \Lucinda\STDOUT\Controller
{
    public function run(): void
    {
        // interrogates DB to get all users via Users model
        $users = new Users();				
        $this->response->view()["users"] = $users->getAll();
    }
}
```

Defined in XML as:

```xml
<route url="users" controller="UsersController" view="users"/>
```

### Abstract Class ViewResolver

Abstract class [Lucinda\STDOUT\ViewResolver](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/ViewResolver.php) implements [Lucinda\STDOUT\Runnable](https://github.com/aherne/php-servlets-api/tree/v3.0.0/src/Runnable.php)) and encapsulates conversion of [Lucinda\STDOUT\Response\View](#class-response-view) to response body for final response format. It defines following public method relevant to developers:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| run | void | void | Inherited prototype to be implemented by developers in order to convert view to response body based on information saved by constructor |

Developers need to implement *run* method for each controller, where they are able to access following protected fields injected by API via constructor:

| Field | Type | Description |
| --- | --- | --- |
| $application | [Lucinda\STDOUT\Application](#class-application) | Gets application information detected from XML. |
| $response | [Lucinda\STDOUT\Response](#class-response) | Gets access to object based on which response can be manipulated. |

To better understand how *resolvers* attribute @ [paths](#application) and *class* attribute @ [format](#formats) matching *response format* play together in locating class that will resolve views later on, let's take a look at table below:

| resolvers | class | File Loaded | Class Instanced |
| --- | --- | --- | --- |
| application/resolvers | HtmlResolver | application/resolvers/HtmlResolver.php | HtmlResolver |
| application/resolvers | foo/HtmlResolver | application/resolvers/foo/HtmlResolver.php | HtmlResolver |
| application/resolvers | \Foo\HtmlResolver | application/resolvers/HtmlResolver.php | \Foo\HtmlResolver |
| application/resolvers | foo/\Bar\HtmlResolver | application/resolvers/foo/HtmlResolver.php | \Bar\HtmlResolver |

Example of a resolver for *html* format:

```php
class HtmlResolver extends Lucinda\STDOUT\ViewResolver
{
    public function run(): void
    {
        $view = $this->response->view();
        if ($view->getFile()) {
            if (!file_exists($view->getFile().".html")) {
                throw new Exception("View file not found");
            }
            ob_start();
            $_VIEW = $view->getData();
            require($view->getFile().".html");
            $output = ob_get_contents();
            ob_end_clean();
            $this->response->setBody($output);
        }
    }
}
```

Defined in XML as:

```xml
<format name="html" content_type="text/html" class="HtmlResolver" charset="UTF-8"/>
```
