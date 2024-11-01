<?php

namespace TotalSurvey\Blocks;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Blocks\Concerns\GenerateInputName;
use TotalSurvey\Models\Block;
use TotalSurvey\Models\Entry;
use TotalSurvey\Models\EntryBlock;
use TotalSurvey\Models\Validation;
use TotalSurveyVendors\TotalSuite\Foundation\Helpers\Html;

class MultipleChoices extends BlockType {
	public static $category = 'question';
	public static $id = 'multiple-choices';
	public static $icon = 'check_box';
	public static $static = false;
	public static $aggregate = true;
	public static $aliases = [ 'checkbox' ];

	use GenerateInputName;

	/**
	 * @param Block $block
	 *
	 * @return Html
	 */
	public static function render( Block $block ) {
		$container = Html::create( 'div', [
			'class' => 'question-choices is-' . $block->option( 'distribution', 'vertical' ),
		] );
		$options   = $block->option( 'choices', [] );

		foreach ( $options as $index => $option ) {
			$input = Html::create(
				'input',
				[
					'type'                => 'checkbox',
					'name'                => static::getInputName( $block->section->uid, $block->uid, '' ),
					'value'               => $option['uid'],
					'id'                  => "{$block->uid}-{$index}",
					'class'               => $block->option( 'cssClass' ),
					'data-variable-name'  => $block->uid,
					'data-variable-value' => $option['label'],
				],
				$option['label']
			);

			$default = $block->option( 'defaultValue', false );

			if ( $default && $default === $option['uid'] ) {
				$input->setAttribute( 'checked' );
			}

			$label = Html::create( 'label', [ 'class' => 'checkbox' ], $input );

            if ($block->option('showDescriptions')) {
                $description = empty($option['description']) ? '' : Html::create(
                    'div',
                    ['class' => 'question-choice-description'],
                    $option['description']
                );
                $label->addContent($description);
            }

			$container->addContent( $label );
		}

		if ( $block->option( 'allowOther' ) === true ) {
			$label    = Html::create( 'label', [ 'class' => 'checkbox other-container' ] );
			$checkbox = Html::create(
				'input',
				[
					'class' => 'other',
					'type'  => 'checkbox',
					'name'  => static::getInputName( $block->section->uid, $block->uid, '' ),
					'value' => '',
				],
				__( 'Other', 'totalsurvey' )
			);
			$input    = Html::create(
				'input',
				[
					'type'        => 'text',
					'name'        => '',
					'data-target' => static::getInputName( $block->section->uid, $block->uid, '' ),
					'id'          => $block->uid,
					'placeholder' => __( 'Other', 'totalsurvey' ),
				]
			);

			$label->addContent( $checkbox )->addContent( $input );
			$container->addContent( $label );
		}

		return $container;
	}

	public static function getValidationRules( Block $block ) {
		if ( $block->field->isRequired() && ! $block->field->allowOther() ) {
			$block->field->validations->set(
				'inArray',
				Validation::from(
					[
						'enabled' => true,
						'options' => [
							'values' => $block->field->getChoicesAttribute( 'uid' ),
						],
					]
				)
			);
		}

		return parent::getValidationRules( $block );
	}

	public static function getValidationMessages( Block $block ) {
		return [
			"{$block->uid}:in_array" => str_replace(
				[ '{{allowedValues}}' ],
				[ implode( ', ', $block->field->getChoicesAttribute( 'label' ) ) ],
				__( 'Must be one of: {{allowedValues}}', 'totalsurvey' )
			),
		];
	}

	public static function getSerializedFromRawValue( Block $block, Entry $entry, $value ) {
		return $value;
	}

	public static function getTextFromRawValue( Block $block, Entry $entry, $value ) {
		$value   = (array) $value;
		$choices = $block->field->getChoicesAttribute( 'label', 'uid' );

		foreach ( $value as $index => $subValue ) {
			if ( isset( $choices[ $subValue ] ) ) {
				$value[ $index ] = esc_html( $choices[ $subValue ] );
			} else {
				$value[ $index ] = sprintf( __( 'Other: %s', 'totalsurvey' ), esc_html( $subValue ) );
			}
		}

		return implode( ', ', (array) $value );
	}

	public static function getMetadataFromRawValue( Block $block, Entry $entry, $value ) {
		$meta    = [ 'choices' => [] ];
		$value   = (array) $value;
		$choices = $block->field->getChoicesAttribute( 'label', 'uid' );

		foreach ( $value as $index => $subValue ) {
			if ( isset( $choices[ $subValue ] ) ) {
				$meta['choices'][ $subValue ] = esc_html( $choices[ $subValue ] );
			} else {
				$meta['other']            = true;
				$meta['choices']['other'] = esc_html( $subValue );
			}
		}

		return $meta;
	}

	public static function getTranslatableAttributes() {
		return [
			'field.options.choices.label',
		];
	}

	public static function getFormDataFromRawValue( EntryBlock $block, Entry $entry ) {
		return [ static::getInputName( $block->section->uid, $block->uid, '' ) => (array) $block->value ];
	}
}