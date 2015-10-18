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

use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\KeyValueStore\KeyValueStoreException;
use Aws\SimpleDb\SimpleDbClient;
use Aws\SimpleDb\Exception\NoSuchDomainException;
use Aws\SimpleDb\Exception\SimpleDbException;

/**
 * SimpleDb storage
 *
 * @author Stan Lemon <stosh1985@gmail.com>
 */
class SimpleDbStorage implements Storage
{
    /**
     * @var \Aws\SimpleDb\SimpleDbClient
     */
    protected $client;

    /**
     * Constructor
     *
     * @param \Aws\SimpleDb\SimpleDbClient $client
     */
    public function __construct(SimpleDbClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsPartialUpdates()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function insert($storageName, $key, array $data)
    {
        $this->createDomain($storageName);

        $this->client->putAttributes(array(
            'DomainName' => $storageName,
            'ItemName'   => $key,
            'Attributes' => $this->makeAttributes($data),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        return $this->insert($storageName, $key, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $this->client->deleteAttributes(array(
            'DomainName' => $storageName,
            'ItemName'   => $key,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $select = "select * from {$storageName} where itemName() = '{$key}'";

        $iterator = $this->client->select(array(
            'SelectExpression' => $select,
        ));
        
        $results = $iterator->get('Items');

        if (count($results)) {
            $result = array_shift($results);

            $data = array('id' => $result['Name']);
        
            foreach ($result['Attributes'] as $attribute) {
                $data[$attribute['Name']] = $attribute['Value'];
            }

            return $data;
        }

        throw new NotFoundException();
    }

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    public function getName()
    {
        return 'simpledb';
    }

    /**
     * @param string $tableName
     */
    protected function createDomain($domainName)
    {
        try {
            $domain = $this->client->domainMetadata(array('DomainName' => $domainName));
        } catch (NoSuchDomainException $e) {
            $this->client->createDomain(array('DomainName' => $domainName));

            $domain = $this->client->domainMetadata(array('DomainName' => $domainName));
        } catch (SimpleDbException $e) {
            throw new KeyValueStoreException($e->getMessage(), 0, $e);
        }

        return $domain;
    }

    /**
     * @param string $key 
     * @param array $data 
     */
    protected function makeAttributes($data)
    {
        $attributes = array();

        foreach ($data as $name => $value) {
            if ($value !== null && $value !== array() && $value !== '') {
                $attributes[] = array(
                    'Name' => $name,
                    'Value' => $value,
                    'Replace' => true,
                );
            }
        }

        return $attributes;
    }
}
