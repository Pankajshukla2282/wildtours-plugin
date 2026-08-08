<?php

namespace PWT\SCF\Builder\Fields;

use PWT\SCF\Builder\Field;

defined('ABSPATH') || exit;

/** Image gallery field. */
class Gallery extends Field
{
    protected string $returnFormat = 'array';
    protected string $previewSize = 'thumbnail';
    protected string $library = 'all';
    protected int $min = 0;
    protected int $max = 0;
    protected string $mimeTypes = '';

    /** Return format: 'array' | 'url' | 'id' */
    public function returnFormat(string $format): static
    {
        $this->returnFormat = $format;
        return $this;
    }

    public function previewSize(string $size): static
    {
        $this->previewSize = $size;
        return $this;
    }

    /** Library: 'all' | 'uploadedTo' */
    public function library(string $library): static
    {
        $this->library = $library;
        return $this;
    }

    public function min(int|float $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(int|float $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function build(): array
    {
        return array_merge($this->baseConfig(), [
            'type'          => 'gallery',
            'return_format' => $this->returnFormat,
            'preview_size'  => $this->previewSize,
            'library'       => $this->library,
            'min'           => $this->min ?: '',
            'max'           => $this->max ?: '',
            'mime_types'    => $this->mimeTypes,
        ]);
    }
}
