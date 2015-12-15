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

abstract class AbstractStorage implements Storage
{
    /**
     * {@inheritDoc}
     */
    abstract public function supportsPartialUpdates();

    /**
     * {@inheritDoc}
     */
    abstract public function supportsCompositePrimaryKeys();

    /**
     * {@inheritDoc}
     */
    abstract public function requiresCompositePrimaryKeys();

    /**
     * {@inheritDoc}
     */
    abstract public function insert($storageName, $key, array $data);

    /**
     * {@inheritDoc}
     */
    abstract public function update($storageName, $key, array $data);

    /**
     * {@inheritDoc}
     */
    abstract public function delete($storageName, $key);

    /**
     * {@inheritDoc}
     */
    abstract public function find($storageName, $key);

    /**
     * {@inheritDoc}
     */
    abstract public function getName();

    /**
     * Used to flattening keys.
     *
     * @param string                      $storageName
     * @param string|int|float|bool|array $key
     *
     * @return string
     */
    protected function flattenKey($storageName, $key)
    {
        if (is_scalar($key)) {
            return $storageName . '-' . $key;
        }

        if ( ! is_array($key)) {
            throw new \InvalidArgumentException('The key should be a string or a flat array.');
        }

        ksort($key);

        $hash = $storageName . '-oid:';

        foreach ($key as $property => $value) {
            $hash .= $property . '=' . $value . ';';
        }

        return $hash;
    }
}
