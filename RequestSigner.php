<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle;

use Arthem\RequestSignerBundle\Exception\InvalidSignatureException;
use Exception;
use InvalidArgumentException;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class RequestSigner
{
    /**
     * @var PsrHttpFactory
     */
    private $psrHttpFactory;
    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;
    /**
     * @var SignerAdapterInterface[]
     */
    private $signerAdapters;
    /**
     * @var string
     */
    private $defaultSigner;

    public function __construct(PsrHttpFactory $psrHttpFactory, HttpFoundationFactory $httpFoundationFactory, array $signerAdapters, string $defaultAdapter)
    {
        $this->psrHttpFactory = $psrHttpFactory;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->signerAdapters = $signerAdapters;
        $this->defaultSigner = $defaultAdapter;
    }

    public function signRequest(SymfonyRequest $request, ?string $signer = null): SymfonyRequest
    {
        return $this->convertToSymfonyRequest(
            $this
            ->getSignerAdapter($signer)
            ->signRequest($this->convertToPsrRequest($request))
        );
    }

    public function validateRequest(SymfonyRequest $request, ?string $adapter = null): void
    {
        $adapter = $this->getSignerAdapter($adapter);
        if (!$adapter instanceof SignatureVerifierInterface) {
            throw new RuntimeException(sprintf('%s does not support validation', get_class($adapter)));
        }

        try {
            $adapter->verifySignature($this->convertToPsrRequest($request));
        } catch (Exception $e) {
            throw new InvalidSignatureException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function signUri(string $uri, SymfonyRequest $currentRequest, ?string $signer = null): string
    {
        $request = $this
            ->convertToPsrRequest($currentRequest)
            ->withUri(new Uri($uri))
            ->withMethod('GET')
        ;

        return $this
            ->getSignerAdapter($signer)
            ->signRequest($request)
            ->getUri()
            ->__toString();
    }

    private function convertToPsrRequest(SymfonyRequest $request): RequestInterface
    {
        return $this->psrHttpFactory->createRequest($request);
    }

    private function convertToSymfonyRequest(RequestInterface $request): SymfonyRequest
    {
        return $this->httpFoundationFactory->createRequest($request);
    }

    private function getSignerAdapter(?string $name): SignerAdapterInterface
    {
        if (null === $name) {
            return $this->signerAdapters[$this->defaultSigner];
        }

        if (!isset($this->signerAdapters[$name])) {
            throw new InvalidArgumentException(sprintf('Signer named "%s" does not exist. Available ones are: %s', $name, implode(', ', array_keys($this->signerAdapters))));
        }

        return $this->signerAdapters[$name];
    }
}
