<?php
require_once 'Odr/Exception.php';

class Api_Odr
{
    /**
     * @var null|array
     *
     * @protected
     */
    protected $_resultBody;

    /**
     * @var null|array
     *
     * @protected
     */
    protected $_resultHeaders;

    /**
     * @var null|string
     *
     * @protected
     */
    protected $_error;

    /**
     * @var array
     *
     * @protected
     */
    protected $_headers = array();

    /**
     * @var array
     *
     * @protected
     */
    protected $_config = array();

    /**
     * In case URL will be changed in the future
     */
    const URL = 'https://api.opendomainregistry.net/';

    const METHOD_GET     = 'GET';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const DEFAULT_METHOD = self::METHOD_GET;

    const MESSAGE_CURL_ERROR_FOUND = 'cURL error catched';

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';

    /**
     * Class constructor
     *
     * @param array $config Configuration data
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function __construct(array $config = array())
    {
        if (extension_loaded('curl') === false) {
            echo 'cURL extension required by this class. Check you php.ini';

            exit();
        }

        if (count($config) > 0) {
            $this->setConfig($config);
        }
    }

    /**
     * Change configuration data
     *
     * @param array $config Configuration array
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function setConfig(array $config = array())
    {
        if (count($config) === 0) {
            throw new Api_Odr_Exception('Config is empty');
        }

        foreach ($config as &$value) {
            $value = trim($value, ' /.,');
        }

        unset($value);

        $this->_config = $config;

        return $this;
    }

    /**
     * Login procedure
     * At first, script tries to find out how signature is generated and after that actually tries to login
     * Is first step necessary? No. There is pretty slim chances that signature generation method will be changed in the future, but still, it wouldn't hurt
     *
     * @param string|null $apiKey    User's API Key
     * @param string|null $apiSecret User's API Secret
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function login($apiKey = null, $apiSecret = null)
    {
        $this->_execute('/info/user/login', self::METHOD_POST);

        if (!empty($this->_error)) {
            throw new Api_Odr_Exception($this->_error);
        }

        $result = $this->_resultBody;

        if (!is_string($apiKey) || $apiKey === '') {
            $apiKey = $this->_config['api_key'];
        }

        if (!is_string($apiSecret) || $apiSecret === '') {
            $apiSecret = $this->_config['api_secret'];
        }

        $apiKey    = trim($apiKey);
        $apiSecret = trim($apiSecret);

        if ($apiKey === '' || $apiSecret === '') {
            throw new Api_Odr_Exception('You should defined `api_key` and `api_secret`');
        }

        $signatureRuleWrapper = $result['response']['fields']['signature']['signature_rule'];
        $signatureRule        = $result['response']['fields']['signature']['signature_rule_clear'];

        $wrapper = 'sha1';

        if (strpos($signatureRuleWrapper, '#SHA1(') === 0) {
            $wrapper = 'sha1';
        } elseif(strpos($signatureRuleWrapper, '#MD5(') === 0) {
            $wrapper = 'md5';
        }

        $timestamp = time();

        $r = array(
            '#API_KEY#'          => $apiKey,
            '#MD5(API_KEY)#'     => md5($apiKey),
            '#SHA1(API_KEY)#'    => sha1($apiKey),
            '#TIMESTAMP#'        => $timestamp,
            '#API_SECRET#'       => $apiSecret,
            '#MD5(API_SECRET)#'  => md5($apiSecret),
            '#SHA1(API_SECRET)#' => sha1($apiSecret),
        );

        $signature = str_replace(array_keys($r), array_values($r), $signatureRule);

        switch($wrapper) {
            case 'sha1':
                    $signature = sha1($signature);
                break;
            case 'md5':
                    $signature = md5($signature);
                break;
            default:
                break;
        }

        $data = array(
            'timestamp' => $timestamp,
            'api_key'   => $apiKey,
            'signature' => 'token$' . $signature,
        );

        $this->_execute('/user/login/', self::METHOD_POST, $data);

        $result = $this->_resultBody;

        if ($result['status'] === self::STATUS_ERROR) {
            throw new Api_Odr_Exception($result['response']['message']);
        }

        $this->setHeader($result['response']['as_header'], $result['response']['token']);

        return $this;
    }

    /**
     * Return list of user's domains
     *
     * @param array $request Additional request parameters, like page, filters and sorting
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getDomains(array $request = array())
    {
        $params = http_build_query($request);

        $this->_execute('/domain/?' . $params, self::METHOD_GET);

        return $this;
    }

    /**
     * Check if domain is available or not
     *
     * @param string|int $domain Either ID or domain name
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function checkDomain($domain)
    {
        if (!is_numeric($domain) && (!is_string($domain) || $domain === '')) {
            throw new Api_Odr_Exception('Domain must be a string, but you give us a '. gettype($domain));
        }

        $domain = trim($domain, ' /.');

        if ($domain === '') {
            throw new Api_Odr_Exception('Domain name is required for this operation');
        }

        $this->_execute('/domain/available/'. $domain .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Update existing domain with new data
     *
     * @param string|int $id   Either ID or domain name
     * @param array      $data Data for update
     *
     * @return Api_Odr
     */
    public function updateDomain($id, array $data = array())
    {
        $this->_execute('/domain/'. trim($id) .'/', self::METHOD_PUT, $data);

        return $this;
    }

    /**
     * Transfers domain from one user to another
     *
     * @param string|int $id   Domain ID or domain name
     * @param array      $data Data to update
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function transferDomain($id, array $data = array())
    {
        $this->_execute('/domain/'. trim($id) .'/transfer/', self::METHOD_PUT, $data);

        return $this;
    }

    /**
     * Return list of user's contacts
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getContacts()
    {
        $this->_execute('/contact/', self::METHOD_GET);

        return $this;
    }

    /**
     * Get information about single contact
     *
     * @param int $contactId Contact ID
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getContact($contactId)
    {
        if (!is_numeric($contactId)) {
            throw new Api_Odr_Exception('Contact ID must be numeric');
        }

        $contactId = (int)$contactId;

        if ($contactId <= 0) {
            throw new Api_Odr_Exception('Contact ID must be a positive number');
        }

        $this->_execute('/contact/'. $contactId .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Creates contact from passed data
     *
     * @param array $data Data for contact
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function createContact(array $data)
    {
        // If you want to pass data directly as part of request, you can uncomment following lines:
        /*
        if (empty($data)) {
            $data = $_REQUEST;
        }
        */

        $this->_execute('/contact/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Registers new domain
     *
     * @param string|array $domainName Either domain name as string or whole request data as array (must have 'domain_name' key)
     * @param array        $data       Data for new domain. Only usable if $domainName is a string
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function registerDomain($domainName, array $data)
    {
        if (is_array($domainName) && count($data) === 0) {
            $data       = $domainName;
            $domainName = null;
        }

        // If you want to pass data directly as part of request, you can uncomment following lines:
        /*
        if (empty($data)) {
            $data = $_REQUEST;
        }
        */

        if ((!is_string($domainName) || $domainName === '') && array_key_exists('domain_name', $data) === false) {
            throw new Api_Odr_Exception('No domain name defined');
        }

        if (!is_string($domainName) || $domainName === '') {
            $domainName = $data['domain_name'];
        }

        $this->_execute('/domain/'. $domainName .'/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Get information about operation, including price and required fields
     *
     * @param string $what   About what you want to know information about. Either URL or a string
     * @param mixed  $method If $what is an URL, then method should be a string. If not, then $method might be an array (instead of data) or null
     * @param array  $data   Additional data for request
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function info($what, $method = null, array $data = array())
    {
        if (!is_string($what) || $what === '') {
            throw new Api_Odr_Exception('I don\'t understand about what you want to get information about');
        }

        $what = strtolower(trim($what));

        return $this->custom('/info/'. trim($what, '/') .'/', $method, $data);
    }

    /**
     * Information about domain registration
     *
     * @param string $domainName Domain name to get info about
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function infoRegisterDomain($domainName)
    {
        return $this->info('/domain/'. $domainName .'/', self::METHOD_POST);
    }

    /**
     * Changes autorenew state of domain
     *
     * @param string $domainName Domain name to change autorenew state
     * @param bool   $state      Set autorenew on or off
     *
     * @return Api_Odr
     */
    public function setAutorenew($domainName, $state)
    {
        return $this->custom('/domain/' . $domainName . '/renew-' . ($state ? 'on' : 'off') .'/', Api_Odr::METHOD_PUT);
    }

    /**
     * Request to any custom API URL
     * Works as shorthand for $this->_execute() function
     *
     * @param string $url    Request URL
     * @param string $method cURL request method
     * @param array  $data   Data for request
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function custom($url, $method = self::DEFAULT_METHOD, array $data = array())
    {
        try {
            return $this->_execute($url, $method, $data);
        } catch (Api_Odr_Exception $e) {
            $this->_error = $e->getMessage();
        }

        return $this;
    }

    /**
     * Executes cURL request and return result and error
     *
     * @param string $url    Where send request
     * @param string $method What method should be called
     * @param array  $data   Additional data to send
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     *
     * @protected
     */
    protected function _execute($url = '', $method = self::DEFAULT_METHOD, array $data = array())
    {
        $this->_resultBody    = null;
        $this->_resultHeaders = null;

        if (!is_string($method) || $method === '') {
            $method = self::DEFAULT_METHOD;
        }

        $method = strtoupper($method);
        $host   = $this->getUrl();

        if (!is_string($url) || $url === '') {
            $url = $host;
        }

        if (strpos($url, '/') === 0) {
            $url = $host . '/' . trim($url, '/') . '/';
        }

        if (strpos($url, $host) !== 0) {
            throw new Api_Odr_Exception('Wrong host for URL ('. $url .')');
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  $method);
        curl_setopt($ch, CURLOPT_HEADER,         true);

        if (count($data) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $headers = array();

        if (count($this->_headers) > 0) {
            foreach ($this->_headers as $k => $v) {
                $headers[] = $k . ': ' . $v;
            }
        }

        $headers[] = 'Expect:'; // Prevent cURL from sending HTTP/1.1 100 Continue (thanks to @githubbauman)

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        list($resultHeaders, $resultBody) = explode("\r\n\r\n", $result, 2);

        $this->_error = curl_error($ch);

        if (empty($this->_error)) {
            $this->_resultBody    = json_decode($resultBody, true);
            $this->_resultHeaders = $this->parseHeaders($resultHeaders);
        }

        curl_close($ch);

        // Too much request at a time can ban us
        usleep(500);

        if (!empty($this->_error)) {
            throw new Api_Odr_Exception($this->_error);
        }

        return $this;
    }

    /**
     * Return request result
     *
     * @return null|array
     */
    public function getResult()
    {
        return $this->_resultBody;
    }

    /**
     * Return request result headers
     *
     * @return null|array
     */
    public function getResultHeaders()
    {
        return $this->_resultHeaders;
    }

    /**
     * Return possible cURL error
     *
     * @return null|string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns all headers, that will be set for request
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Returns usable API URL
     *
     * @return string
     */
    public function getUrl()
    {
        return empty($this->_config['url']) ? self::URL : $this->_config['url'];
    }

    /**
     * Sets header value
     *
     * @param string|array $name  Either array with headers to set or header key name
     * @param mixed        $value Value for header (only if $name is string)
     *
     * @return Api_Odr
     */
    public function setHeader($name, $value = null)
    {
        if (!is_array($name)) {
            $name = array(
                $name => $value,
            );
        }

        $this->_headers = array_merge($this->_headers, $name);

        return $this;
    }

    public function parseHeaders($headers)
    {
        if (is_array($headers)) {
            return $headers;
        }

        $parsed = array();

        foreach (explode("\r\n", $headers) as $i => $headerInfo) {
            if ($i === 0 && strpos($headerInfo, ':') === false) {
                $parsed['HttpCode'] = $parsed['httpcode'] = $headerInfo;

                continue;
            }

            list ($key, $value) = explode(':', $headerInfo, 2);

            $key   = trim($key);
            $value = trim($value);

            $parsed[$key]                = $value;
            $parsed[mb_strtolower($key)] = $value;
        }

        return $parsed;
    }
}