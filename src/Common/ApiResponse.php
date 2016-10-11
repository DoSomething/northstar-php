<?php

namespace DoSomething\Gateway\Common;

use ArrayAccess;
use Carbon\Carbon;

class ApiResponse implements ArrayAccess
{
    /**
     * Raw API response data.
     * @var array
     */
    protected $attributes = [];

    /**
     * Meta-information about this response.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [self::CREATED_AT, self::UPDATED_AT];

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new API response model.
     * @param $attributes
     * @param array $meta
     */
    public function __construct($attributes, $meta = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the status code for this response.
     *
     * @return mixed|null
     */
    public function getStatus()
    {
        return $this->getMeta('code', 200);
    }

    /**
     * Get meta-information about this response.
     *
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getMeta($key, $default = null)
    {
        return isset($this->meta[$key]) ? $this->meta[$key] : $default;
    }

    /**
     * Set meta-information on this response.
     *
     * @param $key
     * @param $value
     */
    public function setMeta($key, $value)
    {
        $this->meta[$key] = $value;
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return (isset($this->attributes[$key]) || isset($this->relations[$key])) ||
        ($this->hasGetMutator($key) && ! is_null($this->getAttributeValue($key)));
    }

    /**
     * Dynamically retrieve attributes from the JSON response.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        return null;
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $value = $this->getAttributeFromArray($key);

        // If the attribute has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        }

        // If the attribute is listed as a date, we will convert it to a DateTime
        // instance on retrieval, which makes it quite convenient to work with
        // date fields without having to create a mutator for each property.
        elseif (in_array($key, $this->dates)) {
            if (! is_null($value)) {
                return $this->asDateTime($value);
            }
        }

        return $value;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        $StudlyKey = ucwords(str_replace(['-', '_'], ' ', $key));

        return method_exists($this, 'get'.$StudlyKey.'Attribute');
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        $StudlyKey = ucwords(str_replace(['-', '_'], ' ', $key));

        return $this->{'get'.$StudlyKey.'Attribute'}($value);
    }

    /**
     * Return a timestamp as DateTime object. Shamelessly ripped from Laravel.
     * @see \Illuminate\Database\Eloquent\Model::toDateString <https://git.io/v2lnE>
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    protected function asDateTime($value)
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return Carbon::instance($value);
        }

        // Check if the value is a timestamp.
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        // Check if the value is in year, month, day format.
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return Carbon::createFromFormat($this->getDateFormat(), $value);
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat ?: \DateTime::ISO8601;
    }

    /**
     * Cast the response as an array.
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Check whether an item exists at that offset in the response.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset - An offset to check for.
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get an item from the response by its offset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    /**
     * Set an item in the response by its offset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Unset an item in the response by its offset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}
