Apollo Bootstrap
================

> The project is NOT stable so far and is lacking any documentation!

Apollo is a bootstrap project meant to help setting up webservices you can earn money with very quickly.
It has built-in support for pages (for informational purposes / marketing), a account/registration system and payment
processing.

Features
--------
- [x] Extendable user management
- [ ] Built-in authentification methods: e-mail or oauth (google, facebook and github)
	- [x] e-mail
	- [ ] oAuth
- [x] Freely configurable content pages
- [ ] Fast data cache (either on disk, or with memcached)
- [ ] Transactional email support upon system triggers
- [ ] Calling webhooks upon system triggers (i.E. for calling Slack)
- [ ] Process single purchases as well as subscriptions
- [ ] Payment processing through Stripe and/or PayPal


Requirements
------------

- Apache 2.2
- PHP 5.6+
- MySQL