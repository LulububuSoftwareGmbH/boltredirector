Bolt Redirector
===============

A wicked little [Bolt] [1] 1.2 extension that allows you to perform any pre-app `301 Moved Permanently` redirects. Kinda handy when you're moving from your silly flat file website/overly complicated CMS to Bolt. ;)

Installation
------------

Install the extension by copying the downloaded `Redirector` directory into the `extensions` directory. Then, activate by adding `Redirector` to the array of enabled extensions in the main `app/config/config.yml` file:

```yml
enabled_extensions: [ Redirector ]
```

You can also grab the extension as a:

1. [zip ball] [3]
2. [tar ball] [4]

301 away!
---------

Setting up your redirects is simple. In the extension's `config.yml` file, add the following:

```yml
aboutus:
	from: 'about-us.html'
	to: 'page/about-us'
```

Let's translate this: before any site processing takes place, and if a request is made to `/about-us.html`, the browser will be redirected to `/page/about-us` with the `301 Moved Permanently` response code.

### [Learn more] (https://github.com/foundry-code/bolt-redirector/blob/master/Redirector/readme.md)

Contributing
------------

If you feel that something is missing, not done right, or can be optimised, please submit a pull request. If you feel that features can be added, please submit an issue.

License
-------

Bolt Redirect is licensed under the Open Source [MIT License] [2].

  [1]: http://bolt.cm/                                  "Bolt"
  [2]: http://opensource.org/licenses/mit-license.php   "MIT License"
  [3]: https://github.com/foundry-code/bolt-redirector/zipball/master
  [4]: https://github.com/foundry-code/bolt-redirector/tarball/master
