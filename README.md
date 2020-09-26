PHP Credit Cards
================

A PHP package to perform operations on debit and credit cards like format, validate 
brand, number and Luhn algorithm. It validates popular brands like Visa, Mastercard, 
American Express, etc.

This package is based on the [braintree/credit-card-type](https://github.com/braintree/credit-card-type) 
javascript package. All the card types configuration have been extracted from it.

## Current Cart Type Validators

* Visa
* Mastercard
* American Express
* Diners Club
* Discover
* JCB
* UnionPay
* Maestro
* Elo
* Mir
* Hiper
* Hipercard

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

With Composer installed, you can then install the extension using the following commands:

```bash
$ php composer.phar require jlorente/php-credit-cards
```

or add 

```json
...
    "require": {
        "jlorente/php-credit-cards": "*"
    }
```

to the ```require``` section of your `composer.json` file.

## Usage

You can create an instance of the validator either by using the common 
constructor or the static make method.

```php
$validator = new CreditCardValidator();
```

or 

```php
$validator = CreditCardValidator::make();
```

By default, the validator will load the configuration of all the card types that 
come along with the package, but you can limit the allowed types by providing 
an array with the card type codes.

```php
$validator = new CreditCardValidator([
    CreditCardValidator::TYPE_VISA,
    CreditCardValidator::TYPE_MASTERCARD,
]);
```

or

```php
$validator = CreditCardValidator::make([
    CreditCardValidator::TYPE_VISA,
    CreditCardValidator::TYPE_MASTERCARD,
]);
```

### Validating a credit card number without knowing the type

```php
$validator->isValid('4242424242424242');
```

### Validating a credit card number knowing the type

```php
$validator->is(CreditCardValidator::TYPE_VISA, '4242424242424242');
```

or 

```php
$validator->isVisa('4242424242424242');
```

### Get the type configuration of a card number

```php
$typeConfig = $validator->getType('4242424242424242');
```

With the type configuration you can know metadata info, perform some validation 
or format the card number using the class methods.

#### CreditCardTypeConfig

|Method|Description|Return example|
|---|---|---|
|getType(): string|Get the type of the card type configuration|"visa", "mastercard"|
|getNiceType(): string|Get the nice type of the card type configuration|"Visa", "Mastercard"|
|getPatterns(): array|Get the patterns that the card type configuration uses to validate a card number|[50, [55, 59]]|
|getGaps(): array|Get the index of the position in card number string where to put blank spaces on card formatting|[4, 8, 12]|
|getLengths(): array|Get the allowed lengths that the card type configuration uses to validate the card numbers|[16, 19]|
|getCode(): string|Get the security code configuration of the card type|["name" => "CVC", "size" => 4]|
|getLuhnCheck(): bool|Get the luhn check value of the configuration|true|
|setLuhnCheck(bool $value): $this|Set the luhn check value. If true, the validator will validate the card number with the Luhn's algorithm||
|matches(string $cardNumber): bool|Check if the given card number matches the card type configuration|false|
|matchesPatterns(string $cardNumber): bool|Check if the card number matches one of the patterns array configuration|true|
|matchesLengths(string $cardNumber): bool|Check if the card number matches one of the lengths array configuration|true|
|satisfiesLuhn(string $cardNumber): bool|Check if the card number satisfies the luhn's algorithm|true|
|matchesSecurityCode(string $cardNumber): bool|Check if the card number satisfies the luhn's algorithm|false|
|format(string $cardNumber): string|Format the card number according to the gap configuration|"4242 4242 4242 4242"|

## Contribute

Feel free to add new credit card configurations or fix the current ones and 
create a pull request to keep the package up to date.

A credit type configuration has the following structure:

```php
[
    'example-card' => [
        'niceType' => 'Test Card',    // Display name
        'type' => 'example-card',     // Type/Code name
        'patterns' => [               // Valid patterns for the card
            272012,                   // Simple validator: true if the card begins with the pattern 272012
            [5, 89],                  // Range validator: true if the card initial two digits value is between 5 and 89 both included
        ],
        'gaps' => [4, 10],            // Values where to put white spaces on pretty card formatting. In this example: XXXX XXXXXX XXXXXX
        'lengths' => [                // Valid lengths for the card
            15,                       // Simple validator: True if length is exactly 15
            [17, 19],                 // Range validator: True if length is between 17 and 19 both included
        ],
        'code' => [                   // Security code configuration
            'name' => 'CVV',          // Name of the security code
            'size' => 3,              // Valid length of the security code
        ],
        'luhnCheck' => true           // To validate the Luhn's algorithm when calling matches
    ],
];
```

## License 
Copyright &copy; 2020 José Lorente Martín <jose.lorente.martin@gmail.com>.

Licensed under the MIT License. See LICENSE.txt for details.
