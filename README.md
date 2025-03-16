# Middle DI

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/?branch=master)

A PHP Dependency Injection Container library meant to allow service and factory definitions in native PHP as to allow type-hinting, automated checks and code completion. It does this by rewriting a class on-the-fly and ensuring that service definitions will always return the same instance.

It aside from the core functionality it also allows for caching the generated code in the filesystem, thus allowing OP-cache to do its work in production. And it als includes a bit of logic for configuration to be passed in.

## Conventions

All service definitions should be public methods starting with "get", and all factories should start with "new". Currently only the service defintions are enforced, additional rules include: it may only take a string name (see below) as parameter and must have a singular returntype that is either a class or an interface.

```php
<?php
use jschreuder\MiddleDi\DiCompiler;

class Container
{
    // This will be rewritten to always return the same object
    public function getDatabase(): PDO
    {
        return new PDO();
    }

    // A service using the database
    public function getService(): Service
    {
        return new Service($this->getDatabase());
    }

    // This will be left alone and work as defined
    public function newObject(): stdClass
    {
        return new stdClass();
    }
}

// Create the compiled container
$dic = (new DiCompiler(Container::class))->compile()->newInstance();

// these will all be true
var_dump(
    $dic instanceof Container,
    $dic->getDatabase() === $dic->getDatabase(),
    $dic->getService() === $dic->getService(),
    $dic->newObject() !== $dic->newObject()
);
```

## Using different named instances of a service

Even though services are expected to be single instances, using multiple instances of a service is allowed when naming them. A first parameter is supported which will be treated as its name and can be used to differentiate in its configuration.

```php
<?php
class Container
{
    public function getDatabase(): PDO
    {
        return new PDO();
    }

    // This will return different instances for different names
    public function getTableService(string $name): TableService
    {
        return new TableService($name, $this->getDatabase());
    }
}
```

## Make containers cached in filesystem in production

To allow op-caching to work and make things as fast as possible in production environment the compiled container can be cached to a file.

```php
<?php
use jschreuder\MiddleDi\DiCompiler;
use jschreuder\MiddleDi\DiCachedCompiler;

class Container {}

// Create the compiled container
$compiler = (new DiCachedCompiler(
    new DiCompiler(Container::class),
    new SplFileObject('path/to/cachefile.php', 'c');
))->compile();
$dic = $compiler->newInstance();
```

## Add configuration

There's a simple configuration helper included with the `ConfigTrait`. This adds a constructor that takes either an array or an ArrayAccess instance and allows requesting configuration items using the config() method. It will throw an exception when a non-existant configuration item is requested.

```php
<?php
use jschreuder\MiddleDi\DiCompiler;

class Container
{
    use jschreuder\MiddleDi\ConfigTrait;

    // This will be rewritten to always return the same object
    public function getDatabase(): PDO
    {
        return new PDO($this->config('db.dsn'));
    }
}

// Create the compiled container
$config = ['dsn' => 'example:dsn'];
$dic = (new DiCompiler(Container::class))->compile()->newInstance($config);
``` 