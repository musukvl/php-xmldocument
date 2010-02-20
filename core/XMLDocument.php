<?php
/**
 * Represents a XMLDocument.
 */
class XMLDocument extends DOMDocument
{
    /**
     * Creates XMLDocument object.
     * @param string $xml The xml source.
     * @return XMLDocument
     */
    public function __construct($xml = null)
    {
        parent::__construct();
        $this->registerNodeClass('DOMElement', 'XMLElement');
        if (empty($xml))
        {
            return;
        }
        if (is_array($xml))
        {
            //$this->FromArray($xml);
        }
        else
        {
            $this->loadXML($xml);
        }
    }

    /**
     * Gets root node of this document.
     * @return XMLElement
     */
    public function getRoot()
    {
        return $this->documentElement;
    }

    /**
     * Adds subnode to this document.
     * @param string $name The name of subnode.
     * @return XMLElement The created node.
     */
    public function addNode($nodeName)
    {
        $newNode = $this->createElement($nodeName);
        $this->appendChild($newNode);
        return $newNode;
    }

    private function isLocalXPath($xpath)
    {
        return strpos($xpath, "/") !== 0 && strpos($xpath, "//") !== 0;
    }

    /**
     * Selects the first XmlNode that matches the map expression.
     * @param string $xpath The map.
     * @return XMLElement
     */
    public function selectSingleNode($xpath)
    {
        if ($this->getRoot() == null)
            return null;
        if ($xpath == $this->getRoot()->nodeName)
            return $this->getRoot();
        if ($this->isLocalXPath($xpath))
            $xpath = "/" . $xpath;
        return $this->getRoot()->selectSingleNode($xpath);
    }

    /**
     * Selects a list of nodes matching the map expression.
     * @param string $xpath The map.
     * @return array of XMLElement
     */
    public function selectNodes($xpath)
    {
        $result = array();
        if ($this->getRoot() == null)
            return $result;
        if ($xpath == $this->getRoot()->nodeName)
        {
            array_push($result, $this->getRoot());
            return $result;
        }
        if ($this->isLocalXPath($xpath))
            $xpath = "/" . $xpath;
        return $this->getRoot()->selectNodes($xpath);
    }

    /**
     * Applies a given function to a list of elements and return.
     * @param string $xpath The xpath to element select.
     * @param function $mapFunc
     * @return array
     */
    public function map($xpath, $mapFunc = null)
    {
        $domXPath = new DOMXPath($this);
        $ret = array();
        $nodes = $domXPath->query($xpath);
        foreach ($nodes as $node)
        {
            if ($mapFunc == null)
                $ret[] = $node;
            else
                $ret[] = $mapFunc($node);
        }
        return $ret;
    }

    /**
     * Performs XLS transformation.
     * @param string $xsltTransformation
     * @return string
     */
    public function transform($xsltTransformation)
    {
        $xsl = new XMLDocument($xsltTransformation);
        $processor = new XSLTProcessor;
        $processor->importStyleSheet($xsl);
        return $processor->transformToXML($this);
    }
}

?>