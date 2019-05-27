<?php

namespace lsb\Log;

use TSocketPool;
use TFramedTransport;
use TBinaryProtocol;
use scribeClient;
use LogEntry;
use Exception;
use lsb\Libs\ILog;

class Scribe implements ILog
{
    private $client;
    private $msg = [];

    public function __construct(array $conf)
    {
        $this->client = $this->createScribeClient($conf);
    }

    private function createScribeClient(array $conf): scribeClient
    {
        try {
            // Set up the socket connections
            $scribeServers = $conf['host'];
            $scribePorts = $conf['port'];

            // Creating socket pool
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
            $scribeClient = new scribeClient($prot);

            // Open the transport (we rely on PHP to close it at script termination)
            $trans->open();
        } catch (Exception $e) {
            // print "Unable to create global scribe client, received exception: $e \n";
            return null;
        }

        return $scribeClient;
    }

    private function getLogEntry(string $category, string $msg): LogEntry
    {
        return new LogEntry([
            'category' => $category,
            'message' => $msg
        ]);
    }

    public function addLog(string $category, string $msg): void
    {
        $this->msg[] = $this->getLogEntry($category, $msg);
    }

    public function flushLog(): void
    {
        $this->client->Log($this->msg);
        $this->msg = [];
    }

    public function writeLog(string $category, string $msg): void
    {
        $logEntry[] = $this->getLogEntry($category, $msg);
        $this->client->Log($logEntry);
    }
}
