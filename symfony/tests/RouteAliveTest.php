<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Generator;

class RouteAliveTest extends WebTestCase
{
    /**
     * @dataProvider providerUdi
     * @PARAM string $uri
     */
    public function testRoute(string $uri): void
    {
        $client = static::createClient();
        $client->request(request::METHOD_GET, uri: $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function providerUdi() : Generator
    {
        yield ['/todo'];
    }
}
