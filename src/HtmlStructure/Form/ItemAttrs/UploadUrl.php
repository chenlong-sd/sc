<?php
/**
 * datetime: 2023/6/4 11:19
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait UploadUrl
{
    protected string $uploadUrl = '';

    /**
     * @param string $uploadUrl
     *
     * @return $this
     */
    public function setUploadUrl(string $uploadUrl): static
    {
        $this->uploadUrl = $uploadUrl;

        return $this;
    }
}