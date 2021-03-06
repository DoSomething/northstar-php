<?php

namespace DoSomething\Gateway\Common;

use DoSomething\Gateway\Exceptions\BadRequestException;
use DoSomething\Gateway\Exceptions\ForbiddenException;
use DoSomething\Gateway\Exceptions\InternalException;
use DoSomething\Gateway\Exceptions\UnauthorizedException;
use DoSomething\Gateway\Exceptions\ValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class RestApiClient
{
    /**
     * The Guzzle HTTP client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Default headers applied to every request.
     *
     * @var array
     */
    protected $defaultHeaders;

    /**
     * The number of times a request has been attempted.
     *
     * @var int
     */
    protected $attempts;

    /**
     * RestApiClient constructor.
     *
     * @param string $url - Base URL for this Resource API, e.g. https://api.dosomething.org/
     * @param array $overrides
     */
    public function __construct($url, $overrides = [])
    {
        $this->defaultHeaders = [
            'Accept' => 'application/json',
        ];

        $options = [
            'base_uri' => $url,
            'defaults' => [
                'headers' => $this->defaultHeaders,
            ],
        ];

        $this->client = new Client(array_merge($options, $overrides));
    }

    /**
     * Get the base URI for where the request is going to.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    public function getBaseUri()
    {
        return $this->client->getConfig('base_uri');
    }

    /**
     * Get the Guzzle Client created for this instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function getGuzzleClient()
    {
        return $this->client;
    }

    /**
     * Send a GET request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $query - Key-value array of query string values
     * @param bool $withAuthorization - Should this request be authorized?
     * @return array
     */
    public function get($path, $query = [], $withAuthorization = true)
    {
        $options = [
            'query' => $query,
        ];

        return $this->send('GET', $path, $options, $withAuthorization);
    }

    /**
     * Send a POST request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $payload - Body of the POST request
     * @param bool $withAuthorization - Should this request be authorized?
     * @return array
     */
    public function post($path, $payload = [], $withAuthorization = true)
    {
        $options = [
            'json' => $payload,
        ];

        return $this->send('POST', $path, $options, $withAuthorization);
    }

    /**
     * Send a PUT request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $payload - Body of the PUT request
     * @param bool $withAuthorization - Should this request be authorized?
     * @return array
     */
    public function put($path, $payload = [], $withAuthorization = true)
    {
        $options = [
            'json' => $payload,
        ];

        return $this->send('PUT', $path, $options, $withAuthorization);
    }

    /**
     * Send a PATCH request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $payload - Body of the PUT request
     * @param bool $withAuthorization - Should this request be authorized?
     * @return array
     */
    public function patch($path, $payload = [], $withAuthorization = true)
    {
        $options = [
            'json' => $payload,
        ];

        return $this->send('PATCH', $path, $options, $withAuthorization);
    }

    /**
     * Send a DELETE request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param bool $withAuthorization - Should this request be authorized?
     * @return bool
     */
    public function delete($path, $withAuthorization = true)
    {
        $response = $this->send('DELETE', $path, [], $withAuthorization);

        return $this->responseSuccessful($response);
    }

    /**
     * Get the authorization header for a request, if needed.
     * @see AuthorizesWithNorthstar
     *
     * @return array
     */
    protected function getAuthorizationHeader()
    {
        return [];
    }

    /**
     * Clean up after a request is sent.
     * @see AuthorizesWithNorthstar
     *
     * @return void
     */
    protected function cleanUp()
    {
        // ...
    }

    /**
     * Handle unauthorized exceptions.
     *
     * @param string $endpoint - The human-readable route that triggered the error.
     * @param array $response - The body of the response.
     * @param string $method - The HTTP method for the request that triggered the error, for optionally resending.
     * @param string $path - The path for the request that triggered the error, for optionally resending.
     * @param array $options - The options for the request that triggered the error, for optionally resending.
     * @return \GuzzleHttp\Psr7\Response|void
     * @throws UnauthorizedException
     */
    public function handleUnauthorizedException($endpoint, $response, $method, $path, $options)
    {
        throw new UnauthorizedException($endpoint, json_encode($response));
    }

    /**
     * Handle validation exceptions.
     *
     * @param string $endpoint - The human-readable route that triggered the error.
     * @param array $response - The body of the response.
     * @param string $method - The HTTP method for the request that triggered the error, for optionally resending.
     * @param string $path - The path for the request that triggered the error, for optionally resending.
     * @param array $options - The options for the request that triggered the error, for optionally resending.
     * @return \GuzzleHttp\Psr7\Response|void
     * @throws UnauthorizedException
     */
    public function handleValidationException($endpoint, $response, $method, $path, $options)
    {
        $errors = $response['error']['fields'];
        throw new ValidationException($errors, $endpoint);
    }

    /**
     * Send a Northstar API request, and parse any returned validation
     * errors or status codes to present to the user.
     *
     * @param string $method - 'GET', 'POST', 'PUT', or 'DELETE'
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $options - Guzzle options (http://guzzle.readthedocs.org/en/latest/request-options.html)
     * @param bool $withAuthorization - Should this request be authorized?
     * @return \GuzzleHttp\Psr7\Response|void
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws InternalException
     * @throws UnauthorizedException
     * @throws ValidationException
     */
    public function send($method, $path, $options = [], $withAuthorization = true)
    {
        try {
            // Increment the number of attempts so we can eventually give up.
            $this->attempts++;

            // Make the request. Any error code will send us to the 'catch' below.
            $response = $this->raw($method, $path, $options, $withAuthorization);

            // Reset the number of attempts back to zero once we've had a successful
            // response, and then perform any other clean-up.
            $this->attempts = 0;
            $this->cleanUp();

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $endpoint = strtoupper($method).' '.$path;
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);

            switch ($e->getCode()) {
                // If the request is bad, throw a generic bad request exception.
                case 400:
                    throw new BadRequestException($endpoint, json_encode($response));

                // If the request is unauthorized, handle it.
                case 401:
                    return $this->handleUnauthorizedException($endpoint, $response, $method, $path, $options);

                // If the request is forbidden, throw a generic forbidden exception.
                case 403:
                    throw new ForbiddenException($endpoint, json_encode($response));

                // If the resource doesn't exist, return null.
                case 404:
                    return null;

                // If it's a validation error, throw a generic validation error.
                case 422:
                    return $this->handleValidationException($endpoint, $response, $method, $path, $options);

                default:
                    throw new InternalException($endpoint, $e->getCode(), $e->getMessage());
            }
        }
    }

    /**
     * Send a raw API request, without attempting to handle error responses.
     *
     * @param $method
     * @param $path
     * @param array $options
     * @param bool $withAuthorization
     * @return Response
     */
    public function raw($method, $path, $options, $withAuthorization = true)
    {
        // Find what traits this class is using.
        $class = get_called_class();
        $traits = Introspector::getAllClassTraits($class);

        if (empty($options['headers'])) {
            // Guzzle doesn't merge default headers with $options array
            $options['headers'] = $this->defaultHeaders ?: [];
        }

        // If these traits have a "hook" (uh oh!), run that before making a request.
        foreach ($traits as $trait) {
            $function = 'run'.Introspector::baseName($trait).'Tasks';
            if (method_exists($class, $method)) {
                $this->{$function}($method, $path, $options, $withAuthorization);
            }
        }

        return $this->client->request($method, $path, $options);
    }

    /**
     * Determine if the response was successful or not.
     *
     * @param mixed $json
     * @return bool
     */
    public function responseSuccessful($json)
    {
        return ! empty($json['success']);
    }

    /**
     * Get the number of times a request has been attempted.
     *
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * Set the logger for the client.
     *
     * @param  \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
