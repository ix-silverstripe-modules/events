<?php

namespace Internetrix\Events\FormFields;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

class SelectAllCheckboxSetField extends CheckboxSetField
{
    public function Field($properties = [])
    {
        Requirements::css('internetrix/events:css/SelectAllCheckboxSetField.css');
        Requirements::javascript('internetrix/events:javascript/SelectAllCheckboxSetField.js');

        $source = $this->source;
        $values = $this->value;
        $items = [];

        // Get values from the join, if available
        if (is_object($this->form)) {
            $record = $this->form->getRecord();
            if (!$values && $record && $record->hasMethod($this->name)) {
                $funcName = $this->name;
                $join = $record->$funcName();
                if ($join) {
                    foreach ($join as $joinItem) {
                        $values[] = $joinItem->ID;
                    }
                }
            }
        }

        // Source is not an array
        if (!is_array($source) && !is_a($source, 'SQLMap')) {
            if (is_array($values)) {
                $items = $values;
            } else {
                // Source and values are DataObject sets.
                if ($values && is_a($values, SS_List::class)) {
                    foreach ($values as $object) {
                        if (is_a($object, DataObject::class)) {
                            $items[] = $object->ID;
                        }
                    }
                } elseif ($values && is_string($values)) {
                    $items = explode(',', $values);
                    $items = str_replace('{comma}', ',', $items);
                }
            }
        } else {
            // Sometimes we pass a singluar default value thats ! an array && !SS_List
            if ($values instanceof SS_List || is_array($values)) {
                $items = $values;
            } else {
                $items = explode(',', $values);
                $items = str_replace('{comma}', ',', $items);
            }
        }

        if (is_array($source)) {
            unset($source['']);
        }

        $odd = 0;
        $options = [];

        if ($source == null) {
            $source = [];
        }

        if ($source) {
            foreach ($source as $value => $item) {
                if ($item instanceof DataObject) {
                    $value = $item->ID;
                    $title = $item->Title;
                    if (method_exists($item, 'customTitle')) {
                        $title = $item->customTitle();
                    }
                } else {
                    $title = $item;
                }

                $itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $value);
                $odd = ($odd + 1) % 2;
                $extraClass = $odd ? 'odd' : 'even';
                $extraClass .= ' val' . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $value);

                $options[] = new ArrayData([
                    'ID' => $itemID,
                    'Class' => $extraClass,
                    'Name' => "{$this->name}[{$item->URLSegment}]",
                    'Value' => $item->URLSegment,
                    'Title' => $title,
                    'isChecked' => in_array($value, $items) || in_array($value, $this->defaultItems),
                    'isDisabled' => $this->disabled || in_array($value, $this->disabledItems)
                ]);
            }
        }

        $properties = array_merge($properties, ['Options' => new ArrayList($options)]);

        return $this->customise($properties)->renderWith($this->getTemplates());
    }
}
