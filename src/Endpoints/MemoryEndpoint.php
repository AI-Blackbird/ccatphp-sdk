<?php

namespace Albocode\CcatphpSdk\Endpoints;

use Albocode\CcatphpSdk\DTO\Api\Memory\CollectionsOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\CollectionsDestroyOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\ConversationHistoryDeleteOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\ConversationHistoryOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\MemoryPointDeleteOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\MemoryPointOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\MemoryPointsDeleteByMetadataOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\MemoryPointsOutput;
use Albocode\CcatphpSdk\DTO\Api\Memory\MemoryRecallOutput;
use Albocode\CcatphpSdk\DTO\ConversationHistoryInfo;
use Albocode\CcatphpSdk\DTO\Memory;
use Albocode\CcatphpSdk\DTO\MemoryPoint;
use Albocode\CcatphpSdk\DTO\Why;
use Albocode\CcatphpSdk\Enum\Collection;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class MemoryEndpoint extends AbstractEndpoint
{
    protected string $prefix = '/memory';

    // -- Memory Collections API

    /**
     * This endpoint returns the collections of memory points, either for the agent identified by the agentId parameter
     * (for multi-agent installations) or for the default agent (for single-agent installations).
     *
     * @throws GuzzleException
     */
    public function getMemoryCollections(?string $agentId = null): CollectionsOutput
    {
        return $this->get(
            $this->formatUrl('/collections'),
            CollectionsOutput::class,
            $agentId,
        );
    }

    /**
     * This endpoint deletes the collections of memory points, either for the agent identified by the agentId parameter
     * (for multi-agent installations) or for the default agent (for single-agent installations).
     *
     * @throws GuzzleException
     */
    public function deleteMemoryCollections(?string $agentId = null): CollectionsDestroyOutput
    {
        return $this->delete(
            $this->formatUrl('/collections'),
            CollectionsDestroyOutput::class,
            $agentId,
        );
    }

    /**
     * This method deletes a collection of memory points, either for the agent identified by the agentId parameter
     * (for multi-agent installations) or for the default agent (for single-agent installations).
     *
     * @throws GuzzleException
     */
    public function deleteMemoryCollection(Collection $collection, ?string $agentId = null): CollectionsDestroyOutput
    {
        return $this->delete(
            $this->formatUrl('/collections/' . $collection->value),
            CollectionsDestroyOutput::class,
            $agentId,
        );
    }

    // END Memory Collections API --

    // -- Memory Conversation History API

    /**
     * This endpoint returns the conversation history, either for the agent identified by the agentId parameter
     * (for multi-agent installations) or for the default agent (for single-agent installations). If the userId
     * parameter is provided, the conversation history is filtered by the user ID.
     *
     * @throws GuzzleException|\JsonException|RuntimeException
     */
    public function getConversationHistory(?string $agentId = null, ?string $userId = null): ConversationHistoryOutput
    {
        $httpClient = $this->getHttpClient($agentId, $userId);
        $jsonResponse = $httpClient->get($this->formatUrl('/conversation_history'))
            ->getBody()
            ->getContents();

        $responseArray = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);

        $result = new ConversationHistoryOutput();

        $conversationHistory = [];
        foreach ($responseArray['history'] as $history) {
            $historyItem = new ConversationHistoryInfo();
            $historyItem->who = $history['who'];
            $historyItem->message = $history['message'];
            $historyItem->when = $history['when'];
            $historyItem->role = $history['role'];

            if (!empty($historyWhy = $history['why'])) {
                $historyItemWhy = new Why();

                $historyItemWhy->input = $historyWhy['input'];
                $historyItemWhy->intermediateSteps = $historyWhy['intermediate_steps'] ?? [];
                $historyItemWhy->modelInteractions = $historyWhy['model_interactions'] ?? [];

                $historyItemWhyMemory = new Memory();
                $historyItemWhyMemory->declarative = $historyWhy['memory']['declarative'] ?? [];
                $historyItemWhyMemory->episodic = $historyWhy['memory']['episodic'] ?? [];
                $historyItemWhyMemory->procedural = $historyWhy['memory']['procedural'] ?? [];

                $historyItemWhy->memory = $historyItemWhyMemory;

                $historyItem->why = $historyItemWhy;
            }

            $conversationHistory[] = $historyItem;
        }

        $result->history = $conversationHistory;
        return $result;
    }

    /**
     * This endpoint deletes the conversation history, either for the agent identified by the agentId parameter
     * (for multi-agent installations) or for the default agent (for single-agent installations). If the userId
     * parameter is provided, the conversation history is filtered by the user ID.
     *
     * @throws GuzzleException
     */
    public function deleteConversationHistory(
        ?string $agentId = null,
        ?string $userId = null
    ): ConversationHistoryDeleteOutput {
        return $this->delete(
            $this->formatUrl('/conversation_history'),
            ConversationHistoryDeleteOutput::class,
            $agentId,
            $userId,
        );
    }

    // END Memory Conversation History API --

    // -- Memory Points API
    /**
     * This endpoint retrieves memory points based on the input text, either for the agent identified by the agentId
     * parameter (for multi-agent installations) or for the default agent (for single-agent installations). The text
     * parameter is the input text for which the memory points are retrieved. The k parameter is the number of memory
     * points to retrieve.
     * If the userId parameter is provided, the memory points are filtered by the user ID.
     *
     * @throws GuzzleException
     */
    public function getMemoryRecall(
        string $text,
        ?int $k = null,
        ?string $agentId = null,
        ?string $userId = null,
    ): MemoryRecallOutput {
        $query = ['text' => $text];
        if ($k) {
            $query['k'] = $k;
        }

        return $this->get(
            $this->formatUrl('/recall'),
            MemoryRecallOutput::class,
            $agentId,
            $userId,
            $query,
        );
    }

    /**
     * This method posts a memory point, either for the agent identified by the agentId parameter (for multi-agent
     * installations) or for the default agent (for single-agent installations).
     * If the userId parameter is provided, the memory point is associated with the user ID.
     *
     * @throws GuzzleException
     */
    public function postMemoryPoint(
        Collection $collection,
        MemoryPoint $memoryPoint,
        ?string $agentId = null,
        ?string $userId = null,
    ): MemoryPointOutput {
        if ($userId && empty($memoryPoint->metadata["source"])) {
            $memoryPoint->metadata = !empty($memoryPoint->metadata)
                ? $memoryPoint->metadata + ["source" => $userId]
                : ["source" => $userId];
        }

        return $this->postJson(
            $this->formatUrl('/collections/' . $collection->value . '/points'),
            MemoryPointOutput::class,
            $memoryPoint->toArray(),
            $agentId,
        );
    }

    /**
     * This endpoint retrieves a memory point, either for the agent identified by the agentId parameter (for multi-agent
     * installations) or for the default agent (for single-agent installations).
     *
     * @throws GuzzleException
     */
    public function deleteMemoryPoint(
        Collection $collection,
        string $pointId,
        ?string $agentId = null,
    ): MemoryPointDeleteOutput {
        return $this->delete(
            $this->formatUrl('/collections/' . $collection->value . '/points/'. $pointId),
            MemoryPointDeleteOutput::class,
            $agentId,
        );
    }

    /**
     * This endpoint retrieves memory points based on the metadata, either for the agent identified by the agentId
     * parameter (for multi-agent installations) or for the default agent (for single-agent installations). The metadata
     * parameter is a dictionary of key-value pairs that the memory points must match.
     *
     * @param array<string, mixed>|null $metadata
     *
     * @throws GuzzleException
     */
    public function deleteMemoryPointsByMetadata(
        Collection $collection,
        ?array $metadata = null,
        ?string $agentId = null,
    ): MemoryPointsDeleteByMetadataOutput {
        return $this->delete(
            $this->formatUrl('/collections/' . $collection->value . '/points'),
            MemoryPointsDeleteByMetadataOutput::class,
            $agentId,
            null,
            $metadata ?? null,
        );
    }

    /**
     * This endpoint retrieves memory points, either for the agent identified by the agentId parameter (for multi-agent
     * installations) or for the default agent (for single-agent installations). The limit parameter is the maximum
     * number of memory points to retrieve. The offset parameter is the number of memory points to skip.
     *
     * @throws GuzzleException
     */
    public function getMemoryPoints(
        Collection $collection,
        ?int $limit = null,
        ?int $offset = null,
        ?string $agentId = null,
    ): MemoryPointsOutput {
        $query = [];
        if ($limit !== null) {
            $query['limit'] = $limit;
        }
        if ($offset !== null) {
            $query['offset'] = $offset;
        }

        return $this->get(
            $this->formatUrl('/collections/' . $collection->value . '/points'),
            MemoryPointsOutput::class,
            $agentId,
            null,
            $query,
        );
    }

    // END Memory Points API --
}