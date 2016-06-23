<?php
/**
 * Created by PhpStorm.
 * User: xandros15
 * Date: 2016-06-18
 * Time: 22:43
 */

namespace Xandros15\SlimPagination;

use Slim\Http\Request;
use Slim\Router;

class Pagination implements \IteratorAggregate
{

    const OPT_TOTAL = 'total';
    const OPT_NAME = 'name';
    const OPT_TYPE = 'type';
    const OPT_PER = 'show';
    /** @var string */
    private $routeName;
    /** @var array */
    private $attributes;
    /** @var array */
    private $query;
    /** @var Router */
    private $router;
    /** @var int */
    private $current;
    /** @var Slider */
    private $slider;
    /** @var array */
    private $options;

    public function __construct(Request $request, Router $router, array $options)
    {
        $this->router = $router;
        $this->init($options);
        $this->initRequest($request);
        $this->slider = new Slider([
            'router' => $this->router,
            'query' => $this->query,
            'attributes' => $this->attributes,
            'current' => $this->current,
            'routeName' => $this->routeName
        ], $this->options);
    }

    private function init(array $options)
    {
        $default = [
            self::OPT_TOTAL => 1,
            self::OPT_PER => 10,
            self::OPT_NAME => 'page',
            self::OPT_TYPE => Page::QUERY_PARAM
        ];

        $options = array_merge($default, $options);

        if (filter_var($options[self::OPT_TOTAL], FILTER_VALIDATE_INT) === false || $options[self::OPT_TOTAL] <= 0) {
            throw new \InvalidArgumentException('option `OPT_TOTAL` must be int and greater than 0');
        }

        if (!is_scalar($options[self::OPT_NAME]) && !method_exists($options[self::OPT_NAME], '__toString')) {
            throw new \InvalidArgumentException('option `OPT_NAME` must be string or instance of object with __toString method');
        }

        if (filter_var($options[self::OPT_PER], FILTER_VALIDATE_INT) === false || $options[self::OPT_PER] <= 0) {
            throw new \InvalidArgumentException('option `OPT_PER` must be int and greater than 0');
        }

        $this->options = $options;
    }

    private function initRequest(Request $request)
    {
        $this->current = $this->getCurrentPage($request);
        $this->attributes = $request->getAttributes();
        $this->query = $request->getQueryParams();
        $this->routeName = $request->getAttribute('route')->getName();
    }

    private function getCurrentPage(Request $request) : int
    {
        switch ($this->options[self::OPT_TYPE]) {
            case Page::ATTRIBUTE:
                return $request->getAttribute($this->options[self::OPT_NAME], 1);
            case Page::QUERY_PARAM:
                return $request->getQueryParam($this->options[self::OPT_NAME], 1);
        }
        throw new \InvalidArgumentException('Wrong type of page');
    }

    public function getIterator()
    {
        return $this->slider;
    }

    public function isEmpty() : bool
    {
        return $this->slider->count() > 0;
    }

    public function previous() : PageInterface
    {
        return $this->slider->get(max($this->current - 1, 1));
    }

    public function next() : PageInterface
    {
        return $this->slider->get(min($this->current + 1, $this->options[self::OPT_TOTAL]));
    }

    public function first() : PageInterface
    {
        return $this->slider->get(1);
    }

    public function last() : PageInterface
    {
        return $this->slider->get($this->options[self::OPT_TOTAL]);
    }
}