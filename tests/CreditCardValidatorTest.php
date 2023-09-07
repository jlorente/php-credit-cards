<?php

namespace Jlorente\CreditCards\Tests;

use Jlorente\CreditCards\CreditCardTypeConfig;
use Jlorente\CreditCards\CreditCardValidator;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * Class CreditCardValidatorTest
 * 
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class CreditCardValidatorTest extends MockeryTestCase
{

    /**
     * 
     * @return array
     */
    protected function getValidConfigMock()
    {
        return [
            'niceType' => 'Test Card',
            'type' => 'test-card',
            'patterns' => [
                272012,
                [5, 89],
            ],
            'gaps' => [4, 10],
            'lengths' => [
                15,
                [17, 19],
            ],
            'code' => [
                'name' => 'CVV',
                'size' => 3,
            ],
            'luhnCheck' => true,
        ];
    }

    /**
     * @group CreditCardValidatorTest
     * @doesNotPerformAssertions
     */
    public function testValidClassConstruction()
    {
        new CreditCardValidator();
        CreditCardValidator::make();
    }

    /**
     * @group CreditCardValidatorTest
     * @doesNotPerformAssertions
     */
    public function testValidClassConstructionWithArguments()
    {
        $arguments = [
            CreditCardValidator::TYPE_VISA,
            CreditCardValidator::TYPE_MASTERCARD,
        ];
        new CreditCardValidator($arguments);
        CreditCardValidator::make($arguments);
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetAllowedTypesListReturnAllTheTypesIfNoArgumentsProvidedOnConstruction()
    {
        $validator = CreditCardValidator::make();

        $this->assertEqualsCanonicalizing(CreditCardValidator::getFullTypesList(), $validator->getAllowedTypesList());
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetAllowedTypesListReturnOnlyTheTypesProvidedOnConstruction()
    {
        $validator = CreditCardValidator::make([
                    CreditCardValidator::TYPE_VISA,
                    CreditCardValidator::TYPE_MASTERCARD,
        ]);

        $result = $validator->getAllowedTypesList();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(
                [
                    CreditCardValidator::TYPE_VISA,
                    CreditCardValidator::TYPE_MASTERCARD,
                ],
                $result
        );
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypesInfoReturnAnArrayOfCreditCardTypeConfigObjects()
    {
        $validator = CreditCardValidator::make([
                    CreditCardValidator::TYPE_VISA,
                    CreditCardValidator::TYPE_MASTERCARD,
        ]);

        $results = $validator->getTypesInfo();

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(CreditCardTypeConfig::class, $result);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @group CreditCardValidatorTest
     */
    public function testCreditCardTypeConfigListGetIsCalledOnlyOnce()
    {
        $mock = \Mockery::mock('alias:Jlorente\\CreditCards\\CreditCardTypeConfigList');

        $mock->shouldReceive('get')->andReturn([])->once();

        $validator = CreditCardValidator::make();
        $validator->getTypesInfo();
        $validator->getTypesInfo();
        $validator->getTypesInfo();
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testHasTypeInfoReturnTrueForAnExistingLoadedType()
    {
        $validator = CreditCardValidator::make([
                    CreditCardValidator::TYPE_VISA,
                    CreditCardValidator::TYPE_MASTERCARD,
        ]);

        $result = $validator->hasTypeInfo(CreditCardValidator::TYPE_VISA);

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testHasTypeInfoReturnFalseForANotLoadedType()
    {
        $validator = CreditCardValidator::make([
                    CreditCardValidator::TYPE_VISA,
                    CreditCardValidator::TYPE_MASTERCARD,
        ]);

        $result = $validator->hasTypeInfo(CreditCardValidator::TYPE_AMERICAN_EXPRESS);

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypeInfoReturnACreditCardTypeConfigForAnExistingLoadedType()
    {
        $validator = CreditCardValidator::make([
                    CreditCardValidator::TYPE_VISA,
                    CreditCardValidator::TYPE_MASTERCARD,
        ]);

        $result = $validator->getTypeInfo(CreditCardValidator::TYPE_VISA);

        $this->assertInstanceOf(CreditCardTypeConfig::class, $result);
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypeInfoReturnNullForANotLoadedType()
    {
        $validator = CreditCardValidator::make([
                    CreditCardValidator::TYPE_VISA,
                    CreditCardValidator::TYPE_MASTERCARD,
        ]);

        $result = $validator->getTypeInfo(CreditCardValidator::TYPE_AMERICAN_EXPRESS);

        $this->assertNull($result);
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypeReturnVisaCreditCardTypeConfigObjectForAVisaCardNumber()
    {
        $validator = CreditCardValidator::make();

        $result = $validator->getType('4242424242424242');

        $this->assertInstanceOf(CreditCardTypeConfig::class, $result);
        $this->assertEquals(CreditCardValidator::TYPE_VISA, $result->getType());
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypeReturnMastercardCreditCardTypeConfigObjectForAMastercardCardNumber()
    {
        $validator = CreditCardValidator::make();

        $result = $validator->getType('5555555555554444');

        $this->assertInstanceOf(CreditCardTypeConfig::class, $result);
        $this->assertEquals(CreditCardValidator::TYPE_MASTERCARD, $result->getType());
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypeReturnAmericanExpressCreditCardTypeConfigObjectForAnAmericanExpressCardNumber()
    {
        $validator = CreditCardValidator::make();

        $result = $validator->getType('378282246310005');

        $this->assertInstanceOf(CreditCardTypeConfig::class, $result);
        $this->assertEquals(CreditCardValidator::TYPE_AMERICAN_EXPRESS, $result->getType());
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypeReturnDiscoverCreditCardTypeConfigObjectForADiscoverCardNumber()
    {
        $validator = CreditCardValidator::make();

        $result = $validator->getType('6011111111111117');

        $this->assertInstanceOf(CreditCardTypeConfig::class, $result);
        $this->assertEquals(CreditCardValidator::TYPE_DISCOVER, $result->getType());
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testGetTypeReturnNullForAnInvalidCreditCardNumber()
    {
        $validator = CreditCardValidator::make();

        $result = $validator->getType('12356');

        $this->assertNull($result);
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsValidReturnTrueForValidsCardNumber()
    {
        $validator = CreditCardValidator::make();

        $this->assertTrue($validator->isValid('4242424242424242'));
        $this->assertTrue($validator->isValid('5555555555554444'));
        $this->assertTrue($validator->isValid('378282246310005'));
        $this->assertTrue($validator->isValid('6011111111111117'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsValidReturnFalseForInvalidCardNumbers()
    {
        $validator = CreditCardValidator::make();

        $this->assertFalse($validator->isValid('12345'));
        $this->assertFalse($validator->isValid('67891'));
        $this->assertFalse($validator->isValid('37828224631000539172371231231231245'));
        $this->assertFalse($validator->isValid('TEST'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsReturnTrueForAVisaCardNumber()
    {
        $validator = CreditCardValidator::make();

        $this->assertTrue($validator->is(CreditCardValidator::TYPE_VISA, '4242424242424242'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsReturnTrueForAMastercardCardNumber()
    {
        $validator = CreditCardValidator::make();

        $this->assertTrue($validator->is(CreditCardValidator::TYPE_MASTERCARD, '5555555555554444'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsReturnFalseAskingForVisaCheckAndGivingAMastercardCardNumber()
    {
        $validator = CreditCardValidator::make();

        $this->assertFalse($validator->is(CreditCardValidator::TYPE_VISA, '5555555555554444'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsVisaReturnTrueForAVisaCardNumber()
    {
        $validator = CreditCardValidator::make();

        $this->assertTrue($validator->isVisa('4242424242424242'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsAmericanExpressReturnTrueForAnAmericanExpressCardNumber()
    {
        $validator = CreditCardValidator::make();

        $this->assertTrue($validator->isAmericanExpress('378282246310005'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsReturnFalseAskingForAmericanExpressCheckAndGivingAVisaCardNumber()
    {
        $validator = CreditCardValidator::make();

        $this->assertFalse($validator->isAmericanExpress('4242424242424242'));
    }

    /**
     * @group CreditCardValidatorTest
     */
    public function testIsHipercardDueToTheCompletionOfThePatternIsHigherThanOtherOnes() {
        $validator = CreditCardValidator::make();

        $this->assertTrue($validator->isHiperCard('6062826786276634'));
    }
}
