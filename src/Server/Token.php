<?php

namespace DoSomething\Gateway\Server;

use Carbon\Carbon;
use Lcobucci\JWT\Token as JwtToken;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @property string      $client
 * @property string|null $id
 * @property string[]    $scopes
 * @property string|null $role
 */
class Token
{
    /**
     * The current HTTP request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The path to the public key.
     *
     * @var string
     */
    protected $publicKey;

    /**
     * The parsed and validated access token.
     *
     * @var \Lcobucci\JWT\Token
     */
    protected $token;

    /**
     * Create a TokenValidator.
     *
     * @param $publicKey
     */
    public function __construct($request, $publicKey)
    {
        $this->request = $request;
        $this->publicKey = $publicKey;
    }

    /**
     * Check if a valid token was provided.
     *
     * @return bool
     */
    public function exists()
    {
        if (! $this->token) {
            $this->token = $this->parseToken();
        }

        return ! empty($this->token);
    }

    /**
     * Get the Client ID from the token.
     *
     * @return string
     */
    public function client()
    {
        return $this->getClaim('aud');
    }

    /**
     * Get the User ID from the token, if set.
     *
     * @return string|null
     */
    public function id()
    {
        return $this->getClaim('sub');
    }

    /**
     * Get the scopes granted to the token.
     *
     * @return array
     */
    public function scopes()
    {
        $scopes = $this->getClaim('scopes');

        return ! empty($scopes) ? $scopes : [];
    }

    /**
     * Get the role for the user who requested this token.
     *
     * @return string|null
     */
    public function role()
    {
        $role = $this->getClaim('role');

        return ! empty($role) ? $role : null;
    }

    /**
     * Return the JWT for the current request.
     *
     * @return null|string
     */
    public function jwt()
    {
        if (! $this->token) {
            $this->token = $this->parseToken();
        }

        return $this->token ? (string) $this->token : null;
    }

    /**
     * Get the given claim from the JWT.
     *
     * @return mixed|null
     */
    protected function getClaim($claim)
    {
        if (! $this->token) {
            $this->token = $this->parseToken();
        }

        return $this->token ? $this->token->getClaim($claim) : null;
    }

    /**
     * Parse and validate the token from the request.
     *
     * @throws AccessDeniedHttpException
     * @return JwtToken|null
     */
    protected function parseToken()
    {
        if (! $this->request->hasHeader('Authorization')) {
            return null;
        }

        try {
            // Attempt to parse and validate the JWT
            $jwt = $this->request->bearerToken();
            $token = (new Parser())->parse($jwt);
            if (! $token->verify(new Sha256(), file_get_contents($this->publicKey))) {
                throw new AccessDeniedHttpException('Access token could not be verified');
            }

            // Ensure access token hasn't expired
            $data = new ValidationData();
            $data->setCurrentTime(Carbon::now()->timestamp);
            if ($token->validate($data) === false) {
                throw new AccessDeniedHttpException('Access token is invalid');
            }

            // We've made it! Save the details on the validator.
            return $token;
        } catch (\InvalidArgumentException $exception) {
            throw new AccessDeniedHttpException('Could not parse JWT.');
        } catch (\RuntimeException $exception) {
            throw new AccessDeniedHttpException('Error while decoding to JSON');
        }
    }

    /**
     * Dynamically retrieve claims on the token.
     *
     * @param  string  $claim
     * @return mixed
     */
    public function __get($claim)
    {
        if (in_array($claim, ['id', 'scopes', 'role', 'client', 'jwt'])) {
            return $this->{$claim}();
        }

        return null;
    }
}
