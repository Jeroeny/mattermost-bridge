<?php

namespace Notifier\Bridge\Mattermost;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class MattermostMessageOptions implements MessageOptionsInterface
{
    /** @var string the channel to send the message to. in case of a person, prefixed with '@' */
    private $channel;

    /** @var string the username to display above the sent message */
    private $username;

    /** @var string the avatar to display with the sent message */
    private $icon_url;

    public function __construct(string $channel, string $username, string $icon_url)
    {
        $this->channel  = $channel;
        $this->username = $username;
        $this->icon_url = $icon_url;
    }

    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'username' => $this->username,
            'icon_url' => $this->icon_url,
        ];
    }

    public function getRecipientId(): ?string
    {
        return $this->channel;
    }
}
