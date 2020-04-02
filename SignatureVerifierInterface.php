<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle;

use Psr\Http\Message\RequestInterface;

interface SignatureVerifierInterface
{
    public function verifySignature(RequestInterface $request): void;
}
