<?php
/**
 * list类
 * Author:Shaun·Yang
 * Date:2023/1/28
 * Time:下午1:04
 * Description:
 */

namespace citic;

class ListData
{
    private string $name;

    /**
     * 二维数组
     * @var array
     */
    private array $list;

    public function __construct($name, $list = [])
    {
        $this->name = $name;
        $this->list = $list;
    }

    /**
     * 以row的形式输出xml字符串
     * @return string
     */
    public function getListXmlWithRow(): string
    {
        return '<list name="' . $this->name . '">' . XmlTools::list_to_xml($this->list) . '</list>';
    }
}