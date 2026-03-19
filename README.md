# MyAdmin Google Wallet Payments Plugin

[![Build Status](https://github.com/detain/myadmin-googlewallet-payments/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-googlewallet-payments/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-googlewallet-payments/version)](https://packagist.org/packages/detain/myadmin-googlewallet-payments)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-googlewallet-payments/downloads)](https://packagist.org/packages/detain/myadmin-googlewallet-payments)
[![License](https://poser.pugx.org/detain/myadmin-googlewallet-payments/license)](https://packagist.org/packages/detain/myadmin-googlewallet-payments)

A payment processing plugin for the [MyAdmin](https://github.com/detain/myadmin) control panel that integrates Google Wallet as a payment gateway. This plugin registers event hooks for system settings and menu configuration, allowing administrators to enable and configure Google Wallet payments with support for both live and sandbox environments.

## Features

- Google Wallet payment gateway integration for MyAdmin
- Configurable live and sandbox merchant credentials
- Event-driven architecture using Symfony EventDispatcher
- Admin settings panel for enabling/disabling and configuring the payment method

## Installation

Install via Composer:

```sh
composer require detain/myadmin-googlewallet-payments
```

## Configuration

Once installed, configure the plugin through the MyAdmin admin panel under **Billing > Google Wallet**:

- **Enable/Disable** Google Wallet payments
- **Environment** selection (Live or Sandbox)
- **Merchant ID** and **Merchant Key** for both live and sandbox environments

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.en.html) license.
