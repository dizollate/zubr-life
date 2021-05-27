<?php

namespace Tests;

use App\Auth\NotAuthorized;
use App\Auth\NotInGroups;
use App\TelegramAdapter;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use function Psl\Json\encode;

class SimpleTest extends WebTestCase
{
    public function testAnonymous(): void
    {
        $client = static::createClient();
        $client->request('POST', '/comment');
        $response = $client->getResponse();
        $this->assertEquals(
            encode(['error' => (new NotAuthorized())->getMessage()]),
            $response->getContent(),
            $response->getContent()
        );
    }

    public function testAuthorized(): void
    {
        $client = static::createClient();
        $param  = self::$container->getParameter('private_key');
        $cookie = new Cookie(
            'AUTH_TOKEN',
            JWT::encode(
                ['id' => 1],
                file_get_contents($param),
                'RS256'
            )
        );
        $mock   = $this->createMock(TelegramAdapter::class);
        $mock->expects(static::any())->method('isUserInAllowedGroups')->willReturn(true);

        self::$container->set(TelegramAdapter::class, $mock);
        $client->getCookieJar()->set($cookie);

        $client->request('POST', '/comment', [], [], []);

        $response = $client->getResponse();
        $this->assertEquals('[]', $response->getContent(), $response->getContent());
    }

    public function testUserNotInGroups(): void
    {
        $client = static::createClient();
        $param  = self::$container->getParameter('private_key');
        $cookie = new Cookie('AUTH_TOKEN', JWT::encode(['id' => 2], file_get_contents($param), 'RS256'));
        $mock   = $this->createMock(TelegramAdapter::class);
        $mock->expects(static::any())->method('isUserInAllowedGroups')->willReturn(false);

        self::$container->set(TelegramAdapter::class, $mock);
        $client->getCookieJar()->set($cookie);

        $client->request('POST', '/comment', [], [], []);

        $response = $client->getResponse();
        $this->assertEquals(
            encode(['error' => (new NotInGroups())->getMessage()]),
            $response->getContent(),
            $response->getContent()
        );
    }

    public function testInvalidJWT(): void
    {
        $client = static::createClient();
        $cookie = new Cookie('AUTH_TOKEN', 'foobar');
        $client->getCookieJar()->set($cookie);

        $client->request('POST', '/comment', [], [], []);

        $response = $client->getResponse();
        $this->assertEquals(
            encode(['error' => (new NotAuthorized())->getMessage()]),
            $response->getContent(),
            $response->getContent()
        );
    }

    public function testAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login', [], [], []);

        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
    }
}
