<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 4:46 PM
 */

namespace ClickhouseClient\Client\Format;


class JsonEachRowFormat implements FormatInterface
{
    /**
     * Encode single row of data
     * Generally is used for proper data inserting
     *
     * @param array $row
     * @return string
     */
    public function encode(array $row): string
    {
        return json_encode($row);
    }

    /**
     * Decode single string of data
     * Generally is used when decoding response from database
     *
     * @param string $row
     * @return array
     */
    public function decode(string $row): array
    {
        $rows = explode(PHP_EOL, rtrim($row));
        return array_map(function($row) { return json_decode($row, true); }, $rows);
    }

    /**
     * Returns format used for getting data from database
     *
     * @return string
     */
    public function queryFormat(): string
    {
        return 'JSONEachRow';
    }

    /**
     * Returns format used for inserting data into database
     *
     * @return string
     */
    public function insertFormat(): string
    {
        return 'JSONEachRow';
    }
}
