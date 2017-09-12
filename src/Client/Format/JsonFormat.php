<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 4:46 PM
 */

namespace ClickhouseClient\Client\Format;


class JsonFormat implements FormatInterface
{

    /**
     * Returns format which is one of the database supported formats
     *
     * @param bool $forInsert
     * @return string
     */
    public function format(bool $forInsert): string
    {
        if ($forInsert) {
            return 'JSONEachRow';
        }
        return 'JSON';
    }

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
        return json_decode($row, true);
    }
}