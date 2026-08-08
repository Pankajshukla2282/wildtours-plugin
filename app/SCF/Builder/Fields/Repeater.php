<?php

namespace PWT\SCF\Builder\Fields;

use PWT\SCF\Builder\Field;

defined('ABSPATH') || exit;

/**
 * Repeater field type (inline version for use inside other field builders).
 * For a standalone repeater, use PWT\SCF\Builder\Repeater.
 */
class Repeater extends Field
{
    protected array $subFields = [];
    protected string $layout = 'table';
    protected int $minRows = 0;
    protected int $maxRows = 0;
    protected string $buttonLabel = 'Add Row';

    public function addField(Field $field): static
    {
        $this->subFields[] = $field;
        return $this;
    }

    public function subFields(array $fields): static
    {
        $this->subFields = $fields;
        return $this;
    }

    /** Layout: 'table' | 'block' | 'row' */
    public function layout(string $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function min(int|float $min): static
    {
        $this->minRows = $min;
        return $this;
    }

    public function max(int|float $max): static
    {
        $this->maxRows = $max;
        return $this;
    }

    public function buttonLabel(string $label): static
    {
        $this->buttonLabel = $label;
        return $this;
    }

    public function build(): array
    {
        return array_merge($this->baseConfig(), [
            'type'         => 'repeater',
            'layout'       => $this->layout,
            'min'          => $this->minRows ?: '',
            'max'          => $this->maxRows ?: '',
            'button_label' => $this->buttonLabel,
            'sub_fields'   => array_map(fn(Field $f) => $f->build(), $this->subFields),
        ]);
    }
}
