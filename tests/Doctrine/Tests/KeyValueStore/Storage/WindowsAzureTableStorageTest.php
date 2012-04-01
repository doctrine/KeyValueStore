<?php

namespace Doctrine\Tests\KeyValueStore\Storage;

use Doctrine\KeyValueStore\Storage\WindowsAzureTable\SharedKeyAuthorization;
use Doctrine\KeyValueStore\Storage\WindowsAzureTableStorage;
use Doctrine\KeyValueStore\Http\Response;

class WindowsAzureTableStorageTest extends AbstractStorageTestCase
{
    private $client;

    protected function createStorage()
    {
        $this->client = $this->getMock('Doctrine\KeyValueStore\Http\Client');
        $auth = $this->getMock('Doctrine\KeyValueStore\Storage\WindowsAzureTable\AuthorizationSchema');
        $auth->expects($this->any())->method('signRequest')->will($this->returnValue('Authorization: SharedKeyLite testaccount1:uay+rilMVayH/SVI8X+a3fL8k/NxCnIePdyZSkqvydM='));

        $storage = new WindowsAzureTableStorage(
            $this->client, 'teststore', $auth, new \DateTime('2012-03-26 10:10:10', new \DateTimeZone('UTC'))
        );

        return $storage;
    }

    public function mockInsertCompositeKey($key, $data)
    {
        $expectedHeaders = array(
            'Content-Type' => 'application/atom+xml',
            'Content-Length' => 617,
            'x-ms-date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'Date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'Authorization' => 'SharedKeyLite testaccount1:uay+rilMVayH/SVI8X+a3fL8k/NxCnIePdyZSkqvydM=',
        );

        $this->client->expects($this->at(0))
                     ->method('request')
                     ->with(
                        $this->equalTo('POST'),
                        $this->equalTo('https://teststore.table.core.windows.net/stdClass'),
                        $this->equalTo(<<<XML
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
  <title/>
  <updated>2012-03-26T10:10:10.0000000Z</updated>
  <author>
    <name/>
  </author>
  <id/>
  <content type="application/xml">
    
  <m:properties>
    <d:PartitionKey>foo</d:PartitionKey><d:RowKey>100</d:RowKey><d:name>Test</d:name><d:value>1</d:value><d:amount>200.23</d:amount><d:timestamp>2012-03-26T12:12:12+02:00</d:timestamp></m:properties></content>
</entry>

XML
                        ),
                        $this->equalTo($expectedHeaders)
                     )->will($this->returnValue(
                        new Response(201, <<<XML
<?xml version="1.0" ?>
<entry xml:base="http://myaccount.table.core.windows.net/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" m:etag="W/&quot;datetime'2008-09-18T23%3A46%3A19.4277424Z'&quot;" xmlns="http://www.w3.org/2005/Atom">
  <id>http://myaccount.table.core.windows.net/mytable(PartitionKey='foo',RowKey='100')</id>
  <title type="text"></title>
  <updated>2008-09-18T23:46:19.3857256Z</updated>
  <author>
    <name />
  </author>
  <link rel="edit" title="stdClass" href="stdClass(PartitionKey='foo',RowKey='100')" />
  <category term="myaccount.Tables" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" />
  <content type="application/xml">
    <m:properties>
      <d:PartitionKey>foo</d:PartitionKey>
      <d:RowKey>100</d:RowKey>
      <d:timestamp m:type="Edm.DateTime">2008-09-18T23:46:19.4277424Z</d:timestamp>
      <d:name>Test</d:name>
      <d:value m:type="Edm.Int32">23</d:value>
      <d:amount m:type="Edm.Double">200.23</d:amount>
    </m:properties>
  </content>
</entry>
XML
, array()

                     ))
        );
    }

    public function mockUpdateCompositeKey($key, $data)
    {
        $expectedHeaders = array(
            'Content-Type' => 'application/atom+xml',
            'Content-Length' => 704,
            'x-ms-date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'Date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'If-Match' => '*',
            'Authorization' => 'SharedKeyLite testaccount1:uay+rilMVayH/SVI8X+a3fL8k/NxCnIePdyZSkqvydM=',
        );

        $this->client->expects($this->at(0))
                     ->method('request')
                     ->with(
                        $this->equalTo('PUT'),
                        $this->equalTo("https://teststore.table.core.windows.net/stdClass". rawurlencode("(PartitionKey='foo', RowKey='100')")),
                        $this->equalTo(<<<XML
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
  <title/>
  <updated>2012-03-26T10:10:10.0000000Z</updated>
  <author>
    <name/>
  </author>
  <id>https://teststore.table.core.windows.net/stdClass(PartitionKey='foo', RowKey='100')</id>
  <content type="application/xml">
    
  <m:properties>
    <d:PartitionKey>foo</d:PartitionKey><d:RowKey>100</d:RowKey><d:name>Test</d:name><d:value>1</d:value><d:amount>200.23</d:amount><d:timestamp>2012-03-26T12:12:12+02:00</d:timestamp></m:properties></content>
</entry>

XML
                        ),
                        $this->equalTo($expectedHeaders))
                     ->will($this->returnValue(new Response(204, "", array()))
        );

    }

    public function mockDeleteCompositeKey($key)
    {
        $expectedHeaders = array(
            'Content-Type' => 'application/atom+xml',
            'Content-Length' => 0,
            'x-ms-date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'Date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'If-Match' => '*',
            'Authorization' => 'SharedKeyLite testaccount1:uay+rilMVayH/SVI8X+a3fL8k/NxCnIePdyZSkqvydM=',
        );

        $this->client->expects($this->at(0))
                     ->method('request')
                     ->with(
                        $this->equalTo('DELETE'),
                        $this->equalTo("https://teststore.table.core.windows.net/stdClass". rawurlencode("(PartitionKey='foo', RowKey='100')")),
                        $this->equalTo(''),
                        $this->equalTo($expectedHeaders)
                     )->will($this->returnValue(new Response(204, "", array())));
    }

    public function mockFindCompositeKey($key)
    {
        $expectedHeaders = array(
            'Content-Type' => 'application/atom+xml',
            'Content-Length' => 0,
            'x-ms-date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'Date' => 'Mon, 26 Mar 2012 10:10:10 GMT',
            'Authorization' => 'SharedKeyLite testaccount1:uay+rilMVayH/SVI8X+a3fL8k/NxCnIePdyZSkqvydM=',
        );

        $this->client->expects($this->at(0))
                     ->method('request')
                     ->with(
                        $this->equalTo('GET'),
                        $this->equalTo("https://teststore.table.core.windows.net/stdClass". rawurlencode("(PartitionKey='foo', RowKey='100')")),
                        $this->equalTo(''),
                        $this->equalTo($expectedHeaders)
                     )->will($this->returnValue(
                        new Response(200, <<<XML
<?xml version="1.0" ?>
<entry xml:base="http://myaccount.table.core.windows.net/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" m:etag="W/&quot;datetime'2008-09-18T23%3A46%3A19.4277424Z'&quot;" xmlns="http://www.w3.org/2005/Atom">
  <id>http://myaccount.table.core.windows.net/mytable(PartitionKey='foo',RowKey='100')</id>
  <title type="text"></title>
  <updated>2008-09-18T23:46:19.3857256Z</updated>
  <author>
    <name />
  </author>
  <link rel="edit" title="stdClass" href="stdClass(PartitionKey='foo',RowKey='100')" />
  <category term="myaccount.Tables" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" />
  <content type="application/xml">
    <m:properties>
      <d:PartitionKey>foo</d:PartitionKey>
      <d:RowKey>100</d:RowKey>
      <d:timestamp m:type="Edm.DateTime">2008-09-18T23:46:19.000000Z</d:timestamp>
      <d:name>Test</d:name>
      <d:value m:type="Edm.Int32">23</d:value>
      <d:amount m:type="Edm.Double">200.23</d:amount>
      <d:bool m:type="Edm.Boolean">1</d:bool>
    </m:properties>
  </content>
</entry>
XML
, array()

                     ))
        );

    }
}

