<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/4
 * Time: 上午9:21
 */

namespace mqttclient\src\swoole;


class Util
{

    /**
     * @param $str
     * @return string
     */
    public static function packLenStr($str){
        return pack('n', strlen($str)) . $str;
    }

    /**
     * @param $length
     * @return string
     */
    public static function packRemainLength($length){
        $string = "";
        do{
            $digit = $length % 0x80;
            $length = $length >> 7;
            if ( $length > 0 ) $digit = ($digit | 0x80);
            $string .= chr($digit);
        } while ( $length > 0 );
        return $string;
    }

    /**
     * @param $byte
     * @return int
     */
    public static function decodeCmd($byte){
        return $byte >> 4;
    }

    /**
     * @param $byte
     * @return int
     */
    public static function decodeFlags($byte){
        return $byte & 0x0f;
    }

    /**
     * @param $data
     * @param $index
     * @return int
     */
    public static function decodeRemainLength($data,&$index){
        $multiplier = 1;
        $value = 0 ;
        do{
            $digit = ord($data[$index]);
            $value += ($digit & 0x7F) * $multiplier;
            $multiplier *= 0x80;
            $index ++;
        } while (($digit & 0x80) != 0);
        return $value;
    }

    /**
     * @param $data
     * @param $index
     * @return int
     */
    public static function decodeUnsignedShort($data,&$index){
        $bytes = substr($data, $index, 2);
        $c = unpack('n', $bytes);
        $result = $c[1];
        $index += 2;
        return $result;
    }

    /**
     * @param $data
     * @param $index
     * @return string
     */
    public static function decodeString($data,&$index){
        $length = self::decodeUnsignedShort($data, $index);
        $data = substr($data, $index, $length);
        $index += $length;
        return $data;
    }
}