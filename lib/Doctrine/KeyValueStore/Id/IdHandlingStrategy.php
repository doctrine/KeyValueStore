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

namespace Doctrine\KeyValueStore\Id;

use Doctrine\KeyValueStore\Mapping\ClassMetadata;

/**
 * Handling of ids inside the UnitOfWork
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
interface IdHandlingStrategy
{
    /**
     * Given an unstructured key format and a class metadata, generate a key.
     *
     * @param ClassMetadata $metadata
     * @param string|array $key
     * @return string|array
     */
    function normalizeId(ClassMetadata $metadata, $key);

    /**
     * Extract identifier from an object
     *
     * @param ClassMetadata $metadata
     * @param object $object
     * @return string|array
     */
    function getIdentifier(ClassMetadata $metadata, $object);

    /**
     * Given a normalized key, generate a hash version for it.
     *
     * @param array|string $key
     * @return string
     */
    function hash($key);
}

