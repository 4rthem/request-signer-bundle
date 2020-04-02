<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle\Tests;

use Arthem\RequestSignerBundle\RequestSigner;
use Arthem\RequestSignerBundle\SignerAdapterInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class RequestSignerTest extends TestCase
{
    public function testUriSigner()
    {
        $signer = $this->createRequestSigner();

        $parentRequest = SymfonyRequest::create('http://foo.bar/list');
        $signedUri = $signer->signUri('http://foo.bar/baz', $parentRequest);

        $this->assertEquals('http://foo.bar/baz?token=signed-token', $signedUri);
    }

    public function testRequestSigner()
    {
        $signer = $this->createRequestSigner();

        $request = SymfonyRequest::create('http://foo.bar/baz', 'GET');

        $signedRequest = $signer->signRequest($request);

        $this->assertInstanceOf(SymfonyRequest::class, $signedRequest);
        $this->assertEquals($signedRequest->getUri(), $request->getUri().'?token=signed-token');
    }

    private function createRequestSigner(): RequestSigner
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        $httpFoundationFactory = new HttpFoundationFactory();

        $adapter = $this->createMock(SignerAdapterInterface::class);

        $adapter
            ->method('signRequest')
            ->willReturn(new ServerRequest('GET', 'http://foo.bar/baz?token=signed-token'));

        return new RequestSigner($psrHttpFactory, $httpFoundationFactory, ['default' => $adapter], 'default');
    }
}
