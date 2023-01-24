<?php declare(strict_types=1);

namespace PagerDuty\Http;

use PagerDuty\Event;
use PagerDuty\Exceptions\PagerDutyConfigurationException;
use PagerDuty\Exceptions\PagerDutyException;

class PagerDutyHttpConnection
{
    final public const HEADER_SEPARATOR = ';';

    /**
     * Some default options for curl.
     *
     * @var array
     */
    public static $defaultCurlOptions = [
        CURLOPT_SSLVERSION      => 6,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 60,
        // maximum number of seconds to allow cURL functions to execute
        CURLOPT_USERAGENT       => 'PagerDuty-PHP-SDK',
        CURLOPT_VERBOSE         => 0,
        CURLOPT_SSL_VERIFYHOST  => 2,
        CURLOPT_SSL_VERIFYPEER  => 1,
        CURLOPT_SSL_CIPHER_LIST => 'TLSv1:TLSv1.2',
    ];

    /**
     * @var array
     */
    protected array $curlOptions = [];

    /**
     * @var int
     */
    protected int $responseCode;

    private string $url;

    private array $headers = [];

    /**
     * PagerDutyHttpConnection constructor.
     *
     * @param null|string $url - PagerDuty's API
     */
    public function __construct(?string $url = null)
    {
        $url ??= 'https://events.pagerduty.com/v2/enqueue';

        $this->setUrl($url);
        $this->setCurlOptions(self::$defaultCurlOptions);
        $this->addHeader('Content-Type', 'application/json');                                                            # assume this is default; can override anytime

        $curl       = \curl_version();
        $sslVersion = $curl['ssl_version'] ?? '';

        if ($sslVersion
            && \substr_compare((string) $sslVersion, 'NSS/', 0, \strlen('NSS/')) === 0) {
            //Remove the Cipher List for NSS
            $this->removeCurlOption(CURLOPT_SSL_CIPHER_LIST);
        }
    }

    /**
     * Set Headers.
     */
    public function setHeaders(array $headers) : void
    {
        if (!\is_array($headers)) {
            throw new \InvalidArgumentException('Argument expected to be of type array');
        }

        $this->headers = $headers;
    }

    /**
     * Adds a Header.
     *
     * @param $name
     * @param $value
     * @param bool $overWrite allows you to override header value
     */
    public function addHeader(string|int $name, string|int $value, bool $overWrite = true) : void
    {
        if (!\array_key_exists($name, $this->headers)
            || $overWrite) {
            $this->headers[$name] = $value;
        } else {
            $this->headers[$name] = $this->headers[$name] . self::HEADER_SEPARATOR . $value;
        }
    }

    /**
     * Gets all Headers.
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Get Header by Name.
     *
     * @param $name
     */
    public function getHeader(string|int $name) : ?string
    {
        if (\array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        }

        return null;
    }

    /**
     * Removes a Header.
     *
     * @param $name
     */
    public function removeHeader(string|int $name) : void
    {
        unset($this->headers[$name]);
    }

    /**
     * Set service url.
     *
     * @param $url
     */
    public function setUrl(string $url) : void
    {
        $this->url = $url;
    }

    /**
     * Get Service url.
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * Add Curl Option.
     */
    public function addCurlOption(string $name, mixed $value) : void
    {
        $this->curlOptions[$name] = $value;
    }

    /**
     * Removes a curl option from the list.
     *
     * @param $name
     */
    public function removeCurlOption($name) : void
    {
        unset($this->curlOptions[$name]);
    }

    /**
     * Set Curl Options. Overrides all curl options.
     */
    public function setCurlOptions(array $options) : void
    {
        if (!\is_array($options)) {
            throw new \InvalidArgumentException('Argument expected to be of type array');
        }

        $this->curlOptions = $options;
    }

    /**
     * Gets all curl options.
     */
    public function getCurlOptions() : array
    {
        return $this->curlOptions;
    }

    /**
     * Get Curl Option by name.
     *
     * @param $name
     *
     * @return null|mixed
     */
    public function getCurlOption($name) : mixed
    {
        if (\array_key_exists($name, $this->curlOptions)) {
            return $this->curlOptions[$name];
        }

        return null;
    }

    /**
     * Set ssl parameters for certificate based client authentication.
     *
     * @param $certPath
     * @param null $passPhrase
     */
    public function setSSLCert($certPath, $passPhrase = null) : void
    {
        $this->curlOptions[CURLOPT_SSLCERT] = \realpath($certPath);

        if ($passPhrase !== null
            && \trim($passPhrase) !== '') {
            $this->curlOptions[CURLOPT_SSLCERTPASSWD] = $passPhrase;
        }
    }

    /**
     * Set connection timeout in seconds.
     */
    public function setTimeout(int $timeout) : void
    {
        $this->curlOptions[CURLOPT_CONNECTTIMEOUT] = $timeout;
    }

    /**
     * Set HTTP proxy information.
     *
     *
     * @throws PagerDutyConfigurationException
     */
    public function setProxy(string $proxy) : void
    {
        $urlParts = \parse_url($proxy);

        if ($urlParts === false
            || !\array_key_exists('host', $urlParts)) {
            throw new PagerDutyConfigurationException('Invalid proxy configuration ' . $proxy);
        }

        $this->curlOptions[CURLOPT_PROXY] = $urlParts['host'];

        if (isset($urlParts['port'])) {
            $this->curlOptions[CURLOPT_PROXY] .= ':' . $urlParts['port'];
        }

        if (isset($urlParts['user'])) {
            $this->curlOptions[CURLOPT_PROXYUSERPWD] = $urlParts['user'] . ':' . $urlParts['pass'];
        }
    }

    /**
     * Sets response code from curl call.
     */
    public function setResponseCode(int $code) : void
    {
        $this->responseCode = $code;
    }

    /**
     * Returns response code.
     */
    public function getResponseCode() : ?int
    {
        return $this->responseCode;
    }

    /**
     * Sets the User-Agent string on the HTTP request.
     */
    public function setUserAgent(string $userAgentString) : void
    {
        $this->curlOptions[CURLOPT_USERAGENT] = $userAgentString;
    }

    /**
     * Send the event to PagerDuty.
     *
     * @param mixed[]|null $result (Opt)(Pass by reference) - If this parameter is given the result of the CURL call will be filled here. The response is an associative array.
     *
     * @throws PagerDutyException - If status code == 400
     * @return int - HTTP response code
     *             202 - Event Processed
     *             400 - Invalid Event. Throws a PagerDutyException
     *             403 - Rate Limited. Slow down and try again later.
     */
    public function send(Event $payload, array &$result = null) : int
    {
        if (!$payload instanceof Event) {
            throw new \InvalidArgumentException('Argument expected to be of type Event');
        }

        $result       = $this->post(\json_encode($payload, JSON_THROW_ON_ERROR));
        $responseCode = $this->getResponseCode();

        if ($responseCode === 400) {
            throw new PagerDutyException($result['message'], $result['errors']);
        }

        return $responseCode;
    }

    /**
     * POST data to PagerDuty.
     *
     *
     */
    protected function post(string $payload) : mixed
    {
        if (!\is_string($payload)) {
            throw new \InvalidArgumentException('Argument expected to be of type string');
        }

        $url = $this->getUrl();
        $this->addHeader('Content-Length', \strlen($payload));

        $curl = \curl_init($url);

        $options = $this->getCurlOptions();
        \curl_setopt_array($curl, $options);

        \curl_setopt($curl, CURLOPT_URL, $url);
        \curl_setopt($curl, CURLOPT_POST, 1);
        \curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        \curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders());

        $response = \curl_exec($curl);
        $result   = \is_array($response) ? \json_decode($response, true, 512, JSON_THROW_ON_ERROR): $response;

        $this->setResponseCode(\curl_getinfo($curl, CURLINFO_HTTP_CODE));

        \curl_close($curl);

        return $result;
    }
}
