<?php

namespace Albocode\CcatphpSdk\Tests;

use Albocode\CcatphpSdk\Builders\SettingInputBuilder;
use Albocode\CcatphpSdk\Tests\Traits\TestTrait;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class LargeLanguageModelEndpointTest extends TestCase
{
    use TestTrait;

    /**
     * @throws GuzzleException|\JsonException|Exception
     */
    public function testGetLargeLanguageModelsSettingsSuccess(): void
    {
        $expected = [
            'settings' => [
                [
                    'name' => 'testLargeLanguageModel',
                    'value' => [
                        'property_first' => 'value_first',
                        'property_second' => 'value_second',
                    ],
                ],
            ],
            'selected_configuration' => 'testLargeLanguageModel',
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->largeLanguageModel();
        $result = $endpoint->getLargeLanguageModelsSettings();

        foreach ($expected['settings'] as $key => $setting) {
            self::assertEquals($setting['name'], $result->settings[$key]->name);
            foreach ($setting['value'] as $property => $value) {
                self::assertEquals($value, $result->settings[$key]->value[$property]);
            }
        }
        self::assertEquals($expected['selected_configuration'], $result->selectedConfiguration);
    }

    /**
     * @throws GuzzleException|\JsonException|Exception
     */
    public function testGetLargeLanguageModelSettingsSuccess(): void
    {
        $expected = [
            'name' => 'testLargeLanguageModel',
            'value' => [
                'property_first' => 'value_first',
                'property_second' => 'value_second',
            ],
            'scheme' => [
                'property_first' => 'value_first',
                'property_second' => 'value_second',
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->largeLanguageModel();
        $result = $endpoint->getLargeLanguageModelSettings('testLargeLanguageModel');

        foreach ($expected as $property => $value) {
            /** @var array<string, string> $property */
            if (in_array($property, ['scheme', 'value'])) {
                /** @var array<string, string> $value */
                foreach ($value as $subProperty => $subValue) {
                    self::assertEquals($subValue, $result->scheme[$subProperty]);
                }
            } else {
                self::assertEquals($value, $result->$property);
            }
        }
    }

    /**
     * @throws GuzzleException|\JsonException|Exception
     */
    public function testPutLargeLanguageModelSettingsSuccess(): void
    {
        $expected = [
            'name' => 'testLargeLanguageModel',
            'value' => [
                'property_first' => 'value_first',
                'property_second' => 'value_second',
            ],
            'scheme' => [
                'property_first' => 'value_first',
                'property_second' => 'value_second',
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);
        $settingInput = SettingInputBuilder::create()
            ->setName($expected['name'])
            ->setValue($expected['value'])
            ->setCategory('testCategory')
            ->build();

        $endpoint = $cCatClient->largeLanguageModel();
        $result = $endpoint->putLargeLanguageModelSettings('testLargeLanguageModel', $settingInput);

        foreach ($expected as $property => $value) {
            /** @var array<string, string> $property */
            if (in_array($property, ['scheme', 'value'])) {
                /** @var array<string, string> $value */
                foreach ($value as $subProperty => $subValue) {
                    self::assertEquals($subValue, $result->scheme[$subProperty]);
                }
            } else {
                self::assertEquals($value, $result->$property);
            }
        }
    }
}