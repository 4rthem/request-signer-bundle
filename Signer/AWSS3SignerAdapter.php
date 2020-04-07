<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle\Signer;

use Arthem\RequestSignerBundle\SignerAdapterInterface;
use Aws\S3\S3Client;
use Psr\Http\Message\RequestInterface;

class AWSS3SignerAdapter implements SignerAdapterInterface
{
    /**
     * @var S3Client
     */
    private $client;

    /**
     * @var string
     */
    private $bucketName;

    /**
     * @var int
     */
    private $ttl;

    public function __construct(S3Client $client, string $bucket, int $ttl)
    {
        $this->client = $client;
        $this->bucketName = $bucket;
        $this->ttl = $ttl;
    }

    public function signRequest(RequestInterface $request, array $options = []): RequestInterface
    {
        $cmd = $this->client->getCommand('GetObject', array_merge([
            'Bucket' => $this->bucketName,
            'Key' => ltrim($request->getUri()->getPath(), '/'),
        ], $options));

        return $this->client->createPresignedRequest($cmd, time() + $this->ttl);
    }
}
