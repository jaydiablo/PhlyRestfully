<?php
/**
 * @link      https://github.com/weierophinney/PhlyRestfully for the canonical source repository
 * @copyright Copyright (c) 2013 Matthew Weier O'Phinney
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @package   PhlyRestfully
 */

namespace PhlyRestfully\Listener;

use PhlyRestfully\ApiProblem;
use PhlyRestfully\View\RestfulJsonModel;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ModelInterface;

/**
 * ApiProblemListener
 *
 * Provides a listener on the render event, at high priority.
 *
 * If the MvcEvent represents an error, then its view model and result are
 * replaced with a RestfulJsonModel containing an API-Problem payload.
 */
class ApiProblemListener implements ListenerAggregateInterface
{
    /**
     * Default values to match in Accept header
     *
     * @var string
     */
    protected static $acceptFilter = 'application/hal+json,application/api-problem+json,application/json';

    /**
     * @var callable[]
     */
    protected $listeners = [];

    /**
     * Constructor
     *
     * Set the accept filter, if one is passed
     *
     * @param string $filter
     */
    public function __construct($filter = null)
    {
        if (is_string($filter) && !empty($filter)) {
            self::$acceptFilter = $filter;
        }
    }

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER, __CLASS__ . '::onRender', 1000);
    }

    /**
     * @param EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            $events->detach($listener);
            unset($this->listeners[$index]);
        }
    }

    /**
     * Listen to the render event
     *
     * @param MvcEvent $e
     *
     * @return void
     */
    public static function onRender(MvcEvent $e)
    {
        // only worried about error pages
        if (!$e->isError()) {
            return;
        }

        // and then, only if we have an Accept header...
        $request = $e->getRequest();
        if (!$request instanceof HttpRequest) {
            return;
        }

        /** @var \Zend\Http\Headers $headers */
        $headers = $request->getHeaders();
        if (!$headers->has('Accept')) {
            return;
        }

        // ... that matches certain criteria
        /** @var \Zend\Http\Header\AbstractAccept $accept */
        $accept = $headers->get('Accept');
        $match  = $accept->match(self::$acceptFilter);
        if (!$match || $match->getTypeString() == '*/*') {
            return;
        }

        // Next, do we have a view model in the result?
        // If not, nothing more to do.
        $model = $e->getResult();
        if (!$model instanceof ModelInterface) {
            return;
        }

        // Marshall the information we need for the API-Problem response
        /** @var \Zend\Http\Response $response */
        $response = $e->getResponse();
        $httpStatus       = $response->getStatusCode();
        $exception        = $model->getVariable('exception');

        if ($exception instanceof \Exception) {
            $apiProblem = new ApiProblem($httpStatus, $exception);
        } else {
            $apiProblem = new ApiProblem($httpStatus, $model->getVariable('message'));
        }

        // Create a new model with the API-Problem payload, and reset
        // the result and view model in the event using it.
        $model = new RestfulJsonModel(['payload' => $apiProblem]);
        $model->setTerminal(true);
        $e->setResult($model);
        $e->setViewModel($model);
    }
}
