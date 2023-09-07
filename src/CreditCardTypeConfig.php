<?php

namespace Jlorente\CreditCards;

use InvalidArgumentException;
use SplQueue;

/**
 * Class CreditCardTypeConfig.
 * 
 * Class used to validate and store the configuration of a credit card type.
 * 
 * The configuration can be loaded using the load method and providing a valid 
 * configuration array.
 * 
 * e.g.:
 * ```php
 * $config = new CreditCardTypeConfig([
 *     'niceType' => 'Visa',
 *     'type' => 'visa',
 *     'patterns' => [
 *         4,
 *     ],
 *     'gaps' => [4, 8, 12],
 *     'lengths' => [
 *         16,
 *         18,
 *         19,
 *     ],
 *     'code' => [
 *         'name' => 'CVV',
 *         'size' => 3,
 *     ],
 * ]);
 * ```
 *
 * @see CreditCardTypeConfigList
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class CreditCardTypeConfig
{

    /**
     *
     * @var string 
     */
    protected $niceType;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     * An array of integers or arrays values with two elements specifying a range.
     * 
     * e.g.:
     * ```php
     * $patterns = [
     *   50,
     *   [5212, 6234],
     *   [743335, 872344],
     * ];
     * ```
     * 
     * @var array
     */
    protected $patterns = [];

    /**
     * An array of integers used to pretty format the card number values by specifying
     * where are the gaps indexes between the number blocks.
     * 
     * e.g.:
     * ```php
     * $gapsA = [4, 8, 12]; // 4242 4242 4242 4242
     * $gapsB = [5, 10]; // 72323 12312 12345
     * ```
     * 
     * @var array 
     */
    protected $gaps = [];

    /**
     * An array of integers or arrays values with two elements specifying a range.
     * 
     * e.g.:
     * ```php
     * $lengths = [
     *   15,
     *   [10, 13],
     *   [17, 20],
     * ];
     * ```
     * 
     * @var array
     */
    protected $lengths = [];

    /**
     * The configuration of the security code validator (CVV, CVC, CVE, etc.)
     * 
     * e.g.:
     * ```php
     * $code = [
     *   'name' => 'CVV',
     *   'size' => 3,
     * ];
     * ```
     * 
     * @var array
     */
    protected $code;

    /**
     * Specifies if the card number should satisfy the luhn check to match the 
     * configuration.
     * 
     * @var bool 
     */
    protected $luhnCheck = true;

    /**
     * CreditCardTypeConfig constructor.
     * 
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->load($config);
    }

    /**
     * Gets the nice type of the card type configuration (e.g. "Visa", "Mastercard").
     * 
     * @return string
     */
    public function getNiceType()
    {
        return $this->niceType;
    }

    /**
     * Gets the type of the card type configuration (e.g. "visa", "mastercard").
     * 
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the patterns that this card type configuration uses to validate a card number.
     * 
     * @return string
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    /**
     * Gets the gaps used by this configuration to pretty format a card number.
     * 
     * @return string
     */
    public function getGaps()
    {
        return $this->gaps;
    }

    /**
     * Gets the lengths that this card type configuration uses to validate the card numbers.
     * 
     * @return string
     */
    public function getLengths()
    {
        return $this->lengths;
    }

    /**
     * Gets the security code configuration of the card type.
     * 
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Gets the luhn check configuration of this object.
     * 
     * @return bool
     */
    public function getLuhnCheck()
    {
        return $this->luhnCheck;
    }

    /**
     * Sets the luhn check configuration for this object.
     * 
     * @param bool $value
     * @return $this For fluent configuration of the object.
     * @throws InvalidArgumentException
     */
    protected function setLuhnCheck($value)
    {
        if (is_bool($value) === false) {
            throw new InvalidArgumentException('Invalid luhnCheck configuration property provided');
        }

        $this->luhnCheck = (bool) $value;

        return $this;
    }

    /**
     * Checks if the given card number matches this card type configuration.
     * 
     * @param string|int $cardNumber
     * @return bool
     */
    public function matches($cardNumber)
    {
        if ($this->matchesLengths($cardNumber) === false) {
            return false;
        }

        if ($this->getLuhnCheck() && $this->satisfiesLuhn($cardNumber) === false) {
            return false;
        }

        return $this->matchesPatterns($cardNumber);
    }

    /**
     * Checks if the card number matches one of the patterns array configuration.
     *
     * @param string|int $cardNumber
     * @return int The greatest strength with which it matches or 0 if it does not match.
     */
    public function getMatchingPatternStrength($cardNumber)
    {
        $strength = 0;
        foreach ($this->getPatterns() as $pattern) {
            if ($this->matchesPattern($cardNumber, $pattern)) {
                $s = $this->getPatternStrength($pattern);
                if ($s > $strength) {
                    $strength = $s;
                }
            }
        }

        return $strength;
    }

    /**
     * Checks if the card number matches one of the patterns array configuration.
     * 
     * @param string|int $cardNumber
     * @return bool
     */
    public function matchesPatterns($cardNumber)
    {
        foreach ($this->getPatterns() as $pattern) {
            if ($this->matchesPattern($cardNumber, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the card number matches one of the lengths array configuration.
     * 
     * @param string $cardNumber
     * @return boolean
     */
    public function matchesLengths($cardNumber)
    {
        foreach ($this->getLengths() as $length) {
            if ($this->matchesLength($cardNumber, $length)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the card number satisfies the luhn's algorithm.
     * 
     * @param string|int $cardNumber
     * @return bool
     */
    public function satisfiesLuhn($cardNumber)
    {
        $stringCardNumber = (string) $cardNumber;

        $checkSum = 0;
        $length = strlen($stringCardNumber);
        for ($i = $length - 1; $i >= 0; $i -= 1) {
            if (($length - $i) % 2 === 0) {
                $val = (string) ((int) $stringCardNumber[$i] * 2);
                if (strlen($val) > 1) {
                    $val = (int) $val[0] + (int) $val[1];
                }
            } else {
                $val = (int) $stringCardNumber[$i];
            }

            $checkSum += $val;
        }

        return $checkSum % 10 === 0;
    }

    /**
     * Checks whether the security code matches the card type configuration or not.
     * 
     * @param string $securityCode
     * @return bool
     */
    public function matchesSecurityCode($securityCode)
    {
        return $this->isDigits($securityCode) && $this->code['size'] === strlen($securityCode);
    }

    /**
     * Formats the card number according to the gap configuration.
     * 
     * @param string|int $cardNumber
     * @return string
     */
    public function format($cardNumber)
    {
        $gaps = $this->getGapsQueue();

        $formatted = '';
        $gap = $gaps->dequeue();
        for ($i = 0, $l = strlen($cardNumber); $i < $l; $i += 1) {
            if ($i === $gap) {
                $formatted .= ' ';
                $gap = $gaps->isEmpty() === false ? $gaps->dequeue() : null;
            }

            $formatted .= $cardNumber[$i];
        }

        return $formatted;
    }

    /**
     * Loads the card type configuration object.
     * 
     * @param array $config
     * @return $this For fluent configuration of the object.
     */
    protected function load(array $config)
    {
        if (is_array($config) === false) {
            throw new InvalidArgumentException('Configuration object must be an array');
        }

        if (isset($config['niceType']) === false) {
            throw new InvalidArgumentException('niceType must be provided in the configuration object');
        }

        if (isset($config['type']) === false) {
            throw new InvalidArgumentException('type must be provided in the configuration object');
        }

        if (isset($config['patterns']) === false) {
            throw new InvalidArgumentException('patterns must be provided in the configuration object');
        }

        if (isset($config['gaps']) === false) {
            throw new InvalidArgumentException('gaps must be provided in the configuration object');
        }

        if (isset($config['lengths']) === false) {
            throw new InvalidArgumentException('lengths must be provided in the configuration object');
        }

        if (isset($config['code']) === false) {
            throw new InvalidArgumentException('code must be provided in the configuration object');
        }

        if (isset($config['luhnCheck']) === false) {
            throw new InvalidArgumentException('luhnCheck must be provided in the configuration object');
        }

        $this->setNiceType($config['niceType'])
                ->setType($config['type'])
                ->setPatterns($config['patterns'])
                ->setGaps($config['gaps'])
                ->setLengths($config['lengths'])
                ->setCode($config['code'])
                ->setLuhnCheck($config['luhnCheck']);

        return $this;
    }

    /**
     * Gets a queue composed by the gaps array for iteration purposes.
     * 
     * @return SplQueue
     */
    protected function getGapsQueue()
    {
        $queue = new SplQueue();
        foreach ($this->getGaps() as $gap) {
            $queue->enqueue($gap);
        }

        return $queue;
    }

    /**
     * Sets the nice type of the card type configuration.
     * 
     * @param string $value
     * @return $this For fluent configuration of the object.
     * @throws InvalidArgumentException
     */
    protected function setNiceType($value)
    {
        if (is_string($value) === false) {
            throw new InvalidArgumentException('Invalid niceType provided');
        }

        $this->niceType = $value;

        return $this;
    }

    /**
     * Sets the type of the card type configuration.
     * 
     * @param string $value
     * @return $this For fluent configuration of the object.
     * @throws InvalidArgumentException
     */
    protected function setType($value)
    {
        if (is_string($value) === false) {
            throw new InvalidArgumentException('Invalid type provided');
        }

        $this->type = $value;

        return $this;
    }

    /**
     * Sets the patterns used by this configuration to validate a card number.
     * 
     * @param array $values
     * @return $this For fluent configuration of the object.
     * @throws InvalidArgumentException
     */
    protected function setPatterns(array $values)
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (count($value) !== 2) {
                    throw new InvalidArgumentException('Pattern elements provided as array range should contain exactly two elements');
                }

                if ($this->isDigits($value[0]) === false || $this->isDigits($value[1]) === false) {
                    throw new InvalidArgumentException('Pattern range elements should be integers or strings representing integer values');
                }

                $value[0] = (int) $value[0];
                $value[1] = (int) $value[1];

                if (0 > $value[0] || $value[0] > $value[1]) {
                    throw new InvalidArgumentException('Pattern range min element should be greater than zero and less than max element');
                }
            } else {
                if ($this->isDigits($value) === false) {
                    throw new InvalidArgumentException('Pattern elements should be integers or strings representing integer values');
                }

                $value = (int) $value;
            }
        }

        $this->patterns = $values;

        return $this;
    }

    /**
     * Sets the gaps used by this configuration to pretty format a card number.
     * 
     * @param array $values
     * @return $this For fluent configuration of the object.
     * @throws InvalidArgumentException
     */
    protected function setGaps(array $values)
    {
        foreach ($values as $value) {
            if ($this->isDigits($value) === false) {
                throw new InvalidArgumentException('Gaps elements should be integers or strings representing integer values');
            }
        }

        $this->gaps = $values;

        return $this;
    }

    /**
     * Sets the lengths used by this configuration to validate a card number.
     * 
     * @param array $values
     * @return $this For fluent configuration of the object.
     * @throws InvalidArgumentException
     */
    protected function setLengths(array $values)
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (count($value) !== 2) {
                    throw new InvalidArgumentException('Length elements provided as array range should contain exactly two elements');
                }

                if ($this->isDigits($value[0]) === false || $this->isDigits($value[1]) === false) {
                    throw new InvalidArgumentException('Length range elements should be integers or strings representing integer values');
                }

                $value[0] = (int) $value[0];
                $value[1] = (int) $value[1];

                if (0 > $value[0] || $value[0] > $value[1]) {
                    throw new InvalidArgumentException('Length range min element should be greater than zero and less than max element');
                }
            } else {
                if ($this->isDigits($value) === false) {
                    throw new InvalidArgumentException('Length elements should be integers or strings representing integer values');
                }

                $value = (int) $value;
            }
        }

        $this->lengths = $values;

        return $this;
    }

    /**
     * Sets the code validator configuration to validate a card number.
     * 
     * @param array $config
     * @return $this For fluent configuration of the object.
     * @throws InvalidArgumentException
     */
    protected function setCode(array $config)
    {
        if (isset($config['name']) === false || is_string($config['name']) === false) {
            throw new InvalidArgumentException('Code name must be provided in the configuration object and must be a string');
        }

        if (isset($config['size']) === false || $this->isDigits($config['size']) === false) {
            throw new InvalidArgumentException('Code size must be provided in the configuration object and must be an integer or a string representing an integer value');
        }

        $config['size'] = (int) $config['size'];

        $this->code = $config;

        return $this;
    }

    /**
     * Gets the pattern matching strength value.
     * 
     * @param string|int $pattern
     * @return int
     */
    protected function getPatternStrength($pattern)
    {
        if (is_array($pattern)) {
            return min(strlen($pattern[0]), strlen($pattern[1]));
        }

        return strlen($pattern);
    }

    /**
     * Checks if the card number matches the pattern.
     * 
     * @param string|int $cardNumber
     * @param string|int|array $pattern
     * @return bool
     */
    protected function matchesPattern($cardNumber, $pattern)
    {
        if (is_array($pattern)) {
            return $this->matchesRange($cardNumber, $pattern[0], $pattern[1]);
        }

        return $this->matchesSimplePattern($cardNumber, $pattern);
    }

    /**
     * Checks if the card number matches a simple pattern.
     * 
     * @param string|int $cardNumber
     * @param string|int $pattern
     * @return bool
     */
    protected function matchesSimplePattern($cardNumber, $pattern)
    {
        return substr($cardNumber, 0, strlen($pattern)) === substr($pattern, 0, strlen($cardNumber));
    }

    /**
     * Checks if the card number matches the given pattern range.
     * 
     * @param string|int $cardNumber
     * @param string|int $min
     * @param string|int $max
     * @return bool
     */
    protected function matchesRange($cardNumber, $min, $max)
    {
        $maxLength = max(strlen($min), strlen($max));
        $intCardNumber = (int) substr($cardNumber, 0, $maxLength);
        $intMin = (int) $min;
        $intMax = (int) $max;

        return $intMin <= $intCardNumber && $intCardNumber <= $intMax;
    }

    /**
     * Checks if the card number matches the given length configuration.
     * 
     * @param string|int $cardNumber
     * @param string|int $length
     * @return bool
     */
    protected function matchesLength($cardNumber, $length)
    {
        if (is_array($length)) {
            return $this->matchesLengthRange($cardNumber, $length[0], $length[1]);
        }

        return $this->matchesSimpleLength($cardNumber, $length);
    }

    /**
     * Checks if the card number matches the given scalar length.
     * 
     * @param string|int $cardNumber
     * @param string|int $length
     * @return bool
     */
    protected function matchesSimpleLength($cardNumber, $length)
    {
        return strlen($cardNumber) === $length;
    }

    /**
     * Checks if the card number matches a length range.
     * 
     * @param string|int $cardNumber
     * @param string|int $min
     * @param string|int $max
     * @return bool
     */
    protected function matchesLengthRange($cardNumber, $min, $max)
    {
        $cardNumberLength = strlen($cardNumber);
        return $min <= $cardNumberLength && $cardNumberLength <= $max;
    }

    /**
     * Checks if the value is formed by digits or not.
     * 
     * @param string|int $value
     * @return bool
     */
    protected function isDigits($value)
    {
        return !!preg_match('/^[0-9]+$/', (string) $value);
    }

}
