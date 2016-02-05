# Introduction #

Here are some text I found on the PHP forum about PHP Sessions.

This is relevant if you use the phpsession sessio handler in simpleSAMLphp. If you use the memcache handler this is not relevant.


# Details #

Text from this page: http://no2.php.net/session


One more note on session duration, and especially long sessions. I hope I've now understood the problem, and this may be useful.

The duration of a session is indeed controlled by many things (too many for my taste).

The first thing is session.cache\_expire, which should be **the** thing, you can set it with ini\_set(), or with session\_cache\_expire(). The value is in minutes, and defaults to 180, which is 3 hours and should be enough.

```
<?php //ini_set("session.cache_expire","180"); // default is 180, which is 3 hours... ?>
```

But that does not work !

Second thing to take into account, session.gc\_maxlifetime. Indeed, if the garbage collector kills your session data, you're lost. So we set it with ini\_set. The value is in seconds, and defaults to 1440, which is only 24 minutes and might be a little short.

```
  <?php ini_set("session.gc_maxlifetime","3600"); // default is 1440, which is only 24 minutes ?>
```

Here I should have a 1 hour session. But in fact, I don't !

Third thing to take into account, session.save\_path. If other people mess up with it, like if it's "/tmp" as it is by default, and their gc has not the same idea as yours, you're lost. So we set it with ini\_set or session\_save\_path.

```
<?php session_save_path("/my/own/path/without/url/sessions"); ?>
```

Here we are. As you can read in the example it should be a path you control (and your web server can write), with no access via URL.

Of course, all these parameters must be set before calling session\_start().

In fact, two other variables (at least) play a role in session duration, and will explain that the session might last more than expected. The gc erasing your data is a probabilistic thing. Each time a session is opened, the probability the gc is started is session.gc\_probability/session.gc\_divisor. session.gc\_probability defaults to 1 and session.gc\_divisor defaults to 100, which makes a 1% probability.

This is to have sessions long enough. I'm unsure about having sessions lasting exactly the time we want them to.