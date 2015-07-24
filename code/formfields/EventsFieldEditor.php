<?php
/**
 * A form field allowing a user to customize and create form fields.
 * for saving into a {@link UserDefinedForm}
 *
 * @package userforms
 */

class EventsFieldEditor extends FieldEditor {
	public function Fields() {
		if($this->form && $this->form->getRecord() && $this->name) {
			$relationName = $this->name;
			$fields = $this->form->getRecord()->getComponents($relationName);
		
			if($fields) {
				foreach($fields as $field) {
					if(!$this->canEdit() && is_a($field, 'FormField')) {
						//$fields->remove($field);
						$fields->push($field->performReadonlyTransformation());
					}
				}
			}
			
			$fieldList = new ArrayList();
			
			foreach($fields as $field) {
				if($field->ForcedField) {
					$field->setReadonly(true);
				}
				
				$fieldList->push($field);
			}
			
			foreach($fieldList as $fields) {
				Debug::show($fields->Name);
				die();
			}
			
			return $fieldList;
		}
	}

}
