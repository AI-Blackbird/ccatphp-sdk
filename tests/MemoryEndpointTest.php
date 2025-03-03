<?php

namespace Albocode\CcatphpSdk\Tests;

use Albocode\CcatphpSdk\Builders\MemoryBuilder;
use Albocode\CcatphpSdk\Builders\MemoryPointBuilder;
use Albocode\CcatphpSdk\Builders\WhyBuilder;
use Albocode\CcatphpSdk\CcatUtility;
use Albocode\CcatphpSdk\DTO\Api\Memory\MemoryPointsOutput;
use Albocode\CcatphpSdk\Enum\Collection;
use Albocode\CcatphpSdk\Enum\Role;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\MockObject\Exception;

class MemoryEndpointTest extends BaseTest
{
    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testGetMemoryCollectionsSuccess(): void
    {
        $expected = [
            'collections' => [
                ['name' => 'episodic', 'vectors_count' => 100],
                ['name' => 'declarative', 'vectors_count' => 100],
                ['name' => 'procedural', 'vectors_count' => 100],
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->getMemoryCollections();

        foreach ($expected['collections'] as $key => $collection) {
            self::assertEquals($collection['name'], $result->collections[$key]->name);
            self::assertEquals($collection['vectors_count'], $result->collections[$key]->vectorsCount);
        }
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testDeleteMemoryCollectionsSuccess(): void
    {
        $expected = [
            'deleted' => [
                'episodic' => true,
                'declarative' => false,
                'procedural' => true,
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->deleteAllMemoryCollectionPoints();

        foreach ($expected['deleted'] as $key => $value) {
            self::assertEquals($value, $result->deleted[$key]);
        }
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testDeleteMemoryCollectionSuccess(): void
    {
        $expected = [
            'deleted' => [
                'episodic' => true,
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->deleteAllSingleMemoryCollectionPoints(Collection::Episodic);

        self::assertEquals($expected['deleted']['episodic'], $result->deleted['episodic']);
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testGetConversationHistorySuccess(): void
    {
        $expected = [
            'history' => [
                [
                    'who' => 'Human',
                    'when' => 0.0,
                    'content' => [
                        'text' => 'Hey you!',
                        'images' => [],
                        'audio' => [],
                    ],
                ],
                [
                    'who' => 'AI',
                    'when' => 0.1,
                    'content' => [
                        'text' => 'Hi!',
                        'images' => [],
                        'audio' => [],
                    ],
                ],
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->getConversationHistory();

        self::assertEquals($expected, $result->toArray());
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testDeleteConversationHistorySuccess(): void
    {
        $expected = ['deleted' => true];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->deleteConversationHistory();

        self::assertEquals($expected['deleted'], $result->deleted);
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testPostConversationHistorySuccess(): void
    {
        $expected = [
            'history' => [
                [
                    'who' => 'Human',
                    'when' => 0.0,
                    'content' => [
                        'text' => 'Hey you!',
                        'images' => [],
                        'audio' => [],
                    ],
                ],
                [
                    'who' => 'AI',
                    'when' => 0.1,
                    'content' => [
                        'text' => 'Hi!',
                        'images' => [],
                        'audio' => [],
                        'why' => [
                            'input' => 'input',
                            'intermediate_steps' => [],
                            'model_interactions' => [],
                            'memory' => [
                                'episodic' => [],
                                'declarative' => [],
                                'procedural' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $memory = MemoryBuilder::create()
            ->setEpisodic($expected['history'][1]['content']['why']['memory']['episodic'])
            ->setDeclarative($expected['history'][1]['content']['why']['memory']['declarative'])
            ->setProcedural($expected['history'][1]['content']['why']['memory']['procedural'])
            ->build();

        $why = WhyBuilder::create()
            ->setInput($expected['history'][1]['content']['why']['input'])
            ->setIntermediateSteps($expected['history'][1]['content']['why']['intermediate_steps'])
            ->setModelInteractions($expected['history'][1]['content']['why']['model_interactions'])
            ->setMemory($memory)
            ->build();

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->postConversationHistory(
            Role::AI,
            $expected['history'][1]['content']['text'],
            $expected['history'][1]['content']['images'],
            $expected['history'][1]['content']['audio'],
            $why,
        );

        self::assertEquals($expected, $result->toArray());
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testGetMemoryRecallSuccess(): void
    {
        $expected = [
            'query' => ['text' => 'test', 'vector' => []],
            'vectors' => [
                'embedder' => 'testEmbedder',
                'collections' => [
                    'episodic' => [],
                    'procedural' => [],
                    'declarative' => [],
                ],
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->getMemoryRecall($expected['query']['text']);

        self::assertEquals($expected['vectors']['embedder'], $result->vectors->embedder);
    }

    //the same but with postMemoryRecall
    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testPostMemoryRecallSuccess(): void
    {
        $expected = [
            'query' => ['text' => 'test', 'vector' => []],
            'vectors' => [
                'embedder' => 'testEmbedder',
                'collections' => [
                    'episodic' => [],
                    'procedural' => [],
                    'declarative' => [],
                ],
            ],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->postMemoryRecall($expected['query']['text']);

        self::assertEquals($expected['vectors']['embedder'], $result->vectors->embedder);
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testPostMemoryPointSuccess(): void
    {
        $expected = [
            'content' => 'test',
            'metadata' => [],
            'id' => 'test_test_test',
            'vector' => [],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();

        $memoryPoint = MemoryPointBuilder::create()
            ->setContent($expected['content'])
            ->setMetadata($expected['metadata'])
            ->build();
        $result = $endpoint->postMemoryPoint(Collection::Declarative, $memoryPoint);

        self::assertEquals($expected['id'], $result->id);
        self::assertEquals($expected['vector'], $result->vector);
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testPutMemoryPointSuccess(): void
    {
        $expected = [
            'content' => 'test',
            'metadata' => [],
            'id' => 'test_test_test',
            'vector' => [],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();

        $memoryPoint = MemoryPointBuilder::create()
            ->setContent($expected['content'])
            ->setMetadata($expected['metadata'])
            ->build();
        $result = $endpoint->putMemoryPoint(Collection::Declarative, $memoryPoint, $expected['id']);

        self::assertEquals($expected['id'], $result->id);
        self::assertEquals($expected['vector'], $result->vector);
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testDeleteMemoryPointSuccess(): void
    {
        $expected = [
            'deleted' => 'test',
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->deleteMemoryPoint(Collection::Declarative, $expected['deleted']);

        self::assertEquals($expected['deleted'], $result->deleted);
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testDeleteMemoryPointsByMetadataSuccess(): void
    {
        $metadata = [
            'property1' => 'value1',
            'property2' => 'value2',
            'property3' => 'value3',
            'property4' => 'value4',
            'property5' => 'value5',
        ];

        $expected = [
            'deleted' => ['operation_id' => 21212414, 'status' => 'ok'],
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->deleteMemoryPointsByMetadata(Collection::Declarative, $metadata);

        foreach ($expected['deleted'] as $key => $value) {
            self::assertEquals($value, $result->deleted->{CcatUtility::camelCase($key)});
        }
    }

    /**
     * @throws GuzzleException|Exception|\JsonException
     */
    public function testGetMemoryPointsSuccess(): void
    {
        $expected = [
            'points' => [
                ['id' => 'dgwrgsehsreysery'],
                ['id' => 'weuhg42jdgouwrow4ls'],
            ],
            'next_offset' => 0,
        ];

        $cCatClient = $this->getCCatClient($this->apikey, $expected);

        $endpoint = $cCatClient->memory();
        $result = $endpoint->getMemoryPoints(Collection::Declarative);

        self::assertInstanceOf(MemoryPointsOutput::class, $result);
    }
}