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

namespace Doctrine\Tests\KeyValueStore\Storage\WindowsAzureTable;

use Doctrine\KeyValueStore\Storage\WindowsAzureTable\SharedKeyLiteAuthorization;

/**
 * @group legacy
 */
class SharedKeyLiteTest extends \PHPUnit_Framework_TestCase
{
    private $auth;

    public function setUp()
    {
        $this->auth = new SharedKeyLiteAuthorization(
            'testing',
            'abcdefg'
        );
    }

    public function testKeyGeneration1()
    {
        $authorization = $this->auth->signRequest('GET', '/', '', '', [
            'x-ms-date' => 'Wed, 29 Apr 2009 13:12:47 GMT',
        ]);
        $this->assertEquals(
            'Authorization: SharedKeyLite testing:vZdOn/j0gW5FG0kAUG9NhSBO9eBjZqfe6RwALPYUtqU=',
            $authorization
        );
    }

    public function testKeyGeneration2()
    {
        $authorization = $this->auth->signRequest('GET', '/test', '', '', [
            'x-ms-date' => 'Wed, 29 Apr 2009 13:12:47 GMT',
        ]);
        $this->assertEquals(
            'Authorization: SharedKeyLite testing:HJTSiRDtMsQVsFVispSHkcODeFykLO+WEuOepwmh51o=',
            $authorization
        );
    }
}
