# Ngorder Q

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ngorder/q.svg?style=flat-square)](https://packagist.org/packages/ngorder/q)
[![Total Downloads](https://img.shields.io/packagist/dt/ngorder/q.svg?style=flat-square)](https://packagist.org/packages/ngorder/q)

Simple Laravel wrapper for php-amqplib/php-amqplib

## Installation

Run composer:

```bash
composer require ngorder/q
```
Publish the config file and service provider with this Artisan command

```bash
php artisan q:install
```
Two files will be generated, a config file located in `config/q.php` and a provider located in
`app/Providers/QServiceProvider.php`

## Usage
First, make sure to update the configuration file.
### Publishing a Message
The `Message` facade provides functionality to publish a message, you can publish a message by calling the
`publish` method, which needs 2 parameters: routing key and the message you want to send, it can be an array or a string.

```php
\Ngorder\Q\Facades\NgorderQ::publish('test.route', [
            'message' => 'Hello World'
 ]);
```
The `publish` method will take the configuration from `app/q.php` to create an exchange and a queue if it does not exist yet.

#### Delaying a Message

```php
\Ngorder\Q\Facades\NgorderQ::delay(2)->publish('test.route', [
            'message' => 'Hello World'
 ]);
```
Delay a message (in minutes) before it gets consumed by the consumer.

### Routing
You can attach a method to handle specific routing key in `QServiceProvider`
```php
    protected $routing = [
        'hello.*' => [MyConsumer::class, 'handleWildcard'],
        'test.key' => [AnotherConsumer::class, 'handleIt'],
        'another.key' => [
            [MultiConsumer::class, 'fun1'],
            [MultiConsumer::class, 'fun2']                        
        ]         
    ];
```
Or an invokable class
```php
    protected $routing = [
        'some.key' => ThisIsInvokable::class,
    ];
```
### Consuming
To run consumer, first make sure the routing key is registered within the `QServiceProvider`. You can run this command after that:
```shell
php artisan q:consume my.routing.key
```
Available options:
```shell
--tries=3
```
Maximal number of tries when a message failed to consume.
```shell
--max-memory=128
```
Maximum memory usage.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
