<?php

namespace DoSomething\Northstar\Common;

use DoSomething\Northstar\Exceptions\APIException;
use DoSomething\Northstar\Exceptions\APIValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;

class RestAPIClient
{
    protected $client;

    /**
     * RestAPIClient constructor.
     *
     * @param $base_url - Base URL for this API, e.g. https://api.dosomething.org/v1/
     * @param array $additional_headers - Additional headers that should be sent with every request
     */
    public function __construct($base_url, $additional_headers = [])
    {
        $standard_headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $client = new Client([
            'base_url' => $base_url,
            'defaults' => [
                'headers' => array_merge($standard_headers, $additional_headers),
            ],
        ]);

        $this->client = $client;
    }

    /**
     * Send a GET request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $query - Key-value array of query string values
     * @return array
     */
    public function get($path, $query = [])
    {
        $response = $this->send('GET', $path, [
            'query' => $query,
        ]);

        return is_null($response) ? null : $response->json();
    }

    /**
     * Send a POST request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $body - Body of the POST request
     * @return array
     */
    public function post($path, $body = [])
    {
        $response = $this->send('POST', $path, [
            'body' => json_encode($body),
        ]);

        return is_null($response) ? null : $response->json();
    }

    /**
     * Send a PUT request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $body - Body of the PUT request
     * @return array
     */
    public function put($path, $body = [])
    {
        $response = $this->send('PUT', $path, [
            'body' => json_encode($body),
        ]);

        return is_null($response) ? null : $response->json();
    }

    /**
     * Send a DELETE request to the given URL.
     *
     * @param string $path - URL to make request to (relative to base URL)
     * @return bool
     */
    public function delete($path)
    {
        $response = $this->send('DELETE', $path);

        return $this->responseSuccessful($response);
    }

    /**
     * Send a Northstar API request, and parse any returned validation
     * errors or status codes to present to the user.
     *
     * @param string $method - 'GET', 'POST', 'PUT', or 'DELETE'
     * @param string $path - URL to make request to (relative to base URL)
     * @param array $options - Guzzle options (http://guzzle.readthedocs.org/en/latest/request-options.html)
     * @return Response|void
     *
     * @throws APIException
     * @throws APIValidationException
     */
    public function send($method, $path, $options = [])
    {
        try {
            return $this->raw($method, $path, $options);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $endpoint = strtoupper($method).' '.$path;

            // If the resource doesn't exist, return null.
            if ($e->getCode() === 404) {
                return null;
            }

            // If it's a validation error, loop through the error response and present as
            // a standard Laravel validation error, so the user can fix their mistakes!
            if ($e->getCode() === 422) {
                $errors  = json_decode($e->getResponse()->getBody()->getContents())->errors;
                throw new APIValidationException($errors, $endpoint);
            }

            throw new APIException($endpoint, $e->getCode(), $e->getMessage());
        }
    }



    /**
     * Send a raw API request, without attempting to handle error responses.
     *
     * @param $method
     * @param $path
     * @param array $options
     * @return Response|void
     */
    public function raw($method, $path, $options)
    {
        return $this->client->send($this->client->createRequest($method, $path, $options));
    }

    /**
     * Determine if the response was successful or not.
     *
     * @param $response
     * @return bool
     */
    public function responseSuccessful(Response $response)
    {
        return isset($response->json()['success']);
    }
}
