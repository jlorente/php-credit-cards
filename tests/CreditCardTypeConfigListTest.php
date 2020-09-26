<?php

namespace Jlorente\CreditCards\Tests;

use Jlorente\CreditCards\CreditCardTypeConfigList;
use PHPUnit\Framework\TestCase;

/**
 * Class CreditCardTypeConfigListTest
 * 
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class CreditCardTypeConfigListTest extends TestCase
{

    /**
     * 
     * @return array
     */
    protected function getStructure()
    {
        return [
            'niceType',
            'type',
            'patterns',
            'gaps',
            'lengths',
            'code' => [
                'name',
                'size',
            ],
            'luhnCheck',
        ];
    }

    /**
     * @group CreditCardTypeConfigListTest
     */
    public function testConfigurationHasAValidStructure()
    {
        $result = CreditCardTypeConfigList::get();

        $this->assertIsArray($result);

        $structure = $this->getStructure();
        foreach ($result as $config) {
            $this->assertArrayHasStructure($structure, $config);
        }
    }

    /**
     * Asserts that an array contains the given structure.
     * 
     * @param array $structure
     * @param array $array
     */
    protected function assertArrayHasStructure($structure, $array)
    {
        foreach ($structure as $key => $value) {
            if (is_numeric($key) === false || is_array($value)) {
                $this->assertArrayHasKey($key, $array);
                $this->assertArrayHasStructure($value, $array[$key]);
            } else {
                $this->assertArrayHasKey($value, $array);
            }
        }
    }

}
