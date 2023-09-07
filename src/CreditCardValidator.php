<?php

namespace Jlorente\CreditCards;

use BadMethodCallException;
use InvalidArgumentException;
use SplPriorityQueue;

/**
 * Class CreditCardValidator.
 * 
 * CreditCard package is used to validate, format and obtain information about 
 * a credit card number.
 * 
 * Configuration of the credit cards can be found in CreditCardTypes class. Feel 
 * free to contribute adding new credit card configurations.
 * 
 * You can define the allowed card types on class instantiation by providing an 
 * array with the values of the class constants. If you do not provide the 
 * array, all the card types of the package will be used to validate card 
 * numbers.
 * 
 * The most useful methods of the class are isValid($cardNumber) to check the 
 * validity of a card number, is($cardType, $cardNumber) to check if a card is 
 * from a specific type and getType($cardNumber) to get the 
 * CreditCardTypeConfiguration object that matches the given card number. With 
 * this object, you can validate the security code of the card, check the Luhn 
 * algorithm or format the card number as the expected pretty format for the 
 * card.
 * 
 * Additionally, you can use the following methods, which are a shortcut to the 
 * "is" method.
 * 
 * @method bool isVisa($cardNumber)
 * @method bool isMastercard($cardNumber)
 * @method bool isAmericanExpress($cardNumber)
 * @method bool isDinersClub($cardNumber)
 * @method bool isDiscover($cardNumber)
 * @method bool isJCB($cardNumber)
 * @method bool isUnionPay($cardNumber)
 * @method bool isMaestro($cardNumber)
 * @method bool isElo($cardNumber)
 * @method bool isMir($cardNumber)
 * @method bool isHiper($cardNumber)
 * @method bool isHiperCard($cardNumber)
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class CreditCardValidator
{

    const TYPE_VISA = 'visa';
    const TYPE_MASTERCARD = 'mastercard';
    const TYPE_AMERICAN_EXPRESS = 'american-express';
    const TYPE_DINERS_CLUB = 'diners-club';
    const TYPE_DISCOVER = 'discover';
    const TYPE_JCB = 'jcb';
    const TYPE_UNIONPAY = 'unionpay';
    const TYPE_MAESTRO = 'maestro';
    const TYPE_ELO = 'elo';
    const TYPE_MIR = 'mir';
    const TYPE_HIPER = 'hiper';
    const TYPE_HIPERCARD = 'hipercard';

    /**
     * Map to help magic __call find the correct type for types with special
     * characters.
     * 
     * @var array 
     */
    protected $methodMap = [
        'americanexpress' => self::TYPE_AMERICAN_EXPRESS
    ];

    /**
     * Array of credit card configuration objects.
     * 
     * @var array 
     */
    protected $typesInfo;

    /**
     * 
     * @var array 
     */
    protected $allowedTypes;

    /**
     * 
     * @return array
     */
    public static function getFullTypesList()
    {
        return [
            self::TYPE_VISA
            , self::TYPE_MASTERCARD
            , self::TYPE_AMERICAN_EXPRESS
            , self::TYPE_DINERS_CLUB
            , self::TYPE_DISCOVER
            , self::TYPE_JCB
            , self::TYPE_UNIONPAY
            , self::TYPE_MAESTRO
            , self::TYPE_ELO
            , self::TYPE_MIR
            , self::TYPE_HIPER
            , self::TYPE_HIPERCARD
        ];
    }

    /**
     * CreditCardValidator static constructor.
     * 
     * @param array $allowedTypes
     * @return \static
     */
    public static function make(array $allowedTypes = [])
    {
        return new static($allowedTypes);
    }

    /**
     * CreditCard class contructor.
     * 
     * @param array $allowedTypes
     */
    public function __construct(array $allowedTypes = [])
    {
        if ($allowedTypes) {
            $this->setAllowedTypesList($allowedTypes);
        } else {
            $this->allowedTypes = static::getFullTypesList();
        }
    }

    /**
     * Gets the best CreditCardTypeConfig object that matches the given card number.
     * 
     * @param string|int $cardNumber
     * @return CreditCardTypeConfig|null
     */
    public function getType($cardNumber)
    {
        $candidate = null;
        $candidateStrength = 0;

        foreach ($this->getTypesInfo() as $config) {
            if ($config->matches($cardNumber)) {
                $strength = $config->getMatchingPatternStrength($cardNumber);
                if ($strength > $candidateStrength) {
                    $candidate = $config;
                    $candidateStrength = $strength;
                }
            }
        }

        return $candidate;
    }

    /**
     * Checks if the credit card number is valid.
     * 
     * @param string $cardNumber
     * @return bool
     */
    public function isValid($cardNumber)
    {
        foreach ($this->getTypesInfo() as $config) {
            if ($config->matches($cardNumber)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the credit card number is a card of the given type.
     * 
     * @param string $cardType
     * @param string $cardNumber
     * @return bool
     */
    public function is($cardType, $cardNumber)
    {
        $bestMatch = $this->getType($cardNumber);
        return $bestMatch ? $bestMatch->getType() === $cardType : false;
    }

    /**
     * Gets the allowed types list for this object.
     * 
     * @return array
     */
    public function getAllowedTypesList()
    {
        return $this->allowedTypes;
    }

    /**
     * Set allowed types list for this CreditCardValidator object.
     * 
     * @param array $types
     */
    public function setAllowedTypesList(array $types)
    {
        $this->allowedTypes = array_intersect($types, static::getFullTypesList());
    }

    /**
     * Gets the credit card typesInfo objects.
     * 
     * @return array|CreditCardTypeConfig[]
     */
    public function getTypesInfo()
    {
        if ($this->typesInfo === null) {
            $this->loadTypesInfo();
        }

        return $this->typesInfo;
    }

    /**
     * Checks if the object has the credit card type configuration.
     * 
     * @param string $cardType
     * @return bool
     */
    public function hasTypeInfo($cardType)
    {
        $typesInfo = $this->getTypesInfo();
        return isset($typesInfo[$cardType]);
    }

    /**
     * Gets the credit card configuration.
     * 
     * @param string $cardType
     * @return CreditCardTypeConfig
     */
    public function getTypeInfo($cardType)
    {
        $typesInfo = $this->getTypesInfo();
        return isset($typesInfo[$cardType]) ? $typesInfo[$cardType] : null;
    }

    /**
     * Magic call support to forward call to is() method.
     * 
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 2) === 'is') {
            if (isset($arguments[0]) === false) {
                throw new InvalidArgumentException('Card number must be provided');
            }

            $cardNumber = $arguments[0];
            $method = strtolower(substr($name, 2));
            if (isset($this->methodMap[$method])) {
                return $this->is($this->methodMap[$method], $cardNumber);
            }

            return $this->is($method, $cardNumber);
        } else {
            throw new BadMethodCallException("Call to undefined method [$name]");
        }
    }

    /**
     * Loads the credit cards typesInfo.
     * 
     * @return $this
     */
    protected function loadTypesInfo()
    {
        $typesInfo = [];
        $cardTypesList = CreditCardTypeConfigList::get();
        foreach ($this->getAllowedTypesList() as $card) {
            if (isset($cardTypesList[$card])) {
                $typesInfo[$card] = new CreditCardTypeConfig($cardTypesList[$card]);
            }
        }

        $this->typesInfo = $typesInfo;
        return $this;
    }

}
