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
     * @var mixed
     */
    protected $relation;

    /**
     * The dependent model.
     *
     * @var mixed
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
        if (method_exists($this->model, 'deletableValidationMessage')) {
            return $this->model->deletableValidationMessage($this->relatedModel);
        }

        return sprintf('This %s %s %s.', class_basename(get_class($this->model)), $this->association(), Str::plural(class_basename($this->relatedModel)));
    }

    /**
     * The association type message.
     *
     * @return string
     */
    private function association()
    {
        if (Str::contains($this->relation, 'BelongsToMany')) {
            return 'belongs to one or more';
        } elseif (Str::contains($this->relation, 'HasMany')) {
            return 'has one or more';
        } else {
            return 'is associated with one or more';
        }
    }
}
