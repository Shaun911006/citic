<?php
/**
 * Author:Shaun·Yang
 * Date:2020/6/15
 * Time:上午9:12
 * Description:
 */

namespace citic;

class XmlTools
{
    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $encoding 数据编码
     * @param bool $root 根节点名
     * @return string
     */
    public static function encode($data, $encoding = 'utf-8', $root = false)
    {
        if ($root) {
            $xml = '<?xml version="1.0" encoding="' . $encoding . '"?><stream>';
        } else {
            $xml = '';
        }
        $xml .= self::data_to_xml($data);
        if ($root) {
            $xml .= '</stream>';
        }
        return $xml;
    }

    /**
     * 数组转xml
     * @param array $data
     * @return string
     */
    private static function data_to_xml($data)
    {
        $xml = '';
        foreach ($data as $key => $val) {
            if ($key === "list") {
                $xml .= "<$key name='userDataList'>";
            } else if (is_numeric($key)) {
                $xml .= "";
            } else {
                $xml .= "<$key>";
            }
            $xml .= (is_array($val) || is_object($val)) ? self::data_to_xml($val) : $val;
            list($key,) = explode(' ', $key);
            if (is_numeric($key)) {
                $xml .= "";
            } else {
                $xml .= "</$key>";
            }

        }
        return $xml;
    }

    //Xml转数组
    public static function decode($xml)
    {
        if ($xml == '') return '';
        libxml_disable_entity_loader(true);
        $jsonStr = json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        //转码
        $jsonStr = CharsetTools::gbkToUtf8($jsonStr);
        return json_decode($jsonStr, true);
    }


}