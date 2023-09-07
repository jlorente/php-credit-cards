<?php

namespace Jlorente\CreditCards\Tests;

use InvalidArgumentException;
use Jlorente\CreditCards\CreditCardTypeConfig;
use PHPUnit\Framework\TestCase;

/**
 * Class CreditCardTypeConfigTest
 * 
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class CreditCardTypeConfigTest extends TestCase
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
     * @group CreditCardTypeConfigTest
     * @doesNotPerformAssertions
     */
    public function testValidClassConstruction()
    {
        new CreditCardTypeConfig($this->getValidConfigMock());
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['type'] = 1;

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidPatternsSimpleElement()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['patterns'][0] = 'bad';

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidPatternsRangeElement()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['patterns'][1][0] = 'bad';

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidPatternsRangeMinElementGreaterThanMax()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['patterns'][1][0] = 90;

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidGapsElement()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['gaps'][0] = 'bad';

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidLengthsSimpleElement()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['lengths'][0] = 'bad';

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidLengthsRangeElement()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['lengths'][1][0] = 'bad';

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidLengthsRangeMinElementGreaterThanMax()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['lengths'][1][0] = 90;

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorMissingCodeName()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        unset($mock['code']['name']);

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorMissingCodeSize()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        unset($mock['code']['size']);

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorInvalidCodeSize()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        $mock['code']['size'] = 'bad';

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testClassConstructionErrorMissingLuhnCheckParam()
    {
        $this->expectException(InvalidArgumentException::class);

        $mock = $this->getValidConfigMock();
        unset($mock['luhnCheck']);

        new CreditCardTypeConfig($mock);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberMatchesExactLength()
    {
        $mock = $this->getValidConfigMock();
        $mock['lengths'] = [5];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesLengths('12345');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberMatchesRangeLength()
    {
        $mock = $this->getValidConfigMock();
        $mock['lengths'] = [[5, 11]];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesLengths('123456789');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberDontMatchExactLength()
    {
        $mock = $this->getValidConfigMock();
        $mock['lengths'] = [4];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesLengths('123456');

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberDontMatchRangeLength()
    {
        $mock = $this->getValidConfigMock();
        $mock['lengths'] = [[4, 8]];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesLengths('123456789');

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberSatisfiesLuhn()
    {
        $mock = $this->getValidConfigMock();

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->satisfiesLuhn('4242424242424242');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberSatisfiesLuhnB()
    {
        $mock = $this->getValidConfigMock();

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->satisfiesLuhn('371449635398431');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberDontSatisfyLuhn()
    {
        $mock = $this->getValidConfigMock();

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->satisfiesLuhn('4242424242424243');

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberMatchesExactPattern()
    {
        $mock = $this->getValidConfigMock();
        $mock['patterns'] = ['2212'];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesPatterns('2212699458');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberMatchesRangePattern()
    {
        $mock = $this->getValidConfigMock();
        $mock['patterns'] = [[578, 99164]];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesPatterns('762389197581');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberDontMatchExactPattern()
    {
        $mock = $this->getValidConfigMock();
        $mock['patterns'] = ['2212'];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesPatterns('2213');

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberDontMatchRangePattern()
    {
        $mock = $this->getValidConfigMock();
        $mock['patterns'] = [[41, 816]];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesPatterns('91231929');

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testSecurityCodeMatchesSize()
    {
        $mock = $this->getValidConfigMock();
        $mock['code']['size'] = 4;

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesSecurityCode('1234');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testSecurityCodeDontMatchSize()
    {
        $mock = $this->getValidConfigMock();
        $mock['code']['size'] = 4;

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->matchesSecurityCode('12345');

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberCompleteValidationUsingMatches()
    {
        $creditCardConfig = new CreditCardTypeConfig([
            'niceType' => 'Test Card',
            'type' => 'test-card',
            'patterns' => [
                272012,
                [54, 89],
            ],
            'gaps' => [4, 10],
            'lengths' => [
                [14, 16],
            ],
            'code' => [
                'name' => 'CVV',
                'size' => 3,
            ],
            'luhnCheck' => true,
        ]);

        $result = $creditCardConfig->matches('5555555555554444');

        $this->assertTrue($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberCompleteValidationFailsUsingMatches()
    {
        $creditCardConfig = new CreditCardTypeConfig([
            'niceType' => 'Test Card',
            'type' => 'test-card',
            'patterns' => [
                272012,
                [54, 89],
            ],
            'gaps' => [4, 10],
            'lengths' => [
                [14, 16],
            ],
            'code' => [
                'name' => 'CVV',
                'size' => 3,
            ],
            'luhnCheck' => true,
        ]);

        $result = $creditCardConfig->matches('30569309025904');

        $this->assertFalse($result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberFormatAccordingToGapConfiguration()
    {
        $mock = $this->getValidConfigMock();
        $mock['gaps'] = [3, 6, 9];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->format('1234567890123456');

        $this->assertEquals('123 456 789 0123456', $result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testCardNumberFormatAccordingToGapConfigurationB()
    {
        $mock = $this->getValidConfigMock();
        $mock['gaps'] = [4, 10];

        $creditCardConfig = new CreditCardTypeConfig($mock);
        $result = $creditCardConfig->format('1234567890123456789');

        $this->assertEquals('1234 567890 123456789', $result);
    }

    /**
     * @group CreditCardTypeConfigTest
     */
    public function testGetMatchingPatternStrengthReturnsTheGreatestStrengthThatSatifiesTheCoincidence()
    {
        $creditCardConfig = new CreditCardTypeConfig([
            'niceType' => 'Test Card',
            'type' => 'test-card',
            'patterns' => [
                27,
                278,
                [27895683013, 27895683018],
                278956,
                27895683,
                2,
            ],
            'gaps' => [4, 10],
            'lengths' => [
                16,
            ],
            'code' => [
                'name' => 'CVV',
                'size' => 3,
            ],
            'luhnCheck' => false,
        ]);

        $result = $creditCardConfig->matches('2789568301312234');

        $this->assertEquals(11, $result);
    }
}
