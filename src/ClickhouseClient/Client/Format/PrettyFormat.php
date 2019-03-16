<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 9/12/17
 * Time: 11:54 PM
 */

namespace ClickhouseClient\Client\Format;


class PrettyFormat implements  FormatInterface
{

    /**
     * Returns format used for getting data from database
     *
     * @return string
     */
    public function queryFormat(): string
    {
        return 'Pretty';
    }

    /**
     * Returns format used for inserting data into database
     *
     * @return string
     */
    public function insertFormat(): string
    {
        throw new \RuntimeException(__CLASS__ . ' does not support data insert');
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
        throw new \RuntimeException(__CLASS__ . ' does not support data encoding');
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
        return [$row];
    }
}
