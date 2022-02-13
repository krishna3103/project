<?php

namespace App\Service;

class XmlService {

    private string $filePath;
    private $type;

    public function __construct() { }

    public function setFilePath($path) {
        $this->filePath = $path;
    }

    public function getFilepath(): string
    {
        return $this->filePath;
    }

    public function setType($type = 'online') {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getXmlFileContent(): array
    {
        $path = "ftp://".$_ENV['USER_NAME'].":".$_ENV['PASSWORD']."@".$_ENV['HOST']."/".$this->filePath;
        if ($this->type == 'offline') {
            $path = $this->filePath;
        }

        if (!file_exists($path)){
            $message = json_encode(['error'=> ['message'=>'XML file does not exist.']]);
            throw new \Exception($message);
        }

        $xmlData = file_get_contents($path);
        return $this->xmlToArray($xmlData);
    }

    public function xmlToArray($xmlData): array
    {
        $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xmlJson = json_encode($xml);
        $xmlArr = json_decode($xmlJson, true);
        $data = [];
        if(!empty($xmlArr) && !empty($xmlArr['item'])) {
            $xmlData = $xmlArr['item'];
            $data = [array_keys($xmlData[0])];
            foreach ($xmlData AS $value) {
                array_push($data, array_values(array_map([$this,"removeEmptyInternal"], $value)));
            }
        }
        return $data;
    }

    /**
     * @param $value
     * @return string
     */
    private function removeEmptyInternal($value) {
        return (!is_array($value)) ? (string)$value : '';
    }

}
