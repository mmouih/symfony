<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Bridge\Sendinblue\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Sendinblue\Transport\SendinblueApiTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\LogicException;
use Symfony\Component\Mime\RemoteEmail;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Mounir Mouih <mounir.mouih@gmail.com>
 */
class SendinblueApiTransportRemoteEmailTest extends TestCase
{
    /**
     * Testing sending a remote email using Email class.
     */
    public function testSendRemoteEmailExpectFails()
    {
        $client = $this->prepareHttpClientMock();

        $transport = new SendinblueApiTransport('ACCESS_KEY', $client);
        $transport->setPort(8984);

        $mail = new Email();
        $mail->subject('Remote Email with Email Mime Object!')
        ->to(new Address('mounir.mouih@gmail.com', 'Mounir Mouih'))
        ->from(new Address('fabpot@symfony.com', 'Fabien'))
        ->addCc('foo@bar.fr')
        ->addBcc('foo@bar.fr')
        ->addReplyTo('foo@bar.fr')
        ->getHeaders()->addTextHeader('templateId', 1);

        $this->expectException(LogicException::class);
        $transport->send($mail);
    }

    public function testSendRemoteEmail()
    {
        $client = $this->prepareHttpClientMock();

        $transport = new SendinblueApiTransport('ACCESS_KEY', $client);
        $transport->setPort(8984);

        $mail = new RemoteEmail();
        $mail
            ->setRemoteTemplate('templateId', 1)
            ->subject('Remote Email with Mime Object !')
            ->to(new Address('mounir.mouih@gmail.com', 'Mounir Mouih'))
            ->from(new Address('fabpot@symfony.com', 'Fabien'))
            ->addCc('foo@bar.fr')
            ->addBcc('foo@bar.fr')
            ->addReplyTo('foo@bar.fr');

        $message = $transport->send($mail);
        $this->assertSame('foobar', $message->getMessageId());
    }

    private function prepareHttpClientMock()
    {
        return new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api.sendinblue.com:8984/v3/smtp/email', $url);
            $this->assertStringContainsString('Accept: */*', $options['headers'][2] ?? $options['request_headers'][1]);

            return new MockResponse(json_encode(['messageId' => 'foobar']), [
                'http_code' => 201,
            ]);
        });
    }
}
