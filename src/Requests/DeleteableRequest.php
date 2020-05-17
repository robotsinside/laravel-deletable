<?php

namespace RobotsInside\Deletable\Requests;

use Exceptions\UnresolvedRouteBindingException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use RobotsInside\Deletable\Rules\DeletableRule;

class DeleteableRequest extends FormRequest
{
    /**
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * A model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $routeModel;

    /**
     * The dependant models as per config.
     *
     * @var Collection
     */
    protected $dependants;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->routeModel = $this->request->route($this->guessRouteBinding());

        $this->dependants = $this->routeModel->discover()->getDependants();

        $this->injectRequestParameters();
    }

    /**
     * Guess the route binding.
     *
     * @throws UnresolvedRouteBindingException
     * @return string
     */
    protected function guessRouteBinding()
    {
        if(count($this->request->route()->parameters())) {
            return array_keys($this->request->route()->parameters())[0];
        }

        if (method_exists($this, 'routeBinding')) {
            return $this->routeBinding();
        }

        throw new UnresolvedRouteBindingException();
    }

    /**
     * Add dependant models to the request so that they can be validated.
     *
     * @return void
     */
    protected function injectRequestParameters()
    {
        $this->dependants->each(function ($dependent) {
            $this->request->request->add([$dependent => null]);
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return $this->dependants->mapWithKeys(function ($dependant) {
            return [$dependant => new DeletableRule($this->routeModel)];
        })->toArray();
    }
}
