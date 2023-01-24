<?php declare(strict_types=1);

namespace PagerDuty;

/**
 * A 'trigger' event.
 *
 * @link https://developer.pagerduty.com/docs/events-api-v2/trigger-events/
 *
 * @author adil
 */
class TriggerEvent extends Event
{
    final public const CRITICAL = 'critical';

    final public const ERROR = 'error';

    final public const WARNING = 'warning';

    final public const INFO = 'info';

    /**
     * Ctor.
     *
     * @param string $routingKey - The routing key, taken from your PagerDuty 'Configuration' > 'Services' page
     * @param string $summary - The Error message
     * @param string $source - The unique location of the affected system, preferably a hostname or FQDN
     * @param string $severity - One of 'critical', 'error', 'warning' or 'info'. Use the constants above
     * @param bool $autoDeDupKey - If true, autogenerates a `dedup_key` based on the md5 hash of the $summary
     */
    public function __construct(string $routingKey, string $summary, string $source, string $severity, private readonly bool $autoDeDupKey = false)
    {
        parent::__construct($routingKey, 'trigger');
        $this->setPayloadSummary($summary);
        $this->setPayloadSource($source);
        $this->setPayloadSeverity($severity);
    }

    /**
     * A human-readable error message.
     * This is what PD will read over the phone.
     *
     *
     */
    public function setPayloadSummary(string $summary) : self
    {
        $this->dict['payload']['summary'] = $summary;

        return $this;
    }

    /**
     * The unique location of the affected system, preferably a hostname or FQDN.
     *
     *
     */
    public function setPayloadSource(string $source) : self
    {
        $this->dict['payload']['source'] = $source;

        return $this;
    }

    /**
     * One of critical, error, warning or info. Use the class constants above.
     *
     *
     */
    public function setPayloadSeverity(string $value) : self
    {
        $this->dict['payload']['severity'] = $value;

        return $this;
    }

    /**
     * The time this error occured.
     *
     * @param string $timestamp - Can be a datetime string as well. See the example @ https://v2.developer.pagerduty.com/docs/send-an-event-events-api-v2
     */
    public function setPayloadTimestamp(string $timestamp) : self
    {
        $this->dict['payload']['timestamp'] = $timestamp;

        return $this;
    }

    /**
     * From the PD docs: "Component of the source machine that is responsible for the event, for example `mysql` or `eth0`".
     *
     *
     */
    public function setPayloadComponent(string $value) : self
    {
        $this->dict['payload']['component'] = $value;

        return $this;
    }

    /**
     * From the PD docs: "Logical grouping of components of a service, for example `app-stack`".
     *
     *
     */
    public function setPayloadGroup(string $value) : self
    {
        $this->dict['payload']['group'] = $value;

        return $this;
    }

    /**
     * From the PD docs: "The class/type of the event, for example `ping failure` or `cpu load`".
     *
     *
     */
    public function setPayloadClass(string $value) : self
    {
        $this->dict['payload']['class'] = $value;

        return $this;
    }

    /**
     * An associative array of additional details about the event and affected system.
     */
    public function setPayloadCustomDetails(array $dict) : self
    {
        $this->dict['payload']['custom_details'] = $dict;

        return $this;
    }

    /**
     * Attach a link to the incident.
     *
     * @link https://developer.pagerduty.com/docs/events-api-v2/trigger-events/#the-links-property
     *
     * @param string $href URL of the link to be attached
     * @param string|null $text Optional. Plain text that describes the purpose of the link, and can be used as the link's text.
     */
    public function addLink(string $href, string $text = null) : self
    {
        if (!\array_key_exists('links', $this->dict)) {
            $this->dict['links'] = [];
        }

        $link = ['href' => $href];

        if (!empty($text)) {
            $link['text'] = $text;
        }
        $this->dict['links'][] = $link;

        return $this;
    }

    /**
     * Attach an image to the incident.
     *
     * @link https://developer.pagerduty.com/docs/events-api-v2/trigger-events/#the-images-property
     *
     * @param string $src The source (URL) of the image being attached to the incident. This image must be served via HTTPS.
     * @param string|null $href optional URL; makes the image a clickable link
     * @param string|null $alt optional alternative text for the image
     */
    public function addImage(string $src, string $href = null, string $alt = null) : self
    {
        if (!\array_key_exists('images', $this->dict)) {
            $this->dict['images'] = [];
        }

        $image = ['src' => (string) $src];

        if (!empty($href)) {
            $image['href'] = (string) $href;
        }

        if (!empty($alt)) {
            $image['alt'] = (string) $alt;
        }
        $this->dict['images'][] = $image;

        return $this;
    }

    public function toArray() : array
    {
        if ($this->autoDeDupKey) {
            $this->setDeDupKey('md5-' . \md5((string) $this->dict['payload']['summary']));
        }

        return $this->dict;
    }
}
