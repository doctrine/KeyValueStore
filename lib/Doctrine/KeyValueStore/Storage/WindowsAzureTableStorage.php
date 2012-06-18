<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Http\Client;
use Doctrine\KeyValueStore\Storage\WindowsAzureTable\AuthorizationSchema;
use Doctrine\KeyValueStore\Storage\WindowsAzureTable\HttpStorageException;
use Doctrine\KeyValueStore\Query\RangeQuery;
use Doctrine\KeyValueStore\Query\RangeQueryStorage;
use Doctrine\KeyValueStore\NotFoundException;

/**
 * Storage implementation for Microsoft Windows Azure Table.
 *
 * Using a HTTP client to communicate with the REST API of Azure Table.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class WindowsAzureTableStorage implements Storage, RangeQueryStorage
{
    const WINDOWS_AZURE_TABLE_BASEURL = 'https://%s.table.core.windows.net';

    const METADATA_NS = 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata';
    const DATA_NS = 'http://schemas.microsoft.com/ado/2007/08/dataservices';

    /**
     * @var string
     */
    const XML_TEMPLATE_ENTITY = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
  <title />
  <updated></updated>
  <author>
    <name />
  </author>
  <id />
  <content type="application/xml">
    <m:properties>
    </m:properties>
  </content>
</entry>';

    const XML_TEMPLATE_TABLE = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom"> 
<title />
<updated></updated>
<author>
  <name/>
</author>
  <id/>
  <content type="application/xml">
    <m:properties>
      <d:TableName />
    </m:properties>
  </content>
</entry>';

    const TYPE_INT32 = 'Edm.Int32';
    const TYPE_INT64 = 'Edm.Int64';
    const TYPE_DATETIME = 'Edm.DateTime';
    const TYPE_BOOLEAN = 'Edm.Boolean';
    const TYPE_DOUBLE = 'Edm.Double';
    const TYPE_BINARY = 'Edm.Binary';

    /**
     * @var \Doctrine\KeyValueStore\Http\Client
     */
    private $client;

    /**
     * @var \Doctrine\KeyValueStore\Storage\WindowsAzureTable\AuthorizationSchema
     */
    private $authorization;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var DateTime
     */
    private $now;

    /**
     * @param HttpClient $client
     * @param AuthorizationSchema $authorization
     */
    public function __construct(Client $client, $accountName, AuthorizationSchema $authorization, \DateTime $now = null)
    {
        $this->client = $client;
        $this->authorization = $authorization;
        $this->baseUrl = sprintf(self::WINDOWS_AZURE_TABLE_BASEURL, $accountName);
        $this->now = $now ? clone $now : new \DateTime();
        $this->now->setTimeZone(new \DateTimeZone("UTC"));
    }

    public function supportsPartialUpdates()
    {
        return false;
    }

    public function supportsCompositePrimaryKeys()
    {
        return true;
    }

    public function requiresCompositePrimaryKeys()
    {
        return true;
    }

    public function insert($storageName, $key, array $data)
    {
        $headers = array(
            'Content-Type' => 'application/atom+xml',
        );
        // TODO: This sucks
        $tableName = $storageName;

        $dom = $this->createDomDocumentRequestBody();

        $propertiesNode = $dom->getElementsByTagNameNS(self::METADATA_NS, 'properties')->item(0);

        $this->serializeKeys($propertiesNode, $key);
        $this->serializeProperties($propertiesNode, $key, $data);

        $contentNodes = $dom->getElementsByTagName('content');
        $contentNodes->item(0)->appendChild($propertiesNode);
        $xml = $dom->saveXML();

        $url = $this->baseUrl . '/' . $tableName;
        $response = $this->request('POST', $url, $xml, $headers);

        if ($response->getStatusCode() == 404) {

            $this->createTable($tableName);
            $this->insert($storageName, $key, $data);
        } else if ($response->getStatusCode() >= 400) {

            $this->convertResponseToException($response);
        }
    }

    private function convertResponseToException($response)
    {
        $dom = new \DomDocument('1.0', 'UTF-8');
        $dom->loadXML($response->getBody());

        $message = "";
        $nodeList = $dom->getElementsByTagName('Message');
        if ($nodeList->length > 0) {
            $message = $nodeList->item(0)->nodeValue;
            throw new HttpStorageException($message);
        }

        $nodeList = $dom->getElementsByTagName('message');
        if ($nodeList->length > 0) {
            $message = $nodeList->item(0)->nodeValue;
            throw new HttpStorageException($message);
        }

        throw new HttpStorageException($response->getBody());
    }

    public function createTable($tableName)
    {
        $headers = array(
            'Content-Type' => 'application/atom+xml',
        );

        $dom = $this->createDomDocumentRequestBody(self::XML_TEMPLATE_TABLE);
        $tableNode = $dom->getElementsByTagNameNS(self::DATA_NS, 'TableName')->item(0);
        $tableNode->appendChild($dom->createTextNode($tableName));
        $xml = $dom->saveXML();

        $url = $this->baseUrl .  '/Tables';
        $response = $this->request('POST', $url, $xml, $headers);

        if ($response->getStatusCode() != 201) {
            $this->convertResponseToException($response);
        }

        return $response;
    }

    public function update($storageName, $key, array $data)
    {
        $headers = array(
            'Content-Type' => 'application/atom+xml',
            'x-ms-date' => $this->now(),
            'If-Match' => '*',
        );
        // TODO: This sucks
        $tableName = $storageName;

        $dom = $this->createDomDocumentRequestBody();

        $propertiesNode = $dom->getElementsByTagNameNS(self::METADATA_NS, 'properties')->item(0);

        $this->serializeKeys($propertiesNode, $key);
        $this->serializeProperties($propertiesNode, $key, $data);
        $keys = array_values($key);
        $clientUrl = $this->baseUrl . '/' . $tableName . ("(PartitionKey='" . $keys[0] . "', RowKey='" . $keys[1] . "')");
        $url = $this->baseUrl . '/' . $tableName . rawurlencode("(PartitionKey='" . $keys[0] . "', RowKey='" . $keys[1] . "')");
        $idNode = $dom->getElementsByTagName('id')->item(0);
        $idNode->appendChild($dom->createTextNode($clientUrl));

        $contentNodes = $dom->getElementsByTagName('content');
        $contentNodes->item(0)->appendChild($propertiesNode);
        $xml = $dom->saveXML();

        $response = $this->request('PUT', $url, $xml, $headers);

        if ($response->getStatusCode() >= 400) {
            $this->convertResponseToException($response);
        }
    }

    public function delete($storageName, $key)
    {
        $headers = array(
            'Content-Type' => 'application/atom+xml',
            'x-ms-date' => $this->now(),
            'Content-Length' => 0,
            'If-Match' => '*',
        );

        // TODO: This sucks
        $tableName = $storageName;
        $keys = array_values($key);
        $url = $this->baseUrl . '/' . $tableName . rawurlencode("(PartitionKey='" . $keys[0] . "', RowKey='" . $keys[1] . "')");

        $response = $this->request('DELETE', $url, '', $headers);

        if ($response->getStatusCode() >= 400) {
            $this->convertResponseToException($response);
        }
    }

    public function find($storageName, $key)
    {
        $headers = array(
            'Content-Type' => 'application/atom+xml',
            'x-ms-date' => $this->now(),
            'Content-Length' => 0,
        );

        // TODO: This sucks
        $tableName = $storageName;
        $keys = array_values($key);
        $url = $this->baseUrl . '/' . $tableName . rawurlencode("(PartitionKey='" . $keys[0] . "', RowKey='" . $keys[1] . "')");

        $response = $this->request('GET', $url, '', $headers);

        if ($response->getStatusCode() == 404) {
            throw new NotFoundException();
        }

        if ($response->getStatusCode() >= 400) {
            $this->convertResponseToException($response);
        }

        $dom = new \DomDocument('1.0', 'UTF-8');
        $dom->loadXML($response->getBody());

        $xpath = new \DOMXpath($dom);
        $xpath->registerNamespace('d', 'http://schemas.microsoft.com/ado/2007/08/dataservices');
        $xpath->registerNamespace('m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
        $xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
        $entries = $xpath->evaluate('/atom:entry');

        return $this->createRow(array_keys($key), $xpath, $entries->item(0));
    }

    private function createRow($keyNames, $xpath, $entry)
    {
        $properties = $xpath->evaluate('atom:content/m:properties/d:*', $entry);

        $data = array();
        list($partitionKey, $rowKey) = $keyNames;

        foreach ($properties as $property) {
            $name = substr($property->tagName, 2);
            if ($name == "PartitionKey") {
                $name = $partitionKey;
            } else if ($name == "RowKey") {
                $name = $rowKey;
            }

            $value = $property->nodeValue;
            if ($property->hasAttributeNS(self::METADATA_NS, 'null')) {
                $value = null;
            } else if ($property->hasAttributeNS(self::METADATA_NS, 'type')) {
                $type = $property->getAttributeNS(self::METADATA_NS, 'type');
                switch ($type) {
                    case self::TYPE_BOOLEAN:
                        $value = ($value == 1);
                        break;
                    case self::TYPE_DATETIME:
                        $value = new \DateTime(substr($value, 0, 19), new \DateTimeZone('UTC'));
                        break;
                    case self::TYPE_INT32:
                        $value = (int)$value;
                        break;
                }
            }
            $data[$name] = $value;
        }

        return $data;
    }

    public function executeRangeQuery(RangeQuery $query, $storageName, $key, \Closure $hydrateRow = null)
    {
        $headers = array(
            'Content-Type' => 'application/atom+xml',
            'x-ms-date' => $this->now(),
            'Content-Length' => 0,
        );

        $filters = array("PartitionKey eq " . $this->quoteFilterValue($query->getPartitionKey()));
        foreach ($query->getConditions() as $condition) {
            if ( ! in_array($condition[0], array('eq', 'neq', 'le', 'lt', 'ge', 'gt'))) {
                throw new \InvalidArgumentException("Windows Azure Table only supports eq, neq, le, lt, ge, gt as conditions.");
            }
            $filters[] = $key[1] . " " . $condition[0] . " " . $this->quoteFilterValue($condition[1]);
        }

        // TODO: This sucks
        $tableName = $storageName;
        $url = $this->baseUrl . '/' . $tableName . '()?$filter=' . rawurlencode(implode(' ', $filters));
        if ($query->getLimit()) {
            $url .= '&$top=' . $query->getLimit();
        }

        $response = $this->request('GET', $url, '', $headers);

        if ($response->getStatusCode() >= 400) {
            $this->convertResponseToException($response);
        }

        $dom = new \DomDocument('1.0', 'UTF-8');
        $dom->loadXML($response->getBody());

        $xpath = new \DOMXpath($dom);
        $xpath->registerNamespace('d', 'http://schemas.microsoft.com/ado/2007/08/dataservices');
        $xpath->registerNamespace('m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
        $xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
        $entries = $xpath->evaluate('/atom:feed/atom:entry');

        $results = array();
        foreach ($entries as $entry) {
            $data = $this->createRow($key, $xpath, $entry);
            $results[] = $hydrateRow ? $hydrateRow($data) : $data;
        }

        return $results;
    }

    private function quoteFilterValue($value)
    {
        if (is_integer($value)) {
            return $value;
        }

        return "'" . str_replace("'", "", $value) . "'";
    }

    public function getName()
    {
        return 'azure_table';
    }

    private function now()
    {
        return $this->isoDate($this->now);
    }

    private function isoDate(\DateTime $date)
    {
        return str_replace('+00:00', '.0000000Z', $date->format('c'));
    }

    private function createDomDocumentRequestBody($xml = null)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml ?: self::XML_TEMPLATE_ENTITY);

        $updatedNodes = $dom->getElementsByTagName('updated');
        $updatedNodes->item(0)->appendChild($dom->createTextNode($this->now()));

        return $dom;
    }

    private function getPropertyType($propertyValue)
    {
        if ($propertyValue instanceof \DateTime) {
            return self::TYPE_DATETIME;
        }
        return null;
    }

    private function convertPropertyValue($propertyValue, $type)
    {
        switch ($type) {
            case self::TYPE_DATETIME:
                $propertyValue = $this->isoDate($propertyValue);
                break;
            case self::TYPE_BOOLEAN:
                $propertyValue = $propertyValue ? "1" : "0";
                break;
        }
        return $propertyValue;
    }

    private function serializeKeys($propertiesNode, $key)
    {
        $keys = 0;
        foreach ($key as $keyName => $keyValue) {
            switch ($keys) {
                case 0:
                    $partitionKey = $propertiesNode->ownerDocument->createElementNS(self::DATA_NS, 'PartitionKey', $keyValue);
                    $propertiesNode->appendChild($partitionKey);
                    break;
                case 1:
                    $rowKey = $propertiesNode->ownerDocument->createElementNS(self::DATA_NS, 'RowKey', $keyValue);
                    $propertiesNode->appendChild($rowKey);
                    break;
                default:
                    throw new \RuntimeException("Only exactly 2 composite key fields allowed.");
            }
            $keys++;
        }
    }

    private function request($method, $url, $xml, $headers)
    {
        $parts = parse_url($url);
        $requestDate = $this->now->format('D, d M Y H:i:s') . ' GMT';
        $headers['Content-Length'] = strlen($xml);
        $headers['Date'] = $requestDate;
        $headers['x-ms-date'] = $requestDate;
        $authorizationHeader = $this->authorization->signRequest(
            $method,
            isset($parts['path']) ? $parts['path'] : '/',
            isset($parts['query']) ? $parts['query'] : '',
            $xml,
            $headers
        );
        $authorizationParts = explode(":" , $authorizationHeader, 2);
        $headers[$authorizationParts[0]] = ltrim($authorizationParts[1]);
        return $this->client->request($method, $url, $xml, $headers);
    }

    private function serializeProperties($propertiesNode, array $key, array $data)
    {
        foreach ($data as $propertyName => $propertyValue) {
            if ( isset($key[$propertyName])) {
                continue;
            }

            $type = $this->getPropertyType($propertyValue);
            $propertyValue = $this->convertPropertyValue($propertyValue, $type);

            $property = $propertiesNode->ownerDocument->createElementNS(self::DATA_NS, $propertyName, $propertyValue);
            if ($propertyValue === null) {
                $property->setAttributeNS(self::METDATA_NS, 'null', 'true');
            }

            $propertiesNode->appendChild($property);
        }
    }
}

