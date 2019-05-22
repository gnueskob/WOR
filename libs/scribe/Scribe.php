<?php

namespace lsb\Scribe;

use TSocketPool;
use TFramedTransport;
use TBinaryProtocol;
use scribeClient;
use Exception;
use lsb\Libs\Singleton;
use lsb\Config\Config;

class Scribe extends Singleton
{
    private $client;

    protected function __construct()
    {
        parent::__construct();
        $this->client = $this->createScribeClient();
    }

    private function createScribeClient(): scribeClient
    {
        try {
            $conf = Config::getInstance()->getConfig('scribe');

            // Set up the socket connections
            $scribeServers = $conf['host'];
            $scribePorts = $conf['port'];

            print "creating socket pool\n";
            $sock = new TSocketPool($scribeServers, $scribePorts);
            $sock->setDebug(0);
            $sock->setSendTimeout(1000);
            $sock->setRecvTimeout(2500);
            $sock->setNumRetries(1);
            $sock->setRandomize(false);
            $sock->setAlwaysTryLast(true);
            $trans = new TFramedTransport($sock);
            $prot = new TBinaryProtocol($trans);

            // Create the client
            print "creating scribe client\n";
            $scribeClient = new scribeClient($prot);

            // Open the transport (we rely on PHP to close it at script termination)
            print "opening transport\n";
            $trans->open();
        } catch (Exception $e) {
            print "Unable to create global scribe client, received exception: $e \n";
            return null;
        }

        return $scribeClient;
    }

    public function log(array $msg)
    {
        $this->client->Log($msg);
    }
}
