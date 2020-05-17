<?php

namespace RobotsInside\Deletable;

use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RobotsInside\Deletable\Exceptions\InvalidConfigException;
use RobotsInside\Deletable\Exceptions\UnsafeDeleteException;

trait Deletable
{
    /**
     * Contains an array of dependent relations.
     *
     * @var array
     */
    private $dependants = [];

    /**
     * The Deletable configruations.
     *
     * @return array
     */
    abstract protected function deletableConfig(): array;

    /**
     * Discover dependent relationships.
     *
     * @return Model
     */
    public function discover()
    {
        foreach ($this->relations() as $key => $relation) {
            $this->handle($key, $relation);
        }

        return $this;
    }

    /**
     * Handle the 'exception' mode.
     *
     * @throws RobotsInside\Deletable\Exceptions\UnsafeDeleteException;
     * @return void
     */
    protected function exception()
    {
        throw new UnsafeDeleteException(__CLASS__ . ' has dependent relationships (' . implode(', ', $this->dependants) . ')');
    }

    /**
     * The validation errors for displaying to the end user.
     *
     * @return Illuminate\Support\Collection
     */
    public function getDependants()
    {
        return collect($this->dependants);
    }

    /**
     * Handle the relation error checking.
     *
     * @param int|string $key
     * @param string|array $relation
     * @return void
     */
    private function handle($key, $relation)
    {
        // If the relation configuration is not an array, the key must be the relation name.
        $relationName = is_array($relation) ? $key : $relation;

        if ($this->{$relationName}()->get()->count() > 0) {
            $this->populateDependents($relationName);
        }
    }

    /**
     * Check if a model can be safely deleted.
     *
     * @return boolean
     */
    public function deletable()
    {
        $this->discover();

        return empty($this->dependants) ? true : false;
    }

    /**
     * The custom handler method.
     *
     * @return string
     */
    private function handler()
    {
        return $this->deletableConfig()['handler'];
    }

    /**
     * If no mode specified, default to exception.
     *
     * @return string
     */
    private function mode()
    {
        if (!array_key_exists('mode', $this->deletableConfig())) {
            return 'exception';
        }

        $mode = $this->deletableConfig()['mode'];

        if (!$this->modes()->contains($mode)) {
            throw new InvalidConfigException('Error handling mode must be "custom|cascade|exception" in: ' . __CLASS__);
        }

        return $mode;
    }

    /**
     * The permitted mode types.
     *
     * @return Illuminate\Support\Collection
     */
    private function modes()
    {
        return collect(['custom', 'cascade', 'exception']);
    }

    /**
     * Specify the models that depend on this model.
     *
     * @return void
     */
    private function populateDependents($relationName)
    {
        if (!in_array($relationName, $this->dependants)) {
            array_push($this->dependants, $relationName);
        }
    }

    /**
     * Get the relations key from the config.
     *
     * @return array
     */
    private function relations()
    {
        if (!array_key_exists('relations', $this->deletableConfig())) {
            throw new Exception('The relations configuration key must be set.');
        }

        if (count($this->deletableConfig()['relations']) == 0) {
            throw new Exception('The relations configuration key must contain at least one resolvable relationship.');
        }

        return $this->deletableConfig()['relations'];
    }

    /**
     * Safely delete a model using the preffered mode.
     *
     * @return void
     */
    public function safeDelete()
    {
        if (!$this->deletable() && $this->mode() == 'custom') {
            $this->{$this->handler}();
        } elseif (!$this->deletable()) {
            if (method_exists($this, $this->mode())) {
                $this->{$this->mode()}();
            }
        } else {
            $this->delete();
        }
    }

    /**
     * Delete the referencing models. Use with caution! This mode will try to delete all referencing models.
     *
     * @return void
     */
    public function cascade()
    {
        foreach ($this->dependants as $dependent) {

            $related = $this->{$dependent}()->get();

            foreach ($related as $model) {
                if ($this->{$dependent}() instanceof BelongsToMany) {
                    $this->{$dependent}()->detach($model->id);
                }
                $model->delete();
            }
        }

        $this->delete();
    }
}
