<?php
declare(strict_types=1);

namespace Wikijump\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Wikijump\Common\Enum;
use Wikijump\Helpers\InteractionType;

/**
 * Class Interaction
 * Core class for creating persistent relationships of various types between two
 *  objects, such as a user following a page, or a site banning a user.
 * @package Wikijump\Models
 */
class Interaction extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $guarded = [];

    /**
     * Metadata is stored in the database as JSON objects.
     * When we retrieve it, we want to cast it as an associative array.
     * Otherwise we'll just end up calling json_decode anyway. This also keeps
     *  us from having to manually json_encode the metadata before saving.
     * @var array
     */
    protected $casts = [
        'metadata' => 'array'
    ];


    /**
     * Find the parent object for a given setting.
     * @return MorphTo
     */
    public function setter() : MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Find the target objects from an instance.
     * @return MorphTo
     */
    public function target() : MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create and persist a new Interaction object.
     * @param $setter
     * @param int $relation
     * @param $target
     * @param array|null $metadata
     * @return Interaction
     */
    public static function add($setter, int $relation, $target, ?array $metadata = []) : ?Interaction
    {

        /**
         * We have to do some validation here as we're accepting generics.
         */
        if(is_subclass_of($setter, 'Illuminate\Database\Eloquent\Model')
            && InteractionType::isValue($relation)
            && is_subclass_of($target, 'Illuminate\Database\Eloquent\Model'))
        {
            $interaction = new Interaction(
                [
                    'setter_type' => get_class($setter),
                    'setter_id' => $setter->id,
                    'interaction_type' => $relation,
                    'target_type' => get_class($target),
                    'target_id' => $target->id,
                    'metadata' => $metadata
                ]
            );

            try {
                $interaction->save();
                return $interaction;
            }
            catch(QueryException $e) {
                /** Postgres unique constraint violation: */
                if($e->errorInfo[0] == 23505) {
                    /**
                     * We'll want to throw something here that a controller can
                     * catch and return data to the user. Pending API work.
                     */
                    return null;
                }
            }
        }
        Log::error("Interaction::add was given an invalid set of params. 
            Setter: $setter Relation: $relation Target: $target");
        abort(500);
        return null;
    }

    /**
     * Delete an interaction from the table.
     * @param $setter
     * @param int $relation
     * @param $target
     * @return int
     */
    public static function remove($setter, int $relation, $target) : ?int
    {

        /**
         * We have to do some validation here as we're accepting generics.
         */
        if(is_subclass_of($setter, 'Illuminate\Database\Eloquent\Model')
            && InteractionType::isValue($relation)
            && is_subclass_of($target, 'Illuminate\Database\Eloquent\Model'))
        {
            $interaction = Interaction::where(
                [
                    'setter_type' => get_class($setter),
                    'setter_id' => $setter->id,
                    'interaction_type' => $relation,
                    'target_type' => get_class($target),
                    'target_id' => $target->id
                ]
            );

            try {
                return $interaction->delete();
            }
            catch(QueryException $e) {
                # TODO: Find some common database issues we might need to handle. This will not fire right now.
                if($e->errorInfo[0] == 23505) {
                    echo('Already exists, do stuff');
                }
            }
        }
        Log::error("Interaction::remove was given an invalid set of params. 
            Setter: $setter Relation: $relation Target: $target");
        abort(500);
        return null;
    }

    /**
     * Determine if a particular relationship exists between a source and target.
     * @param $setter
     * @param int $relation
     * @param $target
     * @return bool
     */
    public static function exists($setter, int $relation, $target) : bool
    {

        /**
         * We have to do some validation here as we're accepting generics.
         */
        if(is_subclass_of($setter, 'Illuminate\Database\Eloquent\Model')
            && InteractionType::isValue($relation)
            && is_subclass_of($target, 'Illuminate\Database\Eloquent\Model'))
        {
            return (bool)Interaction::where(
                [
                    'setter_type' => get_class($setter),
                    'setter_id' => $setter->id,
                    'interaction_type' => $relation,
                    'target_type' => get_class($target),
                    'target_id' => $target->id
                ]
            )->count();
        }
        Log::error("Interaction::exists was given an invalid set of params. 
            Setter: $setter Relation: $relation Target: $target");
        abort(500);
        return false;
    }
}