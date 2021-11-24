<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use App\Rules\IsLocaleInConfigRule;

class MealGetRequest extends FormRequest
{
    public $validator = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            "lang" => ["string", new IsLocaleInConfigRule, "required"],
            "per_page" => "numeric|gte:1",
            "page" => "numeric|gte:1",
            "category" => ["regex:/(^!{0,1}NULL$)|(^\d+$)/"],
            "tags" => ["regex:/^([0-9]+)(,[0-9]+){0,}?$/"],
            "with" => ["regex:/^(((tags|category|ingredients),){1,2}(tags|category|ingredients))$|^(tags|category|ingredients)$/"],//does not cover repetition
            "diff_time" => "numeric|gte:0"
        ];
    }

    /**
     * Allow the controller to handle the error message.
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Get the customized messages for the JSON response.
     *
     * @return array
     */
    public function messages()
    {
        $locales = Config::get("translatable.locales");

        return [
            'lang.required' => 'A title is required and must be from the supported locale list',
            'per_page.gte' => 'Invalid per_page number given',
            'page.gte' => 'Invalid page number given',
            'category.regex' => 'Invalid category list given',
            'tags.regex' => 'Invalid tag list given, e.g. must be format (?tags=1,2,3)',
            'with.regex' => 'Invalid with list given',
            'diff_time' => 'Invalid diff_time given, expected UNIX timestamp'
        ];
    }
}
