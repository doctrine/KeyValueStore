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
namespace Doctrine\KeyValueStore\Http;

/**
 * HTTP Response
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Response
{
    private $code;

    private $body;

    private $headers = array();

    public function __construct($code, $body, array $headers)
    {
        $this->code = $code;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * HTTP Response code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * HTTP response body
     *
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get HTTP Response header
     *
     * @param string $name
     * @return null|string|array
     */
    public function getHeader($name)
    {
        if ( ! isset($this->headers[$name])) {
            return null;
        }
        return $this->headers[$name];
    }
}

