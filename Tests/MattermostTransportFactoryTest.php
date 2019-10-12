<?php

namespace Notifier\Bridge\Mattermost\Tests;

use Notifier\Bridge\Mattermost\MattermostMessageOptions;
use Notifier\Bridge\Mattermost\MattermostTransport;
use Notifier\Bridge\Mattermost\MattermostTransportFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MattermostTransportFactoryTest extends TestCase
{
    /** @var string */
    private $token;
    /** @var string */
    private $channel;

    /** @var MockObject|HttpClientInterface */
    private $httpClient;

    /** @var MattermostTransportFactory */
    private $transportFactory;

    public function setUp(): void
    {
        $this->token = 'testToken';
        $this->channel = 'testChannel';
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->transportFactory = new MattermostTransportFactory(null, $this->httpClient);
    }

    public function test_create_from_string(): void
    {
        $dsn = 'mattermost://token@localhost?channel=test';
        $transport = $this->transportFactory->create(Dsn::fromString($dsn));
        $this->assertInstanceOf(MattermostTransport::class, $transport);
        $this->assertSame($dsn, $transport->__toString());
    }

    public function test_create_unsupported(): void
    {
        $this->expectException(UnsupportedSchemeException::class);
        $dsn = 'test://token@localhost?channel=test';
        $this->transportFactory->create(Dsn::fromString($dsn));
    }

    public function test_supports(): void
    {
        $this->assertTrue($this->transportFactory->supports(Dsn::fromString('mattermost://token@localhost?channel=test')));
        $this->assertFalse($this->transportFactory->supports(Dsn::fromString('test://user@localhost?channel=test')));
    }
}
