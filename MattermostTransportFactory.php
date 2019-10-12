<?php

namespace Notifier\Bridge\Mattermost;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

/**
 * @author Jeroen Spee <spee.jeroen@gmail.com>
 */
final class MattermostTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $token = $this->getUser($dsn);
        $channel = $dsn->getOption('channel');
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();

        if ('mattermost' === $scheme) {
            return (new MattermostTransport($token, $channel, $this->client, $this->dispatcher))
                ->setHost($host)
                ->setPort($port);
        }

        throw new UnsupportedSchemeException($dsn, 'mattermost', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['mattermost'];
    }
}
