<?php

namespace lsb\Libs;

class Plan
{
    public function readCSV(string $file): bool
    {
        $handle = fopen($file, 'r');

        $key = [];
        // save key value
        if (($data = fgetcsv($handle, 1000, ", ")) === false) {
            // there is no data
            return false;
        } else {
            foreach ($data as $idx => $value) {
                $key[$idx] = $value;
            }
        }

        while (($data = fgetcsv($handle, 1000, ", ")) !== false) {
            foreach ($data as $value) {
                $this->savePlanData($data);
            }
        }
    }
}
