<?php
namespace Amba\Core;
use \DOMDocument, \DOMElement, \DOMAttribute, \DOMXPath;
/**
* Represents an xml element.
*/
class XMLElement extends DOMElement
{
    /**
     * Creates XMLElement object.
     * @param string $name The node name.
     * @param string $value The node value.
     * @param string $uri The uri.
     */
    public function __construct($name, $value, $uri)
    {
        parent::__construct($name, $value, $uri);
    }

    /**
     * Gets a well-formed XML string based on this XMLElement.
     * @return string
     */
    public function saveXML()
    {
        return $this->ownerDocument->saveXML($this);
    }

    /**
     * Gets xpath to this node.
     * @return string
     */
    public function getNodePath()
    {
        return $this->getNodePathInternal($this, "");
    }

    private function getNodePathInternal($node, $result)
    {
        if (empty($node) || $node instanceof XMLDocument)
            return "";
        $nodeName = $node->nodeName;
        $nodeNumber = (sizeof($node->precendingSibling($nodeName)) + 1);
        $nodeSection = $nodeName . "[" . $nodeNumber . "]";
        $result = $this->getNodePathInternal($node->parentNode, $result) . "/" . $nodeSection . $result;
        return $result;
    }

    /* Navigation functions */

    /**
     * Gets first matched child node of this node. Does not create map object.
     * @param string $nodeName If $nodeName is not empty, only element with this node name will be selected.
     * @return XMLElement
     */
    public function getChildNode($nodeName = "")
    {
        foreach($this->childNodes as $child)
        {
            if ($child instanceof DOMElement)
            {
                if (!empty($nodeName))
                {
                    if ($child->nodeName == $nodeName)
                        return $child;
                }
                else
                {
                   return $child;
                }
            }
        }
        return null;
    }

    /**
     * Gets child nodes of this node. Does not create map object.
     * @param string $nodeName If $nodeName is not empty, only elements with this node name will be selected.
     * @return array The array of XMLElement.
     */
    public function getChildNodes($nodeName = "")
    {
        $result = array();
        foreach($this->childNodes as $child)
        {
            if ($child instanceof \DOMNode)
            {
                if (!empty($nodeName))
                {
                    if ($child->nodeName == $nodeName)
                        $result[] = $child;
                }
                else
                {
                   $result[] = $child;
                }
            }
        }
        return $result;
    }

    /**
     * Selects the first XmlNode that matches the XPath expression.
     * @param string $xpath The xpath to select.
     * @return XMLElement
     */
    public function selectSingleNode($xpath)
    {
        if (preg_match('/^\w+$/', $xpath))
            return $this->getChildNode($xpath);
        $result = $this->map($xpath);
        if (count($result) > 0)
            return $result[0];
        return null;
    }

    /**
     * Selects a list of nodes matching the XPath expression.
     * @param string $xpath The xpath to select.
     * @return array
     */
    public function selectNodes($xpath)
    {
        if (preg_match('/^\w+$/', $xpath))
            return $this->getChildNodes($xpath);
        return $this->map($xpath);
    }

    private function isLocalXPath($xpath)
    {
        return strpos($xpath, "/") !== 0 && strpos($xpath, "//") !== 0;
    }

    /**
     * Returns parent node for current node.
     * @return XMLElement
     */
    public function getParent()
    {
        return $this->parentNode;
    }

    public function precendingSibling($siblingName = '')
    {
        $parent = $this->parentNode;
        if ($parent == null)
            return null;
        $result = array();
        foreach($parent->childNodes as $child)
        {
            if ($this->isSameNode($child))
            {
                return $result;
            }
            if ($child instanceof DOMElement)
            {
                if (!empty($siblingName))
                {
                    if ($siblingName == $child->nodeName)
                        $result[] = $child;
                }
                else
                    $result[] = $child;
            }
        }
        return $result;
    }

    /**
     * Returns following sibling nodes of this node.
     * @param string $siblingName if not empty, output array will contain elements with $siblingName node name only.
     * @return array of XMLElements
     */
    public function followingSibling($siblingName = '')
    {
        $parent = $this->parentNode;
        if ($parent == null)
            return null;
        $result = array();
        $follow = false;
        foreach($parent->childNodes as $child)
        {
            if ($this->isSameNode($child))
            {
                $follow = true;
                continue;
            }
            if ($follow && ($child instanceof DOMElement))
            {
                if (!empty($siblingName))
                {
                    if ($siblingName == $child->nodeName)
                        $result[] = $child;
                }
                else
                    $result[] = $child;
            }
        }
        return $result;
    }

    /* Attribute processing */

    /**
     * Returns the XmlAttribute with the specified name or 'null' if attribute does not exists.
     * @param string $name
     * @return XMLAttribute
     */
    public function getAttributeNode($name)
    {
        if ($this->hasAttribute($name))
        {
            return parent::getAttributeNode($name);
        }
        return null;
    }

    /**
     * Gets attibute value or default value if attribute does not exists.
     * @param string $name The attribute name.
     * @param object $default The default value.
     * @return object
     */
    public function getAttribute($name, $default = null)
    {
        if (!$this->hasAttribute($name))
        {
            return $default;
        }
        return parent::getAttribute($name);
    }

    /**
     * Sets attribute value. Creates attribute if it does not exists.
     * Returns this XMLElement.
     * @param string $name
     * @param string $value
     * @return XMLElement
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name))
        {
            $this->getAttributeNode($name)->value = $value;
        }
        $newAttr = $this->ownerDocument->createAttribute($name);
        $newAttr->value = $value;
        $this->appendChild($newAttr);
        return $this;
    }

    /**
     * Copies attribute values from $node to this node. Creates this node attribute if it does not exists.
     * @param DOMElement $node The destination node.
     * @param bool $overwrite Indicates whether overwite existed attribute value.
     * @return XMLElement this node.
     */
    public function copyAttributes($node, $overwrite = true)
    {
        if (is_null($node))
            throw new InvalidArgumentException();
        if($node->hasAttributes())
        {
            if(!is_null($node->attributes))
            {
                foreach ($node->attributes as $index => $attr)
                {
                    if ($this->hasAttribute($attr->name) && !$overwrite)
                        continue;
                    $this->setAttribute($attr->name, $attr->value);
                }
            }
        }
        return $this;
    }

    /**
     * Gets this node attibute value by name.
     * @param string $name The name of attribute.
     * @return string
     */
    public function  __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * Sets value to this node attribute. Creates attribute if it does not exists.
     * @param string $name The attribute name.
     * @param string $value The attribute value.
     */
    public function  __set($name,  $value)
    {
        $this->setAttribute($name, $value);
    }

    /* change xml structure */

    /**
     * Adds subnode to this node.
     * @param string $name The name of subnode.
     * @return XMLElement The created node.
     */
    public function addNode($name, XMLElement $subNode = null)
    {
        $newNode = $this->ownerDocument->createElement($name);
        $this->appendChild($newNode);

        if ($subNode != null)
        {
            if($this->ownerDocument !== $subNode->ownerDocument)
            {
                $newNode->importNode($subNode);
                return $newNode;
            }
            $newNode->appendChild($subNode);
        }
        return $newNode;
    }

     /**
     * Adds node before this node.
     * @param string $name The name of new node.
     * @return XMLElement The created and added node.
     */
    public function addNodeBefore($name)
    {
        $newNode = $this->ownerDocument->createElement($name);
        $this->getParent()->insertBefore($newNode, $this);
        return $newNode;
    }

    /**
     * Adds CDATA element to this node.
     * @param string $value The CDATA value.
     * @return XMLElement The created CDATA node.
     */
    public function addCDATA($value)
    {
        $cdata = $this->ownerDocument->createCDATASection($value);
        $this->appendChild($cdata);
        return $cdata;
    }

    /**
     * Creates and adds node with CDATA section to this node.
     * @param string $name The name of node.
     * @param string $value The CDATA value.
     * @return XMLElement created node, parent of cdata section.
     */
    public function addNodeWithCData($name, $value)
    {
        $node = $this->addNode($name);
        $node->addCDATA($value);
        return $node;
    }

    public function addArray($array, $itemNodeName = 'Item', $keyAttributeName = 'Name')
    {
        foreach($array as $key => $value)
        {
            $itemNode = $this->addNodeWithCData($itemNodeName, $value);
            $itemNode->setAttribute($keyAttributeName, $key);
        }
        return $this;
    }

    /**
     * Imports $node to this document and adds $node to this node.
     * @param DOMNode $node The node to import.
     * @return DOMNode The imported node node.
     */
    public function importNode(\DOMNode $node)
    {
        $node = $this->ownerDocument->importNode($node, true);
        $this->appendChild($node);
        return $node;
    }

    /**
     * Creates XMLDocument with branch of this node (this node and subnodes).
     * @return XMLDocument The created XMLDocument.
     */
    public function exportNode()
    {
        $doc = new XMLDocument();
        $node = $doc->importNode($this, true);
        $doc->appendChild($node);
        return $doc;
    }

    /**
     * Sets inner xml to this node.
     * @param string $xml The XML to set.
     * @return XMLElement this node.
     */
    public function setInnerXML($xml)
    {
        foreach($this->childNodes as $child)
        {
            $this->removeChild($child);
        }
        return $this->appendXML($xml);
    }

    /**
     * Returns element inner xml string.
     * @return string
     */
    public function getInnerXML()
    {
        $result = "";
        foreach($this->childNodes as $child)
        {
           if ($child instanceof XMLElement)
                $result .= $child->saveXML();
            else
            {
                $result .= $child->nodeValue;
            }
        }
        return $result;
    }
    
    /**
     * Appends xml to this node.
     * @param string $xml The XML to append.
     * @return XMLElement this node.
     */
    public function appendXML($xml)
    {
        $fragment = $this->ownerDocument->createDocumentFragment();
        $fragment->appendXML($xml);
        $this->appendChild($fragment);
        return $this;
    }

     /**
     * Applies a given function to a list of elements and return.
     * @param string $xpath The xpath to element select.
     * @param function $mapFunc
     * @return array
     */
    public function map($xpath, $mapFunc = null)
    {
        if ($this->isLocalXPath($xpath))
            $xpath = $this->getNodePath() . "/" . $xpath;
        return $this->ownerDocument->map($xpath, $mapFunc);
    }

    /**
     * Wraps this node with node with name $wrapperNodeName.
     * @param string $wrapperNodeName.
     * @return XMLElement wrapper node.
     */
    public function wrapNode($wrapperNodeName)
    {
        $wrapperNode = $this->ownerDocument->createElement($wrapperNodeName);
        $this->parentNode->appendChild($wrapperNode);
        $this->parentNode->replaceChild($wrapperNode, $this);
        $wrapperNode->appendChild($this);
        return $wrapperNode;
    }

    /**
     * Removes all children of this element.
     * @return XMLElement
     */
    public function removeAllChildren()
    {
        foreach ($this->getChildNodes() as $child)
        {
            $this->removeChild($child);
        }
        return $this;
    }

    /**
     * Calls function $func($node) for each node of this element subtree.
     * @param callback $func
     */
    public function subtreeWalk($func)
    {
        foreach ($this->getChildNodes() as $node)
        {
            $func($node);
        }
        foreach ($this->getChildNodes() as $node)
        {
            if ($node instanceof  XMLElement)
                $node->subtreeWalk($func);
        }
    }

    /**
     * Removes all nodes selected by $xpath except $node.
     * @param string $xpath
     * @param XMLElement $node
     */
    public function removeExceptOne($xpath, XMLElement $node)
    {
        foreach($this->selectNodes($xpath) as $child)
        {
            if ($child !== $node)
            {
                $child->parentNode->removeChild($child);
            }
        }
    }

    /**
     * Returns node value by $xpath. Returns $default object, if node does not exits.
     * @param string $xpath
     * @param object $default
     */
    public function getNodeValue($xpath, $default = null)
    {
        $node = $this->selectSingleNode($xpath);
        if (empty($node))
        {
            return $default;
        }
        return $node->nodeValue;
    }
}

?>