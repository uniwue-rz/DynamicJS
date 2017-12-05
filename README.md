# Piwik DynamicJS Plugin

This plugin helps you to deliver the right siteId to the right url, with help of your backend. With this there is no need to add any changes on the backend except a small script and possible no script tag which will be used to determine the right siteId the url belongs to. 

The plugin can be used in institutions that have multiple domains or paths and want to have them logged in different siteIds. Example of these institutions can be universities, government agencies or big corporation. The code snippet can be added to their main template and piwik will be deciding/detecting (with help of given backend) which siteIds the url belongs to. This plugin will work regardless of your CMS settings, using simple javascript or jquery. The no-script part works with simple 302 redirection.

## Configuration

For the configuration simply add this snippet to your `config.ini.php`.

```lang=ini
[DynamicJS]
recursion_level = "1"
enable_cache = "1"
enable_add_host = "1"
enable_add_user = "1"
default_access = "view"
default_backend = "LdapBackend"
accepted_domains = "your-domain-list-comma-separated"
default_email = "anonymous@example.com"
activeBackend = "LdapBackend"
script_template = "your-script-template containing {{siteId}}"
enable_no_script = "1"
; this is when you use LDAP backend as default one.
ldap_host = "ldap-server"
ldap_port = "636"
ldap_encryption = "ssl"
ldap_username = "ldap-user"
ldap_password = "ldap-password"
ldap_username_key = "manager"
ldap_username_regex = "/cn=(?<username>[0-9A-Z-a-z]+)/"
ldap_username_regex_match_key = "username"
ldap_filter = "(&(WebhostTarget=typo3)(WebhostDomain={{domain}})(webhostPath={{path}}))"
ldap_dn = "ldap-dn"
backend_paths = "/srv/www/users/"
ldap_username_info_email_key = "mail"
ldap_username_info_alias_key = "givenName, sn"
ldap_username_info_filter = "(uid={{username}})"
ldap_username_info_username_key = "uid"
ldap_username_info_dn = "ou=accounts,o=uni-wuerzburg"
```

The application without default backend will automatically fallback to piwik. This will have not full functionality as piwik does not know which URLs are new hosts and only works with your existing configurations.

## JavaScript Setting

The following URL will deliver you the javascript snippet you created.

```
https://your-piwik-url/index.php?module=DynamicJS&action=index&domain=your-domain
```

For your CMS or website you are required to have the jquery:

```lang=html
<script type="text/javascript">
    $.ajax({
        type: "GET",
        data:{"domain":window.location.href},
        url: "the-url-above",
        dataType: "script"
    });
</script>
```

## NoScript Settings

The following URL should be added as in `<noscript><img></img></noscript>` tags.

```lang=html
https://your-piwik-url/index.php?module=DynamicJS&action=redirect
```

and The no script tag will look like this:

```lang=html
<noscript><p><img src="https://your-piwik-url/index.php?module=DynamicJS&action=redirect" style="border:0;" alt="" /></p></noscript>
```

## Apache Settings

if the urls above does not look ok to you, there is a chance that you can change them with the help of rewrite:

```lang=conf
RewriteRule /source.js(.*) /index.php?module=DynamicJS&action=index&$1 [QSA,L]
RewriteRule /redirect.png(.*) /index.php?module=DynamicJS&action=index&$1 [QSA,L]
```

And then simply add these newly generated in your CMS.

## Cache

This application uses the cache component of Piwik, so there is no need to configure any cache for the plugin itself. To configure Piwik cache you can take a look at documentation here. If you work in development mode the caching is disabled.

## Developing Backends

DynamicJS is capable of supporting external backends. To add your backend to the system. Simply set `Piwik\Plugins\DynamicJS\Backend\Backend` as parent class of
your backend. Implement the needed the methods:

```lang=php
class ExampleBackend extends Backend{
	// The logic which translate the url to site id should be added here
	public function getSiteId($url){
	}
	// The name of the backend should be implemented here
	public function getName(){
	}
	// The variables your method needs should be added here, using 		`Piwik\Plugins\DynamicJS\Backend\BackendVariable`
	public function getVariables(){
	}
}
```

You can also use the `AddUser` and `AddHost` capability of this plugin with adding this method:

```lang=php
// Returns the user that have the given access to the url
public function getAccessUsers($url, $access = "view"){

}
```

After creating your backend you need to added to the list of backends by simply adding the path of Folder the Backend is located. It is normally `{YOUR PIWIK DIR}/plugins/{YourPiwikPlugin}/Backend`. It is important that your www has access to that folder and the backend file name ends with `Backend.php`. 

After refreshing the page, your backend should show up in the list of existing ones. Select it. It will cause the application to show the configuration you added as variables. After configuring your backend. It can be used.`LdapBackend` is an example Backend that can be used to create other kinds of backends.

## Drawbacks

Probably the only drawback of this plugin would be the two requests you need to send to piwik to get the needed information. First to get the siteId and the second to log the data to the given siteId. Having cache enabled will make the first request faster.

## Change Log
See CHANGELOG file.

## Licence 
See LICENSE file.

## Contribution
Pull requests and bug fixes are accepted.