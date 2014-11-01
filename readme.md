![Bolt Redirector (Logo)](http://code.foundrybusiness.co.za/images/bolt-redirector.png "Bolt Redirector")

## An Introduction

A wicked little extension designed for [Bolt 2.0](//bolt.cm) and up that allows you to perform any pre-app `301 Moved Permanently` redirects. Kinda handy when you're moving from your silly flat file website/overly complicated CMS to Bolt. ;)

## Requirements

This version of Redirector requires at least Bolt 2.0, due to the re-modelled extensions interface and methodology. (See 0.9.2 branch for earlier versions of Bolt.)

## Installation

To install the extension, use the extensions manager in Bolt. The extension is filed under `foundrycode/boltredirector`. You can also just search-ahead for 'redirector', and it'll pop up.

## Defining Redirects

Once you're all up and running, defining your 301-redirects is simple. Each redirect is stored the extension's configuration file (stored at `/app/config/extensions/boltredirector.foundrycode.yml`), under the `redirects` section. The syntax is as follows:

    name:
        from: '<old-location>'
        to: '<new-location>'

Where `<old-location>` is the location as it was on your previous website (or, perhaps even a short URL that you'd like to use for a long link), and `<new-location>` is the location where you'd like the old one to redirect to.

#### An example:

    aboutus:
        from: 'Default/Pages/About_Us.aspx'
        to: 'pages/about-us'

Let's translate this: before any site processing takes place, and if a request is made to `/Default/Pages/About_Us.aspx`, the browser will be redirected to `/page/about-us` with the `301 Moved Permanently` response code.

### Short-hand Notation

For those individuals who are too simple for words to even describe, we've got just thing:

    'Default/Pages/About_Us.aspx': 'pages/about-us'

This isn't very neat in the long-run - can get quite messy...

Oh, and these apply to Just In Time Replacements as well. (See below.)

### Jump Links

There are instance where you may want to redirect a certain slug to an external site. Jump-links, as we call them, can be used in Redirector:

    facebook:
        from: 'facebook'
        to: 'https://www.facebook.com/my-page'

Redirector will first check to see if we're using a jump-link, and act accordingly. If you do not use a jump-link, see point 2 below.

### Some things to consider:

Whilst YAML does allow you to skip the quotes on strings, it's recommended that you use them. There are times when you need them, and it's always best to keep your code consistent.

Do not prepend any URIs with a forward slash. All URIs are relative to the root of your site, unless the destination is a jump-link (a link to an external site). All trailing slashes will be removed before any processing takes place.

When creating route-based redirects, you must make sure that the placeholders match. For example, if you want to capture `{slug}`, you must make sure that the route definition in `routing.yml` has a `{slug}` too.

## Using Wildcards

Sometimes, it aint awesome to specify each redirect. So, we've added the ability to use wilcards. This is really useful if you want to match a whole bunch of requests that have a similar pattern. Let's consider this:

    page:
        from: '{slug:segment}.html'
        to: 'page/{slug}'

Here, we're not specifying an individual request. Instead, we're allowing multiple requests to be processed through a single rule.

Also, notice the `segment` part? That simply means that `slug` must match the rule of `segment`, which, as a regular expression, is `[a-z0-9\-\_]+`. This is required for all wilcards. It's just a rule of thumb.

You can also match various common file extensions in one argument, like so:

    page:
        from: '{slug:segment}.{ext:ext}'
        to: 'page/{slug}'

The following file extensions will be detected: `asp`, `aspx`, `cgi`, `fcgi`, `htm`, `html`, `shtm`, `shtml`, `jhtml`, `rbml`, `jsp`, `php`, and `phps`

We use this expression to do the check: `aspx?|f?cgi|s?html?|jhtml|rbml|jsp|phps?`

**Note:** Upon checking each slug, the extension is *not interested* in what case it is in. Upper and lower case makes no difference. When `autoslug` is enabled (which it is by default), the slug will be converted to it's slugified equivalent (see "Setting Options" below for more information on this).

### Available Wildcard Types

The following wilcard types are available:

Type      | Matches
----      | -------
:all      | Anything and everything, until another wildcard or marker
:alpha    | Alpha characters (letters only)
:alphanum | Alpha-numeric characters (letters and numbers)
:any      | Any common character: letters, numbers, periods (.), hyphens, underscores, percent-signs, equal signs, and whitespace
:num      | Numeric characters (numbers only)
:segment  | A common segment: letters, numbers, hyphens, and underscores
:segments | A set of common segments (separated by slashes)
:ext      | A common file extension (as above)

### Multiple Wildcards

Not everyone has only one segment in their slugs. So, you can use as many as you like:

    news:
        from: '/blog/{year:num}/{month:num}/{slug:segment}'
        to: '/news/{slug}-{year}-{month}'

Of course, in this example, you'd need to make sure that no two names/slugs are the same, otherwise there will be a little overlap - not good!

### Smart Wildcards

In a nutshell these wildcards assume the type you wish you wish to encapsulate. This means that, for specific wildcards (identified by name), you do not need to supply its type from the above table.

Here's an example:

    oldroute:
        from: '{path}.{ext}'
        # This will be translated to '{path:segments}.{ext:ext} before processing takes place
        to: '{path}'

The table below shows you what assumptions are made:<p></p>

Name                                 | Assumption
----                                 | ----------
path                                 | :segments
name, title, page, post, user, model | :segment
year, month, day, id                 | :num
ext                                  | :ext

### Regular Expressions and Capture Prevention

If you need to make use of a Regular Expression within your source slug (that is, before the last wildcard), you'll need to wrap them in angular braces so that they are not captured by the parser. For example, let's say you wanted to match a specific path that may incorporate language-selection, such as `en/page/about-us`, you could do something like this:

    trim_language:
        from: '<en|fr>/{contenttypeslug:segment}/{slug:segment}'
        to: 'route: contentlink'

Here, `en/page/about-us` and `fr/page/about-us` would be matched, but the language expression would not be captured. If you were to use the normal braces for this, the parser would not interpret the path correctly.

In this example, `contentlink` is the default route defined by Bolt in `routing.yml`. So this slug would be redirected to `page/about-us`.

From a internal point of view, the angular braces are converted to `(?:<expression-content>)` upon parsing.

Here's another example:

    api:
        from: 'api/v<[0-9-[3]]+>/{rest:all}'
        to: 'api/v3/{rest}'

Here, we're redirecting all API requests from versions 1-2 and 4-∞ to version 3. So, `api/v1/dosomething` and `api/v19000/dosomething` will redirect to `api/v3/dosomething`.

## Route Moulding

Bolt 1.2 introduced the ability to map custom slugs to actual ones, by means of a `routing.yml` configuration file. Being flexible, you could then do with it anything you like. For example, you could map `/about-us` to the `page` content type. Internally, Bolt will simply perform an internal request to `/page/about-us` or `page/3`, for example.

Now, we've added some functionality to Redirector that moulds into this feature. Let's say that you have a routing rule specifying that `news/tech/{slug}` should be mapped to `newsitem/{slug}`. In your `routing.yml` file, you would have defined something like this in `routing.yml`:

    technews:
        path:           /news/tech/{slug}
        defaults:       { _controller:     'Bolt\Controllers\Frontend::record',
                        'contenttypeslug': 'techitem' }
        contenttype:    techitem

In addition, let's say your old site had the following URI structure for this particular route: `News/Technology/Item/<slug>.html`. Considering that you've defined the above routing rule, Redirector would be able to map to it.

Here's the redirect-rule:

    news:
        from: 'News/Item/{slug:any}.html'
        to: 'route: technews'

In this example, `News/Technology/Item/Microsoft_Acquires_Nokias_Hardware_and_Services_Business.html` would be redirected to `news/tech/microsoft-acquires-nokias-hardware-and-services-business`, which would then be internally processed by Bolt.

So, if you want to redirect to a route, simply use the format `'route: <route-name>'` in the `to:` property of the rule.

## Variables

Redirector allows you to define and make use of variables, which are handy for things that may change in the future.

Variable definitions are declared in the `variables` section of the extension's `config.yml` file. The syntax is:

    variable: 'content'

Variables can only be used in normal redirects, using the following syntax: `{@variable}`

#### An example:

Let's say you wanted to make a Facebook-redirect; short URLs that users can use to access longer Facebook URLs:

    facebook:
        from: 'facebook'
        to: 'https://www.facebook.com/my-awesome-page'

    facebook_about:
        from: 'facebook/about'
        to: 'https://www.facebook.com/my-awesome-page/about'

Using variables you can encapsulate `my-awesome-page` so that you need only change it once in your config every time it changes. Let's define the variable:

    facebook_page: 'my-awesome-page'

Now, you can redefine the rules like this:

    facebook:
        from: 'facebook'
        to: 'https://www.facebook.com/{@facebook_page}'

    facebook_about:
        from: 'facebook/about'
        to: 'https://www.facebook.com/{@facebook_page}/about'

Sure, this isn't very common, but there are good uses for it. Just gotta find them.

## Just In Time Replacements (JITs)

Sometimes it is necessary to perform last-minute replacements on already-processed routes.

For example, let's say your old site had the following path structure (trimmed down for the purposes of this example):

- `About Us.html`
- `Our Services.html`
- `Contact.html`

Suppose you have this redirect-rule defined:

    page:
        from: '{slug:any}.html'
        to: 'page/{slug}'

Naturally, those pages would redirect to:

- `page/about-us`
- `page/our-services`
- `page/contact`

But now, in your new Bolt site, you don't have the `about-us` and `our-services` pages created. And you don't want to change their slugs. Instead, they are `about` and `solutions`, respectively. That's were JITs come in.

Just In Time Replacements are defined in the `jits` section of the extension's `config.yml` file. Considering this example, we'd use the following replacements:

    about:
        replace: 'page/about-us'
        with: 'page/about'

    solutions:
        replace: 'page/our-services'
        with: 'page/solutions'

Now, any requests made to `/About Us.html` will be redirected to `/page/about`, and any made to `/Our Services.html` will be redirected to `page/solutions`.

To be as concise as possible, try to match an entire destination, or something as close to it as possible, as other slugs could be inadvertantly affected. If other slugs won't contain the string, then you can keep it as short as need be:

    solutions:
        replace: 'our-services'
        with: 'solutions'

As mentioned in the Using Redirects section, you can also use short-hand notation to define these replacements:

    'page/about-us': 'page/about'
    'page/our-services': 'page/solutions'

## Setting Options

Options are defined like so:

    options:
        option: value

These are the options presently available, and they are defined in the `options` group of the extension's `config.yml` file:

###`autoslug`
DEFAULT VALUE: `true`

Often, you'll find that old sites do not conform to today's standards of URI slugs/paths. As such, it is important to note that Bolt is very strict about using well-formed slugs.

Redirector takes this to heart, and, by default, enables the `autoslug` option, which ensures that your URI will work in Bolt.

When set to `true` (this is the default, you can turn it off if you really want to), the extension will convert your URIs to their slugified equivalents. So, if you've captured `About_Us` or `About Us` (`About%20Us`), it will be converted to `about-us` before the redirect takes place. Note that it makes these conversions *for each capture*, and not for the entire URI.

When set to `false`, the extension will not implement the auto-slugger. So, if you have a rule stating that `Pages/{page:any}.{ext:ext}` (where `page` could be `About_Us`), it would simply be converted to lowercase, ie. `about_us`. This URI would not work in Bolt (due to the underscore), so only disable the option if it is really necessary, like for use in an extension.

###`append_query_string`
DEFAULT VALUE: `false`

Redirector does not yet handle query strings. So, if you're migrating an old site that follows a query-string-based content-fetcher, such as `page.php?name=page-name`, Redirector would not be able to handle it as it can only capture the define route. In this case, it can only capture `page.php`.

However, there are certain sites that use query strings for other things, like page numbering, languages, and other route-specific definitions. Where these are needed, and where there is an extension to handle them (or Bolt can handle them natively), they can be appended to the resulting destination path.

If you want to be able to do this, simply enable `append_query_string`.

#### Usage example

So, let's say you have the following redirect rule:

    old_page:
        from: 'phones.php'
        to: 'phones'

If you provided that URI with a query string (`phones.php?filter=Samsung`, for example), and enabled the option, your resulting URI would be `phones?filter=Samsung`.

If, however, the option was disabled, you'd simply be redirected to `phones` (which, in this case, would defeat the purpose of making filters for that content type).

# License

Bolt Redirect is licensed under the Open Source [MIT License](//opensource.org/licenses/mit-license.php).