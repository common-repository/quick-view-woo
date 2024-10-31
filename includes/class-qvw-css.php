<?php

class QVW_CSS {
	private $rules = array();

	private $rule_prototype = array(
		'selector'   => '',
		'properties' => array(),
	);

	public function rule_exists( $rule_name ) {
		if ( array_key_exists( $rule_name, $this->rules ) ) {
			return true;
		}

		return false;
	}

	public function add_rule( $rule_name, $selector ) {
		if ( ! array_key_exists( $rule_name, $this->rules ) ) {
			$this->rules[ $rule_name ]             = $this->rule_prototype;
			$this->rules[ $rule_name ]['selector'] = $selector;

			return $this->rules[ $rule_name ];
		}

		return false;
	}

	public function remove_rule( $rule_name ) {
		if ( $this->rule_exists( $rule_name ) ) {
			$removed = $this->rules[ $rule_name ];
			unset( $this->rules[ $rule_name ] );

			return $removed;
		}

		return false;
	}

	public function get_rule( $rule_name ) {
		if ( $this->rule_exists( $rule_name ) ) {
			return $this->rules[ $rule_name ];
		}

		return false;
	}

	public function property_exists( $rule_name, $property ) {
		if ( $this->rule_exists( $rule_name ) && array_key_exists( $property, $this->rules[ $rule_name ]['properties'] ) ) {
			$value = $this->rules[ $rule_name ]['properties'][ $property ];
			if ( false !== $value && null !== $value && '' !== $value ) {
				return true;
			}
		}

		return false;
	}

	public function set_property( $rule_name, $property, $value ) {
		if ( $this->rule_exists( $rule_name ) ) {
			$this->rules[ $rule_name ]['properties'][ $property ] = $value;

			return true;
		}

		return false;
	}

	public function remove_property( $rule_name, $property ) {
		if ( $this->property_exists( $rule_name, $property ) ) {
			$removed = $this->rules[ $rule_name ]['properties'][ $property ];

			return $removed;
		}

		return false;
	}

	public function get_property( $rule_name, $property ) {
		if ( $this->property_exists( $rule_name, $property ) ) {
			return $this->rules[ $rule_name ]['properties'][ $property ];
		}

		return false;
	}

	public function generate_css() {
		$css_array = array();
		foreach ( $this->rules as $rule_name => $rule ) {
			$properties_str = '';
			foreach ( $rule['properties'] as $property => $value ) {
				if ( false !== $value && null !== $value && '' !== $value ) {
					$properties_str .= sprintf( '%1$s: %2$s; ', $property, $value );
				} else {
					continue;
				}
			}

			if ( ! empty( $properties_str ) ) {
				$css_array[] = sprintf( '%1$s { %2$s }', $rule['selector'], trim( $properties_str ) );
			}
		}

		return implode( PHP_EOL, $css_array );
	}

}
