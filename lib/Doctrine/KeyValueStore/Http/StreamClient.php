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

namespace Doctrine\KeyValueStore\Http;

/**
 * Connection handler using PHPs stream wrappers.
 *
 * Requires PHP being compiled with --with-curlwrappers for now, since the PHPs
 * own HTTP implementation is somehow b0rked.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Kore Nordmann <kore@arbitracker.org>
 */
class StreamClient implements Client
{
    /**
     * @var array
     */
    private $options = array(
        'timeout' => 2,
    );

    /**
     * Perform a request to the server and return the result
     *
     * Perform a request to the server and return the result converted into a
     * Response object. If you do not expect a JSON structure, which
     * could be converted in such a response object, set the forth parameter to
     * true, and you get a response object retuerned, containing the raw body.
     *
     * @param string $method
     * @param string $url
     * @param string|null $body
     * @param array $headers
     * @return Response
     */
    public function request($method, $url, $body = null, array $headers)
    {
        $parts = parse_url($url);
        $host = $parts['host'];

        $header = "";
        $header2 = array();
        foreach ($headers as $headerName => $v) {
            foreach ((array)$v as $value) {
                $header .= $headerName . ": " . $value . "\r\n";
            }
        }
        $header = rtrim($header);

        // TODO SSL support?
        $opts = array(
                    'http' => array(
                        'method'            => $method,
                        'content'           => $body,
                        'max_redirects'     => 2,
                        'ignore_errors'     => false,
                        'user_agent'        => 'Doctrine KeyValueStore',
                        'timeout'           => $this->options['timeout'],
                        'header'            => $header,
                        'protocol_version'  => '1.1',
                    ),
                );

        $httpFilePointer = @fopen(
            $url,
            'r',
            false,
            stream_context_create($opts)
        );

        // Check if connection has been established successfully
        if ( $httpFilePointer === false ) {
            $error = error_get_last();
            throw new \RuntimeException("Error sending to " . $host . ": " . $error['message']);
        }

        // Read request body
        $body = '';
        while ( !feof( $httpFilePointer ) ) {
            $body .= fgets( $httpFilePointer );
        }

        $metaData = stream_get_meta_data( $httpFilePointer );
        // The structure of this array differs depending on PHP compiled with
        // --enable-curlwrappers or not. Both cases are normally required.
        $rawHeaders = isset( $metaData['wrapper_data']['headers'] )
            ? $metaData['wrapper_data']['headers'] : $metaData['wrapper_data'];

        $headers = array();
        foreach ( $rawHeaders as $lineContent ) {
            // Extract header values
            if ( preg_match( '(^HTTP/(?P<version>\d+\.\d+)\s+(?P<status>\d+))S', $lineContent, $match ) ) {
                $headers['version'] = $match['version'];
                $headers['status']  = (int) $match['status'];
            } else {
                list( $key, $value ) = explode( ':', $lineContent, 2 );
                $headers[strtolower( $key )] = ltrim( $value );
            }
        }

        if ( empty($headers['status']) ) {
            throw \RuntimeException('Error sending to ' . $host . ': Received an empty response or not status code');
        }

        // Create repsonse object from couch db response
        return new Response($headers['status'], $body, $headers);
    }
}

