<?php

namespace Albocode\CcatphpSdk\Endpoints;

use Albocode\CcatphpSdk\DTO\Api\Admins\ResetOutput;
use Albocode\CcatphpSdk\DTO\Api\Plugin\PluginCollectionOutput;
use Albocode\CcatphpSdk\DTO\Api\Plugin\PluginDeleteOutput;
use Albocode\CcatphpSdk\DTO\Api\Plugin\PluginDetailsOutput;
use Albocode\CcatphpSdk\DTO\Api\Plugin\PluginInstallFromRegistryOutput;
use Albocode\CcatphpSdk\DTO\Api\Plugin\PluginInstallOutput;
use Albocode\CcatphpSdk\DTO\Api\Plugin\PluginsSettingsOutput;
use Albocode\CcatphpSdk\DTO\Api\Plugin\Settings\PluginSettingsOutput;
use Albocode\CcatphpSdk\DTO\Api\TokenOutput;
use Albocode\CcatphpSdk\DTO\Api\Admins\AdminOutput;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;

class AdminsEndpoint extends AbstractEndpoint
{
    protected string $prefix = '/admins';

    /**
     * This endpoint is used to get a token for the user. The token is used to authenticate the user in the system. When
     * the token expires, the user must request a new token.
     *
     * @throws GuzzleException
     */
    public function token(string $username, string $password): TokenOutput
    {
        $httpClient = $this->client->getHttpClient()->createHttpClient();

        $response = $httpClient->post($this->formatUrl('/auth/token'), [
            'json' => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        /** @var TokenOutput $result */
        $result = $this->deserialize($response->getBody()->getContents(), TokenOutput::class);

        $this->client->addToken($result->accessToken);

        return $result;
    }

    /**
     * This endpoint is used to create a new admin user in the system.
     *
     * @param array<string, mixed>|null $permissions
     *
     * @throws GuzzleException
     */
    public function postAdmin(string $username, string $password, ?array $permissions = null): AdminOutput
    {
        $payload = [
            'username' => $username,
            'password' => $password,
        ];
        if ($permissions !== null) {
            $payload['permissions'] = $permissions;
        }

        return $this->postJson($this->prefix, AdminOutput::class, $payload, $this->systemId);
    }

    /**
     * This endpoint is used to get a list of admin users in the system.
     *
     * @return AdminOutput[]
     * @throws GuzzleException|\JsonException
     */
    public function getAdmins(?int $limit = null, ?int $skip = null): array
    {
        $query = [];
        if ($limit) {
            $query['limit'] = $limit;
        }
        if ($skip) {
            $query['skip'] = $skip;
        }

        $response = $this->getHttpClient($this->systemId)->get(
            $this->prefix,
            $query ? ['query' => $query] : []
        );

        $response = $this->client->getSerializer()->decode($response->getBody()->getContents(), 'json');
        $result = [];
        foreach ($response as $item) {
            $result[] = $this->deserialize(
                json_encode($item, JSON_THROW_ON_ERROR), AdminOutput::class, 'json'
            );
        }
        return $result;
    }

    /**
     * This endpoint is used to get a specific admin user in the system.
     *
     * @throws GuzzleException
     */
    public function getAdmin(string $adminId): AdminOutput
    {
        return $this->get($this->formatUrl($adminId), AdminOutput::class, $this->systemId);
    }

    /**
     * This endpoint is used to update an admin user in the system.
     *
     * @param array<string, mixed>|null $permissions
     *
     * @throws GuzzleException
     */
    public function putAdmin(
        string $adminId,
        ?string $username = null,
        ?string $password = null,
        ?array $permissions = null,
    ): AdminOutput {
        $payload = [];
        if ($username !== null) {
            $payload['username'] = $username;
        }
        if ($password !== null) {
            $payload['password'] = $password;
        }
        if ($permissions !== null) {
            $payload['permissions'] = $permissions;
        }

        return $this->put($this->formatUrl($adminId), AdminOutput::class, $payload, $this->systemId);
    }

    /**
     * This endpoint is used to delete an admin user in the system.
     *
     * @throws GuzzleException
     */
    public function deleteAdmin(string $adminId): AdminOutput
    {
        return $this->delete($this->formatUrl($adminId), AdminOutput::class, $this->systemId);
    }

    /**
     * This endpoint is used to reset the system to factory settings. This will delete all data in the system.
     *
     * @throws GuzzleException
     */
    public function factoryReset(): ResetOutput
    {
        return $this->postJson(
            $this->formatUrl('/utils/factory_reset/'),
            ResetOutput::class,
            [],
            $this->systemId,
        );
    }

    /**
     * This endpoint is used to reset the agent to factory settings. This will delete all data in the agent.
     *
     * @throws GuzzleException
     */
    public function agentReset(): ResetOutput
    {
        return $this->postJson(
            $this->formatUrl('/utils/agent_reset/'),
            ResetOutput::class,
            [],
            $this->systemId,
        );
    }

    /**
     * This endpoint is used to reset the agent to factory settings. This will delete all data in the agent.
     *
     * @throws GuzzleException
     */
    public function agentDestroy(): ResetOutput
    {
        return $this->postJson(
            $this->formatUrl('/utils/agent_destroy/'),
            ResetOutput::class,
            [],
            $this->systemId,
        );
    }

    /**
     * This endpoint returns the available plugins, at a system level.
     *
     * @throws GuzzleException
     */
    public function getAvailablePlugins(?string $query = null): PluginCollectionOutput
    {
        return $this->get(
            $this->prefix . '/plugins',
            PluginCollectionOutput::class,
            $this->systemId,
            null,
            $query,
        );
    }

    /**
     * This endpoint installs a plugin from a ZIP file.
     *
     * @throws GuzzleException
     */
    public function installPluginFromZip(string $pathZip): PluginInstallOutput
    {
        return $this->postMultipart(
            $this->formatUrl('/plugins/upload'),
            PluginInstallOutput::class,
            [
                [
                    'name' => 'file',
                    'contents' => Utils::tryFopen($pathZip, 'r'),
                    'filename' => basename($pathZip),
                ],
            ],
            $this->systemId,
        );
    }

    /**
     * This endpoint installs a plugin from the registry.
     *
     * @throws GuzzleException
     */
    public function installPluginFromRegistry(string $url): PluginInstallFromRegistryOutput
    {
        return $this->postJson(
            $this->formatUrl('/plugins/upload/registry'),
            PluginInstallFromRegistryOutput::class,
            ['url' => $url],
            $this->systemId,
        );
    }

    /**
     * This endpoint retrieves the plugins settings, i.e. the default ones at a system level.
     *
     * @throws GuzzleException
     */
    public function getPluginsSettings(): PluginsSettingsOutput
    {
        return $this->get(
            $this->formatUrl('/settings'),
            PluginsSettingsOutput::class,
            $this->systemId,
        );
    }

    /**
     * This endpoint retrieves the plugin settings, i.e. the default ones at a system level.
     *
     * @throws GuzzleException
     */
    public function getPluginSettings(string $pluginId): PluginSettingsOutput
    {
        return $this->get(
            $this->formatUrl('/settings/' . $pluginId),
            PluginSettingsOutput::class,
            $this->systemId,
        );
    }

    /**
     * This endpoint retrieves the plugin details, at a system level.
     *
     * @throws GuzzleException
     */
    public function getPluginDetails(string $pluginId): PluginDetailsOutput
    {
        return $this->get(
            $this->formatUrl($pluginId),
            PluginDetailsOutput::class,
            $this->systemId,
        );
    }

    /**
     * This endpoint deletes a plugin, at a system level.
     *
     * @throws GuzzleException
     */
    public function deletePlugin(string $pluginId): PluginDeleteOutput
    {
        return $this->delete(
            $this->formatUrl('/plugins/' . $pluginId),
            PluginDeleteOutput::class,
            $this->systemId,
        );
    }
}