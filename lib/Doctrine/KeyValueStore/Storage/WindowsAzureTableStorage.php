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

namespace Doctrine\KeyValueStore\Storage;

use Doctrine\KeyValueStore\HTTP\HttpClient;

/**
 * Storage implementation for Microsoft Windows Azure Table.
 *
 * Using a HTTP client to communicate with the REST API of Azure Table.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class WindowsAzureTableStorage implements Storage
{
    const WINDOWS_AZURE_TABLE_BASEURL = 'https://%s.table.core.windows.net';
    /**
     * @var \Doctrine\KeyValueStore\HTTP\HttpClient
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
     * @param HttpClient $client
     * @param AuthorizationSchema $authorization
     */
    public function __construct(HttpClient $client, $accountName, AuthorizationSchema $authorization)
    {
        $this->client = $client;
        $this->authorization = $authorization;
        $this->baseUrl = sprintf(self::WINDOWS_AZURE_TABLE_BASEURL, $accountName);
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

    public function insert($key, array $data)
    {

    }

    public function update($key, array $data)
    {

    }

    public function delete($key)
    {
    }

    public function find($key)
    {

    }

    public function getName()
    {
        return 'azure_table';
    }
}

