<?php

namespace lsb\App\controller;

use lsb\Config\Config;
use lsb\Libs\CSVParser;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;

class Plan extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->post('/upload', function (Context $ctx) {
            $conf = Config::getInstance()->getConfig('plan');
            $keyIndex = $conf['csvKeyIndex'];
            $parser = new CSVParser();

            $res = [];
            $fileInfo = $ctx->req->getFiles();
            foreach ($fileInfo as $keyTag => $file) {
                if ($file['type'] !== 'text/csv') {
                    $res[$keyTag] = [
                        'result' => 1,
                        'msg' => 'file type is not csv'
                    ];
                    continue;
                }

                $csvFile = $file['tmp_name'];
                $isSaved = $parser->saveCSV($csvFile, $keyIndex, $keyTag);

                if ($isSaved === false) {
                    $res[$keyTag] = [
                        'result' => 2,
                        'msg' => 'save failed'
                    ];
                    continue;
                }

                $res[$keyTag] = [
                    'result' => 0,
                    'msg' => 'file is uploaded'
                ];
            }

            $ctx->res->body = $res;
            $ctx->res->send(true);
        });
    }
}
