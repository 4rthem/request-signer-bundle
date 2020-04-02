<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle;

use Psr\Http\Message\RequestInterface;

interface SignerAdapterInterface
{
    /**
     * Creates a signed token and return a cloned request with the token included.
     */
    public function signRequest(RequestInterface $request): RequestInterface;
}
