<?php

namespace DoSomething\Gateway\Common;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

abstract class ApiCollection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The items returned from the request.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Total number of results on the server.
     *
     * @var int
     */
    protected $total;

    /**
     * Maximum number of results returned per page.
     *
     * @var int
     */
    protected $perPage;

    /**
     * Current page being displayed from the API.
     *
     * @var int
     */
    protected $currentPage;

    /**
     * Whether a cursor-based paginator can load more
     * pages from the API endpoint.
     *
     * @var bool
     */
    protected $hasMore;

    /**
     * The collection's paginator.
     *
     * @var \Illuminate\Pagination\LengthAwarePaginator
     */
    protected $paginator;

    /**
     * Create a new API response collection.
     *
     * @param array $response - Array of raw API responses
     * @param string $class - Class to create for contents
     */
    public function __construct($response, $class)
    {
        foreach ($response['data'] as $item) {
            array_push($this->items, new $class($item));
        }

        // If the response is paginated:
        if (isset($response['meta']['pagination'])) {
            $this->total = $response['meta']['pagination']['total'];
            $this->perPage = $response['meta']['pagination']['per_page'];
            $this->currentPage = $response['meta']['pagination']['current_page'];
        }

        // If the response uses a cursor:
        if (isset($response['meta']['cursor'])) {
            $this->perPage = $response['meta']['cursor']['count'];
            $this->currentPage = $response['meta']['cursor']['current'];
            $this->hasMore = ! empty($response['meta']['cursor']['next']);
        }
    }

    /**
     * Set a paginator for this collection.
     *
     * @param string $class
     * @param array $options
     */
    public function setPaginator($class, $options = [])
    {
        $interfaces = class_implements($class);

        if (in_array('Illuminate\Contracts\Pagination\LengthAwarePaginator', $interfaces)) {
            $paginator = new $class($this->items, $this->total, $this->perPage, $this->currentPage, $options);
        } elseif (in_array('Illuminate\Contracts\Pagination\Paginator', $interfaces)) {
            $paginator = new $class($this->items, $this->perPage, $this->currentPage, $options);
            $paginator->hasMorePagesWhen($this->hasMore);
        } else {
            throw new \InvalidArgumentException('Cannot use the given paginator.');
        }

        $this->paginator = $paginator;
    }

    /**
     * Render pagination links to HTML.
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function links($view = null, $data = [])
    {
        return $this->paginator->render($view, $data);
    }

    /**
     * Get the first item from the collection.
     *
     * @return ApiResponse|null
     */
    public function first()
    {
        return $this->count() > 0 ? $this->items[0] : null;
    }

    /**
     * Get the total number of results in the collection (including
     * those not currently fetched from the API, if paginated).
     *
     * @return int
     */
    public function total()
    {
        return isset($this->total) ? $this->total : count($this->items);
    }

    /**
     * Count the number of items in this part of the collection.
     * @see Countable::count
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Whether a offset exists.
     * @see ArrayAccess::offsetExists
     *
     * @param mixed $offset - An offset to check for.
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Offset to retrieve.
     * @see ArrayAccess::offsetGet
     *
     * @param mixed $offset - An offset to check for.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * Offset to set.
     * @see ArrayAccess::offsetSet
     *
     * @param mixed $offset - The offset to assign the value to.
     * @param mixed $value - The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Offset to unset.
     * @see ArrayAccess::offsetUnset
     *
     * @param mixed $offset - The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Get the instance as an array.
     * @see Arrayable::toArray
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Retrieve an iterator for the items in the collection.
     * @see IteratorAggregate::getIterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}
