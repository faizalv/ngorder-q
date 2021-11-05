# Changelog

All notable changes to `q` will be documented in this file

## 1.0.0-beta - 2021-09-06

### Initial Release
Base feature:
- Publish message
- Routing
- Consume message

## 1.0.1-beta - 2021-09-07
Fix:
- Publishing a string caused `null` in consumer

## 1.2.0-beta - 2021-09-08
Feature:
- Publish message with a delay

## 1.2.1-beta - 2021-09-08
Fix:
- Consumer parse AMQPTable as array error

## 1.3-beta - 2021-10-13
Refactor:
- OOP style

Features:
- Consumer max tries
- Consumer max memory

## 1.3.1-beta - 2021-10-13
Fix:
- Fail converting Json to Array
- Set prefetch to 1

## 1.3.2-beta - 2021-10-13
Fix:
- Update `is_callable` usage for PHP8

## 1.3.3-beta - 2021-10-21
Refactor:
- Multiple channels usage

## 1.3.3 - 2021-11-05
Feature:
- Support multiple method to handle a routing key