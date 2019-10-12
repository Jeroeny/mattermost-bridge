<?php

namespace Notifier\Bridge\Mattermost;

use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function array_filter;
use function get_class;
use function sprintf;

/**
 * MattermostTransport.
 *
 * @author Jeroen Spee <spee.jeroen@gmail.com>
 *
 * @internal
 */
final class MattermostTransport extends AbstractTransport
{
    /** @var string webhook token */
    private $token;

    /** @var string channel to send a message to by default. prefix with '@' for user */
    private $chatChannel;

    public function __construct(string $token, string $chatChannel, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->token = $token;
        $this->chatChannel = $chatChannel;
        $this->client = $client;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf('mattermost://%s@%s?channel=%s', $this->token, $this->getEndpoint(), $this->chatChannel);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage;
    }

    /**
     * @see https://docs.mattermost.com/developer/webhooks-incoming.html
     */
    protected function doSend(MessageInterface $message): void
    {
        if (!$message instanceof ChatMessage) {
            throw new LogicException(sprintf('The "%s" transport only supports instances of "%s" (instance of "%s" given).', __CLASS__, ChatMessage::class, get_class($message)));
        }

        $endpoint = sprintf('https://%s/hooks/%s', $this->getEndpoint(), $this->token);
        $options = ($opts = $message->getOptions()) ? $opts->toArray() : [];
        if (!isset($options['channel'])) {
            $options['channel'] = $message->getRecipientId() ?: $this->chatChannel;
        }
        $options['text'] = $message->getSubject();
        $response = $this->client->request('POST', $endpoint, [
            'json' => array_filter($options),
        ]);

        if (200 !== $response->getStatusCode()) {
            $result = $response->toArray(false);

            throw new TransportException(sprintf('Unable to post the Mattermost message: %s (%s: %s).', $result['message'], $result['status_code'], $result['id']), $response);
        }
    }
}
