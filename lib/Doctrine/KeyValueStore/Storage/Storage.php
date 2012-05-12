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

/**
 * Storage abstraction layer for key-value stores
 *
 * The storage layer is an interface for the CRUD operations on datastorages.
 * There is currently no support for update in place operations that many of
 * the no-sql databases have for counters and such. The focus is on mapping
 * the data onto PHP objects. More efficient operations if required should be
 * done with the raw connection of the underlying storage.
 *
 * The assumption is that keys are always arrays of multiple values. Each
 * implementation has to handle the serialization of this keys into a single
 * value if necessary or disallow composite primary keys through the
 * {@supportsCompositePrimarKeys()} flag.
 *
 * Batch Query facilities of the storages will be handled through another
 * interface.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
interface Storage
{
    /**
     * Determine if the storage supports updating only a subset of properties,
     * or if all properties have to be set, even if only a subset of properties
     * changed.
     *
     * @return bool
     */
    function supportsPartialUpdates();

    /**
     * Does this storage support composite primary keys?
     *
     * @return bool
     */
    function supportsCompositePrimaryKeys();

    /**
     * Does this storage require composite primary keys?
     *
     * @return bool
     */
    function requiresCompositePrimaryKeys();

    /**
     * Insert data into the storage key specified.
     *
     * @param string $storageName
     * @param array|string $key
     * @param array $data
     * @return void
     */
    function insert($storageName, $key, array $data);

    /**
     * Update data into the given key.
     *
     * @param string $storageName
     * @param array|string $key
     * @param array $data
     * @return void
     */
    function update($storageName, $key, array $data);

    /**
     * Delete data at key
     *
     * @param string $storageName
     * @param array|string $key
     * @return void
     */
    function delete($storageName, $key);

    /**
     * Find data at key
     *
     * Important note: The returned array does contain the identifier (again)!
     *
     * @throws Doctrine\KeyValueStore\NotFoundException When data with key is not found.
     *
     * @param string $storageName
     * @param array|string $key
     * @return array
     */
    function find($storageName, $key);

    /**
     * Return a name of the underlying storage.
     *
     * @return string
     */
    function getName();
}


