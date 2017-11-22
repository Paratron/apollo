Apollo Bootstrap
================

> The project is NOT stable so far and is lacking any documentation!

Apollo is a bootstrap project meant to help setting up webservices you can earn money with very quickly.
It has built-in support for pages (for informational purposes / marketing), a account/registration system and payment
processing.

Features
--------
- [x] Extendable user management
- [x] Built-in authentification methods: e-mail or oauth (google, facebook and github)
	- [x] e-mail
	- [x] oAuth
- [x] Freely configurable content pages
- [x] System triggers to perform actions on certain events
- [ ] Connection to Mailgun to send transactional emails
- [ ] Fast data cache (either on disk, or with memcached)
- [ ] Calling webhooks upon system triggers (i.E. for calling Slack)
- [ ] Process single purchases as well as subscriptions
- [ ] Payment processing through Stripe and/or PayPal
- [ ] Easy creation of REST APIs


Requirements
------------

- Apache 2.2
- PHP 5.6+
- MySQL


System Triggers
---------------
System triggers are fired upon certain events. You may register a triggerProcessor to perform
actions upon those triggers.

### userRegisterOAuth
Triggered when a new user has registered himself via oAuth.    
$data => \Apollo\User

### userRegisterMail
Triggered when a new user has registered via email. (Before confirmation).
$data => \Apollo\User

### userActivatedMail

### userLogin
Triggered when a user has been logged into the system. Doesnt matter if via mail
or oAuth.
$data => \Apollo\User

### userLoginOAuth
Triggered when a user has been logged into the system via oAuth.
$data => \Apollo\User

### userLoginMail
Triggered when a user has been logged into the system via mail.
$data => \Apollo\User

### userLogout
