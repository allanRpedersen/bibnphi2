<?php
namespace App\Service;

use Monolog\Logger;

/**
 * 
 */
class ContentMgr
{
    private $originalContent = '';
    private $encoding = NULL;

    /**
     * 
     * 
     */
    public function addTags($indexArray, $lengthToSurround, $beginTag, $endTag) : string
    {
        $newContent = '';
        $fromIndex = 0;

        foreach($indexArray as $index){

            $newContent .= mb_substr($this->originalContent, $fromIndex, $index-$fromIndex, $this->encoding);
            $newContent .= $beginTag;
            $newContent .= mb_substr($this->originalContent, $index, $lengthToSurround, $this->encoding);
            $newContent .= $endTag;
            
            $fromIndex = $index+$lengthToSurround;
        }
        
        $newContent .= mb_substr($this->originalContent, $fromIndex, NULL, $this->encoding);
        return $newContent;
    }

    /**
     * Get the value of originalContent
     */ 
    public function getOriginalContent()
    {
        return $this->originalContent;
    }

    /**
     * Set the value of originalContent
     *
     * @return  self
     */ 
    public function setOriginalContent($originalContent)
    {
        $this->originalContent = $originalContent;
        $this->encoding = mb_detect_encoding($originalContent);

        return $this;
    }
}
