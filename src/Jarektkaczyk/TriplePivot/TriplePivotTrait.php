<?php

namespace Jarektkaczyk\TriplePivot;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TriplePivotTrait
 * @package Jarektkaczyk\TriplePivot
 */
trait TriplePivotTrait {

	/**
	 * Defines many-to-many relation with pivot table between 3 models
	 *
	 * @param string $related
	 * @param string $third
	 * @param string $table
	 * @param string $foreignKey
	 * @param string $otherKey
	 * @param string $thirdKey
	 * @param string $relation
	 *
	 * @returns TripleBelongsToMany
	 */
	public function tripleBelongsToMany( $related, $third, $table = null, $foreignKey = null, $otherKey = null, $thirdKey = null, $relation = null ) {
		
		if ( is_null( $relation ) ) {
			$relation = $this->getBelongsToManyCaller();
		}
		
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$instance = new $related;

		$otherKey = $otherKey ?: $instance->getForeignKey();

		$third = new $third;

		$thirdKey = ( $thirdKey ) ?: $third->getForeignKey();

		if ( is_null( $table ) ) {
			$table = $this->tripleJoiningTable( $related, $third );
		}

		$query = $instance->newQuery();
		
		return new TripleBelongsToMany( $query, $this, $third, $table, $foreignKey, $otherKey, $thirdKey, $relation );
	}
	
	
	
	/**
	 * Accessor for third related model
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getThirdAttribute() {
		$pivot = $this->pivot;

		if ( is_null( $pivot ) ) {
			return null;
		}

		if ( is_null( $pivot->{$pivot->getThirdKey()} ) ) {
			return null;
		}

		if ( $pivot->thirdModel ) {
			return $pivot->thirdModel;
		}

		$key = $pivot->{$pivot->getThirdKey()};
		
		$instance = $pivot->getThird();
		
		return $pivot->thirdModel = $instance->find( $key );
	}
	
	/**
	 * Accessor for third related model
	 *
	 * @param int	$second_id 
	 * @return Collection \Illuminate\Database\Eloquent\Model
	 */
	public function getThirdsAttribute($columns = ['*'], $foreign_id=null, $other_id=null) {
		
		$pivot = $this->pivot;
		
		if ( is_null( $pivot ) ) {
			return null;
		}

		if ( $pivot->thirdModel ) {
			return $pivot->thirdModel;
		}
		$foreign_id	= $foreign_id	?:$pivot->{$pivot->getForeignKey()};
		$other_id	= $other_id		?:$pivot->{$pivot->getOtherKey()};
		
		$ids 		= $pivot->getThirds($columns, $foreign_id, $other_id);
		$models		= [];
		
		foreach($ids as $arr){
			$id = $arr->{$pivot->getThirdKey()};
			$models[] = $pivot->getThird()->findOrFail($id);
		}
		
		$instance = $pivot->getThird();
		
		return $pivot->thirdModel = $instance->newCollection($models);
	}

	/**
     * Get a relationship value from a method.
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws \LogicException
     *//*
    protected function getRelationshipFromMethod($method)
    {
        $relations = $this->$method();

        if (! $relations instanceof Relation) {
            throw new LogicException('Relationship method must return an object of type '
                .'Illuminate\Database\Eloquent\Relations\Relation');
        }
		return $this->relations[$method] = $relations->getResults();
    }*/

	/**
	 * Create a new pivot model instance.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model $parent
	 * @param  \Illuminate\Database\Eloquent\Model $third
	 * @param  array $attributes
	 * @param  string $table
	 * @param  bool $exists
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\Pivot
	 */
	public function newTriplePivot( Model $parent, Model $third, array $attributes, $table, $exists ) {
		return new TriplePivot( $parent, $third, $attributes, $table, $exists );
	}

	/**
	 * Get the joining table name for a many-to-many triple relation.
	 *
	 * @param  string $related
	 * @param  string $third
	 *
	 * @return string
	 */
	public function tripleJoiningTable( $related, $third ) {
		$base = snake_case( class_basename( $this ) );

		$related = snake_case( class_basename( $related ) );

		$third = snake_case( class_basename( $third ) );

		$models = array( $related, $base, $third );

		sort( $models );

		return strtolower( implode( '_', $models ) );
	}
}
