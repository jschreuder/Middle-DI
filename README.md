# Middle DI

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle-DI/?branch=master)

A modern PHP Dependency Injection Container that brings **IDE autocompletion, type safety, and zero-configuration** to dependency injection. Unlike traditional containers that use string-based service keys, Middle-DI generates strongly-typed methods during development, then caches optimized code for zero-overhead production performance.

## Why Middle DI?

**Traditional containers:**
```php
$database = $container->get('database');        // What type? Runtime errors possible
$userService = $container->get('user.service'); // No IDE support, typos discovered late
```

**Middle-DI:**
```php
$database = $container->getDatabase();     // Returns PDO, full IDE support
$userService = $container->getUserService(); // Returns UserService, compile-time safe
```

## How It Works

Middle-DI uses **compile-time code generation during development** to transform your simple container definition into an optimized singleton container. 

**Simple Conventions:**
- **Services**: `get*()` methods return singletons, accept optional `string $name` parameter only
- **Factories**: `new*()` methods create fresh instances, accept any parameters

**Your Definition:**
```php
class Container
{
    // Services: singletons
    public function getDatabase(): PDO
    {
        return new PDO($this->config('db.dsn'));
    }

    public function getUserService(): UserService  
    {
        return new UserService($this->getDatabase());
    }

    // Factories: fresh instances
    public function newUser(string $username, array $roles = []): User
    {
        return new User($username, $roles);
    }
}
```

**Generated for Production (cached, opcache-optimized):**
Note: this is a bit simplified, it actually adds a tiny bit of extra logic to allow for named instances.
```php
class Container__Compiled extends Container
{
    private array $__services = [];

    public function getDatabase(): PDO
    {
        return $this->__services['getDatabase'] ?? 
               ($this->__services['getDatabase'] = parent::getDatabase());
    }

    public function getUserService(): UserService
    {
        return $this->__services['getUserService'] ?? 
               ($this->__services['getUserService'] = parent::getUserService());
    }

    // newUser() remains unchanged - creates new instances
}
```

## Basic Usage

```php
<?php
use jschreuder\MiddleDi\DiCompiler;

class Container
{
    public function getDatabase(): PDO
    {
        return new PDO('mysql:host=localhost;dbname=app');
    }

    public function getUserRepository(): UserRepositoryInterface
    {
        return new UserRepository($this->getDatabase());
    }

    public function newUser(string $username): User
    {
        return new User($username);
    }
}

// Compile and use
$container = (new DiCompiler(Container::class))->compile()->newInstance();

// Services: same instances (singletons)
$db1 = $container->getDatabase();
$db2 = $container->getDatabase();
var_dump($db1 === $db2); // true

// Factories: different instances every time
$user1 = $container->newUser('alice');
$user2 = $container->newUser('bob');
var_dump($user1 === $user2); // false
```

## Named Service Instances

Support multiple configurations of the same service type:

```php
class Container
{
    public function getDatabase(?string $name = null): PDO
    {
        $dsn = match($name) {
            'readonly' => 'mysql:host=slave;dbname=app',
            'analytics' => 'mysql:host=analytics;dbname=app',  
            default => 'mysql:host=master;dbname=app'
        };
        return new PDO($dsn);
    }
}

// Usage
$primary = $container->getDatabase();           // Default primary DB
$readonly = $container->getDatabase('readonly'); // Readonly replica
$analytics = $container->getDatabase('analytics'); // Analytics DB
```

## Production Optimization

Cache compiled containers for maximum performance:

```php
use jschreuder\MiddleDi\DiCachedCompiler;

$compiler = new DiCachedCompiler(
    new DiCompiler(Container::class),
    new SplFileObject('var/cache/container.php', 'c+')
);

$container = $compiler->compile()->newInstance($config);
```

In production, this runs as pure opcached PHP with zero container overhead.

## Configuration Support

Use the included `ConfigTrait` for clean configuration handling:

```php
use jschreuder\MiddleDi\ConfigTrait;

class Container
{
    use ConfigTrait;

    public function getDatabase(): PDO
    {
        return new PDO(
            $this->config('db.dsn'),
            $this->config('db.username'),
            $this->config('db.password')
        );
    }
}

$config = [
    'db.dsn' => 'mysql:host=localhost;dbname=app',
    'db.username' => 'user',
    'db.password' => 'pass'
];

$container = $compiler->compile()->newInstance($config);
```

## Key Features

### 🚀 **Zero Configuration**
No YAML, XML, or array configuration. Just write PHP methods with clear return types. The `get` vs `new` prefix tells Middle-DI everything it needs to know.

### 💡 **Full IDE Support**
Complete autocompletion, type hints, and "Go to Definition" support. Refactoring tools work perfectly. No more guessing what `$container->get('service_name')` returns.

### ⚡ **Zero Production Overhead**  
Development-time compilation generates cached PHP files. Production runs pure opcached code that compiles to native PHP speed with full opcache optimization.

### 🔒 **Type Safety**
All dependencies are strongly typed. Catch errors during development, not in production.

### 🎯 **Pure Dependency Injection**
Services remain completely decoupled from the container. No annotations, no string dependencies, no special interfaces—just regular PHP classes.

## Extensibility Features

Add advanced functionality using the decorator pattern:

```php
$compiler = new CircularDependencyCompiler(
    new DiCachedCompiler(
        new DiCompiler(Container::class),
        new SplFileObject('var/cache/container.php', 'c+')
    )
);
```

## Requirements

- PHP 8.3 or higher

Perfect for projects wanting Pimple's simplicity with modern IDE support and optimal production performance.
