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

/**
 * MongoDb storage
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MongoDbStorage implements Storage
{
    /**
     * @var \Mongo
     */
    protected $mongo;

    /**
     * @var array
     */
    protected $dbOptions;

    /**
     * @var \MongoCollection
     */
    protected $collection;

    /**
     * Constructor
     *
     * @param \Mongo $mongo
     * @param array $dbOptions
     */
    public function __construct(\Mongo $mongo, array $dbOptions = array())
    {
        $this->mongo = $mongo;
        $this->dbOptions = array_merge(array(
            'database' => '',
            'collection' => '',
        ), $dbOptions);
    }

    /**
     * Initialize the mongodb collection
     *
     * @throws \RuntimeException
     */
    public function initialize()
    {
        if (null !== $this->collection) {
            return;
        }

        if (empty($this->dbOptions['database'])) {
            throw new \RuntimeException('The option "database" must be set');
        }
        if (empty($this->dbOptions['collection'])) {
            throw new \RuntimeException('The option "collection" must be set');
        }

        $this->collection = $this->mongo->selectDB($this->dbOptions['database'])->selectCollection($this->dbOptions['collection']);
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
        $this->initialize();

        $value = array(
            'key'   => $key,
            'value' => $data,
        );

        $this->collection->insert($value);
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
        $this->initialize();

        $value = array(
            'key'   => $key,
            'value' => $data,
        );

        $this->collection->update(array('key' => $key), $value);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
        $this->initialize();

        $this->collection->remove(array('key' => $key));
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
        $this->initialize();

        $value = $this->collection->findOne(array('key' => $key), array('value'));

        if ($value) {
            return $value['value'];
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
        return 'mongodb';
    }
}