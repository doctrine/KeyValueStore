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

use Doctrine\KeyValueStore\Query\RangeQuery;
use Doctrine\KeyValueStore\Query\RangeQueryStorage;

use Cassandra\Session;
use Cassandra\ExecutionOptions;

class CassandraStorage implements Storage, RangeQueryStorage
{
    /**
     * @var \Cassandra\Session
     */
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
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
        return true;
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
        $keys = $values = array();

        foreach ($key as $name => $value) {
            $keys[] = $name;
            $values[] = $value;
        }

        foreach ($data as $name => $value) {
            $keys[] = $name;
            $values[] = $value;
        }

        $cql = "INSERT INTO " . $storageName . " (" . implode(', ', $keys) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
        $stmt = $this->session->prepare($cql);

        $options = new ExecutionOptions();
        $options->arguments = $values;

        $this->session->execute($stmt, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function update($storageName, $key, array $data)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storageName, $key)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function find($storageName, $key)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function executeRangeQuery(RangeQuery $query, $storageName, $key, \Closure $hydrateRow = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'cassandra';
    }
}
