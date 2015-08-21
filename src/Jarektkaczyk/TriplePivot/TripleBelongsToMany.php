<?php

namespace Jarektkaczyk\TriplePivot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class TripleBelongsToMany
 * @package Jarektkaczyk\TriplePivot
 */
class TripleBelongsToMany extends BelongsToMany {

	/**
	 * Key of the third model.
	 *
	 * @var string
	 */
	protected $thirdKey;

	/**
	 * Third model instance.
	 *
	 * @var mixed
	 */
	protected $third;

	/**
	 * Pivot columns to retrieve.
	 *
	 * @var array
	 */
	protected $pivotColumns = [ ];

	/**
	 * Create a new triple belongs to many relation.
	 *
	 * @param Builder $query
	 * @param Model $parent
	 * @param Model $third
	 * @param string $table
	 * @param string $foreignKey
	 * @param string $otherKey
	 * @param string $thirdKey
	 * @param string $relationName
	 */
	public function __construct( Builder $query, Model $parent, Model $third, $table, $foreignKey, $otherKey, $thirdKey, $relationName = null ) {
		parent::__construct( $query, $parent, $table, $foreignKey, $otherKey, $relationName );

		$this->thirdKey       = $thirdKey;
		$this->third          = $third;
		$this->pivotColumns[] = $thirdKey;
	}

	/**
	 * Get the fully qualified "third key" of the relation
	 *
	 * @return string
	 */
	public function getThirdKey() {
		return $this->table . '.' . $this->thirdKey;
	}

	/**
	 * Attach 3 models.
	 *
	 * @param  mixed $id
	 * @param  array $attributes
	 * @param  boolean $touch
	 *
	 * @return void
	 */
	public function attach( $id, array $attributes = array(), $touch = true ) {
		// First check if developer provided an array of keys or models to attach
		// and set other key as additional pivot data for generic attach method
		// in order to make sure it is always saved upon attaching if provided.
		if ( is_array( $id ) && count( $id ) > 1 ) {
			if( is_array( $id[0] ) && is_array( $id[1] ) && count( $id[0] ) == count( $id[1] ) ){
				$response = null;
				for($i=0; $i<count($id[0]); $i++){
					$otherId = ( $id[1][$i] instanceof Model ) ? $id[1][$i]->getKey() : $id[1][$i];
					$id      = ( $id[0][$i] instanceof Model ) ? $id[0][$i]->getKey() : $id[0][$i];
					
					$attributes[ $this->thirdKey ] = $otherId;
					$response = parent::attach( $id, $attributes, $touch );
				}
				return $response;
			}else{
				$otherId = ( $id[1] instanceof Model ) ? $id[1]->getKey() : $id[1];
				$id      = ( $id[0] instanceof Model ) ? $id[0]->getKey() : $id[0];
	
				$attributes[ $this->thirdKey ] = $otherId;
				return parent::attach( $id, $attributes, $touch );
			}
		}
	}
	
	/**
     * Detach models from the relationship.
     *
     * @param  int|array  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = [], $touch = true)
    {
        if ( is_array( $ids ) && count( $ids ) > 1 ) {
			$query = $this->newPivotQuery();
			
			if( is_array( $ids[0] ) && is_array( $ids[1] ) && count( $ids[0] ) == count( $ids[1] ) ){
				$response = null;
				for($i=0; $i<count($ids[0]); $i++){
					$otherId = ( $ids[0] instanceof Model ) ? $ids[0]->getKey() : $ids[0];
					$thirdId = ( $ids[1] instanceof Model ) ? $ids[1]->getKey() : $ids[1];
				
					$query	->orWhere(function(){
								$query	->where($this->getOtherKey() 	, '=', $otherId)
										->where($this->getThirdKey()	, '=', $thirdId);
							});
				}
				return $response;
			}else{
				$otherId = ( $ids[0] instanceof Model ) ? $ids[0]->getKey() : $ids[0];
				$thirdId = ( $ids[1] instanceof Model ) ? $ids[1]->getKey() : $ids[1];
				
				$query	->where($this->getOtherKey() 	, '=', $otherId)
						->where($this->getThirdKey()	, '=', $thirdId);
			}
			
			$results = $query->delete();

	        if ($touch) {
	            $this->touchIfTouching();
	        }
	
	        return $results;
		} 
        return false;    
    }
    
    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * @param  array  $ids
     * @param  bool   $detaching
     * @return array
     */
    public function sync($ids, $detaching = true)
    {//Not yet implemented.
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => [],
        ];

        if ($ids instanceof Collection) {
            $ids = $ids->modelKeys();
        }

        // First we need to attach any of the associated models that are not currently
        // in this joining table. We'll spin through the given IDs, checking to see
        // if they exist in the array of current ones, and if not we will insert.
        $current = $this->newPivotQuery()->lists($this->otherKey);

        $records = $this->formatSyncList($ids);

        $detach = array_diff($current, array_keys($records));

        // Next, we will take the differences of the currents and given IDs and detach
        // all of the entities that exist in the "current" array but are not in the
        // the array of the IDs given to the method which will complete the sync.
        if ($detaching && count($detach) > 0) {
            $this->detach($detach);

            $changes['detached'] = (array) array_map(function ($v) { return (int) $v; }, $detach);
        }

        // Now we are finally ready to attach the new records. Note that we'll disable
        // touching until after the entire operation is complete so we don't fire a
        // ton of touch operations until we are totally done syncing the records.
        $changes = array_merge(
            $changes, $this->attachNew($records, $current, false)
        );

        if (count($changes['attached']) || count($changes['updated'])) {
            $this->touchIfTouching();
        }

        return $changes;
    }
    
    /**
     * Format the sync list so that it is keyed by ID.
     *
     * @param  array  $records
     * @return array
     */
    protected function formatSyncList(array $records)
    {
        $results = [];

        foreach ($records as $id => $attributes) {
            if (! is_array($attributes)) {
                list($id, $attributes) = [$attributes, []];
            }

            $results[$id] = $attributes;
        }

        return $results;
    }
	
	/**
	 * Create a new pivot model instance.
	 *
	 * @param  array $attributes
	 * @param  bool $exists
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\Pivot
	 */
	public function newPivot( array $attributes = array(), $exists = false ) {
		$pivot = $this->related->newTriplePivot( $this->parent, $this->third, $attributes, $this->table, $exists );

		return $pivot->setTriplePivotKeys( $this->foreignKey, $this->otherKey, $this->thirdKey );
	}

}
