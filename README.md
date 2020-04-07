# Request signer bundle

This bundle helps you to sign requests in order to provide access to protected resources.

[![Build Status](https://travis-ci.com/4rthem/request-signer-bundle.svg?branch=master)](https://travis-ci.com/4rthem/request-signer-bundle)

## Adapters

Supported providers:

- AWS S3 (composer req arthem/jwt-request-signer)
- Local with JWT (composer req arthem/jwt-request-signer)

## Installation & configuration

```bash
composer require arthem/request-signer-bundle
```

Configure your signers:

```yaml
# config/packages/arthem_request_signer.yaml
services:
  s3_client:
    class: Aws\S3\S3Client
    arguments:
    -
      region: us-east-2
      version: "2006-03-01"
      credentials:
        key: '%env(AWS_ACCESS_KEY)%'
        secret: '%env(AWS_SECRET_KEY)%'

arthem_request_signer:
  signers:
    my_local_jwt: # your signer name
      jwt: # signer adapter
        ttl: 120 # in seconds
        signing_key: '%env(resolve:MY_SIGNING_KEY)%'
    aws_images: # your signer name
      aws_s3: # signer adapter
        bucket_name: 'my_bucket'
        service_id: 's3_client' # id of your s3 client service
```

```dotenv
# .env
MY_SIGNING_KEY=change-me
AWS_ACCESS_KEY=change-me
AWS_SECRET_KEY=change-me
```

## Usage

Sign your asset URLs:

```php
<?php
namespace App\Serializer\Normalizer;

use App\Entity\Asset;
use Arthem\RequestSignerBundle\RequestSigner;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class ApiNormalizer
{
    private UrlGeneratorInterface $urlGenerator;
    private RequestSigner $requestSigner;
    private RequestStack $requestStack;

    protected function generateAssetUrl(Asset $asset): string
    {
        return $this->requestSigner->signUri(
            $this->urlGenerator->generate('asset_preview', ['id' => $asset->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->requestStack->getCurrentRequest(),
            [
                'signer' => 'aws_images', // override default adapter (optional)
                'ResponseContentDisposition' => 'attachment; filename=image.jpg', // Force S3 download
            ]
        );
    }
}
```

If validation is made by your application:

```php
<?php
namespace App\Controller;

use Arthem\RequestSignerBundle\RequestSigner;
use Arthem\RequestSignerBundle\Exception\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AssetController
{
    /**
     * @Route("/assets/{id}", name="asset_preview")
     */
    public function previewAction(string $id, Request $request, RequestSigner $requestSigner)
    {
        try {
            $requestSigner->validateRequest($request);
        } catch (InvalidSignatureException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }

        // Stream asset here
    }
}
```
