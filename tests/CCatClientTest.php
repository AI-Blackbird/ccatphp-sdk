<?php

use Albocode\CcatphpSdk\Clients\HttpClient;
use Albocode\CcatphpSdk\Clients\WSClient;
use Albocode\CcatphpSdk\Model\Message;
use Albocode\CcatphpSdk\Model\Response;

class CCatClientTest extends \PHPUnit\Framework\TestCase
{
    public function testSendMessage()
    {

        $cCatClient = new \Albocode\CcatphpSdk\CCatClient($this->getWSClientMock(), $this->getHttpClientMock());
        $result = $cCatClient->sendMessage(new Message('Hello message'));
        $this->assertTrue($result instanceof Response, 'Always true');
    }

    private function getWSClientMock()
    {
        $catResponse = file_get_contents('tests/cat_response.json');
        $wsClMock = $this->createMock(\WebSocket\Client::class);
        $wsClMock->method('receive')->willReturn($catResponse);
        $wsClientMock = $this->createMock(WSClient::class);
        $wsClientMock->method('getWsClient')->willReturn($wsClMock);
        return $wsClientMock;
    }

    private function getHttpClientMock()
    {
        return $this->createMock(HttpClient::class);
    }
}