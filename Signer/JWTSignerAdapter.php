<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle\Signer;

use Arthem\JWTRequestSigner\JWTRequestSigner;
use Arthem\RequestSignerBundle\SignatureVerifierInterface;
use Arthem\RequestSignerBundle\SignerAdapterInterface;
use Psr\Http\Message\RequestInterface;

class JWTSignerAdapter implements SignerAdapterInterface, SignatureVerifierInterface
{
    /**
     * @var JWTRequestSigner
     */
    private $signer;

    public function __construct(JWTRequestSigner $signer)
    {
        $this->signer = $signer;
    }

    public function signRequest(RequestInterface $request, array $options = []): RequestInterface
    {
        return $this->signer->signRequest($request);
    }

    public function verifySignature(RequestInterface $request): void
    {
        $this->signer->validateSignedRequest($request);
    }
}
