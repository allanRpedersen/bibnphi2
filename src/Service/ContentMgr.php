<?php
namespace App\Service;

use Monolog\Logger;

/**
 * 
 */
class ContentMgr
{
    private $originalContent = '';
    private $originalLength;
    private $encoding = NULL;
    private $newContent;

    /**
     * Get the value of originalContent
     */ 
    public function getOriginalContent(): string
    {
        return $this->originalContent;
    }

    /**
     * Set the value of originalContent
     *
     * @return  self
     */ 
    public function setOriginalContent($originalContent): self
    {
        $this->originalContent = $originalContent;
        $this->originalLength = mb_strlen($this->originalContent);
        $this->encoding = mb_detect_encoding($originalContent);

        return $this;
    }


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
     * 
     */
    public function removeTag($tag): self {

        //
        //
        //
        $this->newContent = mb_eregi_replace($tag, '', $this->originalContent);
        return($this);



    } 

    /**
     * Get the value of newContent
     */ 
    public function getNewContent()
    {
        return $this->newContent;
    }

    /**
     * Set the value of newContent
     *
     * @return  self
     */ 
    public function setNewContent($newContent)
    {
        $this->newContent = $newContent;

        return $this;
    }
}
