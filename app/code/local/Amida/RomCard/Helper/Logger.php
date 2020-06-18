<?php

class Amida_RomCard_Helper_Logger extends Mage_Core_Helper_Abstract
{
    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _data()
    {
        return Mage::helper('romcard');
    }

    protected function encode($arr)
    {
        return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @var string $requestType
     * @var Omnipay\Common\Message\RequestInterface $request
     */
    public function logRequest($requestType, $request)
    {
        $data = $request->getData();
        $data = is_string($data) ? $data : $this->encode($data);
        $this->log("REQUEST({$requestType}). url: {$request->getEndpointUrl()},
data: $data");
    }

    /**
     * @var string $responseType
     * @var array|string $response
     */
    public function logResponse($responseType, $response)
    {
        $response = is_string($response) ? $response : $this->encode($response);
        $this->log("RESPONSE({$responseType}): {$response}");
    }

    public function log($message)
    {
        if (! $this->logEnabled()) {
            return;
        }

        Mage::log($message, null, $this->getLogFile());
    }

    public function getLogFile()
    {
        return 'romcard.log';
    }

    public function logEnabled()
    {
        return $this->_data()->getConfig('log_enabled');
    }

    /**
     * @param Omnipay\Common\Message\RequestInterface $request
     * @param string $requestType
     *
     * @throws Exception|Amida_RomCard_Exception
     * @return Omnipay\Common\Message\ResponseInterface
     */
    public function decorateRequest($request, $requestType)
    {
        $this->logRequest($requestType, $request);

        try {
            $response = $request->send();

            if (is_object($response)) {
                $this->logResponse($requestType, $response->getData());
            } else {
                $this->logResponse($requestType, '[check url] ' . $request->getEndpointUrl() . '?' . http_build_query($request->getData()));
            }

            return $response;
        } catch (Exception $exception) {
            $this->logResponse($requestType, $exception->getMessage());
            throw $exception;
        }
    }
}