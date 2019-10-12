<?php

namespace Notifier\Bridge\Mattermost\Tests;

use Notifier\Bridge\Mattermost\MattermostMessageOptions;
use Notifier\Bridge\Mattermost\MattermostTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MattermostTransportTest extends TestCase
{
    /** @var string */
    private $token;
    /** @var string */
    private $channel;

    /** @var MockObject|HttpClientInterface */
    private $httpClient;

    /** @var MattermostTransport */
    private $transport;

    public function setUp(): void
    {
        $this->token      = 'testToken';
        $this->channel    = 'testChannel';
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->transport  = new MattermostTransport($this->token, $this->channel, $this->httpClient);
    }

    public function test_transport(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $channel        = 'testChannel';
        $messageOptions = new MattermostMessageOptions(
            $channel, 'webhookTest', 'iconUrl'
        );
        $this->assertSame($channel, $messageOptions->getRecipientId());
        $this->transport->send(new ChatMessage('testMessage', $messageOptions));
    }

    public function test_transport_fallback_channel(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $this->transport->send(new ChatMessage('testMessage'));
    }

    public function test_to_string(): void
    {
        $this->assertSame('mattermost://' . $this->token . '@localhost?channel=' . $this->channel, $this->transport->__toString());
    }

    public function test_supports(): void
    {
        $this->assertTrue($this->transport->supports(new ChatMessage('test')));
        $this->assertFalse($this->transport->supports($this->createMock(MessageInterface::class)));
    }

    public function test_send_fail(): void
    {
        $this->expectException(LogicException::class);
        $this->transport->send($this->createMock(MessageInterface::class));
    }

    public function test_transport_fail(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to post the Mattermost message: Invalid webhook (400: web.incoming_webhook.invalid.app_error).');
        $response = $this->createMock(ResponseInterface::class);
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $response->expects($this->once())->method('getStatusCode')->willReturn(400);
        $response->expects($this->once())->method('toArray')->willReturn([
            'id' => 'web.incoming_webhook.invalid.app_error',
            'message' => 'Invalid webhook',
            'detailed_error' => '',
            'request_id' => 'ging73md9qmadtest',
            'status_code' => 400
        ]);

        $this->transport->send(new ChatMessage(
                'testMessage',
                new MattermostMessageOptions(
                    'testChannel', 'webhookTest', 'iconUrl'
                )
            )
        );
    }
}
