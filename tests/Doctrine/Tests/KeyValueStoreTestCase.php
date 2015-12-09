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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests;

use Doctrine\KeyValueStore\EntityManager;
use Doctrine\KeyValueStore\Configuration;
use Doctrine\KeyValueStore\Mapping;
use Doctrine\KeyValueStore\Storage\DoctrineCacheStorage;
use Doctrine\Common\Cache\ArrayCache;

abstract class KeyValueStoreTestCase extends \PHPUnit_Framework_TestCase
{
    public function createManager($storage = null, $driver = 'annotation')
    {
        $cache = new ArrayCache;
        $storage = $storage ?: new DoctrineCacheStorage($cache);

        switch ($driver) {
            case 'annotation':
                $reader = new \Doctrine\Common\Annotations\AnnotationReader();
                $metadata = new Mapping\AnnotationDriver($reader);

                break;
            case 'yaml':
                $metadata = new Mapping\YamlDriver(__DIR__.'/fixtures/yaml');

                break;
            case 'xml':
                $metadata = new Mapping\XmlDriver(__DIR__.'/fixtures/xml');

                break;
        }

        $config = new Configuration();
        $config->setMappingDriverImpl($metadata);
        $config->setMetadataCache($cache);

        return new EntityManager($storage, $config);
    }
}
