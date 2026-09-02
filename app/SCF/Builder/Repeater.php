<?php

namespace PWT\SCF\Builder;

use PWT\SCF\Builder\Fields\Image;
use PWT\SCF\Builder\Fields\Number;
use PWT\SCF\Builder\Fields\Select;
use PWT\SCF\Builder\Fields\Text;
use PWT\SCF\Builder\Fields\Textarea;
use PWT\SCF\Builder\Fields\Wysiwyg;

defined('ABSPATH') || exit;

/**
 * Repeater field — holds a list of sub-fields repeated by the user.
 */
class Repeater extends Field
{
    /** @var Field[] Sub-fields inside this repeater */
    protected array $subFields = [];

    /** @var string Row layout: 'table' | 'block' | 'row' */
    protected string $layout = 'table';

    /** @var int|null Minimum number of rows */
    protected ?int $minRows = null;

    /** @var int|null Maximum number of rows */
    protected ?int $maxRows = null;

    /** @var string Label for the 'Add Row' button */
    protected string $buttonLabel = 'Add Row';

    /** Add a sub-field. */
    public function addField(Field $field): static
    {
        $this->subFields[] = $field;
        return $this;
    }

    public function text(string $name, ?string $label = null): static
    {
        return $this->addField(new Text($name, $label));
    }

    public function textarea(string $name, ?string $label = null): static
    {
        return $this->addField(new Textarea($name, $label));
    }

    public function wysiwyg(string $name, ?string $label = null): static
    {
        return $this->addField(new Wysiwyg($name, $label));
    }

    public function image(string $name, ?string $label = null): static
    {
        return $this->addField(new Image($name, $label));
    }

    public function number(string $name, ?string $label = null): static
    {
        return $this->addField(new Number($name, $label));
    }

    public function select(string $name, ?string $label = null): static
    {
        return $this->addField(new Select($name, $label));
    }

    /** Set all sub-fields at once. */
    public function subFields(array $fields): static
    {
        $this->subFields = $fields;
        return $this;
    }

    /** Set row layout ('table', 'block', or 'row'). */
    public function layout(string $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    /** Set minimum number of rows. */
    public function min(int|float $min): static
    {
        $this->minRows = $min;
        return $this;
    }

    /** Set maximum number of rows. */
    public function max(int|float $max): static
    {
        $this->maxRows = $max;
        return $this;
    }

    /** Set the 'Add Row' button label. */
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
            'min'          => $this->minRows,
            'max'          => $this->maxRows,
            'button_label' => $this->buttonLabel,
            'sub_fields'   => array_map(fn(Field $f) => $f->build(), $this->subFields),
        ]);
    }
}
