<?php

use MVPS\Lumis\Framework\Support\Str;

return [
	/*
	|--------------------------------------------------------------------------
	| Default Session Driver
	|--------------------------------------------------------------------------
	|
	| Configure the default session storage driver. Lumis supports multiple
	| options for persisting session data. The 'database' driver is set as
	| the default.
	|
	| Supported options: "file", "cookie", "database", "array"
	|
	*/
	'driver' => env('SESSION_DRIVER', 'database'),

	/*
	|--------------------------------------------------------------------------
	| Session Lifetime
	|--------------------------------------------------------------------------
	|
	| Configure the session lifetime and expiration behavior.
	|
	| The `lifetime` option specifies the number of minutes a session remains
	| active after the last user activity. The `expire_on_close` option
	| determines if the session expires when the browser is closed, overriding
	| the `lifetime` setting.
	|
	*/
	'lifetime' => env('SESSION_LIFETIME', 120),

	'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

	/*
	|--------------------------------------------------------------------------
	| Session Encryption
	|--------------------------------------------------------------------------
	|
	| Enable automatic encryption of session data before storage. This
	| provides an added layer of security for sensitive session information.
	|
	*/
	'encrypt' => env('SESSION_ENCRYPT', false),

	/*
	|--------------------------------------------------------------------------
	| Session File Storage Path
	|--------------------------------------------------------------------------
	|
	| Configure the directory where session files are stored when using the
	| "file" driver. This path defines the default location for session data.
	|
	*/
	'files' => storage_path('framework/sessions'),

	/*
	|--------------------------------------------------------------------------
	| Session Database Connection
	|--------------------------------------------------------------------------
	|
	| Configure the database connection used for storing session data when
	| using the "database" session drivers. This value should correspond to
	| a connection defined in your database configuration.
	|
	*/
	'connection' => env('SESSION_CONNECTION'),

	/*
	|--------------------------------------------------------------------------
	| Session Database Table
	|--------------------------------------------------------------------------
	|
	| Configure the database table used to store session data when using the
	| "database" session driver. The default table name is "sessions".
	|
	*/
	'table' => env('SESSION_TABLE', 'sessions'),

	/*
	|--------------------------------------------------------------------------
	| Session Sweeping Lottery
	|--------------------------------------------------------------------------
	|
	| Configure the probability of session garbage collection on each request.
	|
	| The first value represents the number of successful attempts required.
	| The second value represents the total number of attempts.
	|
	| For example, [2, 100] means there is a 2% chance of garbage collection
	| on each request.
	|
	*/
	'lottery' => [2, 100],

	/*
	|--------------------------------------------------------------------------
	| Session Cookie Name
	|--------------------------------------------------------------------------
	|
	| Configure the name of the session cookie used for storing session data.
	| The default value is usually sufficient for most applications.
	|
	*/
	'cookie' => env(
		'SESSION_COOKIE',
		Str::slug(env('APP_NAME', 'Lumis'), '_') . '_session'
	),

	/*
	|--------------------------------------------------------------------------
	| Session Cookie Path
	|--------------------------------------------------------------------------
	|
	| Configure the path where the session cookie is accessible. This value
	| typically matches your application's root path.
	|
	*/
	'path' => env('SESSION_PATH', '/'),

	/*
	|--------------------------------------------------------------------------
	| Session Cookie Domain
	|--------------------------------------------------------------------------
	|
	| Configure the domain for the session cookie. This value determines the
	| domains and subdomains where the cookie is accessible. The default
	| value allows access from the root domain and all subdomains.
	|
	*/
	'domain' => env('SESSION_DOMAIN', ''),

	/*
	|--------------------------------------------------------------------------
	| HTTPS Only Cookies
	|--------------------------------------------------------------------------
	|
	| Require HTTPS for session cookie transmission.
	|
	| When enabled, the session cookie is only sent over secure HTTPS
	| connections, preventing transmission over insecure HTTP connections.
	|
	*/
	'secure' => env('SESSION_SECURE_COOKIE'),

	/*
	|--------------------------------------------------------------------------
	| HTTP Only Cookies
	|--------------------------------------------------------------------------
	|
	| When enabled, the session cookie will only be accessible through the
	| HTTP protocol, preventing client-side JavaScript from reading its value.
	| This enhances security by mitigating potential cross-site scripting
	| (XSS) attacks.
	|
	*/
	'http_only' => env('SESSION_HTTP_ONLY', true),

	/*
	|--------------------------------------------------------------------------
	| SameSite Cookies
	|--------------------------------------------------------------------------
	|
	| This option configures the "SameSite" attribute of cookies used for
	| session management. The SameSite attribute helps mitigate Cross-Site
	| Request Forgery (CSRF) attacks by restricting when browser can send
	| cookies in cross-site requests.
	|
	| By default, this value is set to "lax". This permits cookies to be sent
	| in same-site and cross-site requests initiated with navigation methods
	| (like links, forms) but not with cross-site requests initiated through
	| JavaScript (e.g., XMLHttpRequest).
	|
	| You can choose a stricter option for enhanced security:
	|
	| - "strict": Prevents sending cookies in any cross-site requests,
	|             including navigation methods.
	| - "none": Allows sending cookies only in secure HTTPS requests and
	|           requires the "Secure" attribute to be set on the cookie as
	|           well. This option should be used with caution due to
	|           compatibility issues with older browsers.
	|
	| Disabling SameSite by setting it to `null` is generally not recommended
	| for security reasons.
	|
	| See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
	|
	| Supported options: "lax", "strict", "none", null
	|
	*/
	'same_site' => env('SESSION_SAME_SITE', 'lax'),

	/*
	|--------------------------------------------------------------------------
	| Partitioned Cookies
	|--------------------------------------------------------------------------
	|
	| Enable partitioned cookies for cross-site contexts. This requires the
	| cookie to be marked as secure and have the SameSite attribute set to
	| "none".
	|
	| Note: Partitioned cookies are a security feature designed to prevent
	| cross-site request forgery (CSRF) attacks. Use with caution and ensure
	| proper configuration.
	|
	*/
	'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
];
