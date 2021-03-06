<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 4:33 PM
 */

namespace ClickhouseClient\Client\Format;


interface FormatInterface
{
    /**
     * Returns format used for getting data from database
     *
     * @return string
     */
    public function queryFormat() : string;

    /**
     * Returns format used for inserting data into database
     *
     * @return string
     */
    public function insertFormat() : string;

    /**
     * Encode single row of data
     * Generally is used for proper data inserting
     *
     * @param array $row
     * @return string
     */
    public function encode(array $row) : string;

    /**
     * Decode single string of data
     * Generally is used when decoding response from database
     *
     * @param string $row
     * @return array
     */
    public function decode(string $row) : array;
}
