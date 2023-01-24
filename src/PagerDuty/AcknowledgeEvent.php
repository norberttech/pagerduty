<?php declare(strict_types=1);

namespace PagerDuty;

/**
 * An 'acknowledge' Event.
 *
 * @author adil
 */
class AcknowledgeEvent extends Event
{
    public function __construct(string $routingKey, $dedupKey)
    {
        parent::__construct($routingKey, 'acknowledge');

        $this->setDeDupKey($dedupKey);
    }
}
