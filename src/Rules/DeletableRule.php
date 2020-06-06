<?php

namespace RobotsInside\Deletable\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeletableRule implements Rule
{
    /**
     * The model instance being evaluated.
     *
     * @var Model
     */
    protected $model;

    /**
     * The Eloquent relation type.
     *
     * @var Illuminate\Database\Eloquent\Relations\Relation
     */
    protected $relation;

    /**
     * The dependent model.
     *
     * @var Model
     */
    protected $relatedModel;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->relation = get_class($this->model->{$attribute}());
        $this->relatedModel = get_class($this->model->{$attribute}()->getRelated());

        return $this->model->deletable();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return sprintf('This %s %s %s.', class_basename(get_class($this->model)), $this->association(), Str::plural(class_basename($this->relatedModel)));
    }

    /**
     * The association type message.
     *
     * @return string
     */
    private function association()
    {
        if(Str::contains($this->relation, 'BelongsToMany')) {
            return 'belongs to one or more';
        }

        if(Str::contains($this->relation, 'HasMany')) {
            return 'has one or more';
        }

        if(Str::contains($this->relation, 'Morph')) {
            return 'is associated with one or more';
        }
    }
}
