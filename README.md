# Chatter
A basic Twitter clone

Uses SQLite for persistent data and Symfony to run. Make sure you have both these dependencies to run, and that your PHP install has SQLite driver enabled. 

If you have Symfony CLI and Composer you should be able to run the site simply: 

```bash
$ composer install
$ php bin/console doctrine:database:create
$ symfony server:start
```
