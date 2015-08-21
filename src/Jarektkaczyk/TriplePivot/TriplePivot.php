<?php

namespace Jarektkaczyk\TriplePivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TriplePivot
 * @package Jarektkaczyk\TriplePivot
 */
class TriplePivot extends Pivot {

	/**
	 * Third model key name
	 *
	 * @var string
	 */
	protected $thirdKey;

	/**
	 * Third model
	 *
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	protected $third;

	/**
	 * @param Model $parent
	 * @param Model $third
	 * @param array $attributes
	 * @param string $table
	 * @param boolean $exists
	 */
	public function __construct( Model $parent, Model $third, $attributes, $table, $exists = false ) {
		parent::__construct( $parent, $attributes, $table, $exists );

		$this->thirdKey = $third->getKeyName();
		$this->third    = $third;
	}

	/**
	 * @return string
	 */
	public function getThirdKey() {
		return $this->thirdKey;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getThird() {
		return $this->third;
	}
	
	/**
	 * Return a collection of ids of the third relation.
	 * 
	 * @access public
	 * @return \Illuminate\Database\Eloquent\Collection 
	 */
	public function getThirds($foreign_id=null) {
		
		$models = $this	->getQuery()
						->where( $this->getForeignKey(), '=', $foreign_id?:$this->{$this->getForeignKey()} )
						->where( $this->getOtherKey(), '=', $this->{$this->getOtherKey()} )
						->get([$this->getThirdKey()]);
		return $models;
	}
	
	/**
	 * Set the key names for the pivot model instance.
	 *
	 * @param  string $foreignKey
	 * @param  string $otherKey
	 * @param  string $thirdKey
	 *
	 * @return TriplePivot
	 */
	public function setTriplePivotKeys( $foreignKey, $otherKey, $thirdKey ) {
		$this->foreignKey = $foreignKey;

		$this->otherKey = $otherKey;

		$this->thirdKey = $thirdKey;

		return $this;
	}

}
