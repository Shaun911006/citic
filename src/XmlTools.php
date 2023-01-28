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
    public static function encode($data, string $encoding = 'utf-8', bool $root = false): string
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
    private static function data_to_xml(array $data): string
    {
        $xml = '';
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $xml .= '<' . $key . '>' . self::data_to_xml($val) . '</' . $key . '>';
            } elseif ($val instanceof ListData) {
                $xml .= $val->getListXmlWithRow();
            } else {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            }
        }
        return $xml;
    }

    /**
     * list转xml
     * @param array $list
     * @return string
     */
    public static function list_to_xml(array $list): string
    {
        $xml = '';
        foreach ($list as $row) {
            $xml .= '<row>';
            $xml .= self::data_to_xml($row);
            $xml .= '</row>';
        }
        return $xml;
    }

    /**
     * xml转json字符串
     * @param $xml
     * @return string
     */
    public static function decode($xml) :string
    {
        if ($xml == '') return '';
        libxml_disable_entity_loader(true);
        return json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE);
    }
}