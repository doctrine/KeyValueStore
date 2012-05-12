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
 * Relational databased backed system.
 *
 * Uses a simple string, blob table which the data gets {@serialize} into.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DBALStorage implements Storage
{
    private $conn;
    private $table;
    private $keyColumn;
    private $dataColumn;

    public function __construct(Connection $conn, $table = 'storage', $keyColumn = 'id', $dataColumn = 'serialized_data')
    {
        $this->conn = $conn;
        $this->table = $table;
        $this->keyColumn = $keyColumn;
        $this->dataColumn = $dataColumn;
    }

    public function supportsPartialUpdates()
    {
        return false;
    }

    /**
     * Does this storage support composite primary keys?
     *
     * @return bool
     */
    public function supportsCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * Does this storage require composite primary keys?
     *
     * @return bool
     */
    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * Insert data into the storage key specified.
     *
     * @param array|string $key
     * @param array $data
     * @return void
     */
    function insert($storageName, $key, array $data)
    {
        try {
            $this->conn->insert($this->table, array(
                $this->keyColumn => $key,
                $this->dataColumn => serialize($data)
            ));
        } catch(\Exception $e) {
        }
    }

    /**
     * Update data into the given key.
     *
     * @param array|string $key
     * @param array $data
     * @return void
     */
    public function update($storageName, $key, array $data)
    {
        try {
            $this->conn->update($this->table, array(
                $this->dataColumn => serialize($data)
            ), array(
                $this->keyColumn => $key
            ));
        } catch(\Exception $e) {
        }
    }

    /**
     * Delete data at key
     *
     * @param array|string $key
     * @return void
     */
    public function delete($storageName, $key)
    {
        try {
            $this->conn->delete($this->table, array($this->keyColumn => $key));
        } catch(\Exception $e) {
        }
    }

    /**
     * Find data at key
     *
     * @param array|string $key
     * @return array
     */
    public function find($storageName, $key)
    {
        $sql = "SELECT " . $this->dataColumn . " FROM " . $this->table . " " .
               $this->keyColumn = " = ?";
        $stmt = $this->conn->executeQuery($sql, array($key));
        $data = $stmt->fetchColumn();
        if (!$data) {
            throw new NotFoundException();
        }

        return unserialize($data);
    }

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    public function getName()
    {
        return 'dbal';
    }
}

