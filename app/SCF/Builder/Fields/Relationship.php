<?php

namespace PWT\SCF\Builder\Fields;

use PWT\SCF\Builder\Field;

defined('ABSPATH') || exit;

/** Relationship field (many posts). */
class Relationship extends Field
{
    protected array $postType = [];
    protected array $taxonomy = [];
    protected array $filters = ['search', 'post_type', 'taxonomy'];
    protected int $min = 0;
    protected int $max = 0;
    protected string $returnFormat = 'object';

    /** Filter by post type slugs. */
    public function postType(array $types): static
    {
        $this->postType = $types;
        return $this;
    }

    /** Filter by taxonomy terms. */
    public function taxonomy(array $terms): static
    {
        $this->taxonomy = $terms;
        return $this;
    }

    /** Sidebar filters to show. */
    public function filters(array $filters): static
    {
        $this->filters = $filters;
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

    /** Return format: 'object' | 'id' */
    public function returnFormat(string $format): static
    {
        $this->returnFormat = $format;
        return $this;
    }

    public function build(): array
    {
        return array_merge($this->baseConfig(), [
            'type'          => 'relationship',
            'post_type'     => $this->postType,
            'taxonomy'      => $this->taxonomy,
            'filters'       => $this->filters,
            'min'           => $this->min ?: '',
            'max'           => $this->max ?: '',
            'return_format' => $this->returnFormat,
        ]);
    }
}
