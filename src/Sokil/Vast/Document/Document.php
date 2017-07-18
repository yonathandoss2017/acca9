<?php

namespace Sokil\Vast\Document;

class Document
{    
    /**
     * @var \DomDocument
     */
    private $xml;

    /**
     * Cached ad sequence
     *
     * @var array
     */
    private $vastAdSequence = array();

    /**
     * Document constructor.
     *
     * @param \DOMDocument $xml
     */
    public function __construct(\DOMDocument $xml)
    {
        $this->xml = $xml;
    }

    /**
     * Convert to string
     *
     * @deprecated use `(string) $document` instead
     *
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * "Magic" method to convert document to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->xml->saveXML();

    }

    /**
     * Get DomDocument object
     *
     * @return \DomDocument
     */
    public function toDomDocument()
    {
        return $this->xml;
    }
    
    /**
     * Create "Ad" section ov "VAST" node
     *
     * @param string $type
     * @throws \Exception
     *
     * @return \Sokil\Vast\Ad
     */
    private function createAdSection($type)
    {        
        // Check Ad type
        $adTypeClassName = '\\Sokil\\Vast\\Ad\\' . $type;
        if (!class_exists($adTypeClassName)) {
            throw new \Exception('Ad type ' . $type . ' not supported');
        }
        
        // create dom node
        $adDomElement = $this->xml->createElement('Ad');
        $this->xml->documentElement->appendChild($adDomElement);

        // Create type element
        $adTypeDomElement = $this->xml->createElement($type);
        $adDomElement->appendChild($adTypeDomElement);
        
        // create ad section
        $adSection = new $adTypeClassName($adDomElement);
        
        // cache
        $this->vastAdSequence[] = $adSection;
        
        return $adSection;
    }
    
    /**
     * Create inline Ad section
     *
     * @return \Sokil\Vast\Ad\InLine
     */
    public function createInLineAdSection()
    {
        return $this->createAdSection('InLine');
    }
    
    /**
     * Create Wrapper Ad section
     *
     * @return \Sokil\Vast\Ad\Wrapper
     */
    public function createWrapperAdSection()
    {
        return $this->createAdSection('Wrapper');
    }

    /**
     * Get document ad sections
     *
     * @return array
     * @throws \Exception
     */
    public function getAdSections()
    {
        if (!$this->vastAdSequence) {
            
            foreach ($this->xml->documentElement->childNodes as $adDomElement) {
                
                // get Ad tag
                if (!($adDomElement instanceof \DOMElement)) {
                    continue;
                }
                
                if ('ad' !== strtolower($adDomElement->tagName)) {
                    continue;
                }

                // get Ad type tag
                foreach ($adDomElement->childNodes as $node) {
                    if (!($node instanceof \DomElement)) {
                        continue;
                    }
                    
                    $type = $node->tagName;

                    // create ad section
                    $adTypeClassName = '\\Sokil\\Vast\\Ad\\' . $type;
                    if(!class_exists($adTypeClassName)) {
                        throw new \Exception('Ad type ' . $type . ' not allowed');
                    }

                    $this->vastAdSequence[] = new $adTypeClassName($adDomElement);
                    break;
                }
            }
        }
        
        return $this->vastAdSequence;
    }
}